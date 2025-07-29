<?php

namespace Colame\Item\Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Colame\Item\Services\ItemService;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Contracts\ModifierRepositoryInterface;
use Colame\Item\Contracts\PricingRepositoryInterface;
use Colame\Item\Contracts\InventoryRepositoryInterface;
use App\Core\Contracts\FeatureFlagInterface;
use Colame\Item\Data\ItemData;
use Colame\Item\Data\ItemWithRelationsData;
use App\Core\Data\PaginatedResourceData;
use Colame\Item\Events\ItemCreated;
use Colame\Item\Events\ItemUpdated;
use Colame\Item\Events\ItemDeleted;
use Colame\Item\Exceptions\ItemNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

class ItemServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private ItemService $service;
    private $itemRepository;
    private $modifierRepository;
    private $pricingRepository;
    private $inventoryRepository;
    private $features;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->itemRepository = Mockery::mock(ItemRepositoryInterface::class);
        $this->modifierRepository = Mockery::mock(ModifierRepositoryInterface::class);
        $this->pricingRepository = Mockery::mock(PricingRepositoryInterface::class);
        $this->inventoryRepository = Mockery::mock(InventoryRepositoryInterface::class);
        $this->features = Mockery::mock(FeatureFlagInterface::class);
        
        $this->service = new ItemService(
            $this->itemRepository,
            $this->modifierRepository,
            $this->pricingRepository,
            $this->inventoryRepository,
            $this->features
        );
        
        Event::fake();
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function test_create_item_creates_and_returns_item_data()
    {
        // Arrange
        $itemData = [
            'name' => 'Test Item',
            'type' => 'product',
            'base_price' => 1000,
        ];
        
        $createdItem = new ItemData(
            id: 1,
            name: 'Test Item',
            description: null,
            type: 'product',
            categoryId: null,
            basePrice: 1000,
            cost: null,
            sku: null,
            barcode: null,
            trackStock: false,
            isAvailable: true,
            allowModifiers: false,
            preparationTime: null,
            sortOrder: 0,
            createdAt: now(),
            updatedAt: now()
        );
        
        $this->itemRepository->shouldReceive('create')
            ->once()
            ->with($itemData)
            ->andReturn($createdItem);
        
        // Act
        $result = $this->service->createItem($itemData);
        
        // Assert
        $this->assertInstanceOf(ItemData::class, $result);
        $this->assertEquals('Test Item', $result->name);
        Event::assertDispatched(ItemCreated::class);
    }
    
    public function test_create_item_with_variants()
    {
        // Arrange
        $itemData = [
            'name' => 'Test Item',
            'type' => 'product',
            'base_price' => 1000,
            'variants' => [
                ['name' => 'Small', 'price' => 800],
                ['name' => 'Large', 'price' => 1200],
            ],
        ];
        
        $createdItem = new ItemData(
            id: 1,
            name: 'Test Item',
            description: null,
            type: 'product',
            categoryId: null,
            basePrice: 1000,
            cost: null,
            sku: null,
            barcode: null,
            trackStock: false,
            isAvailable: true,
            allowModifiers: false,
            preparationTime: null,
            sortOrder: 0,
            createdAt: now(),
            updatedAt: now()
        );
        
        $this->features->shouldReceive('isEnabled')
            ->with('item.variants')
            ->andReturn(true);
        
        $this->itemRepository->shouldReceive('create')
            ->once()
            ->andReturn($createdItem);
        
        $this->itemRepository->shouldReceive('createVariant')
            ->twice()
            ->andReturn(Mockery::mock());
        
        // Act
        $result = $this->service->createItem($itemData);
        
        // Assert
        $this->assertInstanceOf(ItemData::class, $result);
        Event::assertDispatched(ItemCreated::class);
    }
    
    public function test_update_item_updates_and_returns_data()
    {
        // Arrange
        $itemId = 1;
        $updateData = ['name' => 'Updated Item'];
        
        $existingItem = new ItemData(
            id: $itemId,
            name: 'Original Item',
            description: null,
            type: 'product',
            categoryId: null,
            basePrice: 1000,
            cost: null,
            sku: null,
            barcode: null,
            trackStock: false,
            isAvailable: true,
            allowModifiers: false,
            preparationTime: null,
            sortOrder: 0,
            createdAt: now(),
            updatedAt: now()
        );
        
        $updatedItem = new ItemData(
            id: $itemId,
            name: 'Updated Item',
            description: null,
            type: 'product',
            categoryId: null,
            basePrice: 1000,
            cost: null,
            sku: null,
            barcode: null,
            trackStock: false,
            isAvailable: true,
            allowModifiers: false,
            preparationTime: null,
            sortOrder: 0,
            createdAt: now(),
            updatedAt: now()
        );
        
        $this->itemRepository->shouldReceive('find')
            ->once()
            ->with($itemId)
            ->andReturn($existingItem);
        
        $this->itemRepository->shouldReceive('update')
            ->once()
            ->with($itemId, $updateData)
            ->andReturn($updatedItem);
        
        // Act
        $result = $this->service->updateItem($itemId, $updateData);
        
        // Assert
        $this->assertInstanceOf(ItemData::class, $result);
        $this->assertEquals('Updated Item', $result->name);
        Event::assertDispatched(ItemUpdated::class);
    }
    
    public function test_update_item_throws_exception_when_not_found()
    {
        // Arrange
        $this->itemRepository->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);
        
        // Act & Assert
        $this->expectException(ItemNotFoundException::class);
        $this->service->updateItem(999, ['name' => 'Test']);
    }
    
    public function test_delete_item_soft_deletes_and_dispatches_event()
    {
        // Arrange
        $itemId = 1;
        $item = new ItemData(
            id: $itemId,
            name: 'Test Item',
            description: null,
            type: 'product',
            categoryId: null,
            basePrice: 1000,
            cost: null,
            sku: null,
            barcode: null,
            trackStock: false,
            isAvailable: true,
            allowModifiers: false,
            preparationTime: null,
            sortOrder: 0,
            createdAt: now(),
            updatedAt: now()
        );
        
        $this->itemRepository->shouldReceive('find')
            ->once()
            ->with($itemId)
            ->andReturn($item);
        
        $this->itemRepository->shouldReceive('delete')
            ->once()
            ->with($itemId)
            ->andReturn(true);
        
        // Act
        $result = $this->service->deleteItem($itemId);
        
        // Assert
        $this->assertTrue($result);
        Event::assertDispatched(ItemDeleted::class);
    }
    
    public function test_get_paginated_items_returns_paginated_resource_data()
    {
        // Arrange
        $filters = ['type' => 'product'];
        $perPage = 20;
        
        $paginatorMock = Mockery::mock('Illuminate\Pagination\LengthAwarePaginator');
        $paginatorMock->shouldReceive('items')->andReturn(collect());
        $paginatorMock->shouldReceive('currentPage')->andReturn(1);
        $paginatorMock->shouldReceive('lastPage')->andReturn(1);
        $paginatorMock->shouldReceive('perPage')->andReturn($perPage);
        $paginatorMock->shouldReceive('total')->andReturn(0);
        $paginatorMock->shouldReceive('path')->andReturn('/items');
        $paginatorMock->shouldReceive('url')->andReturn('http://localhost/items');
        $paginatorMock->shouldReceive('previousPageUrl')->andReturn(null);
        $paginatorMock->shouldReceive('nextPageUrl')->andReturn(null);
        $paginatorMock->shouldReceive('firstItem')->andReturn(1);
        $paginatorMock->shouldReceive('lastItem')->andReturn(0);
        $paginatorMock->shouldReceive('links')->andReturn([]);
        
        $this->itemRepository->shouldReceive('paginateWithFilters')
            ->once()
            ->with($filters, $perPage)
            ->andReturn($paginatorMock);
        
        $this->itemRepository->shouldReceive('getFilterOptions')
            ->times(4)
            ->andReturn([]);
        
        // Act
        $result = $this->service->getPaginatedItems($filters, $perPage);
        
        // Assert
        $this->assertInstanceOf(PaginatedResourceData::class, $result);
    }
    
    public function test_check_availability_with_inventory_tracking()
    {
        // Arrange
        $itemId = 1;
        $quantity = 10;
        
        $this->features->shouldReceive('isEnabled')
            ->with('item.inventory_tracking')
            ->andReturn(true);
        
        $this->inventoryRepository->shouldReceive('checkAvailability')
            ->once()
            ->with($itemId, $quantity, null, null)
            ->andReturn(true);
        
        // Act
        $result = $this->service->checkAvailability($itemId, $quantity);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function test_check_availability_without_inventory_tracking()
    {
        // Arrange
        $itemId = 1;
        $quantity = 10;
        
        $this->features->shouldReceive('isEnabled')
            ->with('item.inventory_tracking')
            ->andReturn(false);
        
        $this->itemRepository->shouldReceive('checkAvailability')
            ->once()
            ->with($itemId, $quantity, null, null)
            ->andReturn(true);
        
        // Act
        $result = $this->service->checkAvailability($itemId, $quantity);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function test_get_item_with_relations()
    {
        // Arrange
        $itemId = 1;
        $item = new ItemData(
            id: $itemId,
            name: 'Test Item',
            description: null,
            type: 'product',
            categoryId: null,
            basePrice: 1000,
            cost: null,
            sku: null,
            barcode: null,
            trackStock: false,
            isAvailable: true,
            allowModifiers: false,
            preparationTime: null,
            sortOrder: 0,
            createdAt: now(),
            updatedAt: now()
        );
        
        $this->itemRepository->shouldReceive('find')
            ->once()
            ->with($itemId)
            ->andReturn($item);
        
        $this->itemRepository->shouldReceive('getVariants')
            ->once()
            ->with($itemId)
            ->andReturn(collect());
        
        $this->features->shouldReceive('isEnabled')
            ->with('item.modifiers')
            ->andReturn(true);
        
        $this->modifierRepository->shouldReceive('getItemModifierGroups')
            ->once()
            ->with($itemId)
            ->andReturn(collect());
        
        $this->itemRepository->shouldReceive('getImages')
            ->once()
            ->with($itemId)
            ->andReturn(collect());
        
        // Act
        $result = $this->service->getItemWithRelations($itemId);
        
        // Assert
        $this->assertInstanceOf(ItemWithRelationsData::class, $result);
        $this->assertEquals($itemId, $result->id);
    }
    
    public function test_search_items()
    {
        // Arrange
        $query = 'empanada';
        $options = ['location_id' => 1];
        
        $items = collect([
            new ItemData(
                id: 1,
                name: 'Empanada de Pino',
                description: null,
                type: 'product',
                categoryId: null,
                basePrice: 2500,
                cost: null,
                sku: null,
                barcode: null,
                trackStock: false,
                isAvailable: true,
                allowModifiers: false,
                preparationTime: null,
                sortOrder: 0,
                createdAt: now(),
                updatedAt: now()
            )
        ]);
        
        $this->itemRepository->shouldReceive('searchItems')
            ->once()
            ->with($query, Mockery::type('array'))
            ->andReturn($items);
        
        // Act
        $result = $this->service->searchItems($query, $options);
        
        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Empanada de Pino', $result->first()->name);
    }
    
    public function test_bulk_update_items()
    {
        // Arrange
        $itemIds = [1, 2, 3];
        $action = 'update_availability';
        $data = ['is_available' => false];
        
        $this->itemRepository->shouldReceive('bulkUpdateAvailability')
            ->once()
            ->with($itemIds, false)
            ->andReturn(3);
        
        // Act
        $result = $this->service->bulkUpdate($itemIds, $action, $data);
        
        // Assert
        $this->assertEquals(['updated' => 3, 'failed' => 0], $result);
    }
}