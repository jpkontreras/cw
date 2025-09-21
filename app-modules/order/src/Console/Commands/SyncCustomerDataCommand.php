<?php

declare(strict_types=1);

namespace Colame\Order\Console\Commands;

use Illuminate\Console\Command;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;
use Colame\Order\Events\CustomerInfoEntered;
use Colame\Order\Projectors\OrderProjector;

class SyncCustomerDataCommand extends Command
{
    protected $signature = 'order:sync-customer-data';
    protected $description = 'Sync customer data from CustomerInfoEntered events to Order models';

    public function handle(): int
    {
        $this->info('Syncing customer data from events to orders...');

        $projector = new OrderProjector();

        // Get all CustomerInfoEntered events
        $events = EloquentStoredEvent::where('event_class', CustomerInfoEntered::class)->get();

        $this->info("Found {$events->count()} CustomerInfoEntered events");

        $bar = $this->output->createProgressBar($events->count());
        $bar->start();

        foreach ($events as $storedEvent) {
            // Convert stored event to actual event
            $event = $storedEvent->toStoredEvent()->event;

            // Process the event through the projector
            if ($event instanceof CustomerInfoEntered) {
                $projector->onCustomerInfoEntered($event);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Customer data sync completed!');

        return Command::SUCCESS;
    }
}