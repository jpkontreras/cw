<?php

declare(strict_types=1);

namespace Colame\OrderEs\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Traits\HandlesPaginationBounds;
use Colame\OrderEs\Aggregates\OrderSession as OrderSessionAggregate;
use Colame\OrderEs\Contracts\OrderRepositoryInterface;
use Colame\OrderEs\Models\Order;
use Colame\OrderEs\Models\OrderSession;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Taxonomy\Contracts\TaxonomyServiceInterface;
use Colame\Location\Contracts\LocationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Web controller for event-sourced orders
 * Follows same flow as original order module:
 * 1. /new - Welcome screen to select order type
 * 2. /session/{uuid} - Item picker with search and categories  
 * 3. Checkout -> creates order
 * 4. /show/{order} - Order detail with events
 */
class OrderController extends Controller
{
    use HandlesPaginationBounds;

    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private ?ItemRepositoryInterface $itemRepository = null,
        private ?TaxonomyServiceInterface $taxonomyService = null,
        private ?LocationServiceInterface $locationService = null
    ) {}
    
    /**
     * Display a listing of orders
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $filters = $request->only(['status', 'type', 'locationId', 'date', 'search', 'sort', 'page']);
        $perPage = (int) $request->input('per_page', 20);

        // Get paginated orders
        $paginator = $this->orderRepository->paginateWithFilters($filters, $perPage);
        
        $responseData = [
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'metadata' => [
                'total' => $paginator->total(),
            ]
        ];

        // Handle out-of-bounds pages
        if ($redirect = $this->handleOutOfBoundsPagination($responseData['pagination'], $request, 'es-order.index')) {
            return $redirect;
        }

        // Get locations for filter
        $locations = [];
        if ($this->locationService) {
            $locationsCollection = $this->locationService->getActiveLocations();
            foreach ($locationsCollection as $location) {
                $locations[] = [
                    'id' => $location->id,
                    'name' => $location->name,
                ];
            }
        }

        // Use es-order views
        return Inertia::render('es-order/index', [
            'view' => [
                'orders' => $responseData['data'],
                'pagination' => $responseData['pagination'],
                'metadata' => $responseData['metadata'],
                'locations' => $locations,
                'filters' => $filters,
                'stats' => [
                    'total_orders' => $paginator->total(),
                    'completed_orders' => 0,
                    'cancelled_orders' => 0,
                    'average_order_value' => 0,
                ],
            ],
        ]);
    }

    /**
     * Display the order initialization screen (welcome screen)
     * Step 1: User selects order type
     */
    public function new(Request $request): Response
    {
        // Show the welcome screen where user selects service type
        return Inertia::render('es-order/new');
    }

    /**
     * Start a new order session
     * Called after user selects order type
     */
    public function start(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:dine_in,takeout,delivery,catering',
            'table_number' => 'nullable|integer',
            'customer_count' => 'nullable|integer|min:1',
        ]);

        // Get current location
        $locationId = $request->user()?->location_id ?? 1;

        // Create new session through event sourcing
        $sessionUuid = Str::uuid()->toString();
        
        OrderSessionAggregate::retrieve($sessionUuid)
            ->initiateSession(
                userId: $request->user()?->id,
                locationId: $locationId,
                deviceInfo: [],
                referrer: $request->header('referer'),
                metadata: [
                    'type' => $validated['type'],
                    'table_number' => $validated['table_number'] ?? null,
                    'customer_count' => $validated['customer_count'] ?? 1,
                ]
            )
            ->persist();

        // Redirect to session view
        return redirect()->route('es-order.session', $sessionUuid);
    }

    /**
     * Start a new order session (AJAX)
     * Returns JSON response for frontend
     */
    public function startSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_type' => 'required|in:dine_in,takeout,delivery',
            'platform' => 'nullable|string',
            'source' => 'nullable|string',
        ]);

        // Get current location
        $locationId = $request->user()?->location_id ?? 1;

        // Create new session through event sourcing
        $sessionUuid = Str::uuid()->toString();
        
        OrderSessionAggregate::retrieve($sessionUuid)
            ->initiateSession(
                userId: $request->user()?->id,
                locationId: $locationId,
                deviceInfo: [
                    'platform' => $validated['platform'] ?? 'web',
                    'source' => $validated['source'] ?? 'web',
                ],
                referrer: $request->header('referer'),
                metadata: [
                    'type' => $validated['order_type'],
                ]
            )
            ->persist();

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $sessionUuid,
            ],
        ]);
    }

    /**
     * Sync session data (AJAX)
     * Handles auto-save and data synchronization
     */
    public function syncSession(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'nullable|array',
            'customer_info' => 'nullable|array',
            'search_history' => 'nullable|array',
            'favorites' => 'nullable|array',
        ]);

        // Get session from projection
        $session = OrderSession::find($uuid);
        
        if (!$session || $session->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Session not found or expired',
            ], 404);
        }

        // For now, we'll just track the sync without updating the aggregate
        // In a real implementation, you'd process each item change as individual events
        // (addToCart, removeFromCart, modifyCartItem) based on the diff

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $uuid,
                'synced_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Display the order creation session
     * Step 2: Item picker with search and categories
     */
    public function session(Request $request, string $uuid): Response|RedirectResponse
    {
        // Get session from projection
        $session = OrderSession::find($uuid);
        
        if (!$session || $session->status !== 'active') {
            return redirect()->route('es-order.new')->with('error', 'Session not found or expired');
        }
        
        // Get categories for the location
        $categories = [];
        if ($this->taxonomyService && $session->location_id) {
            $categoriesCollection = $this->taxonomyService->getFormattedItemCategories($session->location_id);
            $categories = $categoriesCollection->toArray();
        }

        // Get location details
        $locationData = null;
        if ($this->locationService && $session->location_id) {
            try {
                $location = $this->locationService->getLocation($session->location_id);
                if ($location) {
                    $locationData = [
                        'id' => $location->id,
                        'name' => $location->name,
                        'currency' => $location->currency ?? 'CLP',
                    ];
                }
            } catch (\Exception $e) {
                // Location not found or error, use defaults
                $locationData = [
                    'id' => $session->location_id,
                    'name' => 'Default Location',
                    'currency' => 'CLP',
                ];
            }
        }

        // Get popular items for the location
        $popularItems = [];
        
        // Try to get items using repository if available
        if ($this->itemRepository && $session->location_id) {
            try {
                // Get all active items for the location
                $items = $this->itemRepository->getActiveItemsForLocation($session->location_id);
                
                // Transform to the format expected by frontend
                $popularItems = $items->map(function ($item) {
                    // Calculate price - prioritize sale_price if exists
                    $price = 0;
                    if (isset($item->salePrice) && $item->salePrice > 0) {
                        $price = $item->salePrice;
                    } elseif (isset($item->basePrice) && $item->basePrice > 0) {
                        $price = $item->basePrice;
                    } elseif (isset($item->price) && $item->price > 0) {
                        $price = $item->price;
                    }
                    
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description ?? '',
                        'price' => $price,
                        'image' => $item->image ?? null,
                        'category' => $item->category ?? null,
                        'available' => $item->isAvailable ?? true,
                    ];
                })->take(20)->toArray(); // Limit to 20 popular items
            } catch (\Exception $e) {
                // Repository not working, fallback to direct query
                $popularItems = [];
            }
        }
        
        // If no items from repository, try direct database query
        if (empty($popularItems)) {
            try {
                // Direct query to items table
                $items = DB::table('items')
                    ->where('is_active', 1)
                    ->whereNull('deleted_at')
                    ->limit(20)
                    ->get();
                
                $popularItems = $items->map(function ($item) {
                    // Calculate display price - prioritize sale_price if it exists and is greater than 0
                    $price = 0;
                    
                    // Convert to float and check
                    $basePrice = floatval($item->base_price ?? 0);
                    $salePrice = floatval($item->sale_price ?? 0);
                    
                    // Use sale price if it's greater than 0, otherwise use base price
                    if ($salePrice > 0) {
                        $price = $salePrice;
                    } elseif ($basePrice > 0) {
                        $price = $basePrice;
                    }
                    
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description ?? '',
                        'price' => $price, // Already a float from floatval
                        'image' => null, // Images would need to be fetched separately
                        'category' => $item->category ?? '',
                        'available' => (bool) $item->is_available,
                    ];
                })->toArray();
            } catch (\Exception $e) {
                // Even direct query failed, return empty
                $popularItems = [];
            }
        }

        // Use es-order/session view
        return Inertia::render('es-order/session', [
            'sessionUuid' => $uuid,
            'sessionLocation' => $locationData,
            'sessionType' => $session->type,
            'tableNumber' => $session->table_number,
            'popularItems' => $popularItems,
            'categories' => $categories,
        ]);
    }

    /**
     * Display the specified order
     * Step 4: Order detail page with all events
     */
    public function show(string $orderId): Response
    {
        $order = Order::with(['items', 'statusHistory'])->find($orderId);
        
        if (!$order) {
            abort(404, 'Order not found');
        }

        // Get state transition data
        $stateTransitionData = $this->getStateTransitionData($order);

        // Use es-order/show view
        return Inertia::render('es-order/show', [
            'order' => $order->toArray(),
            'user' => null, // Load if needed
            'orderLocation' => null, // Load if needed
            'payments' => [],
            'offers' => [],
            'isPaid' => $order->payment_status === 'paid',
            'remainingAmount' => $order->total,
            'eventStreamData' => [
                'orderUuid' => $order->id,
                'events' => [],
                'currentState' => $order->toArray(),
                'statistics' => [
                    'totalEvents' => 0,
                    'statusChanges' => $order->statusHistory->count(),
                ],
            ],
            'stateTransitionData' => $stateTransitionData,
        ]);
    }

    /**
     * Add item to cart in session (AJAX)
     */
    public function addItem(Request $request, string $sessionUuid): RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'modifiers' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        // Get item details
        $item = $this->itemRepository?->find($validated['item_id']);
        if (!$item) {
            return back()->with('error', 'Item not found');
        }

        // Add item to cart through event sourcing
        $basePrice = $item->basePrice ?? $item->price ?? 0;
        $unitPrice = $item->salePrice ?? $item->price ?? $basePrice;
        
        OrderSessionAggregate::retrieve($sessionUuid)
            ->addToCart(
                itemId: $validated['item_id'],
                itemName: $item->name,
                quantity: $validated['quantity'],
                basePrice: $basePrice,
                unitPrice: $unitPrice,
                category: $item->category ?? null,
                modifiers: $validated['modifiers'] ?? [],
                notes: $validated['notes'] ?? null,
                addedFrom: 'catalog'
            )
            ->persist();

        return back()->with('success', 'Item added to cart');
    }

    /**
     * Remove item from cart (AJAX)
     */
    public function removeItem(Request $request, string $sessionUuid, int $itemIndex): RedirectResponse
    {
        // Need to get the item details first
        // For now, we'll use the item index as the item ID (this needs to be adjusted based on your cart structure)
        OrderSessionAggregate::retrieve($sessionUuid)
            ->removeFromCart(
                itemId: $itemIndex,
                itemName: 'Item', // You'd get this from the session/cart
                removedQuantity: 1,
                removalReason: 'user_action'
            )
            ->persist();

        return back()->with('success', 'Item removed from cart');
    }

    /**
     * Convert session to order (checkout)
     * Step 3: Checkout and create order
     */
    public function checkout(Request $request, string $sessionUuid): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'items' => 'nullable|array',
            'customer_info' => 'nullable|array',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'delivery_address' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|string',
        ]);

        // Check if session exists
        $session = OrderSession::find($sessionUuid);
        if (!$session) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Session not found'], 404);
            }
            return redirect()->route('es-order.new')->with('error', 'Session not found');
        }
        
        // If already converted, just return the existing order - NO PROCESSING
        if ($session->status === 'converted' && $session->order_id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'order_uuid' => $session->order_id,
                    ],
                ]);
            }
            return redirect()->route('es-order.show', $session->order_id)
                ->with('info', 'Order already created');
        }

        // ONLY process if NOT converted
        $aggregate = OrderSessionAggregate::retrieve($sessionUuid);
        
        // Add items to cart if provided
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                // Get item details for proper pricing
                $itemData = null;
                if ($this->itemRepository) {
                    $itemData = $this->itemRepository->find($item['id']);
                }
                
                // Calculate price
                $basePrice = $item['price'] ?? 0;
                $unitPrice = $item['price'] ?? 0;
                if ($itemData) {
                    $basePrice = $itemData->basePrice ?? $itemData->price ?? $basePrice;
                    $unitPrice = $itemData->salePrice ?? $itemData->price ?? $basePrice;
                }
                
                $aggregate->addToCart(
                    itemId: $item['id'],
                    itemName: $item['name'] ?? 'Item',
                    quantity: $item['quantity'] ?? 1,
                    basePrice: $basePrice,
                    unitPrice: $unitPrice,
                    category: $item['category'] ?? null,
                    modifiers: $item['modifiers'] ?? [],
                    notes: $item['notes'] ?? null,
                    addedFrom: 'checkout'
                );
            }
        }
        
        // Extract customer info from either format
        $customerInfo = [];
        if (!empty($validated['customer_info'])) {
            $customerInfo = [
                'name' => $validated['customer_info']['name'] ?? null,
                'phone' => $validated['customer_info']['phone'] ?? null,
                'email' => $validated['customer_info']['email'] ?? null,
            ];
        } else {
            $customerInfo = [
                'name' => $validated['customer_name'] ?? null,
                'phone' => $validated['customer_phone'] ?? null,
                'email' => $validated['customer_email'] ?? null,
            ];
        }
        
        // Update customer info if provided
        if (!empty($customerInfo['name']) || !empty($customerInfo['phone'])) {
            $aggregate->enterCustomerInfo(
                fields: $customerInfo,
                validationErrors: [],
                isComplete: true
            );
        }
        
        // Set payment method - default to cash if not provided
        $paymentMethod = $validated['payment_method'] ?? 'cash';
        $aggregate->selectPaymentMethod($paymentMethod);
        
        // Convert session to order and persist
        $aggregate->convertToOrder()->persist();
        
        // Re-fetch session to get the generated order ID
        $session = OrderSession::find($sessionUuid);
        $orderId = $session->order_id ?? $sessionUuid;

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'order_uuid' => $orderId,
                ],
            ]);
        }

        // Redirect for regular requests
        return redirect()->route('es-order.show', $orderId)
            ->with('success', 'Order created successfully');
    }

    /**
     * Display kitchen view
     */
    public function kitchen(Request $request): Response
    {
        $locationId = $request->user()?->location_id ?? 1;
        
        $orders = $this->orderRepository?->getActiveKitchenOrders($locationId, 50) ?? collect();

        return Inertia::render('order/kitchen', [
            'orders' => $orders->toArray(),
        ]);
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, string $orderId): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Get order to find session UUID
        $order = Order::find($orderId);
        if (!$order || !$order->session_id) {
            return back()->with('error', 'Order not found');
        }

        // For now, mark session as abandoned since we don't have cancelOrder method
        OrderSessionAggregate::retrieve($order->session_id)
            ->abandonSession(
                reason: $validated['reason'],
                sessionDurationSeconds: 0,
                lastActivity: 'order_cancelled'
            )
            ->persist();

        return back()->with('success', 'Order cancelled');
    }

    /**
     * Confirm order
     */
    public function confirm(string $orderId): RedirectResponse
    {
        $order = Order::find($orderId);
        if (!$order || !$order->session_id) {
            return back()->with('error', 'Order not found');
        }

        // For now, we can only convert to order, not confirm
        // This would need to be handled through order status transitions
        return back()->with('info', 'Order confirmation not yet implemented in pure ES');

        return back()->with('success', 'Order confirmed');
    }

    /**
     * Get state transition data for order
     */
    private function getStateTransitionData(Order $order): array
    {
        $availableTransitions = [];
        
        // Define available transitions based on current status
        switch ($order->status) {
            case 'draft':
                $availableTransitions = ['placed', 'cancelled'];
                break;
            case 'placed':
                $availableTransitions = ['confirmed', 'cancelled'];
                break;
            case 'confirmed':
                $availableTransitions = ['preparing', 'cancelled'];
                break;
            case 'preparing':
                $availableTransitions = ['ready', 'cancelled'];
                break;
            case 'ready':
                $availableTransitions = ['delivering', 'delivered', 'completed'];
                break;
            case 'delivering':
                $availableTransitions = ['delivered'];
                break;
            case 'delivered':
                $availableTransitions = ['completed'];
                break;
        }

        return [
            'currentState' => $order->status,
            'availableTransitions' => $availableTransitions,
            'canTransition' => !empty($availableTransitions),
        ];
    }
}