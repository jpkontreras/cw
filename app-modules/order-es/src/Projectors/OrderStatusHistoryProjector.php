<?php

declare(strict_types=1);

namespace Colame\OrderEs\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\OrderEs\Events\OrderEvents\OrderStatusTransitioned;
use Colame\OrderEs\Models\OrderStatusHistory;

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