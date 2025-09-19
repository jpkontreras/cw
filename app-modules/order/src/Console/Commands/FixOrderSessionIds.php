<?php

declare(strict_types=1);

namespace Colame\Order\Console\Commands;

use Illuminate\Console\Command;
use Colame\Order\Models\Order;
use Colame\Order\Models\OrderSession;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class FixOrderSessionIds extends Command
{
    protected $signature = 'orders:fix-session-ids';
    protected $description = 'Fix missing session_id values in orders by matching them with sessions';

    public function handle(): void
    {
        $this->info('Fixing missing session_id values in orders...');
        
        // Get all orders without session_id
        $ordersWithoutSession = Order::whereNull('session_id')->get();
        
        $this->info("Found {$ordersWithoutSession->count()} orders without session_id");
        
        foreach ($ordersWithoutSession as $order) {
            // Try to find session by order_id
            $session = OrderSession::where('order_id', $order->id)->first();
            
            if ($session) {
                $order->update(['session_id' => $session->id]);
                $this->line("✓ Updated order {$order->order_number} with session {$session->id}");
            } else {
                // Try to find through events
                $event = EloquentStoredEvent::where('event_class', 'LIKE', '%OrderStarted%')
                    ->where('event_properties->orderId', $order->id)
                    ->first();
                    
                if ($event) {
                    // The aggregate_uuid is the session_id
                    $order->update(['session_id' => $event->aggregate_uuid]);
                    $this->line("✓ Updated order {$order->order_number} with session {$event->aggregate_uuid} from events");
                } else {
                    $this->warn("✗ Could not find session for order {$order->order_number}");
                }
            }
        }
        
        $this->info('Done!');
    }
}