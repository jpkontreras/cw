<?php

declare(strict_types=1);

namespace Colame\OrderEs\Projectors;

use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Colame\OrderEs\Events\SessionEvents\SessionInitiated;
use Colame\OrderEs\Events\SessionEvents\SessionUpdated;
use Colame\OrderEs\Events\SessionEvents\SessionClosed;
use Colame\OrderEs\Events\SessionConverted;
use Colame\OrderEs\Models\OrderSession;

class OrderSessionProjector extends Projector
{
    /**
     * Handle session initiated
     */
    public function onSessionInitiated(SessionInitiated $event): void
    {
        OrderSession::create([
            'id' => $event->sessionId,
            'staff_id' => $event->staffId,
            'location_id' => $event->locationId,
            'table_number' => $event->tableNumber,
            'customer_count' => $event->customerCount ?? 1,
            'status' => 'active',
            'type' => $event->type ?? 'dine_in',
            'metadata' => $event->metadata ?? [],
            'started_at' => now(),
        ]);
    }

    /**
     * Handle session updated
     */
    public function onSessionUpdated(SessionUpdated $event): void
    {
        $session = OrderSession::find($event->sessionId);
        if ($session) {
            $session->update($event->updates);
        }
    }

    /**
     * Handle order conversion
     */
    public function onSessionConverted(SessionConverted $event): void
    {
        $session = OrderSession::find($event->sessionId);
        if ($session) {
            $session->update([
                'order_id' => $event->orderId,
                'status' => 'converted',
                'converted_at' => now(),
            ]);
        }
        
        // Also update the Order with the session_id
        $order = \Colame\OrderEs\Models\Order::find($event->orderId);
        if ($order) {
            $order->update([
                'session_id' => $event->sessionId,
            ]);
        }
    }

    /**
     * Handle session closed
     */
    public function onSessionClosed(SessionClosed $event): void
    {
        $session = OrderSession::find($event->sessionId);
        if ($session) {
            $session->update([
                'status' => 'closed',
                'closed_reason' => $event->reason,
                'closed_at' => now(),
            ]);
        }
    }
}