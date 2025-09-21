<?php

namespace Colame\Item\Services;

use App\Core\Contracts\FeatureFlagInterface;
use App\Core\Contracts\ResourceMetadataInterface;
use App\Core\Data\PaginatedResourceData;
use App\Core\Data\ResourceMetadata;
use App\Core\Data\ColumnMetadata;
use App\Core\Data\FilterMetadata;
use App\Core\Data\FilterPresetData;
use App\Core\Services\BaseService;
use Colame\Item\Contracts\ItemServiceInterface;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Contracts\ModifierRepositoryInterface;
use Colame\Item\Contracts\PricingRepositoryInterface;
use Colame\Item\Contracts\InventoryRepositoryInterface;
use Colame\Item\Contracts\RecipeRepositoryInterface;
use Colame\Item\Data\ItemData;
use Colame\Item\Data\ItemWithRelationsData;
use Colame\Item\Data\PriceCalculationData;
use Colame\Item\Data\ModifierPriceImpactData;
use Colame\Item\Exceptions\ItemNotFoundException;
use Colame\Item\Exceptions\InsufficientStockException;
use Colame\Item\Events\ItemCreated;
use Colame\Item\Events\ItemUpdated;
use Colame\Item\Events\ItemDeleted;
use Colame\Item\Events\StockUpdated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Colame\AiDiscovery\Contracts\AiDiscoveryInterface;
use Colame\AiDiscovery\Contracts\FoodIntelligenceInterface;

class ItemService extends BaseService implements ItemServiceInterface, ResourceMetadataInterface
{
    public function __construct(
        private ItemRepositoryInterface $itemRepository,
        private ModifierRepositoryInterface $modifierRepository,
        private PricingRepositoryInterface $pricingRepository,
        private InventoryRepositoryInterface $inventoryRepository,
        private RecipeRepositoryInterface $recipeRepository,
        FeatureFlagInterface $features,
        private ?AiDiscoveryInterface $aiDiscovery = null,
        private ?FoodIntelligenceInterface $foodIntelligence = null,
    ) {
        parent::__construct($features);
    }
    
    /**
     * Get paginated items with filters
     */
    public function getPaginatedItems(array $filters = [], int $perPage = 20): PaginatedResourceData
    {
        $paginator = $this->itemRepository->paginateWithFilters($filters, $perPage);
        $metadata = $this->getResourceMetadata()->toArray();
        
        return PaginatedResourceData::fromPaginator(
            $paginator,
            ItemData::class,
            $metadata
        );
    }
    
    /**
     * Find a single item by ID (returns basic data)
     */
    public function find(int $id): ?ItemData
    {
        return $this->itemRepository->find($id);
    }
    
    /**
     * Get a single item by ID with relations
     */
    public function getItem(int $id): ItemWithRelationsData
    {
        $item = $this->itemRepository->findWithRelations($id);
        
        if (!$item) {
            throw new ItemNotFoundException("Item with ID {$id} not found");
        }
        
        return $item;
    }
    
    /**
     * Get items for public display (active, available)
     */
    public function getPublicItems(array $filters = []): Collection
    {
        $query = $this->itemRepository->getActiveItems();
        
        // Apply additional filters
        if (!empty($filters['category_id'])) {
            $categoryIds = is_array($filters['category_id']) ? $filters['category_id'] : [$filters['category_id']];
            $query = $this->itemRepository->getByCategories($categoryIds);
        }
        
        if (!empty($filters['featured'])) {
            $query = $this->itemRepository->getFeaturedItems();
        }
        
        if (!empty($filters['location_id']) && $this->features->isEnabled('item.dynamic_pricing')) {
            $query = $this->itemRepository->getActiveItemsForLocation($filters['location_id']);
        }
        
        return $query;
    }
    
    /**
     * Create a new item
     */
    public function createItem(array $data): ItemData
    {
        DB::beginTransaction();
        
        try {
            // Create the item
            $item = $this->itemRepository->create($data);
            
            // Handle categories if provided
            if (!empty($data['categories'])) {
                $this->attachCategories($item->id, $data['categories']);
            }
            
            // Handle variants if provided
            if (!empty($data['variants'])) {
                $this->createVariants($item->id, $data['variants']);
            }
            
            // Handle modifiers if provided
            if (!empty($data['modifier_groups']) && $this->features->isEnabled('item.modifiers')) {
                $this->modifierRepository->syncGroupsForItem($item->id, $data['modifier_groups']);
            }
            
            // Handle images if provided
            if (!empty($data['images'])) {
                $this->processImages($item->id, $data['images']);
            }
            
            // Create recipe if provided
            if (!empty($data['recipe']) && $this->features->isEnabled('item.recipes')) {
                $recipeData = array_merge($data['recipe'], ['item_id' => $item->id]);
                $recipe = $this->recipeRepository->create($recipeData);
                
                if (!empty($data['recipe']['ingredients'])) {
                    $this->recipeRepository->addIngredientsToRecipe($recipe->id, $data['recipe']['ingredients']);
                }
            }
            
            DB::commit();
            
            // Dispatch event
            event(new ItemCreated($item));
            
            return $item;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create item', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }
    
    /**
     * Update an existing item
     */
    public function updateItem(int $id, array $data): ItemData
    {
        DB::beginTransaction();
        
        try {
            // Update the item
            $item = $this->itemRepository->updateAndReturn($id, $data);
            
            // Update categories if provided
            if (isset($data['categories'])) {
                $this->syncCategories($item->id, $data['categories']);
            }
            
            // Update variants if provided
            if (isset($data['variants'])) {
                $this->syncVariants($item->id, $data['variants']);
            }
            
            // Update modifiers if provided
            if (isset($data['modifier_groups']) && $this->features->isEnabled('item.modifiers')) {
                $this->modifierRepository->syncGroupsForItem($item->id, $data['modifier_groups']);
            }
            
            // Update images if provided
            if (isset($data['images'])) {
                $this->syncImages($item->id, $data['images']);
            }
            
            // Update recipe if provided
            if (isset($data['recipe']) && $this->features->isEnabled('item.recipes')) {
                $recipe = $this->recipeRepository->findByItem($item->id);
                
                if ($recipe) {
                    $this->recipeRepository->updateAndReturn($recipe->id, $data['recipe']);
                    
                    if (isset($data['recipe']['ingredients'])) {
                        $this->recipeRepository->updateRecipeIngredients($recipe->id, $data['recipe']['ingredients']);
                    }
                } else {
                    $recipeData = array_merge($data['recipe'], ['item_id' => $item->id]);
                    $recipe = $this->recipeRepository->create($recipeData);
                    
                    if (!empty($data['recipe']['ingredients'])) {
                        $this->recipeRepository->addIngredientsToRecipe($recipe->id, $data['recipe']['ingredients']);
                    }
                }
            }
            
            DB::commit();
            
            // Dispatch event
            event(new ItemUpdated($item));
            
            return $item;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update item', ['error' => $e->getMessage(), 'id' => $id, 'data' => $data]);
            throw $e;
        }
    }
    
    /**
     * Delete an item
     */
    public function deleteItem(int $id): bool
    {
        $result = $this->itemRepository->delete($id);
        
        if ($result) {
            event(new ItemDeleted($id));
        }
        
        return $result;
    }
    
    /**
     * Duplicate an item
     */
    public function duplicateItem(int $id, array $overrides = []): ItemData
    {
        return DB::transaction(function () use ($id, $overrides) {
            // Get original item with relations
            $originalItem = $this->itemRepository->findWithRelations($id);
            
            if (!$originalItem) {
                throw new ItemNotFoundException("Item with ID {$id} not found");
            }
            
            // Duplicate the item
            $newItem = $this->itemRepository->duplicate($id, $overrides);
            
            // Duplicate recipe if exists
            if ($originalItem->recipe && $this->features->isEnabled('item.recipes')) {
                $recipeData = $originalItem->recipe->toArray();
                unset($recipeData['id'], $recipeData['created_at'], $recipeData['updated_at']);
                $recipeData['item_id'] = $newItem->id;
                
                $newRecipe = $this->recipeRepository->create($recipeData);
                
                if (!empty($originalItem->recipe->ingredients)) {
                    $ingredients = collect($originalItem->recipe->ingredients)->map(function ($ing) {
                        return [
                            'ingredient_id' => $ing->ingredientId,
                            'quantity' => $ing->quantity,
                            'unit' => $ing->unit,
                            'is_optional' => $ing->isOptional,
                        ];
                    })->all();
                    
                    $this->recipeRepository->addIngredientsToRecipe($newRecipe->id, $ingredients);
                }
            }
            
            return $newItem;
        });
    }
    
    /**
     * Check item availability
     */
    public function checkAvailability(int $itemId, int $quantity, ?int $variantId = null, ?int $locationId = null): bool
    {
        if (!$this->features->isEnabled('item.inventory_tracking')) {
            return $this->itemRepository->checkAvailability($itemId, $quantity);
        }
        
        $availableStock = $this->inventoryRepository->getAvailableStock($itemId, $variantId, $locationId);
        
        return $availableStock >= $quantity;
    }
    
    /**
     * Calculate item price with modifiers
     */
    public function calculatePrice(int $itemId, ?int $variantId = null, array $modifierIds = [], ?int $locationId = null): PriceCalculationData
    {
        $item = $this->itemRepository->find($itemId);
        
        if (!$item) {
            throw new ItemNotFoundException("Item with ID {$itemId} not found");
        }
        
        $basePrice = $item->basePrice;
        $variantAdjustment = 0;
        $locationPrice = null;
        
        // Get variant adjustment
        if ($variantId) {
            $variants = DB::table('item_variants')
                ->where('item_id', $itemId)
                ->where('id', $variantId)
                ->first();
            
            if ($variants) {
                $variantAdjustment = $variants->price_adjustment;
            }
        }
        
        // Get location-specific price
        if ($locationId && $this->features->isEnabled('item.dynamic_pricing')) {
            $pricingRule = $this->pricingRepository->getCurrentPrice($itemId, $locationId, $variantId);
            
            if ($pricingRule) {
                $locationPrice = $pricingRule->price;
            }
        }
        
        // Calculate modifier adjustments
        $modifierAdjustments = [];
        $totalModifierAdjustment = 0;
        
        if (!empty($modifierIds) && $this->features->isEnabled('item.modifiers')) {
            foreach ($modifierIds as $modifierData) {
                $modifierId = is_array($modifierData) ? $modifierData['modifier_id'] : $modifierData;
                $quantity = is_array($modifierData) ? ($modifierData['quantity'] ?? 1) : 1;
                
                $modifier = $this->modifierRepository->findModifier($modifierId);
                if ($modifier) {
                    $group = $this->modifierRepository->findGroup($modifier->modifierGroupId);
                    $priceImpact = $modifier->priceAdjustment * $quantity;
                    
                    $modifierAdjustments[] = new ModifierPriceImpactData(
                        modifierId: $modifier->id,
                        modifierName: $modifier->name,
                        modifierGroupId: $modifier->modifierGroupId,
                        modifierGroupName: $group->name ?? 'Unknown Group',
                        quantity: $quantity,
                        unitPrice: $modifier->priceAdjustment,
                        priceImpact: $priceImpact,
                    );
                    
                    $totalModifierAdjustment += $priceImpact;
                }
            }
        }
        
        // Calculate totals
        $effectiveBasePrice = $locationPrice ?? $basePrice;
        $subtotal = $effectiveBasePrice + $variantAdjustment;
        $total = $subtotal + $totalModifierAdjustment;
        
        return new PriceCalculationData(
            itemId: $itemId,
            variantId: $variantId,
            locationId: $locationId,
            basePrice: $basePrice,
            variantAdjustment: $variantAdjustment,
            modifierAdjustments: $modifierAdjustments,
            locationPrice: $locationPrice,
            subtotal: $subtotal,
            total: $total,
            currency: 'CLP',
            appliedRules: $locationPrice ? ['location_pricing'] : [],
        );
    }
    
    /**
     * Bulk update items
     */
    public function bulkUpdate(array $itemIds, array $data): int
    {
        $count = 0;
        
        DB::transaction(function () use ($itemIds, $data, &$count) {
            foreach ($itemIds as $itemId) {
                try {
                    $this->itemRepository->update($itemId, $data);
                    $count++;
                } catch (\Exception $e) {
                    Log::warning("Failed to update item {$itemId} in bulk update", ['error' => $e->getMessage()]);
                }
            }
        });
        
        return $count;
    }
    
    /**
     * Import items from file
     */
    public function importItems(string $filePath, array $options = []): array
    {
        if (!$this->features->isEnabled('item.import_export')) {
            throw new \Exception('Import/Export feature is not enabled');
        }
        
        // This would be implemented by the ImportService
        throw new \Exception('Import functionality not yet implemented');
    }
    
    /**
     * Export items to file
     */
    public function exportItems(array $filters = [], string $format = 'csv'): string
    {
        if (!$this->features->isEnabled('item.import_export')) {
            throw new \Exception('Import/Export feature is not enabled');
        }
        
        // This would be implemented by the ExportService
        throw new \Exception('Export functionality not yet implemented');
    }
    
    /**
     * Get low stock items
     */
    public function getLowStockItems(?int $locationId = null): Collection
    {
        if (!$this->features->isEnabled('item.inventory_tracking')) {
            return collect();
        }
        
        return $this->itemRepository->getLowStockItems($locationId);
    }
    
    /**
     * Update item stock
     */
    public function updateStock(int $itemId, int $quantity, string $reason, ?int $variantId = null, ?int $locationId = null): bool
    {
        if (!$this->features->isEnabled('item.inventory_tracking')) {
            return false;
        }
        
        $currentStock = $this->inventoryRepository->getStockLevel($itemId, $variantId, $locationId);
        $newStock = $currentStock + $quantity;
        
        if ($newStock < 0) {
            throw new InsufficientStockException("Cannot reduce stock below zero");
        }
        
        $movement = $this->inventoryRepository->adjustStock($itemId, $quantity, $reason, [
            'location_id' => $locationId,
            'variant_id' => $variantId,
            'user_id' => auth()->id(),
        ]);
        
        event(new StockUpdated($itemId, $variantId, $locationId, $currentStock, $newStock));
        
        return true;
    }
    
    /**
     * Get resource metadata
     */
    public function getResourceMetadata(array $context = []): ResourceMetadata
    {
        $columns = [];
        
        // Define columns
        $columns['name'] = ColumnMetadata::text('name', 'Name')
            ->withFilter(FilterMetadata::search('search', 'Search items', 'Search by name, SKU, or barcode'));
        
        $columns['base_price'] = ColumnMetadata::number('base_price', 'Price');
        
        $columns['is_active'] = ColumnMetadata::boolean('is_active', 'Active')
            ->withFilter(FilterMetadata::select('status', 'Status', $this->itemRepository->getFilterOptions('status')));
        
        $columns['item_type'] = ColumnMetadata::enum('item_type', 'Type', $this->itemRepository->getFilterOptions('type'))
            ->withFilter(FilterMetadata::multiSelect('type', 'Item Type', $this->itemRepository->getFilterOptions('type')));
        
        $columns['is_featured'] = ColumnMetadata::boolean('is_featured', 'Featured')
            ->withFilter(FilterMetadata::select('featured', 'Featured', $this->itemRepository->getFilterOptions('featured')));
        
        if ($this->features->isEnabled('item.inventory_tracking')) {
            $columns['stock_quantity'] = ColumnMetadata::number('stock_quantity', 'Stock')
                ->withFilter(FilterMetadata::multiSelect('inventory', 'Inventory Status', $this->itemRepository->getFilterOptions('inventory')));
        }
        
        $columns['created_at'] = ColumnMetadata::datetime('created_at', 'Created');
        
        return new ResourceMetadata(
            columns: ColumnMetadata::collect($columns, \Spatie\LaravelData\DataCollection::class),
            defaultFilters: ['search', 'status', 'type', 'category_id'],
            defaultSort: 'sort_order',
            perPageOptions: [10, 20, 50, 100],
            defaultPerPage: 20,
        );
    }
    
    /**
     * Attach categories to an item
     */
    private function attachCategories(int $itemId, array $categoryIds): void
    {
        foreach ($categoryIds as $index => $categoryId) {
            DB::table('item_categories')->insert([
                'item_id' => $itemId,
                'category_id' => $categoryId,
                'is_primary' => $index === 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    
    /**
     * Sync categories for an item
     */
    private function syncCategories(int $itemId, array $categoryIds): void
    {
        DB::table('item_categories')->where('item_id', $itemId)->delete();
        
        if (!empty($categoryIds)) {
            $this->attachCategories($itemId, $categoryIds);
        }
    }
    
    /**
     * Create variants for an item
     */
    private function createVariants(int $itemId, array $variants): void
    {
        foreach ($variants as $index => $variantData) {
            $variantData['item_id'] = $itemId;
            $variantData['sort_order'] = $variantData['sort_order'] ?? $index;
            
            DB::table('item_variants')->insert($variantData);
        }
    }
    
    /**
     * Sync variants for an item
     */
    private function syncVariants(int $itemId, array $variants): void
    {
        // Get existing variant IDs
        $existingIds = DB::table('item_variants')
            ->where('item_id', $itemId)
            ->pluck('id')
            ->toArray();
        
        $updatedIds = [];
        
        foreach ($variants as $index => $variantData) {
            if (!empty($variantData['id']) && in_array($variantData['id'], $existingIds)) {
                // Update existing variant
                DB::table('item_variants')
                    ->where('id', $variantData['id'])
                    ->update($variantData);
                
                $updatedIds[] = $variantData['id'];
            } else {
                // Create new variant
                $variantData['item_id'] = $itemId;
                $variantData['sort_order'] = $variantData['sort_order'] ?? $index;
                unset($variantData['id']);
                
                DB::table('item_variants')->insert($variantData);
            }
        }
        
        // Delete removed variants
        $idsToDelete = array_diff($existingIds, $updatedIds);
        if (!empty($idsToDelete)) {
            DB::table('item_variants')
                ->whereIn('id', $idsToDelete)
                ->delete();
        }
    }
    
    /**
     * Process and store images
     */
    private function processImages(int $itemId, array $images): void
    {
        foreach ($images as $index => $imageData) {
            if (is_string($imageData)) {
                // If just a path is provided
                $imageData = ['image_path' => $imageData];
            }
            
            $imageData['item_id'] = $itemId;
            $imageData['sort_order'] = $imageData['sort_order'] ?? $index;
            $imageData['is_primary'] = $imageData['is_primary'] ?? ($index === 0);
            
            DB::table('item_images')->insert($imageData);
        }
    }
    
    /**
     * Sync images for an item
     */
    private function syncImages(int $itemId, array $images): void
    {
        // Delete existing images
        $existingImages = DB::table('item_images')
            ->where('item_id', $itemId)
            ->get();
        
        DB::table('item_images')
            ->where('item_id', $itemId)
            ->delete();
        
        // Delete physical files
        foreach ($existingImages as $image) {
            Storage::delete($image->image_path);
            if ($image->thumbnail_path) {
                Storage::delete($image->thumbnail_path);
            }
        }
        
        // Add new images
        if (!empty($images)) {
            $this->processImages($itemId, $images);
        }
    }
    
    /**
     * Get available item types
     */
    public function getItemTypes(): array
    {
        return [
            ['value' => 'product', 'label' => 'Product'],
            ['value' => 'service', 'label' => 'Service'],
            ['value' => 'combo', 'label' => 'Combo'],
        ];
    }
    
    /**
     * Get items formatted for select options
     */
    public function getItemsForSelect(array $options = []): Collection
    {
        $query = $this->itemRepository->getActiveItems();
        
        if (!empty($options['with_variants']) && $this->features->isEnabled('item.variants')) {
            // Include variants in the selection
            return $query->map(function ($item) {
                $options = collect([[
                    'value' => $item->id,
                    'label' => $item->name,
                    'type' => 'item',
                    'price' => $item->basePrice,
                ]]);
                
                if (!empty($item->variants)) {
                    foreach ($item->variants as $variant) {
                        $options->push([
                            'value' => "item_{$item->id}_variant_{$variant->id}",
                            'label' => "{$item->name} - {$variant->name}",
                            'type' => 'variant',
                            'itemId' => $item->id,
                            'variantId' => $variant->id,
                            'price' => $item->basePrice + $variant->priceAdjustment,
                        ]);
                    }
                }
                
                return $options;
            })->flatten(1);
        }
        
        return $query->map(function ($item) {
            return [
                'value' => $item->id,
                'label' => $item->name,
                'price' => $item->basePrice,
                'sku' => $item->sku,
                'type' => $item->type,
            ];
        });
    }
    
    /**
     * Get items for stock take
     */
    public function getItemsForStockTake(?int $locationId = null): Collection
    {
        if (!$this->features->isEnabled('item.inventory_tracking')) {
            return collect();
        }
        
        $items = $this->itemRepository->getItemsWithStock($locationId);
        
        return $items->map(function ($item) use ($locationId) {
            $currentStock = $this->inventoryRepository->getStockLevel($item->id, null, $locationId);
            
            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'category' => $item->categoryName,
                'current_stock' => $currentStock,
                'counted_stock' => null,
                'variance' => null,
                'unit' => $item->stockUnit ?? 'units',
            ];
        });
    }
    
    /**
     * Alias for getItem() to match controller usage
     */
    public function getItemWithRelations(int $id): ItemWithRelationsData
    {
        return $this->getItem($id);
    }
    
    /**
     * Get filter presets for the resource
     */
    public function getFilterPresets(): array
    {
        return [
            new FilterPresetData(
                id: 'active',
                name: 'Active Items',
                description: 'Items that are currently available',
                filters: [
                    'status' => ['active'],
                    'is_available' => true,
                ],
                icon: 'package'
            ),
            new FilterPresetData(
                id: 'low-stock',
                name: 'Low Stock',
                description: 'Items with low inventory',
                filters: [
                    'inventory' => ['low', 'out_of_stock'],
                ],
                icon: 'alert-triangle'
            ),
            new FilterPresetData(
                id: 'featured',
                name: 'Featured Items',
                description: 'Items marked as featured',
                filters: [
                    'featured' => 'yes',
                ],
                icon: 'star'
            ),
        ];
    }
    
    /**
     * Get available actions for the resource
     */
    public function getAvailableActions(array $context = []): array
    {
        $actions = [
            [
                'id' => 'view',
                'label' => 'View Details',
                'icon' => 'eye',
                'route' => 'items.show',
            ],
            [
                'id' => 'edit',
                'label' => 'Edit Item',
                'icon' => 'edit',
                'route' => 'items.edit',
            ],
        ];
        
        if ($this->features->isEnabled('item.inventory_tracking')) {
            $actions[] = [
                'id' => 'adjust-stock',
                'label' => 'Adjust Stock',
                'icon' => 'package',
                'route' => 'items.inventory.adjust',
            ];
        }
        
        if ($this->features->isEnabled('item.batch_operations')) {
            $actions[] = [
                'id' => 'duplicate',
                'label' => 'Duplicate',
                'icon' => 'copy',
                'route' => 'items.duplicate',
            ];
        }
        
        $actions[] = [
            'id' => 'delete',
            'label' => 'Delete',
            'icon' => 'trash',
            'route' => 'items.destroy',
            'destructive' => true,
        ];
        
        return $actions;
    }
    
    /**
     * Get export configuration for the resource
     */
    public function getExportConfiguration(): array
    {
        return [
            'formats' => [
                'csv' => [
                    'label' => 'CSV',
                    'extension' => 'csv',
                    'mimeType' => 'text/csv',
                ],
                'excel' => [
                    'label' => 'Excel',
                    'extension' => 'xlsx',
                    'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
                'pdf' => [
                    'label' => 'PDF',
                    'extension' => 'pdf',
                    'mimeType' => 'application/pdf',
                ],
            ],
            'columns' => [
                'id' => 'ID',
                'name' => 'Name',
                'sku' => 'SKU',
                'description' => 'Description',
                'base_price' => 'Base Price',
                'cost' => 'Cost',
                'type' => 'Type',
                'status' => 'Status',
                'is_available' => 'Available',
                'is_featured' => 'Featured',
                'stock_quantity' => 'Stock Quantity',
                'created_at' => 'Created Date',
                'updated_at' => 'Last Updated',
            ],
            'defaultColumns' => [
                'name',
                'sku',
                'base_price',
                'type',
                'status',
                'stock_quantity',
            ],
        ];
    }
    
    /**
     * Get all items (including inactive)
     */
    public function getAllItems(): Collection
    {
        return collect($this->itemRepository->all())->map(fn($item) => ItemData::from($item));
    }

    /**
     * Start AI Discovery session for item creation
     * Returns DiscoverySessionData object or null
     */
    public function startAiDiscovery(string $itemName, ?string $description = null, array $context = []): mixed
    {
        if (!$this->aiDiscovery) {
            return null;
        }

        try {
            $session = $this->aiDiscovery->startDiscovery($itemName, $context, $description);
            return $session; // Return the Data object, not array
        } catch (\Exception $e) {
            Log::error('Failed to start AI discovery', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Process AI Discovery response
     * Returns ConversationContextData object or null
     */
    public function processAiResponse(string $sessionId, string $response, ?array $selections = null): mixed
    {
        if (!$this->aiDiscovery) {
            return null;
        }

        try {
            $context = $this->aiDiscovery->processUserResponse($sessionId, $response, $selections);
            return $context;
        } catch (\Exception $e) {
            Log::error('Failed to process AI response', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Complete AI Discovery and create item with extracted data
     */
    public function completeAiDiscoveryAndCreateItem(string $sessionId, array $additionalData = []): ?ItemData
    {
        if (!$this->aiDiscovery) {
            return null;
        }

        try {
            // Complete the discovery session
            $session = $this->aiDiscovery->completeDiscovery($sessionId);
            $extractedData = $session->extractedData;

            // Prepare item data from AI extraction
            $itemData = array_merge([
                'name' => $additionalData['name'] ?? 'New Item',
                'description' => $additionalData['description'] ?? '',
                'base_price' => $additionalData['base_price'] ?? 0,
                'type' => $additionalData['type'] ?? 'product',
                'category' => $additionalData['category'] ?? null,
                'is_active' => true,
                'is_available' => true,
            ], $additionalData);

            // Create the item
            $item = $this->create($itemData);

            // Add variants if extracted
            if (!empty($extractedData->variants)) {
                foreach ($extractedData->variants as $variant) {
                    $this->itemRepository->createVariant($item->id, [
                        'name' => $variant['name'],
                        'price_adjustment' => $variant['priceAdjustment'] ?? 0,
                        'is_default' => $variant['isDefault'] ?? false,
                    ]);
                }
            }

            // Add modifiers if extracted
            if (!empty($extractedData->modifiers) && $this->modifierRepository) {
                foreach ($extractedData->modifiers as $modifier) {
                    // Create or get modifier group
                    $group = $this->modifierRepository->createGroup([
                        'name' => $modifier['groupName'],
                        'selection_type' => $modifier['selectionType'] ?? 'multiple',
                        'is_required' => $modifier['isRequired'] ?? false,
                    ]);

                    // Create modifier
                    $this->modifierRepository->createModifier([
                        'modifier_group_id' => $group->id,
                        'name' => $modifier['name'],
                        'price_adjustment' => $modifier['priceAdjustment'] ?? 0,
                    ]);

                    // Link to item
                    $this->modifierRepository->attachGroupToItem($item->id, $group->id);
                }
            }

            return $item;
        } catch (\Exception $e) {
            Log::error('Failed to complete AI discovery and create item', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get AI-powered suggestions for an item
     */
    public function getAiSuggestions(string $itemName, ?string $category = null): ?array
    {
        if (!$this->foodIntelligence) {
            return null;
        }

        try {
            $analysis = $this->foodIntelligence->analyzeItemStructure($itemName, null, $category);
            return $analysis;
        } catch (\Exception $e) {
            Log::error('Failed to get AI suggestions', ['error' => $e->getMessage()]);
            return null;
        }
    }
}