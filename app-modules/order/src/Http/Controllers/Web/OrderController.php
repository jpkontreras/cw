<?php

declare(strict_types=1);

namespace Colame\Order\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Traits\HandlesPaginationBounds;
use Colame\Order\Contracts\OrderServiceInterface;
use Colame\Order\Data\CreateOrderData;
use Colame\Order\Data\OrderData;
use Colame\Order\Data\UpdateOrderData;
use Colame\Order\Exceptions\OrderException;
use Colame\Order\Models\Order;
use Colame\Order\Services\OrderStatusService;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Web controller for orders (Inertia responses)
 */
class OrderController extends Controller
{
    use HandlesPaginationBounds;
    public function __construct(
        private OrderServiceInterface $orderService,
        private OrderStatusService $statusService,
        private ItemRepositoryInterface $itemRepository
    ) {}

    /**
     * Display a listing of orders
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $filters = $request->only(['status', 'type', 'locationId', 'date', 'search', 'sort', 'page', 'orderNumber', 'customerName', 'paymentStatus']);
        $perPage = (int) $request->input('per_page', 20);

        // Get paginated orders with filters and metadata
        $paginatedData = $this->orderService->getPaginatedOrders($filters, $perPage);
        $responseData = $paginatedData->toArray();

        // Handle out-of-bounds page numbers
        if ($redirect = $this->handleOutOfBoundsPagination($responseData['pagination'], $request, 'orders.index')) {
            return $redirect;
        }

        // Get locations for filter dropdown  
        $locations = [
            ['id' => 1, 'name' => 'Main Branch'],
            ['id' => 2, 'name' => 'Downtown Branch'],
            // TODO: Replace with actual location service
        ];

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
    public function create(Request $request): Response
    {
        $user = $request->user();

        // Get locations for dropdown
        $locations = [
            ['id' => 1, 'name' => 'Main Branch'],
            ['id' => 2, 'name' => 'Downtown Branch'],
            // TODO: Replace with actual location service
        ];

        // Get available tables for location
        $tables = [
            ['id' => 1, 'number' => 1, 'available' => true],
            ['id' => 2, 'number' => 2, 'available' => true],
            ['id' => 3, 'number' => 3, 'available' => false],
            ['id' => 4, 'number' => 4, 'available' => true],
            // TODO: Replace with actual table service
        ];

        // Get active items from the repository
        $activeItems = $this->itemRepository->getActiveItems();

        // Transform items for the frontend
        $items = $activeItems->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->basePrice,
                'category' => $item->categoryName ?? 'Uncategorized',
                'description' => $item->description,
                'isAvailable' => $item->isAvailable,
                'modifiers' => [], // TODO: Add modifiers when needed
            ];
        })->toArray();

        return Inertia::render('order/create', [
            'locations' => $locations,
            'tables' => $tables,
            'items' => $items,
        ]);
    }

    /**
     * Show the new improved form for creating orders (V2)
     */
    public function createV2(): Response
    {
        // For the new version, we'll use our search-based approach
        // No need to load all items upfront
        return Inertia::render('order/create-v2', [
            // We can add any initial data needed here
            'popularItems' => [], // Will be loaded via API
            'recentOrders' => [], // Could show recent orders by this user
        ]);
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            // Create order data from request with validation
            $payload = array_merge($request->all(), [
                'userId' => $request->user()?->id,
            ]);
            $data = CreateOrderData::validateAndCreate($payload);
            $order = $this->orderService->createOrder($data);

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
        // The order is already loaded via route model binding
        $orderWithRelations = $this->orderService->getOrderWithRelations($order->id);

        if (!$orderWithRelations) {
            abort(404, 'Order not found');
        }

        // Extract the order data and other relations
        $orderData = $orderWithRelations->order;

        return Inertia::render('order/show', [
            'order' => $orderData->toArray(),
            'user' => $orderWithRelations->user,
            'location' => $orderWithRelations->location,
            'payments' => $orderWithRelations->payments ?? [],
            'offers' => $orderWithRelations->offers ?? [],
            'isPaid' => $orderWithRelations->isPaid(),
            'remainingAmount' => $orderWithRelations->getRemainingAmount(),
            'statusHistory' => [], // TODO: Implement status history
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

        if (!$orderWithRelations->order->canBeModified()) {
            return redirect()
                ->route('orders.show', $order->id)
                ->with('error', 'Order cannot be modified');
        }

        return Inertia::render('order/edit', [
            'order' => $orderWithRelations,
        ]);
    }

    /**
     * Update the specified order
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        try {
            $data = UpdateOrderData::validateAndCreate($request->all());
            $updatedOrder = $this->orderService->updateOrder($order->id, $data);

            return redirect()
                ->route('orders.show', $updatedOrder->id)
                ->with('success', 'Order updated successfully');
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
        $result = $this->statusService->transitionStatus(
            $order,
            'placed',
            $request->input('reason')
        );

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['error']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Confirm the order
     */
    public function confirm(Request $request, Order $order): RedirectResponse
    {
        $result = $this->statusService->transitionStatus(
            $order,
            'confirmed',
            $request->input('reason')
        );

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['error']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Start preparing the order
     */
    public function startPreparing(Request $request, Order $order): RedirectResponse
    {
        $result = $this->statusService->transitionStatus(
            $order,
            'preparing',
            $request->input('reason')
        );

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['error']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Mark order as ready
     */
    public function markReady(Request $request, Order $order): RedirectResponse
    {
        $result = $this->statusService->transitionStatus(
            $order,
            'ready',
            $request->input('reason')
        );

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['error']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Complete the order
     */
    public function complete(Request $request, Order $order): RedirectResponse
    {
        $result = $this->statusService->transitionStatus(
            $order,
            'completed',
            $request->input('reason')
        );

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['error']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Start delivery
     */
    public function startDelivery(Request $request, Order $order): RedirectResponse
    {
        $result = $this->statusService->transitionStatus(
            $order,
            'delivering',
            $request->input('reason')
        );

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['error']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Mark as delivered
     */
    public function markDelivered(Request $request, Order $order): RedirectResponse
    {
        $result = $this->statusService->transitionStatus(
            $order,
            'delivered',
            $request->input('reason')
        );

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['error']);
        }

        return redirect()->back()->with('success', $result['message']);
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
            $validated = $this->validateWith($request, [
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
        $locationId = $user ? ($user->location_id ?? 1) : 1;
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
            ->with(['items', 'waiter'])
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

        // Get locations
        $locations = [
            ['id' => 1, 'name' => 'Main Branch'],
            ['id' => 2, 'name' => 'Downtown Branch'],
        ];

        return Inertia::render('order/operations', [
            'orders' => $orders,
            'locations' => $locations,
            'stats' => $stats,
        ]);
    }

    /**
     * Display payment page
     */
    public function payment(int $id): Response|RedirectResponse
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
    public function processPayment(Request $request, int $id): RedirectResponse
    {
        $order = Order::find($id);

        if (!$order) {
            abort(404, 'Order not found');
        }

        $validated = $this->validateWith($request, [
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
}
