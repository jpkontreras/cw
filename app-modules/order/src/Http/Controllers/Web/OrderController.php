<?php

declare(strict_types=1);

namespace Colame\Order\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Order\Contracts\OrderServiceInterface;
use Colame\Order\Data\CreateOrderData;
use Colame\Order\Data\OrderData;
use Colame\Order\Data\UpdateOrderData;
use Colame\Order\Exceptions\OrderException;
use Colame\Order\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Web controller for orders (Inertia responses)
 */
class OrderController extends Controller
{
    public function __construct(
        private OrderServiceInterface $orderService
    ) {}

    /**
     * Display a listing of orders
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['status', 'type', 'location_id', 'date', 'search']);
        
        // Get paginated orders with filters
        $orders = $this->orderService->getPaginatedOrders($filters, 20);
        
        // Get locations for filter dropdown
        $locations = [
            ['id' => 1, 'name' => 'Main Branch'],
            ['id' => 2, 'name' => 'Downtown Branch'],
            // TODO: Replace with actual location service
        ];
        
        // Get available statuses and types
        $statuses = ['draft', 'placed', 'confirmed', 'preparing', 'ready', 'delivering', 'delivered', 'completed', 'cancelled', 'refunded'];
        $types = ['dine_in', 'takeout', 'delivery', 'catering'];
        
        // Get stats for the dashboard cards
        $stats = $this->orderService->getOrderStats($filters);

        return Inertia::render('order/index', [
            'orders' => $orders,
            'locations' => $locations,
            'filters' => $filters,
            'statuses' => $statuses,
            'types' => $types,
            'stats' => $stats,
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
        
        // Get menu items
        $items = [
            // Starters
            ['id' => 1, 'name' => 'Empanada de Pino', 'price' => 2500, 'category' => 'Starters', 'modifiers' => [
                ['id' => 1, 'name' => 'Extra Cheese', 'price' => 500],
                ['id' => 2, 'name' => 'Spicy Sauce', 'price' => 0],
            ]],
            ['id' => 2, 'name' => 'Sopaipillas (4)', 'price' => 1500, 'category' => 'Starters', 'modifiers' => []],
            
            // Main Courses
            ['id' => 3, 'name' => 'Completo Italiano', 'price' => 3500, 'category' => 'Main Courses', 'modifiers' => [
                ['id' => 3, 'name' => 'Double Meat', 'price' => 1000],
                ['id' => 4, 'name' => 'No Mayo', 'price' => 0],
            ]],
            ['id' => 4, 'name' => 'Churrasco', 'price' => 5500, 'category' => 'Main Courses', 'modifiers' => []],
            ['id' => 5, 'name' => 'Pastel de Choclo', 'price' => 4500, 'category' => 'Main Courses', 'modifiers' => []],
            
            // Beverages
            ['id' => 6, 'name' => 'Coca Cola 350ml', 'price' => 1500, 'category' => 'Beverages', 'modifiers' => []],
            ['id' => 7, 'name' => 'Jugo Natural', 'price' => 2000, 'category' => 'Beverages', 'modifiers' => []],
            ['id' => 8, 'name' => 'Pisco Sour', 'price' => 3500, 'category' => 'Beverages', 'modifiers' => []],
            
            // Desserts
            ['id' => 9, 'name' => 'Leche Asada', 'price' => 2000, 'category' => 'Desserts', 'modifiers' => []],
            ['id' => 10, 'name' => 'Mote con Huesillo', 'price' => 1800, 'category' => 'Desserts', 'modifiers' => []],
            // TODO: Replace with actual item service
        ];
        
        return Inertia::render('order/create', [
            'locations' => $locations,
            'tables' => $tables,
            'items' => $items,
        ]);
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            // Convert camelCase to snake_case for Laravel data
            $requestData = $request->all();
            
            // Map frontend field names to backend field names
            $mappedData = [
                'user_id' => $request->user()?->id,
                'location_id' => $requestData['location_id'] ?? null,
                'type' => $requestData['type'] ?? 'dine_in',
                'table_number' => $requestData['table_number'] ?? null,
                'customer_name' => $requestData['customer_name'] ?? null,
                'customer_phone' => $requestData['customer_phone'] ?? null,
                'customer_email' => $requestData['customer_email'] ?? null,
                'delivery_address' => $requestData['delivery_address'] ?? null,
                'notes' => $requestData['notes'] ?? null,
                'special_instructions' => $requestData['special_instructions'] ?? null,
                'items' => array_map(function($item) {
                    return [
                        'item_id' => $item['item_id'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'unit_price' => 0, // Will be set by service
                        'notes' => $item['notes'] ?? null,
                        'modifiers' => $item['modifiers'] ?? [],
                    ];
                }, $requestData['items'] ?? []),
            ];
            
            $data = CreateOrderData::from($mappedData);
            $order = $this->orderService->createOrder($data);

            return redirect()
                ->route('orders.show', $order->id)
                ->with('success', 'Order created successfully');
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
    public function show(int $id): Response
    {
        $orderWithRelations = $this->orderService->getOrderWithRelations($id);

        if (!$orderWithRelations) {
            abort(404, 'Order not found');
        }

        return Inertia::render('order/show', [
            'order' => $orderWithRelations,
        ]);
    }

    /**
     * Show the form for editing the order
     */
    public function edit(int $id): Response|RedirectResponse
    {
        $orderWithRelations = $this->orderService->getOrderWithRelations($id);

        if (!$orderWithRelations) {
            abort(404, 'Order not found');
        }

        if (!$orderWithRelations->order->canBeModified()) {
            return redirect()
                ->route('orders.show', $id)
                ->with('error', 'Order cannot be modified');
        }

        return Inertia::render('order/edit', [
            'order' => $orderWithRelations,
        ]);
    }

    /**
     * Update the specified order
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        try {
            $data = UpdateOrderData::from($request->all());
            $order = $this->orderService->updateOrder($id, $data);

            return redirect()
                ->route('orders.show', $order->id)
                ->with('success', 'Order updated successfully');
        } catch (OrderException $e) {
            return redirect()
                ->back()
                ->withErrors(['order' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Confirm the order
     */
    public function confirm(int $id): RedirectResponse
    {
        try {
            $this->orderService->confirmOrder($id);

            return redirect()
                ->back()
                ->with('success', 'Order confirmed');
        } catch (OrderException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Start preparing the order
     */
    public function startPreparing(int $id): RedirectResponse
    {
        try {
            $this->orderService->startPreparingOrder($id);

            return redirect()
                ->back()
                ->with('success', 'Order preparation started');
        } catch (OrderException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark order as ready
     */
    public function markReady(int $id): RedirectResponse
    {
        try {
            $this->orderService->markOrderReady($id);

            return redirect()
                ->back()
                ->with('success', 'Order marked as ready');
        } catch (OrderException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Complete the order
     */
    public function complete(int $id): RedirectResponse
    {
        try {
            $this->orderService->completeOrder($id);

            return redirect()
                ->back()
                ->with('success', 'Order completed');
        } catch (OrderException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Start delivery
     */
    public function startDelivery(int $id): RedirectResponse
    {
        try {
            $order = Order::find($id);
            if (!$order) {
                abort(404, 'Order not found');
            }

            if ($order->status !== 'ready' || $order->type !== 'delivery') {
                return redirect()
                    ->back()
                    ->with('error', 'Order must be ready for delivery');
            }

            $order->update([
                'status' => 'delivering',
                'delivering_at' => now()
            ]);

            return redirect()
                ->back()
                ->with('success', 'Delivery started');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to start delivery: ' . $e->getMessage());
        }
    }

    /**
     * Mark as delivered
     */
    public function markDelivered(int $id): RedirectResponse
    {
        try {
            $order = Order::find($id);
            if (!$order) {
                abort(404, 'Order not found');
            }

            if ($order->status !== 'delivering') {
                return redirect()
                    ->back()
                    ->with('error', 'Order must be in delivery');
            }

            $order->update([
                'status' => 'delivered',
                'delivered_at' => now()
            ]);

            return redirect()
                ->back()
                ->with('success', 'Order marked as delivered');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to mark as delivered: ' . $e->getMessage());
        }
    }

    /**
     * Display receipt
     */
    public function receipt(int $id): Response
    {
        $order = Order::with(['items', 'payments'])->find($id);

        if (!$order) {
            abort(404, 'Order not found');
        }

        // TODO: Implement receipt view
        return Inertia::render('order/receipt', [
            'order' => OrderData::from($order),
        ]);
    }

    /**
     * Show cancel order form
     */
    public function showCancelForm(int $id): Response
    {
        $order = $this->orderService->getOrderWithRelations($id);

        if (!$order || !$order->order->canBeCancelled()) {
            abort(403, 'Order cannot be cancelled');
        }

        return Inertia::render('order/cancel', [
            'order' => $order,
        ]);
    }

    /**
     * Cancel the order
     */
    public function cancel(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->orderService->cancelOrder($id, $request->input('reason'));

            return redirect()
                ->route('orders.show', $id)
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

        return Inertia::render('order/kitchen', [
            'orders' => $orders,
            'locationId' => $locationId,
        ]);
    }

    /**
     * Display order dashboard
     */
    public function dashboard(Request $request): Response
    {
        $filters = $request->only(['period', 'location_id']);
        $dashboardData = $this->orderService->getDashboardData($filters);

        return Inertia::render('order/dashboard', $dashboardData);
    }

    /**
     * Display operations center
     */
    public function operations(Request $request): Response
    {
        $locationId = $request->input('location_id');
        
        // Get active orders
        $orders = Order::query()
            ->with(['items', 'waiter'])
            ->whereNotIn('status', ['completed', 'cancelled', 'refunded'])
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($order) => OrderData::from($order));

        // Calculate stats
        $stats = [
            'active' => $orders->count(),
            'preparing' => $orders->where('status', 'preparing')->count(),
            'ready' => $orders->where('status', 'ready')->count(),
            'avgWaitTime' => 25, // Mock data - would calculate from actual times
        ];

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
    public function payment(int $id): Response
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
            'order' => OrderData::from($order),
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
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount'],
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