<?php

declare(strict_types=1);

namespace Colame\Order\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Order\Contracts\OrderServiceInterface;
use Colame\Order\Data\CreateOrderData;
use Colame\Order\Data\UpdateOrderData;
use Colame\Order\Exceptions\OrderException;
use Colame\Order\Exceptions\OrderNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * API controller for orders (JSON responses)
 */
class OrderController extends Controller
{
    public function __construct(
        private OrderServiceInterface $orderService
    ) {}

    /**
     * Display a listing of orders
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'type', 'location_id', 'date', 'search', 'sort', 'page']);
        $perPage = $request->input('per_page', 20);
        
        // Special case for kitchen display
        if ($request->input('status') === 'kitchen') {
            $locationId = $request->input('location_id', $request->user()->location_id ?? 1);
            $orders = $this->orderService->getKitchenOrders($locationId);
            
            return response()->json([
                'data' => $orders,
                'meta' => [
                    'total' => count($orders),
                    'location_id' => $locationId,
                ],
            ]);
        }
        
        // Get paginated orders with filters and metadata
        $paginatedData = $this->orderService->getPaginatedOrders($filters, $perPage);
        $responseData = $paginatedData->toArray();
        
        // Format response in JSON:API compliant structure
        return response()->json([
            'data' => $responseData['data'],
            'meta' => array_merge(
                $responseData['pagination'],
                [
                    'resource' => $responseData['metadata'],
                ]
            ),
            'links' => [
                'self' => request()->fullUrl(),
                'first' => $responseData['pagination']['first_page_url'],
                'last' => $responseData['pagination']['last_page_url'],
                'prev' => $responseData['pagination']['prev_page_url'],
                'next' => $responseData['pagination']['next_page_url'],
            ],
        ]);
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = CreateOrderData::from($request->all());
            $order = $this->orderService->createOrder($data);

            return response()->json([
                'data' => $order,
                'message' => 'Order created successfully',
            ], Response::HTTP_CREATED);
        } catch (OrderException $e) {
            return response()->json([
                'error' => $e->toArray(),
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'An unexpected error occurred',
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified order
     */
    public function show(int $id): JsonResponse
    {
        try {
            $orderWithRelations = $this->orderService->getOrderWithRelations($id);

            if (!$orderWithRelations) {
                throw new OrderNotFoundException("Order {$id} not found");
            }

            return response()->json([
                'data' => $orderWithRelations,
            ]);
        } catch (OrderNotFoundException $e) {
            return response()->json([
                'error' => $e->toArray(),
            ], $e->getStatusCode());
        }
    }

    /**
     * Update the specified order
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = UpdateOrderData::from($request->all());
            $order = $this->orderService->updateOrder($id, $data);

            return response()->json([
                'data' => $order,
                'message' => 'Order updated successfully',
            ]);
        } catch (OrderException $e) {
            return response()->json([
                'error' => $e->toArray(),
            ], $e->getStatusCode());
        }
    }

    /**
     * Remove the specified order (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        // In real implementation, would check permissions and business rules
        return response()->json([
            'message' => 'Order deletion not implemented',
        ], Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:confirmed,preparing,ready,completed'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $status = $request->input('status');
            $order = match ($status) {
                'confirmed' => $this->orderService->confirmOrder($id),
                'preparing' => $this->orderService->startPreparingOrder($id),
                'ready' => $this->orderService->markOrderReady($id),
                'completed' => $this->orderService->completeOrder($id),
            };

            return response()->json([
                'data' => $order,
                'message' => "Order status updated to {$status}",
            ]);
        } catch (OrderException $e) {
            return response()->json([
                'error' => $e->toArray(),
            ], $e->getStatusCode());
        }
    }

    /**
     * Cancel the order
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $order = $this->orderService->cancelOrder($id, $request->input('reason'));

            return response()->json([
                'data' => $order,
                'message' => 'Order cancelled successfully',
            ]);
        } catch (OrderException $e) {
            return response()->json([
                'error' => $e->toArray(),
            ], $e->getStatusCode());
        }
    }

    /**
     * Get order statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'location_id' => ['required', 'integer'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        // In real implementation, would call a statistics method
        return response()->json([
            'data' => [
                'total_orders' => 0,
                'completed_orders' => 0,
                'cancelled_orders' => 0,
                'average_order_value' => 0,
                'total_revenue' => 0,
            ],
        ]);
    }

    /**
     * Update order item status
     */
    public function updateItemStatus(Request $request, int $orderId, int $itemId): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,preparing,prepared,served,cancelled'],
        ]);

        try {
            $success = $this->orderService->updateOrderItemStatus(
                $orderId,
                $itemId,
                $request->input('status')
            );

            if (!$success) {
                return response()->json([
                    'error' => [
                        'code' => 'UPDATE_FAILED',
                        'message' => 'Failed to update item status',
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return response()->json([
                'message' => 'Item status updated successfully',
            ]);
        } catch (OrderException $e) {
            return response()->json([
                'error' => $e->toArray(),
            ], $e->getStatusCode());
        }
    }

    /**
     * Apply offers to order
     */
    public function applyOffers(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'offer_codes' => ['required', 'array', 'min:1'],
            'offer_codes.*' => ['required', 'string'],
        ]);

        try {
            $order = $this->orderService->applyOffers($id, $request->input('offer_codes'));

            return response()->json([
                'data' => $order,
                'message' => 'Offers applied successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'OFFER_APPLICATION_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}