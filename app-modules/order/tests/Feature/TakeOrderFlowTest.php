<?php

namespace Colame\Order\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\EventSourcing\Facades\Projectionist;
use Colame\Order\Aggregates\OrderAggregate;
use Colame\Order\Events\OrderStarted;
use Colame\Order\Events\ItemsAddedToOrder;
use Colame\Order\Events\ItemsValidated;
use Colame\Order\Events\PromotionsCalculated;
use Colame\Order\Events\OrderConfirmed;
use Colame\Order\Models\Order;
use Colame\Staff\Models\Staff;
use Colame\Location\Models\Location;
use Colame\Item\Models\Item;
use Colame\Offer\Models\Promotion;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class TakeOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Register projectors
        Projectionist::addProjector(\Colame\Order\Projectors\OrderProjector::class);
        Projectionist::addProjector(\Colame\Item\Projectors\ItemValidationProjector::class);
        Projectionist::addProjector(\Colame\Offer\Projectors\PromotionCalculatorProjector::class);
        
        // Register process manager as reactor
        Projectionist::addReactor(\Colame\Order\ProcessManagers\TakeOrderProcessManager::class);
    }

    /** @test */
    public function it_completes_full_order_flow_with_promotions()
    {
        // Arrange
        $staff = Staff::factory()->create();
        $location = Location::factory()->create();
        $items = Item::factory()->count(3)->create([
            'location_id' => $location->id,
            'price' => 10000, // CLP 10,000
            'is_available' => true,
        ]);
        
        // Create a promotion
        $promotion = Promotion::factory()->create([
            'name' => '10% Weekend Discount',
            'type' => 'percentage',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'auto_apply' => false,
            'is_active' => true,
        ]);

        // Act - Step 1: Start Order
        $response = $this->postJson('/api/orders/flow/start', [
            'staffId' => $staff->id,
            'locationId' => $location->id,
            'tableNumber' => 'A1',
        ]);

        $response->assertSuccessful();
        $orderUuid = $response->json('data.order_uuid');
        $processId = $response->json('data.process_id');

        $this->assertNotNull($orderUuid);
        $this->assertEquals('add_items', $response->json('data.next_step'));

        // Act - Step 2: Add Items
        $itemsToAdd = [
            ['item_id' => $items[0]->id, 'quantity' => 2],
            ['item_id' => $items[1]->id, 'quantity' => 1],
            ['item_id' => $items[2]->id, 'quantity' => 3],
        ];

        $response = $this->postJson("/api/orders/flow/{$orderUuid}/items", [
            'items' => $itemsToAdd,
            'process_id' => $processId,
        ]);

        $response->assertSuccessful();
        
        // Should have validated items and calculated promotions
        $this->assertEquals('items_validated', $response->json('data.status'));
        $this->assertEquals(60000, $response->json('data.subtotal')); // 2*10000 + 1*10000 + 3*10000
        $this->assertNotEmpty($response->json('data.promotions.available'));

        // Act - Step 3: Apply Promotion
        $response = $this->postJson("/api/orders/flow/{$orderUuid}/promotion", [
            'promotion_id' => $promotion->id,
            'action' => 'apply',
            'process_id' => $processId,
        ]);

        $response->assertSuccessful();
        $this->assertEquals(6000, $response->json('data.discount')); // 10% of 60000

        // Act - Step 4: Add Tip
        $response = $this->postJson("/api/orders/flow/{$orderUuid}/tip", [
            'tip_amount' => 3000,
        ]);

        $response->assertSuccessful();
        $this->assertEquals(3000, $response->json('data.tip'));

        // Act - Step 5: Confirm Order
        $response = $this->postJson("/api/orders/flow/{$orderUuid}/confirm", [
            'payment_method' => 'card',
            'process_id' => $processId,
        ]);

        $response->assertSuccessful();
        $this->assertEquals('confirmed', $response->json('data.status'));
        $this->assertNotNull($response->json('data.order_number'));
        $this->assertArrayHasKey('print_data', $response->json('data'));
        $this->assertTrue($response->json('data.kitchen_notified'));

        // Assert - Check database
        $order = Order::find($orderUuid);
        $this->assertNotNull($order);
        $this->assertEquals('confirmed', $order->status);
        $this->assertEquals(60000, $order->subtotal);
        $this->assertEquals(6000, $order->discount);
        $this->assertEquals(3000, $order->tip);
        $this->assertEquals('card', $order->payment_method);
    }

    /** @test */
    public function it_handles_item_validation_failures()
    {
        // Arrange
        $staff = Staff::factory()->create();
        $location = Location::factory()->create();
        $unavailableItem = Item::factory()->create([
            'location_id' => $location->id,
            'is_available' => false,
        ]);

        // Start order
        $response = $this->postJson('/api/orders/flow/start', [
            'staffId' => $staff->id,
            'locationId' => $location->id,
            'tableNumber' => 'B2',
        ]);

        $orderUuid = $response->json('data.order_uuid');
        $processId = $response->json('data.process_id');

        // Try to add unavailable item
        $response = $this->postJson("/api/orders/flow/{$orderUuid}/items", [
            'items' => [
                ['item_id' => $unavailableItem->id, 'quantity' => 1],
            ],
            'process_id' => $processId,
        ]);

        $response->assertStatus(400);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /** @test */
    public function it_handles_concurrent_order_modifications()
    {
        // This tests that event sourcing handles concurrent modifications correctly
        $staff = Staff::factory()->create();
        $location = Location::factory()->create();
        $item = Item::factory()->create([
            'location_id' => $location->id,
            'price' => 5000,
            'is_available' => true,
        ]);

        $orderUuid = Str::uuid()->toString();

        // Simulate concurrent operations
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        // Operation 1: Start order and add items
        $aggregate->startOrder($staff->id, $location->id, 'C3')
                 ->addItems([['item_id' => $item->id, 'quantity' => 2]]);

        // Operation 2: Another process tries to add more items
        $aggregate2 = OrderAggregate::retrieve($orderUuid);
        $aggregate2->addItems([['item_id' => $item->id, 'quantity' => 1]]);

        // Both persist
        $aggregate->persist();
        $aggregate2->persist();

        // Check that both operations are recorded
        $events = \Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent::query()
            ->where('aggregate_uuid', $orderUuid)
            ->get();

        $this->assertCount(3, $events); // OrderStarted + 2x ItemsAddedToOrder
    }

    /** @test */
    public function it_can_replay_order_from_events()
    {
        // Arrange
        $staff = Staff::factory()->create();
        $location = Location::factory()->create();
        $orderUuid = Str::uuid()->toString();

        // Create order with events
        $aggregate = OrderAggregate::retrieve($orderUuid)
            ->startOrder($staff->id, $location->id, 'D4')
            ->persist();

        // Clear projections
        Order::find($orderUuid)?->delete();

        // Replay events
        Projectionist::replay(
            \Colame\Order\Projectors\OrderProjector::class,
            $orderUuid
        );

        // Assert order is recreated
        $order = Order::find($orderUuid);
        $this->assertNotNull($order);
        $this->assertEquals('started', $order->status);
        $this->assertEquals($staff->id, $order->staff_id);
    }

    /** @test */
    public function it_handles_order_cancellation()
    {
        // Arrange
        $staff = Staff::factory()->create();
        $location = Location::factory()->create();

        // Start order
        $response = $this->postJson('/api/orders/flow/start', [
            'staffId' => $staff->id,
            'locationId' => $location->id,
        ]);

        $orderUuid = $response->json('data.order_uuid');

        // Cancel order
        $response = $this->postJson("/api/orders/flow/{$orderUuid}/cancel", [
            'reason' => 'Customer left',
        ]);

        $response->assertSuccessful();

        // Check order is cancelled
        $order = Order::find($orderUuid);
        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('Customer left', $order->cancellation_reason);
    }

    /** @test */
    public function it_handles_offline_sync_scenario()
    {
        // Simulate mobile device creating events offline then syncing
        $staff = Staff::factory()->create();
        $location = Location::factory()->create();
        $item = Item::factory()->create([
            'location_id' => $location->id,
            'price' => 8000,
            'is_available' => true,
        ]);

        $orderUuid = Str::uuid()->toString();
        
        // Events created offline (timestamps in the past)
        $offlineEvents = [
            new OrderStarted($orderUuid, $staff->id, $location->id, 'E5', []),
            new ItemsAddedToOrder($orderUuid, [
                ['item_id' => $item->id, 'quantity' => 2]
            ], now()->subMinutes(5)),
        ];

        // Sync events when back online
        foreach ($offlineEvents as $event) {
            event($event);
        }

        // Process should handle out-of-order events correctly
        $order = Order::find($orderUuid);
        $this->assertNotNull($order);
        $this->assertEquals('items_added', $order->status);
    }

    /** @test */
    public function it_ensures_idempotency_of_operations()
    {
        // Same operation executed multiple times should have same result
        $staff = Staff::factory()->create();
        $location = Location::factory()->create();

        // Start order
        $response1 = $this->postJson('/api/orders/flow/start', [
            'staffId' => $staff->id,
            'locationId' => $location->id,
            'tableNumber' => 'F6',
        ]);

        $orderUuid = $response1->json('data.order_uuid');

        // Try to start same order again (should handle gracefully)
        $aggregate = OrderAggregate::retrieve($orderUuid);
        
        try {
            $aggregate->startOrder($staff->id, $location->id, 'F6');
            $this->fail('Should not allow starting order twice');
        } catch (\Colame\Order\Exceptions\InvalidOrderStateException $e) {
            $this->assertStringContainsString('Cannot start order', $e->getMessage());
        }
    }
}