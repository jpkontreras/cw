<?php

declare(strict_types=1);

namespace Colame\Order\Http\Controllers\Api;

use Colame\Order\Services\OrderService;
use Colame\Order\Data\ChangeOrderStatusData;
use Colame\Order\Data\CreateOrderData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class OrderController
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $perPage = $request->input('per_page', 15);
        $orders = $this->orderService->getPaginatedOrders($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $orders->toArray()
        ]);
    }

    public function show(string $orderId): JsonResponse
    {
        $order = $this->orderService->findOrderByIdOrNumber($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order->toArray()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = CreateOrderData::validateAndCreate($request);
        $order = $this->orderService->createOrder($data);

        return response()->json([
            'success' => true,
            'data' => $order->toArray(),
            'message' => 'Order created successfully'
        ], 201);
    }

    public function confirm(string $orderId): JsonResponse
    {
        $order = $this->orderService->confirmOrder($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or cannot be confirmed'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->id,
                'status' => $order->status,
                'message' => 'Order confirmed successfully'
            ]
        ]);
    }

    public function cancel(Request $request, string $orderId): JsonResponse
    {
        $reason = $request->input('reason', 'Cancelled by user');
        $order = $this->orderService->cancelOrder($orderId, $reason);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or cannot be cancelled'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->id,
                'status' => $order->status,
                'message' => 'Order cancelled successfully'
            ]
        ]);
    }

    public function changeStatus(Request $request, string $orderId): JsonResponse
    {
        $data = ChangeOrderStatusData::validateAndCreate($request);
        $order = $this->orderService->changeOrderStatus($orderId, $data->status, $data->notes);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or status change failed'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->id,
                'status' => $order->status,
                'message' => 'Order status updated successfully'
            ]
        ]);
    }

    public function getStateAtTimestamp(Request $request, string $orderId): JsonResponse
    {
        $timestamp = $request->input('timestamp');

        if (!$timestamp) {
            return response()->json([
                'success' => false,
                'message' => 'Timestamp parameter is required'
            ], 400);
        }

        try {
            $carbonTimestamp = Carbon::parse($timestamp);
            $state = $this->orderService->getOrderStateAtTimestamp($orderId, $carbonTimestamp);

            if (!$state) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or no state at given timestamp'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $state
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid timestamp format'
            ], 400);
        }
    }
}