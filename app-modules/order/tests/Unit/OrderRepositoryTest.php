<?php

declare(strict_types=1);

namespace Colame\Order\Tests\Unit;

use Colame\Order\Data\OrderData;
use Colame\Order\Models\Order;
use Colame\Order\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private OrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new OrderRepository();
    }

    public function test_can_create_order(): void
    {
        $data = [
            'user_id' => 1,
            'location_id' => 1,
            'status' => 'draft',
            'subtotal' => 100.00,
            'tax_amount' => 10.00,
            'discount_amount' => 0.00,
            'total_amount' => 110.00,
            'notes' => 'Test order',
        ];

        $orderData = $this->repository->create($data);

        $this->assertInstanceOf(OrderData::class, $orderData);
        $this->assertEquals($data['user_id'], $orderData->userId);
        $this->assertEquals($data['location_id'], $orderData->locationId);
        $this->assertEquals($data['status'], $orderData->status);
        $this->assertEquals($data['total_amount'], $orderData->totalAmount);
    }

    public function test_can_find_order_by_id(): void
    {
        $order = Order::factory()->create();

        $orderData = $this->repository->find($order->id);

        $this->assertInstanceOf(OrderData::class, $orderData);
        $this->assertEquals($order->id, $orderData->id);
    }

    public function test_returns_null_when_order_not_found(): void
    {
        $orderData = $this->repository->find(999);

        $this->assertNull($orderData);
    }

    public function test_can_update_order_status(): void
    {
        $order = Order::factory()->create(['status' => 'draft']);

        $updated = $this->repository->updateStatus($order->id, 'placed');

        $this->assertTrue($updated);
        
        $updatedOrder = Order::find($order->id);
        $this->assertEquals('placed', $updatedOrder->status);
        $this->assertNotNull($updatedOrder->placed_at);
    }

    public function test_can_get_orders_by_status(): void
    {
        Order::factory()->count(3)->create(['status' => 'placed']);
        Order::factory()->count(2)->create(['status' => 'completed']);

        $placedOrders = $this->repository->getByStatus('placed');

        $this->assertCount(3, $placedOrders);
        foreach ($placedOrders as $order) {
            $this->assertEquals('placed', $order->status);
        }
    }

    public function test_can_get_active_kitchen_orders(): void
    {
        $locationId = 1;
        
        // Create orders with different statuses
        Order::factory()->create(['location_id' => $locationId, 'status' => 'draft']);
        Order::factory()->create(['location_id' => $locationId, 'status' => 'confirmed']);
        Order::factory()->create(['location_id' => $locationId, 'status' => 'preparing']);
        Order::factory()->create(['location_id' => $locationId, 'status' => 'ready']);
        Order::factory()->create(['location_id' => $locationId, 'status' => 'completed']);
        Order::factory()->create(['location_id' => 2, 'status' => 'confirmed']); // Different location

        $kitchenOrders = $this->repository->getActiveKitchenOrders($locationId);

        $this->assertCount(3, $kitchenOrders); // Only confirmed, preparing, and ready orders
        
        $statuses = array_map(fn($order) => $order->status, $kitchenOrders);
        $this->assertContains('confirmed', $statuses);
        $this->assertContains('preparing', $statuses);
        $this->assertContains('ready', $statuses);
    }

    public function test_search_is_case_insensitive(): void
    {
        // Create orders with different case variations
        Order::factory()->create([
            'customer_name' => 'Diego Soto',
            'customer_email' => 'diego@example.com',
            'order_number' => 'ORD-20250723-6581',
        ]);
        
        Order::factory()->create([
            'customer_name' => 'JUAN PEREZ',
            'customer_email' => 'JUAN@EXAMPLE.COM',
            'order_number' => 'ORD-20250723-6582',
        ]);
        
        Order::factory()->create([
            'customer_name' => 'maria garcia',
            'customer_email' => 'maria@example.com',
            'order_number' => 'ORD-20250723-6583',
        ]);

        // Test lowercase search for mixed case name
        $results = $this->repository->paginateWithFilters(['search' => 'diego'], 10);
        $this->assertEquals(1, $results->total());
        $this->assertEquals('Diego Soto', $results->items()[0]->customer_name);

        // Test uppercase search for lowercase name
        $results = $this->repository->paginateWithFilters(['search' => 'MARIA'], 10);
        $this->assertEquals(1, $results->total());
        $this->assertEquals('maria garcia', $results->items()[0]->customer_name);

        // Test mixed case search for uppercase name
        $results = $this->repository->paginateWithFilters(['search' => 'Juan'], 10);
        $this->assertEquals(1, $results->total());
        $this->assertEquals('JUAN PEREZ', $results->items()[0]->customer_name);

        // Test partial search is also case insensitive
        $results = $this->repository->paginateWithFilters(['search' => 'example.COM'], 10);
        $this->assertEquals(3, $results->total());
    }
}