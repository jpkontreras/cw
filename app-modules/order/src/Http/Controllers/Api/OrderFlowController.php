<?php

namespace Colame\Order\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Order\Aggregates\OrderAggregate;
use Colame\Order\ProcessManagers\TakeOrderProcessManager;
use Colame\Order\Services\OrderSessionService;
use Colame\Order\Data\CreateOrderFlowData;
use Colame\Order\Data\OrderFlowResponseData;
use Colame\Order\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Money\Money;
use Money\Currency;

class OrderFlowController extends Controller
{
    public function __construct(
        private TakeOrderProcessManager $processManager,
        private OrderSessionService $sessionService
    ) {}

    /**
     * Start a new order - First step in the flow
     * Mobile app calls this when waiter starts taking an order
     */
    public function startOrder(Request $request)
    {
        $data = CreateOrderFlowData::validateAndCreate($request);
        
        // Generate order UUID
        $orderUuid = Str::uuid()->toString();
        
        // Start the process
        $processId = $this->processManager->startProcess([
            'order_uuid' => $orderUuid,
            'staff_id' => $data->staffId,
            'location_id' => $data->locationId,
            'table_number' => $data->tableNumber,
        ]);

        // Link process to order for event handling
        $this->processManager->linkProcessToOrder($processId, $orderUuid);

        // Start the order aggregate
        OrderAggregate::retrieve($orderUuid)
            ->startOrder(
                staffId: $data->staffId,
                locationId: $data->locationId,
                tableNumber: $data->tableNumber,
                metadata: [
                    'device_id' => $request->header('X-Device-Id'),
                    'app_version' => $request->header('X-App-Version'),
                ]
            )
            ->persist();

        return response()->json([
            'success' => true,
            'data' => [
                'order_uuid' => $orderUuid,
                'process_id' => $processId,
                'status' => 'started',
                'next_step' => 'add_items',
            ],
        ]);
    }

    /**
     * Add items to order and get validation + promotions
     * This triggers the full validation and promotion calculation flow
     */
    public function addItems(Request $request, string $orderUuid)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.modifiers' => 'nullable|array',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        // Add items to aggregate
        OrderAggregate::retrieve($orderUuid)
            ->addItems($validated['items'])
            ->persist();

        // Get process ID from order UUID
        $processId = $request->get('process_id');
        
        if (!$processId) {
            // Try to find process ID from cache
            $state = $this->processManager->getOrderState($orderUuid);
            $processId = $state['process_id'] ?? null;
        }

        // Wait for validation and promotion calculation
        $result = $this->processManager->waitForCompletion($processId, timeoutSeconds: 5);

        if (!$result['success']) {
            // Check if we're just waiting for promotions
            $state = $this->processManager->getOrderState($processId);
            
            if ($state && $state['current_step'] === 'awaiting_confirmation') {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => 'items_validated',
                        'validated_items' => $state['validated_items'] ?? [],
                        'subtotal' => $state['subtotal'] ?? 0,
                        'promotions' => $state['promotions'] ?? [],
                        'discount' => $state['discount'] ?? 0,
                        'tax' => $state['tax'] ?? 0,
                        'total' => $state['total'] ?? 0,
                        'next_step' => !empty($state['promotions']['available']) 
                            ? 'select_promotions' 
                            : 'confirm_order',
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Validation failed',
                'errors' => $result['errors'] ?? [],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Apply or remove a promotion
     * Mobile app calls this when customer selects/deselects promotions
     */
    public function applyPromotion(Request $request, string $orderUuid)
    {
        $validated = $request->validate([
            'promotion_id' => 'required|string',
            'action' => 'required|in:apply,remove',
        ]);

        $aggregate = OrderAggregate::retrieve($orderUuid);

        if ($validated['action'] === 'apply') {
            // Get promotion details from the process state
            $processId = $request->get('process_id');
            $state = $this->processManager->getOrderState($processId);
            
            $promotion = collect($state['promotions']['available'] ?? [])
                ->firstWhere('id', $validated['promotion_id']);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'error' => 'Promotion not found or not applicable',
                ], 400);
            }

            $aggregate->applyPromotion(
                $validated['promotion_id'],
                new Money($promotion['discount_amount'], new Currency('CLP'))
            );
        } else {
            $aggregate->removePromotion($validated['promotion_id']);
        }

        $aggregate->persist();

        // Recalculate total
        $order = Order::where('uuid', $orderUuid)->first();
        $newTotal = $order->subtotal - $order->discount + $order->tax + $order->tip;

        return response()->json([
            'success' => true,
            'data' => [
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'tax' => $order->tax,
                'tip' => $order->tip,
                'total' => $newTotal,
            ],
        ]);
    }

    /**
     * Add tip to the order
     */
    public function addTip(Request $request, string $orderUuid)
    {
        $validated = $request->validate([
            'tip_amount' => 'required|integer|min:0',
        ]);

        OrderAggregate::retrieve($orderUuid)
            ->addTip(new Money($validated['tip_amount'], new Currency('CLP')))
            ->persist();

        $order = Order::where('uuid', $orderUuid)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'tip' => $validated['tip_amount'],
                'total' => $order->total,
            ],
        ]);
    }

    /**
     * Confirm the order - Final step
     * This completes the order and sends it to kitchen
     */
    public function confirmOrder(Request $request, string $orderUuid)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,transfer,account',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
        ]);

        $aggregate = OrderAggregate::retrieve($orderUuid);

        // Set payment method
        $aggregate->setPaymentMethod($validated['payment_method']);

        // Calculate final price
        $order = Order::where('uuid', $orderUuid)->first();
        $total = $order->subtotal - $order->discount + $order->tax + $order->tip;

        $aggregate->calculateFinalPrice(
            new Money($order->tax, new Currency('CLP')),
            new Money($total, new Currency('CLP'))
        );

        // Confirm the order
        $aggregate->confirmOrder();
        $aggregate->persist();

        // Get the final order state
        $processId = $request->get('process_id');
        $result = $this->processManager->waitForCompletion($processId, timeoutSeconds: 5);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => array_merge($result['data'], [
                    'print_data' => $this->generatePrintData($order),
                    'kitchen_notified' => true,
                ]),
            ]);
        }

        // Even if process manager fails, we have the order
        $order->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'order_uuid' => $orderUuid,
                'order_number' => $order->order_number,
                'status' => 'confirmed',
                'total' => $total,
                'print_data' => $this->generatePrintData($order),
                'kitchen_notified' => true,
            ],
        ]);
    }

    /**
     * Get current order state - For polling/recovery
     */
    public function getOrderState(string $orderUuid)
    {
        $order = Order::where('uuid', $orderUuid)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => OrderFlowResponseData::from($order),
        ]);
    }

    /**
     * Initiate a new order session (called when user opens order creation)
     */
    public function initiateSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'nullable|integer',
            'platform' => 'nullable|string|in:web,mobile,kiosk',
            'source' => 'nullable|string',
            'order_type' => 'nullable|string|in:dine_in,takeout,delivery',
            'referrer' => 'nullable|string',
        ]);
        
        $result = $this->sessionService->startSession($validated);
        
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Track generic events during order session
     */
    public function trackEvent(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'required|string',
            'data' => 'nullable|array',
        ]);
        
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        // Handle different event types
        switch ($validated['event_type']) {
            case 'search':
                $aggregate->recordSearch(
                    query: $validated['data']['query'] ?? '',
                    filters: $validated['data']['filters'] ?? [],
                    resultsCount: $validated['data']['results_count'] ?? 0,
                    searchId: $validated['data']['search_id'] ?? null
                );
                break;
                
            case 'category_browse':
                $aggregate->browseCategory(
                    categoryId: $validated['data']['category_id'],
                    categoryName: $validated['data']['category_name'],
                    itemsViewed: $validated['data']['items_viewed'] ?? 0,
                    timeSpentSeconds: $validated['data']['time_spent'] ?? 0
                );
                break;
                
            case 'item_view':
                $aggregate->viewItem(
                    itemId: $validated['data']['item_id'],
                    itemName: $validated['data']['item_name'],
                    price: $validated['data']['price'],
                    category: $validated['data']['category'] ?? null,
                    viewSource: $validated['data']['source'] ?? 'browse',
                    viewDurationSeconds: $validated['data']['duration'] ?? 0
                );
                break;
                
            case 'serving_type':
                $aggregate->setServingType(
                    servingType: $validated['data']['type'],
                    tableNumber: $validated['data']['table_number'] ?? null,
                    deliveryAddress: $validated['data']['delivery_address'] ?? null
                );
                break;
                
            case 'customer_info':
                $aggregate->enterCustomerInfo(
                    fields: $validated['data']['fields'] ?? [],
                    validationErrors: $validated['data']['errors'] ?? [],
                    isComplete: $validated['data']['is_complete'] ?? false
                );
                break;
                
            case 'payment_method':
                $aggregate->selectPaymentMethod(
                    paymentMethod: $validated['data']['payment_method']
                );
                break;
                
            default:
                return response()->json([
                    'success' => false,
                    'error' => 'Unknown event type',
                ], 400);
        }
        
        $aggregate->persist();
        
        // Update session activity
        $this->updateSessionActivity($orderUuid);
        
        return response()->json([
            'success' => true,
            'data' => [
                'event_tracked' => $validated['event_type'],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Add item to cart (not yet confirmed as order)
     */
    public function addToCart(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|integer',
            'item_name' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'category' => 'nullable|string',
            'modifiers' => 'nullable|array',
            'notes' => 'nullable|string',
            'source' => 'nullable|string',
        ]);
        
        OrderAggregate::retrieve($orderUuid)
            ->addToCart(
                itemId: $validated['item_id'],
                itemName: $validated['item_name'],
                quantity: $validated['quantity'],
                unitPrice: $validated['unit_price'],
                category: $validated['category'] ?? null,
                modifiers: $validated['modifiers'] ?? [],
                notes: $validated['notes'] ?? null,
                addedFrom: $validated['source'] ?? 'browse'
            )
            ->persist();
        
        $this->updateSessionActivity($orderUuid);
        
        return response()->json([
            'success' => true,
            'data' => [
                'item_added' => $validated['item_name'],
                'quantity' => $validated['quantity'],
            ],
        ]);
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|integer',
            'item_name' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);
        
        OrderAggregate::retrieve($orderUuid)
            ->removeFromCart(
                itemId: $validated['item_id'],
                itemName: $validated['item_name'],
                removedQuantity: $validated['quantity'],
                removalReason: $validated['reason'] ?? 'user_action'
            )
            ->persist();
        
        $this->updateSessionActivity($orderUuid);
        
        return response()->json([
            'success' => true,
            'data' => [
                'item_removed' => $validated['item_name'],
                'quantity' => $validated['quantity'],
            ],
        ]);
    }

    /**
     * Update cart item (quantity, notes, modifiers)
     */
    public function updateCartItem(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|integer',
            'item_name' => 'required|string',
            'modification_type' => 'required|string|in:quantity_changed,notes_updated,modifiers_changed',
            'changes' => 'required|array',
        ]);
        
        OrderAggregate::retrieve($orderUuid)
            ->modifyCartItem(
                itemId: $validated['item_id'],
                itemName: $validated['item_name'],
                modificationType: $validated['modification_type'],
                changes: $validated['changes']
            )
            ->persist();
        
        $this->updateSessionActivity($orderUuid);
        
        return response()->json([
            'success' => true,
            'data' => [
                'item_updated' => $validated['item_name'],
                'modification' => $validated['modification_type'],
            ],
        ]);
    }

    /**
     * Get current session state (for recovery/polling)
     */
    public function getSessionState(string $orderUuid): JsonResponse
    {
        // Check cache first
        $sessionData = Cache::get("order_session:{$orderUuid}");
        
        if (!$sessionData) {
            // Try to recover from event store
            try {
                $aggregate = OrderAggregate::retrieve($orderUuid);
                $snapshot = $aggregate->snapshot();
                
                if (!$snapshot) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Session not found',
                    ], 404);
                }
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'order_uuid' => $orderUuid,
                        'status' => $snapshot->aggregateState['status'] ?? 'unknown',
                        'cart_items' => $snapshot->aggregateState['cartItems'] ?? [],
                        'customer_info' => $snapshot->aggregateState['customerInfo'] ?? [],
                        'serving_type' => $snapshot->aggregateState['servingType'] ?? null,
                        'payment_method' => $snapshot->aggregateState['paymentMethod'] ?? null,
                        'metadata' => $snapshot->aggregateState['metadata'] ?? [],
                    ],
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session not found',
                ], 404);
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => array_merge($sessionData, [
                'is_expired' => now()->greaterThan($sessionData['last_activity']->addHours(2)),
            ]),
        ]);
    }

    /**
     * Recover an abandoned session
     */
    public function recoverSession(Request $request, string $orderUuid): JsonResponse
    {
        try {
            $aggregate = OrderAggregate::retrieve($orderUuid);
            
            // Check if session can be recovered
            $snapshot = $aggregate->snapshot();
            if (!$snapshot || $snapshot->aggregateState['status'] === 'abandoned') {
                return response()->json([
                    'success' => false,
                    'error' => 'Session cannot be recovered',
                ], 400);
            }
            
            // Update session activity
            $this->updateSessionActivity($orderUuid);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'order_uuid' => $orderUuid,
                    'status' => 'recovered',
                    'cart_items' => $snapshot->aggregateState['cartItems'] ?? [],
                    'customer_info' => $snapshot->aggregateState['customerInfo'] ?? [],
                    'serving_type' => $snapshot->aggregateState['servingType'] ?? null,
                    'payment_method' => $snapshot->aggregateState['paymentMethod'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to recover session',
            ], 500);
        }
    }

    /**
     * Save draft order
     */
    public function saveDraft(Request $request, string $orderUuid): JsonResponse
    {
        $autoSaved = $request->input('auto_saved', false);
        
        OrderAggregate::retrieve($orderUuid)
            ->saveDraft($autoSaved)
            ->persist();
        
        $this->updateSessionActivity($orderUuid);
        
        return response()->json([
            'success' => true,
            'data' => [
                'draft_saved' => true,
                'auto_saved' => $autoSaved,
                'saved_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Convert cart to confirmed order
     */
    public function convertToOrder(Request $request, string $orderUuid): JsonResponse
    {
        try {
            $aggregate = OrderAggregate::retrieve($orderUuid);
            
            // Convert cart to order
            $aggregate->convertCartToOrder();
            
            // Set payment method if provided
            if ($request->has('payment_method')) {
                $aggregate->setPaymentMethod($request->input('payment_method'));
            }
            
            // Confirm the order
            $aggregate->confirmOrder();
            $aggregate->persist();
            
            // Get the created order
            $order = Order::where('uuid', $orderUuid)->first();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'order_uuid' => $orderUuid,
                    'order_number' => $order->order_number ?? 'PENDING',
                    'status' => 'confirmed',
                    'total' => $order->total ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update session activity timestamp
     */
    private function updateSessionActivity(string $orderUuid): void
    {
        $sessionData = Cache::get("order_session:{$orderUuid}");
        if ($sessionData) {
            $sessionData['last_activity'] = now();
            Cache::put("order_session:{$orderUuid}", $sessionData, now()->addHours(24));
        }
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Request $request, string $orderUuid)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        OrderAggregate::retrieve($orderUuid)
            ->cancelOrder($validated['reason'])
            ->persist();

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
        ]);
    }

    /**
     * Generate thermal printer data
     */
    private function generatePrintData(Order $order): array
    {
        return [
            'header' => [
                'business_name' => $order->location->business->name ?? 'Restaurant',
                'location_name' => $order->location->name ?? '',
                'date' => now()->format('d/m/Y H:i'),
                'order_number' => $order->order_number,
                'table' => $order->table_number,
                'waiter' => $order->staff->name ?? '',
            ],
            'items' => $order->items->map(fn($item) => [
                'quantity' => $item->quantity,
                'name' => $item->name,
                'modifiers' => $item->modifiers,
                'price' => $item->price,
                'notes' => $item->notes,
            ])->toArray(),
            'totals' => [
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'tax' => $order->tax,
                'tip' => $order->tip,
                'total' => $order->total,
            ],
            'payment_method' => $order->payment_method,
            'footer' => [
                'message' => 'Gracias por su preferencia',
            ],
        ];
    }
}