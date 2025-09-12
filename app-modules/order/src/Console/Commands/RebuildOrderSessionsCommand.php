<?php

declare(strict_types=1);

namespace Colame\Order\Console\Commands;

use Illuminate\Console\Command;
use Colame\Order\Models\OrderSession;
use Spatie\EventSourcing\Facades\Projectionist;

class RebuildOrderSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:rebuild-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild order session projections from events';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Clearing existing order session projections...');
        OrderSession::truncate();
        
        $this->info('Replaying events to rebuild projections...');
        // Use the artisan command to replay events for the specific projector
        $this->call('event-sourcing:replay', [
            'projector' => [\Colame\Order\Projectors\OrderSessionProjector::class],
            '--force' => true,
        ]);
        
        $sessionCount = OrderSession::count();
        $this->info("Order sessions rebuilt successfully! Total sessions: {$sessionCount}");
    }
}