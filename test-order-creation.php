<?php

use Colame\Order\Data\CreateOrderData;
use Colame\Order\Data\CreateOrderItemData;
use Colame\Order\Services\OrderService;
use Spatie\LaravelData\DataCollection;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Create test order data
$orderData = new CreateOrderData(
    locationId: 1,
    type: 'dineIn',
    items: new DataCollection(CreateOrderItemData::class, [
        new CreateOrderItemData(
            itemId: 1,
            quantity: 2,
            unitPrice: 5000, // $50 in minor units
            notes: 'No onions'
        ),
        new CreateOrderItemData(
            itemId: 2,
            quantity: 1,
            unitPrice: 3500, // $35 in minor units
        )
    ]),
    userId: 1,
    tableNumber: 5,
    customerName: 'Test Customer'
);

try {
    $orderService = app(OrderService::class);
    $order = $orderService->createOrder($orderData);
    
    echo "✅ Order created successfully!\n";
    echo "Order ID: {$order->id}\n";
    echo "Order UUID: {$order->uuid}\n";
    echo "Status: {$order->status}\n";
    
    // Check if items were created correctly
    $orderModel = \Colame\Order\Models\Order::find($order->id);
    $items = $orderModel->items;
    
    echo "\nOrder Items:\n";
    foreach ($items as $item) {
        echo "- Item ID: {$item->item_id}, ";
        echo "Quantity: {$item->quantity}, ";
        echo "Base Price: {$item->base_price}, ";
        echo "Unit Price: {$item->unit_price}, ";
        echo "Total: {$item->total_price}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating order: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}