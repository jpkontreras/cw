<?php

declare(strict_types=1);

namespace Colame\Order\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\Order\Events\OrderEvents\OrderStatusTransitioned;
use Colame\Order\Models\OrderStatusHistory;

class OrderStatusHistoryProjector extends Projector
{
    /**
     * Handle order status transition
     */
    public function onOrderStatusTransitioned(OrderStatusTransitioned $event): void
    {
        OrderStatusHistory::create([
            'order_id' => $event->orderId,
            'from_status' => $event->fromStatus,
            'to_status' => $event->toStatus,
            'user_id' => $event->userId,
            'reason' => $event->reason,
            'metadata' => $event->metadata ?? [],
        ]);
    }
}