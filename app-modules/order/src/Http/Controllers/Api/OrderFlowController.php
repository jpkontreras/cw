<?php

namespace Colame\Order\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Order\Aggregates\OrderAggregate;
use Colame\Order\ProcessManagers\TakeOrderProcessManager;
use Colame\Order\Data\CreateOrderFlowData;
use Colame\Order\Data\OrderFlowResponseData;
use Colame\Order\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Money\Money;
use Money\Currency;

class OrderFlowController extends Controller
{
    public function __construct(
        private TakeOrderProcessManager $processManager
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