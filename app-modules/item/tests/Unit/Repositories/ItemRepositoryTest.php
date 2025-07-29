<?php

namespace Colame\Item\Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Colame\Item\Repositories\ItemRepository;
use Colame\Item\Models\Item;
use Colame\Item\Models\ItemVariant;
use Colame\Item\Data\ItemData;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemRepositoryTest extends TestCase
{
    use RefreshDatabase;
    
    private ItemRepository $repository;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ItemRepository();
    }
    
    public function test_find_returns_item_data()
    {
        // Arrange
        $item = Item::factory()->create([
            'name' => 'Test Item',
            'base_price' => 1000,
        ]);
        
        // Act
        $result = $this->repository->find($item->id);
        
        // Assert
        $this->assertInstanceOf(ItemData::class, $result);
        $this->assertEquals($item->id, $result->id);
        $this->assertEquals('Test Item', $result->name);
        $this->assertEquals(1000, $result->basePrice);
    }
    
    public function test_find_returns_null_for_non_existent_item()
    {
        // Act
        $result = $this->repository->find(999);
        
        // Assert
        $this->assertNull($result);
    }
    
    public function test_create_creates_item_and_returns_data()
    {
        // Arrange
        $data = [
            'name' => 'New Item',
            'description' => 'Test description',
            'type' => 'product',
            'base_price' => 2500,
            'sku' => 'TEST-001',
            'track_stock' => true,
            'is_available' => true,
        ];
        
        // Act
        $result = $this->repository->create($data);
        
        // Assert
        $this->assertInstanceOf(ItemData::class, $result);
        $this->assertEquals('New Item', $result->name);
        $this->assertEquals(2500, $result->basePrice);
        $this->assertTrue($result->trackStock);
        
        $this->assertDatabaseHas('items', [
            'name' => 'New Item',
            'sku' => 'TEST-001',
        ]);
    }
    
    public function test_update_updates_item_and_returns_data()
    {
        // Arrange
        $item = Item::factory()->create([
            'name' => 'Original Name',
            'base_price' => 1000,
        ]);
        
        $updateData = [
            'name' => 'Updated Name',
            'base_price' => 1500,
        ];
        
        // Act
        $result = $this->repository->update($item->id, $updateData);
        
        // Assert
        $this->assertInstanceOf(ItemData::class, $result);
        $this->assertEquals('Updated Name', $result->name);
        $this->assertEquals(1500, $result->basePrice);
        
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Updated Name',
            'base_price' => 1500,
        ]);
    }
    
    public function test_delete_soft_deletes_item()
    {
        // Arrange
        $item = Item::factory()->create();
        
        // Act
        $result = $this->repository->delete($item->id);
        
        // Assert
        $this->assertTrue($result);
        $this->assertSoftDeleted('items', ['id' => $item->id]);
    }
    
    public function test_paginate_with_filters_returns_paginated_data()
    {
        // Arrange
        Item::factory()->count(25)->create(['type' => 'product']);
        Item::factory()->count(5)->create(['type' => 'service']);
        
        $filters = ['type' => 'product'];
        
        // Act
        $result = $this->repository->paginateWithFilters($filters, 10);
        
        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(25, $result->total());
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(3, $result->lastPage());
    }
    
    public function test_paginate_with_search_filter()
    {
        // Arrange
        Item::factory()->create(['name' => 'Empanada de Pino']);
        Item::factory()->create(['name' => 'Empanada de Queso']);
        Item::factory()->create(['name' => 'Completo']);
        
        $filters = ['search' => 'empanada'];
        
        // Act
        $result = $this->repository->paginateWithFilters($filters, 10);
        
        // Assert
        $this->assertEquals(2, $result->total());
    }
    
    public function test_check_availability_with_tracking_enabled()
    {
        // Arrange
        $item = Item::factory()->create(['track_stock' => true]);
        
        // Act & Assert - No inventory record exists
        $this->assertFalse($this->repository->checkAvailability($item->id, 10));
    }
    
    public function test_check_availability_with_tracking_disabled()
    {
        // Arrange
        $item = Item::factory()->create(['track_stock' => false]);
        
        // Act & Assert - Always available when not tracking
        $this->assertTrue($this->repository->checkAvailability($item->id, 10));
    }
    
    public function test_find_by_sku_returns_item_data()
    {
        // Arrange
        $item = Item::factory()->create(['sku' => 'TEST-SKU-001']);
        
        // Act
        $result = $this->repository->findBySku('TEST-SKU-001');
        
        // Assert
        $this->assertInstanceOf(ItemData::class, $result);
        $this->assertEquals($item->id, $result->id);
        $this->assertEquals('TEST-SKU-001', $result->sku);
    }
    
    public function test_find_by_barcode_returns_item_data()
    {
        // Arrange
        $item = Item::factory()->create(['barcode' => '1234567890']);
        
        // Act
        $result = $this->repository->findByBarcode('1234567890');
        
        // Assert
        $this->assertInstanceOf(ItemData::class, $result);
        $this->assertEquals($item->id, $result->id);
        $this->assertEquals('1234567890', $result->barcode);
    }
    
    public function test_get_items_by_category()
    {
        // Arrange
        Item::factory()->count(3)->create(['category_id' => 1]);
        Item::factory()->count(2)->create(['category_id' => 2]);
        
        // Act
        $result = $this->repository->getItemsByCategory(1);
        
        // Assert
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(ItemData::class, $result);
    }
    
    public function test_create_variant()
    {
        // Arrange
        $item = Item::factory()->create();
        $variantData = [
            'name' => 'Large Size',
            'sku' => 'VAR-001',
            'price' => 3000,
        ];
        
        // Act
        $result = $this->repository->createVariant($item->id, $variantData);
        
        // Assert
        $this->assertEquals('Large Size', $result->name);
        $this->assertEquals($item->id, $result->itemId);
        
        $this->assertDatabaseHas('item_variants', [
            'item_id' => $item->id,
            'name' => 'Large Size',
        ]);
    }
    
    public function test_update_availability()
    {
        // Arrange
        $item = Item::factory()->create(['is_available' => true]);
        
        // Act
        $result = $this->repository->updateAvailability($item->id, false);
        
        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'is_available' => false,
        ]);
    }
    
    public function test_bulk_update_availability()
    {
        // Arrange
        $items = Item::factory()->count(3)->create(['is_available' => true]);
        $ids = $items->pluck('id')->toArray();
        
        // Act
        $result = $this->repository->bulkUpdateAvailability($ids, false);
        
        // Assert
        $this->assertEquals(3, $result);
        
        foreach ($ids as $id) {
            $this->assertDatabaseHas('items', [
                'id' => $id,
                'is_available' => false,
            ]);
        }
    }
    
    public function test_get_filter_options_for_status()
    {
        // Act
        $result = $this->repository->getFilterOptions('status');
        
        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('value', $result[0]);
        $this->assertArrayHasKey('label', $result[0]);
    }
    
    public function test_get_filter_options_for_type()
    {
        // Act
        $result = $this->repository->getFilterOptions('type');
        
        // Assert
        $this->assertIsArray($result);
        $this->assertEquals([
            ['value' => 'product', 'label' => 'Product'],
            ['value' => 'service', 'label' => 'Service'],
            ['value' => 'combo', 'label' => 'Combo'],
        ], $result);
    }
}