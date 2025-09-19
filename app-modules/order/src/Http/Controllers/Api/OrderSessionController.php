<?php

declare(strict_types=1);

namespace Colame\Order\Http\Controllers\Api;

use Colame\Order\Services\OrderSessionService;
use Colame\Order\Data\CreateOrderSessionData;
use Colame\Order\Data\AddItemToSessionData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderSessionController
{
    public function __construct(
        private OrderSessionService $sessionService
    ) {}

    public function start(Request $request): JsonResponse
    {
        $data = CreateOrderSessionData::validateAndCreate($request);
        $session = $this->sessionService->startSession($data);

        return response()->json([
            'success' => true,
            'data' => [
                'session_uuid' => $session->uuid,
                'status' => $session->status,
            ]
        ]);
    }

    public function sync(Request $request, string $uuid): JsonResponse
    {
        $items = $request->input('items', []);
        $customerInfo = $request->input('customer_info', []);

        $session = $this->sessionService->syncSession($uuid, $items, $customerInfo);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session_uuid' => $session->uuid,
                'status' => $session->status,
                'synced_at' => now()->toIso8601String()
            ]
        ]);
    }

    public function addItem(Request $request, string $uuid): JsonResponse
    {
        $data = AddItemToSessionData::validateAndCreate($request);
        $session = $this->sessionService->addItemToSession($uuid, $data);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session_uuid' => $session->uuid,
                'items' => $session->items,
            ]
        ]);
    }

    public function removeItem(string $uuid, int $itemIndex): JsonResponse
    {
        $session = $this->sessionService->removeItemFromSession($uuid, $itemIndex);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session_uuid' => $session->uuid,
                'items' => $session->items,
            ]
        ]);
    }

    public function checkout(Request $request, string $uuid): JsonResponse
    {
        $items = $request->input('items', []);
        $customerInfo = $request->input('customer_info', []);

        $order = $this->sessionService->checkoutSession($uuid, $items, $customerInfo);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found or checkout failed'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->id,
                'order_uuid' => $order->uuid,
                'order_number' => $order->orderNumber,
                'status' => $order->status,
            ]
        ]);
    }
}