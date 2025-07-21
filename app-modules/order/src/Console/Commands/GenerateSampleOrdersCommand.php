<?php

declare(strict_types=1);

namespace Colame\Order\Console\Commands;

use Colame\Order\Models\Order;
use Colame\Order\Models\OrderItem;
use Illuminate\Console\Command;

class GenerateSampleOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:generate-samples 
                            {count=20 : Number of orders to generate}
                            {--with-items : Generate items for each order}
                            {--yesterday=8 : Number of orders from yesterday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sample orders for testing and development';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $yesterdayCount = (int) $this->option('yesterday');
        $withItems = $this->option('with-items');
        
        $this->info("Generating {$count} sample orders...");
        
        $orders = [];
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        // Generate orders with various statuses
        $statusDistribution = [
            'placed' => (int) ($count * 0.15),
            'confirmed' => (int) ($count * 0.15),
            'preparing' => (int) ($count * 0.25),
            'ready' => (int) ($count * 0.15),
            'completed' => (int) ($count * 0.30),
        ];
        
        foreach ($statusDistribution as $status => $statusCount) {
            for ($i = 0; $i < $statusCount; $i++) {
                $order = Order::factory()->{$status}()->create();
                $orders[] = $order;
                $bar->advance();
            }
        }
        
        // Generate orders from yesterday
        if ($yesterdayCount > 0) {
            $this->info("\n\nGenerating {$yesterdayCount} orders from yesterday...");
            
            for ($i = 0; $i < $yesterdayCount; $i++) {
                $order = Order::factory()->completed()->create();
                $order->update([
                    'created_at' => now()->subDay(),
                    'placed_at' => now()->subDay()->subHours(rand(1, 8)),
                ]);
                $orders[] = $order;
            }
        }
        
        // Generate items for each order if requested
        if ($withItems) {
            $this->info("\n\nGenerating items for orders...");
            $itemBar = $this->output->createProgressBar(count($orders));
            $itemBar->start();
            
            foreach ($orders as $order) {
                $itemCount = rand(1, 5);
                $orderTotal = 0;
                
                for ($j = 0; $j < $itemCount; $j++) {
                    $item = OrderItem::factory()->create([
                        'order_id' => $order->id,
                    ]);
                    
                    // Set item status based on order status
                    switch ($order->status) {
                        case 'placed':
                        case 'confirmed':
                            $item->update([
                                'status' => OrderItem::STATUS_PENDING,
                                'kitchen_status' => OrderItem::KITCHEN_STATUS_PENDING,
                            ]);
                            break;
                        case 'preparing':
                            $item->update([
                                'status' => OrderItem::STATUS_PREPARING,
                                'kitchen_status' => OrderItem::KITCHEN_STATUS_PREPARING,
                            ]);
                            break;
                        case 'ready':
                            $item->update([
                                'status' => OrderItem::STATUS_PREPARED,
                                'kitchen_status' => OrderItem::KITCHEN_STATUS_READY,
                                'prepared_at' => now()->subMinutes(rand(5, 20)),
                            ]);
                            break;
                        case 'completed':
                            $item->update([
                                'status' => OrderItem::STATUS_SERVED,
                                'kitchen_status' => OrderItem::KITCHEN_STATUS_SERVED,
                                'prepared_at' => now()->subMinutes(rand(30, 60)),
                                'served_at' => now()->subMinutes(rand(10, 25)),
                            ]);
                            break;
                    }
                    
                    $orderTotal += $item->total_price;
                }
                
                // Update order totals based on items
                $order->update([
                    'subtotal' => $orderTotal,
                    'tax_amount' => $orderTotal * 0.19,
                    'total_amount' => $orderTotal + ($orderTotal * 0.19),
                ]);
                
                $itemBar->advance();
            }
            
            $itemBar->finish();
        }
        
        $bar->finish();
        
        $this->newLine();
        $this->info('Sample orders generated successfully!');
        $this->table(
            ['Status', 'Count'],
            collect($statusDistribution)->map(function ($count, $status) {
                return [$status, $count];
            })->toArray()
        );
        
        if ($yesterdayCount > 0) {
            $this->info("Plus {$yesterdayCount} orders from yesterday");
        }
        
        return Command::SUCCESS;
    }
}