<?php

declare(strict_types=1);

namespace Colame\OrderEs\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\OrderEs\Aggregates\Order as OrderAggregate;
use Colame\OrderEs\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderSlipController extends Controller
{
    /**
     * Print order slip (triggers OrderSlipPrinted event)
     */
    public function print(string $orderId): JsonResponse
    {
        $order = Order::findOrFail($orderId);

        // Record the slip print event
        OrderAggregate::retrieve($orderId)
            ->printSlip()
            ->persist();

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'message' => 'Order slip printed successfully',
        ]);
    }

    /**
     * Scan order slip to mark as ready
     */
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => 'required|string',
        ]);

        $orderNumber = $request->input('barcode');

        // Find order by order number (which is our barcode)
        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        try {
            // Mark as ready via event
            OrderAggregate::retrieve($order->id)
                ->markReadyViaSlipScan($orderNumber)
                ->persist();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => 'ready',
                'message' => 'Order marked as ready',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get orders ready to print slips (confirmed but not printed)
     */
    public function getPrintQueue(): JsonResponse
    {
        $orders = Order::where('status', 'confirmed')
            ->where('slip_printed', false)
            ->orderBy('confirmed_at')
            ->get();

        return response()->json([
            'orders' => $orders->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'type' => $order->type,
                'items_count' => $order->items()->count(),
                'confirmed_at' => $order->confirmed_at,
            ]),
        ]);
    }
}