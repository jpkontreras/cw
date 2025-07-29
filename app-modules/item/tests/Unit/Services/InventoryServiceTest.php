<?php

namespace Colame\Item\Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Colame\Item\Services\InventoryService;
use Colame\Item\Contracts\InventoryRepositoryInterface;
use Colame\Item\Contracts\ItemRepositoryInterface;
use App\Core\Contracts\FeatureFlagInterface;
use Colame\Item\Data\InventoryData;
use Colame\Item\Data\InventoryAdjustmentData;
use Colame\Item\Data\ItemData;
use Colame\Item\Exceptions\InsufficientStockException;
use Colame\Item\Exceptions\InvalidInventoryOperationException;
use Colame\Item\Exceptions\ItemNotFoundException;
use Colame\Item\Events\StockAdjusted;
use Colame\Item\Events\LowStockAlert;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private InventoryService $service;
    private $inventoryRepository;
    private $itemRepository;
    private $features;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryRepository = Mockery::mock(InventoryRepositoryInterface::class);
        $this->itemRepository = Mockery::mock(ItemRepositoryInterface::class);
        $this->features = Mockery::mock(FeatureFlagInterface::class);
        
        $this->service = new InventoryService(
            $this->inventoryRepository,
            $this->itemRepository,
            $this->features
        );
        
        Event::fake();
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function test_get_inventory_level_returns_inventory_data()
    {
        // Arrange
        $itemId = 1;
        $item = $this->createMockItem($itemId);
        $inventory = $this->createMockInventory($itemId, 100);
        
        $this->itemRepository->shouldReceive('find')
            ->once()
            ->with($itemId)
            ->andReturn($item);
        
        $this->inventoryRepository->shouldReceive('getInventoryLevel')
            ->once()
            ->with($itemId, null, null)
            ->andReturn($inventory);
        
        // Act
        $result = $this->service->getInventoryLevel($itemId);
        
        // Assert
        $this->assertInstanceOf(InventoryData::class, $result);
        $this->assertEquals(100, $result->quantityOnHand);
    }
    
    public function test_get_inventory_level_throws_exception_when_item_not_found()
    {
        // Arrange
        $this->itemRepository->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);
        
        // Act & Assert
        $this->expectException(ItemNotFoundException::class);
        $this->service->getInventoryLevel(999);
    }
    
    public function test_check_availability_returns_true_when_stock_not_tracked()
    {
        // Arrange
        $itemId = 1;
        $item = $this->createMockItem($itemId, false); // trackStock = false
        
        $this->itemRepository->shouldReceive('find')
            ->once()
            ->with($itemId)
            ->andReturn($item);
        
        // Act
        $result = $this->service->checkAvailability($itemId, 100);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function test_check_availability_with_stock_tracking()
    {
        // Arrange
        $itemId = 1;
        $item = $this->createMockItem($itemId, true);
        $inventory = $this->createMockInventory($itemId, 50, 10); // 50 on hand, 10 reserved
        
        $this->itemRepository->shouldReceive('find')
            ->once()
            ->with($itemId)
            ->andReturn($item);
        
        $this->inventoryRepository->shouldReceive('getInventoryLevel')
            ->once()
            ->with($itemId, null, null)
            ->andReturn($inventory);
        
        $this->features->shouldReceive('isEnabled')
            ->with('item.stock_reservation')
            ->andReturn(true);
        
        // Act & Assert
        $this->assertTrue($this->service->checkAvailability($itemId, 40)); // 50 - 10 = 40 available
        $this->assertFalse($this->service->checkAvailability($itemId, 50)); // Not enough available
    }
    
    public function test_adjust_inventory_increases_stock()
    {
        // Arrange
        $itemId = 1;
        $adjustmentData = [
            'item_id' => $itemId,
            'quantity_change' => 50,
            'adjustment_type' => 'restock',
            'reason' => 'New shipment',
        ];
        
        $item = $this->createMockItem($itemId, true);
        $currentInventory = $this->createMockInventory($itemId, 100);
        $adjustment = new InventoryAdjustmentData(
            id: 1,
            itemId: $itemId,
            variantId: null,
            locationId: null,
            quantityChange: 50,
            adjustmentType: 'restock',
            reason: 'New shipment',
            notes: null,
            adjustedBy: 1,
            adjustedAt: now()
        );
        
        $this->itemRepository->shouldReceive('find')
            ->once()
            ->with($itemId)
            ->andReturn($item);
        
        $this->inventoryRepository->shouldReceive('getInventoryLevel')
            ->once()
            ->with($itemId, null, null)
            ->andReturn($currentInventory);
        
        $this->inventoryRepository->shouldReceive('adjustInventory')
            ->once()
            ->with($adjustmentData)
            ->andReturn($adjustment);
        
        $this->inventoryRepository->shouldReceive('getInventoryLevel')
            ->once()
            ->with($itemId, null, null)
            ->andReturn($this->createMockInventory($itemId, 150)); // After adjustment
        
        // Act
        $result = $this->service->adjustInventory($adjustmentData);
        
        // Assert
        $this->assertInstanceOf(InventoryAdjustmentData::class, $result);
        $this->assertEquals(50, $result->quantityChange);
        Event::assertDispatched(StockAdjusted::class);
    }
    
    public function test_adjust_inventory_throws_exception_for_insufficient_stock()
    {
        // Arrange
        $itemId = 1;
        $adjustmentData = [
            'item_id' => $itemId,
            'quantity_change' => -150, // Trying to remove more than available
            'adjustment_type' => 'sale',
            'reason' => 'Order fulfillment',
        ];
        
        $item = $this->createMockItem($itemId, true);
        $currentInventory = $this->createMockInventory($itemId, 100);
        
        $this->itemRepository->shouldReceive('find')
            ->once()
            ->with($itemId)
            ->andReturn($item);
        
        $this->inventoryRepository->shouldReceive('getInventoryLevel')
            ->once()
            ->with($itemId, null, null)
            ->andReturn($currentInventory);
        
        // Act & Assert
        $this->expectException(InsufficientStockException::class);
        $this->service->adjustInventory($adjustmentData);
    }
    
    public function test_adjust_inventory_throws_exception_when_stock_not_tracked()
    {
        // Arrange
        $itemId = 1;
        $item = $this->createMockItem($itemId, false); // trackStock = false
        
        $this->itemRepository->shouldReceive('find')
            ->once()
            ->with($itemId)
            ->andReturn($item);
        
        // Act & Assert
        $this->expectException(InvalidInventoryOperationException::class);
        $this->service->adjustInventory(['item_id' => $itemId, 'quantity_change' => 10]);
    }
    
    public function test_reserve_stock_when_available()
    {
        // Arrange
        $itemId = 1;
        $quantity = 10;
        
        $this->features->shouldReceive('isEnabled')
            ->with('item.stock_reservation')
            ->andReturn(true);
        
        $this->inventoryRepository->shouldReceive('getInventoryLevel')
            ->andReturn($this->createMockInventory($itemId, 100, 20));
        
        $this->itemRepository->shouldReceive('find')
            ->andReturn($this->createMockItem($itemId, true));
        
        $this->inventoryRepository->shouldReceive('reserveStock')
            ->once()
            ->with($itemId, $quantity, null, null, null, null)
            ->andReturn(true);
        
        // Act
        $result = $this->service->reserveStock($itemId, $quantity);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function test_reserve_stock_returns_true_when_feature_disabled()
    {
        // Arrange
        $this->features->shouldReceive('isEnabled')
            ->with('item.stock_reservation')
            ->andReturn(false);
        
        // Act
        $result = $this->service->reserveStock(1, 10);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function test_get_low_stock_items()
    {
        // Arrange
        $lowStockItems = collect([
            $this->createMockInventory(1, 5, 0, 10), // Below min quantity
            $this->createMockInventory(2, 8, 0, 15),
        ]);
        
        $this->inventoryRepository->shouldReceive('getLowStockItems')
            ->once()
            ->with(null)
            ->andReturn($lowStockItems);
        
        // Act
        $result = $this->service->getLowStockItems();
        
        // Assert
        $this->assertCount(2, $result);
    }
    
    public function test_update_reorder_levels()
    {
        // Arrange
        $itemId = 1;
        $minQuantity = 20;
        $reorderQuantity = 50;
        $maxQuantity = 200;
        
        $item = $this->createMockItem($itemId);
        $updatedInventory = $this->createMockInventory($itemId, 100, 0, $minQuantity, $reorderQuantity, $maxQuantity);
        
        $this->itemRepository->shouldReceive('find')
            ->once()
            ->with($itemId)
            ->andReturn($item);
        
        $this->inventoryRepository->shouldReceive('updateReorderLevels')
            ->once()
            ->with($itemId, null, null, $minQuantity, $reorderQuantity, $maxQuantity)
            ->andReturn($updatedInventory);
        
        // Act
        $result = $this->service->updateReorderLevels($itemId, null, null, $minQuantity, $reorderQuantity, $maxQuantity);
        
        // Assert
        $this->assertInstanceOf(InventoryData::class, $result);
        $this->assertEquals($minQuantity, $result->minQuantity);
        $this->assertEquals($reorderQuantity, $result->reorderQuantity);
    }
    
    public function test_perform_stock_take()
    {
        // Arrange
        $counts = [
            ['item_id' => 1, 'counted_quantity' => 95, 'location_id' => 1],
            ['item_id' => 2, 'counted_quantity' => 150, 'location_id' => 1],
        ];
        
        $item1 = $this->createMockItem(1);
        $item2 = $this->createMockItem(2);
        
        $this->itemRepository->shouldReceive('find')
            ->with(1)
            ->andReturn($item1);
        
        $this->itemRepository->shouldReceive('find')
            ->with(2)
            ->andReturn($item2);
        
        $this->inventoryRepository->shouldReceive('getInventoryLevel')
            ->with(1, null, 1)
            ->andReturn($this->createMockInventory(1, 100)); // 5 less than counted
        
        $this->inventoryRepository->shouldReceive('getInventoryLevel')
            ->with(2, null, 1)
            ->andReturn($this->createMockInventory(2, 140)); // 10 more than counted
        
        $this->inventoryRepository->shouldReceive('adjustInventory')
            ->twice()
            ->andReturn(Mockery::mock(InventoryAdjustmentData::class));
        
        $this->inventoryRepository->shouldReceive('getInventoryLevel')
            ->andReturn($this->createMockInventory(1, 95));
        
        // Act
        $result = $this->service->performStockTake($counts);
        
        // Assert
        $this->assertEquals(2, $result['adjusted']);
        $this->assertEmpty($result['errors']);
    }
    
    /**
     * Helper method to create mock item
     */
    private function createMockItem(int $id, bool $trackStock = true): ItemData
    {
        return new ItemData(
            id: $id,
            name: "Test Item {$id}",
            description: null,
            type: 'product',
            categoryId: null,
            basePrice: 1000,
            cost: 500,
            sku: "TEST-{$id}",
            barcode: null,
            trackStock: $trackStock,
            isAvailable: true,
            allowModifiers: false,
            preparationTime: null,
            sortOrder: 0,
            createdAt: now(),
            updatedAt: now()
        );
    }
    
    /**
     * Helper method to create mock inventory
     */
    private function createMockInventory(
        int $itemId,
        float $quantityOnHand,
        float $quantityReserved = 0,
        float $minQuantity = 10,
        float $reorderQuantity = 50,
        ?float $maxQuantity = null
    ): InventoryData {
        return new InventoryData(
            id: 1,
            itemId: $itemId,
            variantId: null,
            locationId: null,
            quantityOnHand: $quantityOnHand,
            quantityReserved: $quantityReserved,
            minQuantity: $minQuantity,
            reorderQuantity: $reorderQuantity,
            maxQuantity: $maxQuantity,
            unitCost: 500,
            lastCountedAt: now(),
            lastRestockedAt: now()->subDays(1)
        );
    }
}