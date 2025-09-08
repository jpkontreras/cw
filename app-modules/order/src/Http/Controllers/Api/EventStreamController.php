<?php

declare(strict_types=1);

namespace Colame\Order\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Order\Services\EventStreamService;
use Colame\Order\Data\EventStreamData;
use Colame\Order\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EventStreamController extends Controller
{
    public function __construct(
        private readonly EventStreamService $eventStreamService
    ) {}
    
    /**
     * Get event stream for an order
     */
    public function getEventStream(string $orderUuid): JsonResponse
    {
        // Verify order exists
        $order = Order::findOrFail($orderUuid);
        
        // Get events
        $events = $this->eventStreamService->getOrderEventStream($orderUuid);
        $statistics = $this->eventStreamService->getEventStatistics($orderUuid);
        
        $data = EventStreamData::fromServiceData($orderUuid, $events->toArray(), $statistics);
        
        return response()->json($data->toArray());
    }
    
    /**
     * Get order state at specific timestamp
     */
    public function getStateAtTimestamp(string $orderUuid, Request $request): JsonResponse
    {
        $request->validate([
            'timestamp' => 'required|date',
        ]);
        
        // Verify order exists
        $order = Order::findOrFail($orderUuid);
        
        $timestamp = Carbon::parse($request->input('timestamp'));
        $state = $this->eventStreamService->getOrderStateAtTimestamp($orderUuid, $timestamp);
        
        return response()->json($state);
    }
    
    /**
     * Replay events between timestamps
     */
    public function replayEvents(string $orderUuid, Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date',
        ]);
        
        // Verify order exists
        $order = Order::findOrFail($orderUuid);
        
        $from = Carbon::parse($request->input('from'));
        $to = Carbon::parse($request->input('to'));
        
        $events = $this->eventStreamService->replayEventsBetween($orderUuid, $from, $to);
        
        return response()->json([
            'orderUuid' => $orderUuid,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'events' => $events->toArray(),
            'count' => $events->count(),
        ]);
    }
    
    /**
     * Get event statistics
     */
    public function getStatistics(string $orderUuid): JsonResponse
    {
        // Verify order exists
        $order = Order::findOrFail($orderUuid);
        
        $statistics = $this->eventStreamService->getEventStatistics($orderUuid);
        
        return response()->json($statistics);
    }
}