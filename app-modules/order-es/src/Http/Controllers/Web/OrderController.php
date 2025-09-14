<?php

declare(strict_types=1);

namespace Colame\OrderEs\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Traits\HandlesPaginationBounds;
use Colame\OrderEs\Aggregates\OrderSession as OrderSessionAggregate;
use Colame\OrderEs\Contracts\OrderRepositoryInterface;
use Colame\OrderEs\Models\Order;
use Colame\OrderEs\Models\OrderSession;
use Colame\OrderEs\Models\OrderStatusHistory;
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
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

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
        $paginatedData = $this->orderRepository->getPaginatedOrders($filters, $perPage);
        $responseData = $paginatedData->toArray();

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

        // Get stats for the dashboard cards
        $stats = $this->getOrderStats($filters);
        
        // Use es-order views
        return Inertia::render('es-order/index', [
            'view' => [
                'orders' => $responseData['data'],
                'pagination' => $responseData['pagination'],
                'metadata' => $responseData['metadata'],
                'locations' => $locations,
                'filters' => $filters,
                'stats' => $stats,
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
        
        // Get user data if available
        $user = null;
        if ($order->user_id) {
            try {
                $user = \App\Models\User::find($order->user_id);
            } catch (\Exception $e) {
                // User model might not exist
            }
        }
        
        // Get location data
        $location = null;
        if ($order->location_id && $this->locationService) {
            try {
                $locationData = $this->locationService->getLocation($order->location_id);
                if ($locationData) {
                    $location = is_array($locationData) ? $locationData : $locationData->toArray();
                }
            } catch (\Exception $e) {
                // Location service might not be available
            }
        }
        
        // Format order data with items
        $orderData = $order->toArray();
        $orderData['orderNumber'] = $order->order_number;
        $orderData['totalAmount'] = $order->total; // Already in minor units
        $orderData['createdAt'] = $order->created_at;
        $orderData['customerName'] = $order->customer_name;
        $orderData['customerPhone'] = $order->customer_phone;
        $orderData['customerEmail'] = $order->customer_email;
        $orderData['items'] = $order->items->map(function ($item) {
            return [
                'id' => $item->id,
                'itemId' => $item->item_id,
                'name' => $item->item_name,
                'quantity' => $item->quantity,
                'unitPrice' => $item->unit_price, // Already in minor units
                'totalPrice' => $item->total_price, // Already in minor units
                'notes' => $item->notes,
                'status' => $item->status,
                'kitchenStatus' => $item->kitchen_status,
            ];
        })->toArray();

        // Get actual stored events for this order session
        $storedEvents = collect([]);
        if ($order->session_id) {
            $storedEvents = EloquentStoredEvent::query()
                ->where('aggregate_uuid', $order->session_id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        // Build event stream data from actual events
        $eventStreamData = [
            'orderUuid' => $order->session_id ?? $order->id,
            'events' => [],
            'currentState' => [
                'orderId' => $order->id,
                'orderNumber' => $order->order_number,
                'status' => $order->status,
                'items' => $orderData['items'],
                'totalAmount' => $order->total,
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'tip' => $order->tip,
                'discount' => $order->discount,
            ],
            'statistics' => [
                'totalEvents' => $storedEvents->count(),
                'eventTypes' => [],
                'firstEventAt' => $storedEvents->last() ? \Carbon\Carbon::parse($storedEvents->last()->created_at)->toIso8601String() : \Carbon\Carbon::parse($order->created_at)->toIso8601String(),
                'lastEventAt' => $storedEvents->first() ? \Carbon\Carbon::parse($storedEvents->first()->created_at)->toIso8601String() : \Carbon\Carbon::parse($order->updated_at)->toIso8601String(),
                'duration' => '0 minutes',
            ],
        ];
        
        // Format stored events for display
        $eventIcons = [
            'SessionInitiated' => 'play-circle',
            'ItemAddedToCart' => 'plus-circle',
            'ItemRemovedFromCart' => 'minus-circle',
            'SessionConverted' => 'check-circle',
            'OrderSessionInitiated' => 'play-circle',
            'CartItemAdded' => 'plus-circle',
            'CartItemRemoved' => 'minus-circle',
            'OrderCheckedOut' => 'shopping-cart',
            'OrderStatusChanged' => 'refresh-cw',
            'OrderStarted' => 'play-circle',
            'ItemAddedToOrder' => 'plus-circle',
        ];
        
        $eventColors = [
            'SessionInitiated' => 'blue',
            'ItemAddedToCart' => 'green',
            'ItemRemovedFromCart' => 'red',
            'SessionConverted' => 'green',
            'OrderSessionInitiated' => 'blue',
            'CartItemAdded' => 'green',
            'CartItemRemoved' => 'red',
            'OrderCheckedOut' => 'purple',
            'OrderStatusChanged' => 'orange',
            'OrderStarted' => 'blue',
            'ItemAddedToOrder' => 'green',
        ];
        
        $eventDescriptions = [
            'SessionInitiated' => 'Order session started',
            'OrderSessionInitiated' => 'Order session started',
            'ItemAddedToCart' => 'Item added to cart',
            'CartItemAdded' => 'Item added to cart',
            'ItemRemovedFromCart' => 'Item removed from cart',
            'CartItemRemoved' => 'Item removed from cart',
            'SessionConverted' => 'Session converted to order',
            'OrderCheckedOut' => 'Order checked out',
            'OrderStatusChanged' => 'Order status changed',
            'OrderStarted' => 'Order started',
            'ItemAddedToOrder' => 'Item added to order',
        ];
        
        foreach ($storedEvents as $index => $storedEvent) {
            $eventClass = class_basename($storedEvent->event_class);
            $eventData = $storedEvent->event_properties;
            
            // Get user info
            $userName = 'System';
            if (isset($storedEvent->meta_data['user_id'])) {
                $user = \App\Models\User::find($storedEvent->meta_data['user_id']);
                $userName = $user ? $user->name : 'User #' . $storedEvent->meta_data['user_id'];
            }
            
            $eventStreamData['events'][] = [
                'id' => $storedEvent->id,
                'type' => $eventClass,
                'eventClass' => $eventClass,
                'version' => $storedEvent->aggregate_version ?? $index + 1,
                'properties' => $eventData,
                'metadata' => $storedEvent->meta_data ?? [],
                'userId' => $storedEvent->meta_data['user_id'] ?? null,
                'userName' => $userName,
                'description' => $eventDescriptions[$eventClass] ?? ucwords(str_replace('_', ' ', strtolower(preg_replace('/([a-z])([A-Z])/', '$1 $2', $eventClass)))),
                'icon' => $eventIcons[$eventClass] ?? 'activity',
                'color' => $eventColors[$eventClass] ?? 'gray',
                'createdAt' => \Carbon\Carbon::parse($storedEvent->created_at)->toIso8601String(),
                'timestamp' => \Carbon\Carbon::parse($storedEvent->created_at)->toIso8601String(),
                'relativeTime' => \Carbon\Carbon::parse($storedEvent->created_at)->diffForHumans(),
            ];
            
            // Count event types
            if (!isset($eventStreamData['statistics']['eventTypes'][$eventClass])) {
                $eventStreamData['statistics']['eventTypes'][$eventClass] = 0;
            }
            $eventStreamData['statistics']['eventTypes'][$eventClass]++;
        }
        
        // Calculate duration
        if ($storedEvents->count() > 0) {
            $first = \Carbon\Carbon::parse($storedEvents->last()->created_at);
            $last = \Carbon\Carbon::parse($storedEvents->first()->created_at);
            $duration = $first->diff($last);
            $eventStreamData['statistics']['duration'] = $duration->format('%H:%I:%S');
        }
        
        // Use es-order/show view
        return Inertia::render('es-order/show', [
            'order' => $orderData,
            'user' => $user,
            'orderLocation' => $location,
            'payments' => [],
            'offers' => [],
            'isPaid' => $order->payment_status === 'paid',
            'remainingAmount' => $order->payment_status === 'paid' ? 0 : $order->total,
            'stateTransitionData' => $stateTransitionData,
            'eventStreamData' => $eventStreamData,
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
     * Change order status
     */
    public function changeStatus(Request $request, string $orderId): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:placed,confirmed,preparing,ready,delivering,delivered,completed,cancelled',
        ]);
        
        $order = Order::find($orderId);
        if (!$order || !$order->session_id) {
            return back()->with('error', 'Order not found or missing session');
        }
        
        // Use event sourcing to change status
        OrderSessionAggregate::retrieve($order->session_id)
            ->changeOrderStatus(
                orderId: $orderId,
                toStatus: $validated['status'],
                userId: auth()->id(),
                reason: 'Manual status change'
            )
            ->persist();
        
        // Add to status history (this will also be handled by projector but we keep for consistency)
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => $order->status,
            'to_status' => $validated['status'],
            'user_id' => auth()->id(),
            'reason' => 'Manual status change',
        ]);
        
        return redirect()
            ->route('es-order.show', $orderId)
            ->with('success', 'Order status updated');
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
     * Get order statistics for dashboard cards
     */
    private function getOrderStats(array $filters = []): array
    {
        $query = Order::query();
        
        // Apply location filter if present
        if (!empty($filters['locationId'])) {
            $query->where('location_id', $filters['locationId']);
        }
        
        // Today's orders
        $todayOrders = (clone $query)->whereDate('created_at', today())->count();
        
        // Active orders (not completed or cancelled)
        $activeOrders = (clone $query)->whereIn('status', ['started', 'placed', 'confirmed', 'preparing', 'ready'])->count();
        
        // Ready to serve
        $readyToServe = (clone $query)->where('status', 'ready')->count();
        
        // Pending payment
        $pendingPayment = (clone $query)->where('payment_status', 'pending')->count();
        
        return [
            'todayOrders' => $todayOrders,
            'activeOrders' => $activeOrders,
            'readyToServe' => $readyToServe,
            'pendingPayment' => $pendingPayment,
        ];
    }

    /**
     * Get state transition data for order
     */
    private function getStateTransitionData(Order $order): array
    {
        $nextStates = [];
        $canCancel = false;
        
        // Define status labels and icons
        $statusConfig = [
            'placed' => ['label' => 'Place Order', 'icon' => 'shopping-cart', 'color' => 'blue'],
            'confirmed' => ['label' => 'Confirm Order', 'icon' => 'check-circle', 'color' => 'green'],
            'preparing' => ['label' => 'Start Preparing', 'icon' => 'clock', 'color' => 'yellow'],
            'ready' => ['label' => 'Mark as Ready', 'icon' => 'check', 'color' => 'green'],
            'delivering' => ['label' => 'Start Delivery', 'icon' => 'truck', 'color' => 'blue'],
            'delivered' => ['label' => 'Mark as Delivered', 'icon' => 'package-check', 'color' => 'green'],
            'completed' => ['label' => 'Complete Order', 'icon' => 'check-square', 'color' => 'green'],
            'cancelled' => ['label' => 'Cancel Order', 'icon' => 'x-circle', 'color' => 'red'],
        ];
        
        // Define available transitions based on current status
        switch ($order->status) {
            case 'draft':
            case 'started':
                $nextStates[] = [
                    'value' => 'confirmed', 
                    'action_label' => $statusConfig['confirmed']['label'],
                    'icon' => $statusConfig['confirmed']['icon'],
                    'color' => $statusConfig['confirmed']['color']
                ];
                $canCancel = true;
                break;
            case 'placed':
                $nextStates[] = [
                    'value' => 'confirmed', 
                    'action_label' => $statusConfig['confirmed']['label'],
                    'icon' => $statusConfig['confirmed']['icon'],
                    'color' => $statusConfig['confirmed']['color']
                ];
                $canCancel = true;
                break;
            case 'confirmed':
                $nextStates[] = [
                    'value' => 'preparing', 
                    'action_label' => $statusConfig['preparing']['label'],
                    'icon' => $statusConfig['preparing']['icon'],
                    'color' => $statusConfig['preparing']['color']
                ];
                $canCancel = true;
                break;
            case 'preparing':
                $nextStates[] = [
                    'value' => 'ready', 
                    'action_label' => $statusConfig['ready']['label'],
                    'icon' => $statusConfig['ready']['icon'],
                    'color' => $statusConfig['ready']['color']
                ];
                $canCancel = false; // Can't cancel once preparing
                break;
            case 'ready':
                if ($order->type === 'delivery') {
                    $nextStates[] = [
                        'value' => 'delivering', 
                        'action_label' => $statusConfig['delivering']['label'],
                        'icon' => $statusConfig['delivering']['icon'],
                        'color' => $statusConfig['delivering']['color']
                    ];
                } else {
                    $nextStates[] = [
                        'value' => 'completed', 
                        'action_label' => $statusConfig['completed']['label'],
                        'icon' => $statusConfig['completed']['icon'],
                        'color' => $statusConfig['completed']['color']
                    ];
                }
                break;
            case 'delivering':
                $nextStates[] = [
                    'value' => 'delivered', 
                    'action_label' => $statusConfig['delivered']['label'],
                    'icon' => $statusConfig['delivered']['icon'],
                    'color' => $statusConfig['delivered']['color']
                ];
                break;
            case 'delivered':
                $nextStates[] = [
                    'value' => 'completed', 
                    'action_label' => $statusConfig['completed']['label'],
                    'icon' => $statusConfig['completed']['icon'],
                    'color' => $statusConfig['completed']['color']
                ];
                break;
        }

        return [
            'current_state' => $order->status,
            'next_states' => $nextStates,
            'can_cancel' => $canCancel,
            'can_transition' => !empty($nextStates),
        ];
    }
}