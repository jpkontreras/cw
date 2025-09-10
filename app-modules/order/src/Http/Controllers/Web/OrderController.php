<?php

declare(strict_types=1);

namespace Colame\Order\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Traits\HandlesPaginationBounds;
use Colame\Order\Contracts\OrderServiceInterface;
use Colame\Order\Data\CreateOrderData;
use Colame\Order\Data\OrderData;
use Colame\Order\Data\ModifyOrderData;
use Colame\Order\Services\EventSourcedOrderService;
use Colame\Order\Services\EventStreamService;
use Colame\Order\Exceptions\OrderException;
use Colame\Order\Models\Order;
use Colame\Order\Models\OrderSession;
use Colame\Order\States\StartedState;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Taxonomy\Contracts\TaxonomyServiceInterface;
use Colame\Location\Contracts\LocationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

/**
 * Web controller for orders (Inertia responses)
 */
class OrderController extends Controller
{
    use HandlesPaginationBounds;
    public function __construct(
        private OrderServiceInterface $orderService,
        private ItemRepositoryInterface $itemRepository,
        private ?TaxonomyServiceInterface $taxonomyService = null,
        private ?EventSourcedOrderService $eventService = null,
        private ?EventStreamService $eventStreamService = null,
        private ?LocationServiceInterface $locationService = null
    ) {
        $this->eventService = $eventService ?? app(EventSourcedOrderService::class);
        $this->eventStreamService = $eventStreamService ?? app(EventStreamService::class);
    }

    /**
     * Display a listing of orders
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $filters = $request->only(['status', 'type', 'locationId', 'date', 'search', 'sort', 'page', 'orderNumber', 'customerName', 'paymentStatus']);
        $perPage = (int) $request->input('per_page', 20);

        // Get paginated orders with filters and metadata
        $paginatedData = $this->orderService->getPaginatedOrders($filters, $perPage);
        $responseData = $paginatedData->toArray();

        // Handle out-of-bounds page numbers
        if ($redirect = $this->handleOutOfBoundsPagination($responseData['pagination'], $request, 'orders.index')) {
            return $redirect;
        }

        // Get locations for filter dropdown using the location service
        $locations = [];
        if ($this->locationService) {
            // Get the raw collection of LocationData objects
            $locationsCollection = $this->locationService->getActiveLocations();
            // Transform to simple array for the filter dropdown
            $locations = [];
            foreach ($locationsCollection as $location) {
                $locations[] = [
                    'id' => $location->id,
                    'name' => $location->name,
                ];
            }
        }

        // Update location options in metadata
        if (isset($responseData['metadata']['columns']['locationId'])) {
            $responseData['metadata']['columns']['locationId']['filter']['options'] = $locations;
        }

        // Get stats for the dashboard cards
        $stats = $this->orderService->getOrderStats($filters);

        return Inertia::render('order/index', [
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
     * Show the form for creating a new order
     */
    /**
     * Display the order initialization screen (welcome screen)
     */
    public function new(Request $request): Response
    {
        // This is the welcome screen where user selects service type
        // No data needed here - just show the welcome screen
        return Inertia::render('order/new');
    }

    /**
     * Display the order creation session
     */
    public function session(Request $request, string $uuid): Response|RedirectResponse
    {
        // Get session from event-sourced table
        $session = OrderSession::find($uuid);
        
        if (!$session || !$session->isActive()) {
            return redirect()->route('orders.new')->with('error', 'Session not found or expired');
        }
        
        // Get location from session (now properly stored as integer)
        $locationId = $session->location_id;
        $locationData = $session->getLocationData();
        
        // Get formatted categories from the taxonomy service using session location
        $categories = [];
        if ($this->taxonomyService && $locationId) {
            $categoriesCollection = $this->taxonomyService->getFormattedItemCategories($locationId);
            // Transform to array for Inertia (includes computed properties)
            $categories = $categoriesCollection->toArray();
        }

        // Return the order creation interface with the session UUID and location context
        return Inertia::render('order/session', [
            'sessionUuid' => $uuid,
            'sessionLocation' => $locationData, // Include locked location
            'popularItems' => [], // Will be loaded via API
            'categories' => $categories,
        ]);
    }

    public function create(Request $request): Response
    {
        // Legacy method - redirects to new
        return redirect()->route('orders.new');
    }


    /**
     * Store a newly created order
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            // Get session location if available
            $sessionUuid = $request->input('sessionUuid');
            $sessionLocationId = null;
            if ($sessionUuid) {
                $session = OrderSession::find($sessionUuid);
                $sessionLocationId = $session ? $session->location_id : null;
            }
            
            // Create order data from request with validation
            $payload = array_merge($request->all(), [
                'userId' => $request->user()?->id,
                'sessionLocationId' => $sessionLocationId, // Pass session location
            ]);
            $data = CreateOrderData::validateAndCreate($payload);
            $order = $this->orderService->createOrder($data);

            // Debug: Log the order ID being used for redirect
            \Illuminate\Support\Facades\Log::info("Order created with ID: " . $order->id);

            return redirect()
                ->route('orders.show', $order->id)
                ->with('success', 'Order created successfully');
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (OrderException $e) {
            return redirect()
                ->back()
                ->withErrors(['order' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified order
     */
    public function show(Order $order): Response
    {
        // Refresh the order to ensure we have the latest status
        // This is important after state transitions and redirects
        $order->refresh();
        
        $orderWithRelations = $this->orderService->getOrderWithRelations($order->id);

        if (!$orderWithRelations) {
            abort(404, 'Order not found');
        }

        // Extract the order data and other relations
        $orderData = $orderWithRelations->order;

        // Get comprehensive event stream data - order->id IS the UUID
        $eventStreamData = null;
        if ($order->id) {
            $events = $this->eventStreamService->getOrderEventStream($order->id);
            $eventStatistics = $this->eventStreamService->getEventStatistics($order->id);
            $currentState = $this->eventStreamService->getOrderStateAtTimestamp($order->id, now());

            $eventStreamData = [
                'orderUuid' => $order->id, // id IS the UUID
                'events' => $events->toArray(),
                'currentState' => $currentState, // This now contains the full order state with items
                'statistics' => $eventStatistics,
            ];
        }

        // Get state transition data - use refreshed order
        $stateTransitionData = $this->getStateTransitionData($order);
        
        return Inertia::render('order/show', [
            'order' => $orderData->toArray(),
            'user' => $orderWithRelations->user,
            'orderLocation' => $orderWithRelations->location, // Renamed to avoid conflict with location switcher
            'payments' => $orderWithRelations->payments ?? [],
            'offers' => $orderWithRelations->offers ?? [],
            'isPaid' => $orderWithRelations->isPaid(),
            'remainingAmount' => $orderWithRelations->getRemainingAmount(),
            'eventStreamData' => $eventStreamData,
            'stateTransitionData' => $stateTransitionData,
        ]);
    }

    /**
     * Show the form for editing the order
     */
    public function edit(Order $order): Response|RedirectResponse
    {
        $orderWithRelations = $this->orderService->getOrderWithRelations($order->id);

        if (!$orderWithRelations) {
            abort(404, 'Order not found');
        }

        // Get modification permissions using event service
        $permissions = $this->eventService->getModificationPermissions(
            $order->uuid,
            request()->user()->id
        );

        if (!$permissions['canModify']) {
            return redirect()
                ->route('orders.show', $order->id)
                ->with('error', 'Order cannot be modified');
        }

        // Add canBeModified method to order data for compatibility
        $orderData = $orderWithRelations->order->toArray();
        $orderData['canBeModified'] = fn() => $permissions['canModify'];

        return Inertia::render('order/edit', [
            'order' => $orderData,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update the specified order using event sourcing
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        try {
            // Add user info to the modification data
            $payload = array_merge($request->all(), [
                'modifiedBy' => $request->user()->email ?? 'User #' . $request->user()->id
            ]);

            $data = ModifyOrderData::validateAndCreate($payload);

            // Use event-sourced service for modifications
            $this->eventService->modifyOrder($order->uuid, $data);

            return redirect()
                ->route('orders.show', $order->id)
                ->with('success', 'Order modified successfully');
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (OrderException $e) {
            return redirect()
                ->back()
                ->withErrors(['order' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Place order (move to placed status)
     */
    public function place(Request $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->transitionOrderStatus(
                $order->id,
                'placed',
                $request->input('reason')
            );

            return redirect()->back()->with('success', 'Order placed successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Confirm the order
     */
    public function confirm(Request $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->transitionOrderStatus(
                $order->id,
                'confirmed',
                $request->input('reason')
            );

            return redirect()->back()->with('success', 'Order confirmed successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Start preparing the order
     */
    public function startPreparing(Request $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->transitionOrderStatus(
                $order->id,
                'preparing',
                $request->input('reason')
            );

            return redirect()->back()->with('success', 'Order preparation started');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark order as ready
     */
    public function markReady(Request $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->transitionOrderStatus(
                $order->id,
                'ready',
                $request->input('reason')
            );

            return redirect()->back()->with('success', 'Order marked as ready');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete the order
     */
    public function complete(Request $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->transitionOrderStatus(
                $order->id,
                'completed',
                $request->input('reason')
            );

            return redirect()->back()->with('success', 'Order completed successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Start delivery
     */
    public function startDelivery(Request $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->transitionOrderStatus(
                $order->id,
                'delivering',
                $request->input('reason')
            );

            return redirect()->back()->with('success', 'Delivery started');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark as delivered
     */
    public function markDelivered(Request $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->transitionOrderStatus(
                $order->id,
                'delivered',
                $request->input('reason')
            );

            return redirect()->back()->with('success', 'Order marked as delivered');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display receipt
     */
    public function receipt(Order $order): Response
    {
        $order->load(['items', 'payments']);

        // TODO: Implement receipt view
        return Inertia::render('order/receipt', [
            'order' => OrderData::fromModel($order)->toArray(),
        ]);
    }

    /**
     * Show cancel order form
     */
    public function showCancelForm(Order $order): Response
    {
        $orderWithRelations = $this->orderService->getOrderWithRelations($order->id);

        if (!$orderWithRelations || !$orderWithRelations->order->canBeCancelled()) {
            abort(403, 'Order cannot be cancelled');
        }

        return Inertia::render('order/cancel', [
            'order' => $orderWithRelations,
        ]);
    }

    /**
     * Cancel the order
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        try {
            // Use a simple DTO for cancel validation
            $validated = $request->validate([
                'reason' => ['required', 'string', 'min:5', 'max:500'],
            ]);

            $this->orderService->cancelOrder($order->id, $validated['reason'] ?? '');

            return redirect()
                ->route('orders.show', $order->id)
                ->with('success', 'Order cancelled');
        } catch (OrderException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display kitchen view
     */
    public function kitchen(Request $request): Response
    {
        $user = $request->user();
        
        // Kitchen display still needs current location for filtering
        // This is one of the few places where we need real-time location
        $locationId = null;
        if ($this->locationService && $user) {
            $currentLocation = $this->locationService->getUserCurrentLocation($user->id);
            $locationId = $currentLocation ? $currentLocation->id : null;
        }
        
        // If no location found, we can't show kitchen orders
        if (!$locationId) {
            return Inertia::render('order/kitchen-display', [
                'orders' => [],
                'locationId' => null,
                'error' => 'No location selected. Please select a location to view kitchen orders.',
            ]);
        }
        
        $orders = $this->orderService->getKitchenOrders($locationId);

        return Inertia::render('order/kitchen-display', [
            'orders' => $orders->toArray(),
            'locationId' => $locationId,
        ]);
    }

    /**
     * Display order dashboard
     */
    public function dashboard(Request $request): Response
    {
        $filters = $request->only(['period', 'locationId']);
        $dashboardData = $this->orderService->getDashboardData($filters);

        return Inertia::render('order/dashboard', $dashboardData);
    }

    /**
     * Display operations center
     */
    public function operations(Request $request): Response
    {
        $locationId = $request->input('locationId');

        // Get active orders
        $ordersQuery = Order::query()
            ->with(['items'])
            ->whereNotIn('status', ['completed', 'cancelled', 'refunded'])
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->orderBy('created_at', 'asc');

        $ordersCollection = $ordersQuery->get();

        // Calculate stats first
        $stats = [
            'active' => $ordersCollection->count(),
            'preparing' => $ordersCollection->where('status', 'preparing')->count(),
            'ready' => $ordersCollection->where('status', 'ready')->count(),
            'avgWaitTime' => 25, // Mock data - would calculate from actual times
        ];

        // Convert to DTOs
        $orders = $ordersCollection->map(fn($order) => OrderData::fromModel($order)->toArray());

        // Get locations from location service
        $locations = [];
        if ($this->locationService) {
            $locationsCollection = $this->locationService->getActiveLocations();
            $locations = $locationsCollection->map(fn($location) => [
                'id' => $location->id,
                'name' => $location->name,
            ])->toArray();
        }

        return Inertia::render('order/operations', [
            'orders' => $orders,
            'locations' => $locations,
            'stats' => $stats,
        ]);
    }

    /**
     * Display payment page
     */
    public function payment(string $id): Response|RedirectResponse
    {
        $order = Order::with(['items', 'payments'])->find($id);

        if (!$order) {
            abort(404, 'Order not found');
        }

        if ($order->payment_status === 'paid') {
            return redirect()
                ->route('orders.show', $id)
                ->with('info', 'Order is already paid');
        }

        // Calculate remaining amount
        $paidAmount = $order->payments()->where('status', 'completed')->sum('amount');
        $remainingAmount = $order->total_amount - $paidAmount;

        // Get payment methods
        $paymentMethods = [
            ['id' => 'cash', 'name' => 'Cash', 'icon' => 'cash', 'enabled' => true],
            ['id' => 'card', 'name' => 'Credit/Debit Card', 'icon' => 'card', 'enabled' => true],
            ['id' => 'transfer', 'name' => 'Bank Transfer', 'icon' => 'transfer', 'enabled' => true],
            ['id' => 'other', 'name' => 'Other', 'icon' => 'other', 'enabled' => true],
        ];

        return Inertia::render('order/payment', [
            'order' => OrderData::fromModel($order)->toArray(),
            'payments' => $order->payments,
            'remainingAmount' => $remainingAmount,
            'suggestedTip' => 10, // 10% suggested tip
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request, string $id): RedirectResponse
    {
        $order = Order::find($id);

        if (!$order) {
            abort(404, 'Order not found');
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:cash,card,transfer,other'],
            'amount' => ['required', 'numeric', 'min:0'],
            'tip_amount' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'split_payments' => ['nullable', 'array'],
            'split_payments.*.name' => ['required_with:split_payments', 'string'],
            'split_payments.*.amount' => ['required_with:split_payments', 'numeric', 'min:0'],
            'split_payments.*.payment_method' => ['required_with:split_payments', 'string'],
        ]);

        try {
            // Process payment through service
            // In real implementation, this would handle payment gateway integration

            // Update tip amount if provided
            if (isset($validated['tip_amount'])) {
                $order->update([
                    'tip_amount' => $validated['tip_amount'],
                    'total_amount' => $order->subtotal + $order->tax_amount + $validated['tip_amount'] - $order->discount_amount,
                ]);
            }

            // Create payment record
            $order->payments()->create([
                'payment_method' => $validated['payment_method'] ?? '',
                'amount' => $validated['amount'] ?? 0,
                'status' => 'completed',
                'reference_number' => $validated['reference_number'] ?? null,
                'processed_at' => now(),
                'metadata' => [
                    'notes' => $validated['notes'] ?? null,
                    'split_payments' => $validated['split_payments'] ?? null,
                ],
            ]);

            // Check if fully paid
            $totalPaid = $order->payments()->where('status', 'completed')->sum('amount');
            if ($totalPaid >= $order->total_amount) {
                $order->update(['payment_status' => 'paid']);
            } else {
                $order->update(['payment_status' => 'partial']);
            }

            return redirect()
                ->route('orders.show', $id)
                ->with('success', 'Payment processed successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Payment processing failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get order state at a specific timestamp (for time travel)
     */
    public function getStateAtTimestamp(Order $order, Request $request): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $request->validate([
            'timestamp' => 'required|date',
        ]);

        if (!$order->uuid) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Order does not have UUID for event sourcing'], 400);
            }
            return back()->with('error', 'Order does not have UUID for event sourcing');
        }

        $timestamp = Carbon::parse($request->input('timestamp'));
        $state = $this->eventStreamService->getOrderStateAtTimestamp($order->uuid, $timestamp);

        // For AJAX/JSON requests, return JSON
        if ($request->wantsJson()) {
            return response()->json($state);
        }

        // For Inertia requests, redirect back with data
        return back()->with('orderState', $state);
    }

    /**
     * Replay events between timestamps
     */
    public function replayEvents(Order $order, Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        if (!$order->uuid) {
            return response()->json(['error' => 'Order does not have UUID for event sourcing'], 400);
        }

        $from = Carbon::parse($request->input('from'));
        $to = Carbon::parse($request->input('to'));

        $events = $this->eventStreamService->replayEventsBetween($order->uuid, $from, $to);

        return response()->json([
            'orderUuid' => $order->uuid,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'events' => $events->toArray(),
            'count' => $events->count(),
        ]);
    }

    /**
     * Handle order confirmation - use appropriate service based on order state
     */
    private function handleOrderConfirmation(Order $order, string $paymentMethod): mixed
    {
        // Check if order is already confirmed
        if ($order->status && $order->status->getValue() === 'confirmed') {
            // Order is already confirmed, just return it without changes
            return $order;
        }
        
        // Update payment method if provided
        if ($paymentMethod && $paymentMethod !== $order->payment_method) {
            $order->update(['payment_method' => $paymentMethod]);
        }
        
        // For orders with UUID (event-sourced orders), always use the event service
        // This ensures proper state progression and event recording
        if ($order->uuid) {
            // For draft orders with items, transition directly to confirmed
            // The event service will handle any necessary intermediate states
            if ($order->status && $order->status->getValue() === 'draft' && $order->items()->exists()) {
                // First transition to started state if needed
                $order->update(['status' => StartedState::class]);
            }
            
            // Use event-sourced service which handles all intermediate state transitions
            $this->eventService->confirmOrder($order->uuid, $paymentMethod);
            
            // Refresh and return the updated order
            $order->refresh();
            return $order;
        }
        
        // For regular orders without UUID, use the regular transition
        return $this->orderService->transitionOrderStatus(
            $order->id,
            'confirmed',
            'Order confirmed'
        );
    }

    /**
     * Add a new event to the order (through event sourcing)
     */
    public function addEvent(Order $order, Request $request): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        if (!$order->uuid) {
            // For Inertia requests, redirect back with error
            if (!$request->wantsJson()) {
                return redirect()->back()->with('error', 'Order does not have UUID for event sourcing');
            }
            return response()->json(['error' => 'Order does not have UUID for event sourcing'], 400);
        }

        $eventType = $request->input('eventType');
        $data = $request->except('eventType');

        try {
            // Use the event sourced service to handle the event
            $result = match ($eventType) {
                'add_items' => $this->eventService->addItems($order->uuid, $data['items']),
                'apply_promotion' => $this->eventService->applyPromotion($order->uuid, $data['promotionId']),
                'add_tip' => $this->eventService->addTip($order->uuid, $data['amount'], $data['percentage'] ?? null),
                'update_customer' => $this->eventService->updateCustomerInfo($order->uuid, $data),
                'confirm' => $this->handleOrderConfirmation($order, $data['paymentMethod'] ?? 'cash'),
                'cancel' => $this->eventService->cancelOrder($order->uuid, $data['reason'] ?? ''),
                // Handle status changes through the regular service
                'change_status' => $this->orderService->transitionOrderStatus(
                    $order->id,
                    $data['newStatus'],
                    $data['reason'] ?? 'Quick status update'
                ),
                default => throw new \InvalidArgumentException("Unknown event type: {$eventType}"),
            };

            // For Inertia requests, redirect back
            if (!$request->wantsJson()) {
                return redirect()->back()->with('success', 'Action completed successfully');
            }

            // Refresh event stream for JSON responses
            $events = $this->eventStreamService->getOrderEventStream($order->uuid);
            $currentState = $this->eventStreamService->getOrderStateAtTimestamp($order->uuid, now());

            return response()->json([
                'success' => true,
                'message' => 'Event added successfully',
                'latestEvents' => $events->take(-5)->toArray(), // Return last 5 events
                'currentState' => $currentState,
            ]);
        } catch (\Exception $e) {
            // For Inertia requests, redirect back with error
            if (!$request->wantsJson()) {
                return redirect()->back()->with('error', $e->getMessage());
            }
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    
    /**
     * Get state transition data for an order
     */
    private function getStateTransitionData(Order $order): array
    {
        try {
            // Get the current state information
            $currentState = [
                'value' => $order->status->getValue(),
                'display_name' => $order->status->displayName(),
                'color' => $order->status->color(),
                'icon' => $order->status->icon(),
                'can_be_modified' => $order->status->canBeModified(),
                'can_be_cancelled' => $order->status->canBeCancelled(),
            ];
            
            // Get the transitionable states - returns array of state names
            $nextStateNames = $order->status->transitionableStates();
            
            // Map state names to full class names and metadata
            $stateNameToClass = [
                'draft' => \Colame\Order\States\DraftState::class,
                'started' => \Colame\Order\States\StartedState::class,
                'items_added' => \Colame\Order\States\ItemsAddedState::class,
                'items_validated' => \Colame\Order\States\ItemsValidatedState::class,
                'promotions_calculated' => \Colame\Order\States\PromotionsCalculatedState::class,
                'price_calculated' => \Colame\Order\States\PriceCalculatedState::class,
                'confirmed' => \Colame\Order\States\ConfirmedState::class,
                'preparing' => \Colame\Order\States\PreparingState::class,
                'ready' => \Colame\Order\States\ReadyState::class,
                'delivering' => \Colame\Order\States\DeliveringState::class,
                'delivered' => \Colame\Order\States\DeliveredState::class,
                'completed' => \Colame\Order\States\CompletedState::class,
                'cancelled' => \Colame\Order\States\CancelledState::class,
                'refunded' => \Colame\Order\States\RefundedState::class,
            ];
            
            // Define state metadata directly without instantiation
            $stateMetadata = [
                'confirmed' => ['display_name' => 'Confirmed', 'action_label' => 'Confirm Order', 'color' => 'blue', 'icon' => 'check-circle'],
                'started' => ['display_name' => 'Started', 'action_label' => 'Start Order', 'color' => 'blue', 'icon' => 'play-circle'],
                'cancelled' => ['display_name' => 'Cancelled', 'action_label' => 'Cancel Order', 'color' => 'red', 'icon' => 'x-circle'],
                'preparing' => ['display_name' => 'Preparing', 'action_label' => 'Start Preparing', 'color' => 'orange', 'icon' => 'clock'],
                'ready' => ['display_name' => 'Ready', 'action_label' => 'Mark as Ready', 'color' => 'green', 'icon' => 'check-circle-2'],
                'completed' => ['display_name' => 'Completed', 'action_label' => 'Complete Order', 'color' => 'green', 'icon' => 'check-circle'],
                'delivering' => ['display_name' => 'Delivering', 'action_label' => 'Start Delivery', 'color' => 'blue', 'icon' => 'truck'],
                'delivered' => ['display_name' => 'Delivered', 'action_label' => 'Mark as Delivered', 'color' => 'green', 'icon' => 'package-check'],
                'items_added' => ['display_name' => 'Items Added', 'action_label' => 'Add Items', 'color' => 'blue', 'icon' => 'shopping-cart'],
                'items_validated' => ['display_name' => 'Items Validated', 'action_label' => 'Validate Items', 'color' => 'blue', 'icon' => 'check'],
                'promotions_calculated' => ['display_name' => 'Promotions Calculated', 'action_label' => 'Calculate Promotions', 'color' => 'blue', 'icon' => 'percent'],
                'price_calculated' => ['display_name' => 'Price Calculated', 'action_label' => 'Calculate Price', 'color' => 'blue', 'icon' => 'calculator'],
                'refunded' => ['display_name' => 'Refunded', 'action_label' => 'Refund Order', 'color' => 'gray', 'icon' => 'rotate-ccw'],
            ];
            
            // Build next states array
            $nextStates = [];
            foreach ($nextStateNames as $stateName) {
                if (isset($stateMetadata[$stateName])) {
                    $nextStates[] = [
                        'value' => $stateName,
                        'display_name' => $stateMetadata[$stateName]['display_name'],
                        'action_label' => $stateMetadata[$stateName]['action_label'],
                        'color' => $stateMetadata[$stateName]['color'],
                        'icon' => $stateMetadata[$stateName]['icon'],
                    ];
                }
            }
            
            // Check if we can cancel from current state
            $canCancel = in_array('cancelled', $nextStateNames);
            
            return [
                'current_state' => $currentState,
                'next_states' => $nextStates,
                'can_cancel' => $canCancel,
                'is_final_state' => empty($nextStates),
            ];
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error getting state transition data: ' . $e->getMessage());
            
            // Return empty state data on error
            return [
                'current_state' => null,
                'next_states' => [],
                'can_cancel' => false,
                'is_final_state' => true,
            ];
        }
    }
}
