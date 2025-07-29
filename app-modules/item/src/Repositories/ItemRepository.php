<?php

namespace Colame\Item\Repositories;

use App\Core\Traits\ValidatesPagination;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Data\ItemData;
use Colame\Item\Data\ItemWithRelationsData;
use Colame\Item\Data\ItemVariantData;
use Colame\Item\Data\ModifierGroupWithModifiersData;
use Colame\Item\Data\ModifierGroupData;
use Colame\Item\Data\ItemModifierData;
use Colame\Item\Data\ItemImageData;
use Colame\Item\Data\ItemLocationPriceData;
use Colame\Item\Data\ItemLocationStockData;
use Colame\Item\Data\RecipeData;
use Colame\Item\Models\Item;
use Colame\Item\Models\ItemVariant;
use Colame\Item\Models\ItemModifierGroup;
use Colame\Item\Models\ModifierGroup;
use Colame\Item\Models\ItemModifier;
use Colame\Item\Models\ItemImage;
use Colame\Item\Models\ItemLocationPrice;
use Colame\Item\Models\ItemLocationStock;
use Colame\Item\Models\ItemCategory;
use Colame\Item\Models\Recipe;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItemRepository implements ItemRepositoryInterface
{
    use ValidatesPagination;
    
    /**
     * Find an item by ID
     */
    public function find(int $id): ?ItemData
    {
        $item = Item::find($id);
        
        return $item ? ItemData::from($item) : null;
    }
    
    /**
     * Find an item with all relations
     */
    public function findWithRelations(int $id): ?ItemWithRelationsData
    {
        $item = Item::find($id);
        
        if (!$item) {
            return null;
        }
        
        // Load variants
        $variants = ItemVariant::where('item_id', $id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($v) => ItemVariantData::from($v))
            ->all();
        
        // Load modifier groups with modifiers
        $modifierGroups = DB::table('item_modifier_groups')
            ->join('modifier_groups', 'modifier_groups.id', '=', 'item_modifier_groups.modifier_group_id')
            ->where('item_modifier_groups.item_id', $id)
            ->where('modifier_groups.is_active', true)
            ->whereNull('modifier_groups.deleted_at')
            ->orderBy('item_modifier_groups.sort_order')
            ->select('modifier_groups.*', 'item_modifier_groups.sort_order as pivot_sort_order')
            ->get()
            ->map(function ($group) {
                $modifiers = ItemModifier::where('modifier_group_id', $group->id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn($m) => ItemModifierData::from($m))
                    ->all();
                
                return new ModifierGroupWithModifiersData(
                    modifierGroup: ModifierGroupData::from($group),
                    modifiers: $modifiers,
                    sortOrder: $group->pivot_sort_order
                );
            })
            ->all();
        
        // Load images
        $images = ItemImage::where('item_id', $id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($i) => ItemImageData::from($i))
            ->all();
        
        // Load categories
        $categories = ItemCategory::where('item_id', $id)
            ->pluck('category_id')
            ->all();
        
        // Load recipe if exists
        $recipe = Recipe::where('item_id', $id)->first();
        $recipeData = $recipe ? RecipeData::from($recipe) : null;
        
        return new ItemWithRelationsData(
            item: ItemData::from($item),
            variants: $variants,
            modifierGroups: $modifierGroups,
            images: $images,
            categories: $categories,
            tags: [], // Tags would come from taxonomy module
            recipe: $recipeData,
            currentPrice: null, // Will be set by service layer based on context
            childItems: [], // Will be loaded if compound item
            stockInfo: null, // Will be set by service layer based on context
        );
    }
    
    /**
     * Find an item by slug
     */
    public function findBySlug(string $slug): ?ItemData
    {
        $item = Item::where('slug', $slug)->first();
        
        return $item ? ItemData::from($item) : null;
    }
    
    /**
     * Get all active items
     */
    public function getActiveItems(): Collection
    {
        return Item::active()
            ->available()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($item) => ItemData::from($item));
    }
    
    /**
     * Get active items for a specific location
     */
    public function getActiveItemsForLocation(int $locationId): Collection
    {
        // This would involve checking location-specific availability
        // For now, return all active items
        return $this->getActiveItems();
    }
    
    /**
     * Check if an item is available
     */
    public function checkAvailability(int $id, int $quantity = 1): bool
    {
        $item = Item::find($id);
        
        if (!$item || !$item->is_active || !$item->is_available) {
            return false;
        }
        
        // Check time-based availability
        $now = now();
        if ($item->available_from && $now->isBefore($item->available_from)) {
            return false;
        }
        if ($item->available_until && $now->isAfter($item->available_until)) {
            return false;
        }
        
        // Check stock if tracking inventory
        if ($item->track_inventory && $item->stock_quantity < $quantity) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get the current price for an item
     */
    public function getCurrentPrice(int $id, ?int $locationId = null, ?int $variantId = null): float
    {
        $item = Item::find($id);
        
        if (!$item) {
            return 0;
        }
        
        $basePrice = $item->base_price;
        
        // Add variant adjustment if specified
        if ($variantId) {
            $variant = ItemVariant::find($variantId);
            if ($variant && $variant->item_id === $id) {
                $basePrice += $variant->price_adjustment;
            }
        }
        
        // Check for location-specific pricing
        if ($locationId) {
            $locationPrice = ItemLocationPrice::where('item_id', $id)
                ->where('location_id', $locationId)
                ->when($variantId, function ($query) use ($variantId) {
                    $query->where('item_variant_id', $variantId);
                })
                ->currentlyValid()
                ->orderBy('priority', 'desc')
                ->first();
            
            if ($locationPrice) {
                return $locationPrice->price;
            }
        }
        
        return $basePrice;
    }
    
    /**
     * Get items by category
     */
    public function getByCategory(int $categoryId, bool $activeOnly = true): Collection
    {
        $itemIds = ItemCategory::where('category_id', $categoryId)
            ->pluck('item_id');
        
        $query = Item::whereIn('id', $itemIds);
        
        if ($activeOnly) {
            $query->active()->available();
        }
        
        return $query->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($item) => ItemData::from($item));
    }
    
    /**
     * Get items by multiple categories
     */
    public function getByCategories(array $categoryIds, bool $activeOnly = true): Collection
    {
        $itemIds = ItemCategory::whereIn('category_id', $categoryIds)
            ->pluck('item_id')
            ->unique();
        
        $query = Item::whereIn('id', $itemIds);
        
        if ($activeOnly) {
            $query->active()->available();
        }
        
        return $query->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($item) => ItemData::from($item));
    }
    
    /**
     * Get featured items
     */
    public function getFeaturedItems(int $limit = 10): Collection
    {
        return Item::featured()
            ->active()
            ->available()
            ->orderBy('sort_order')
            ->limit($limit)
            ->get()
            ->map(fn($item) => ItemData::from($item));
    }
    
    /**
     * Get low stock items
     */
    public function getLowStockItems(?int $locationId = null): Collection
    {
        if ($locationId) {
            // Get from location-specific stock
            $itemIds = ItemLocationStock::where('location_id', $locationId)
                ->needsReorder()
                ->pluck('item_id')
                ->unique();
            
            return Item::whereIn('id', $itemIds)
                ->tracksInventory()
                ->get()
                ->map(fn($item) => ItemData::from($item));
        }
        
        // Get from main stock
        return Item::lowStock()
            ->get()
            ->map(fn($item) => ItemData::from($item));
    }
    
    /**
     * Create a new item
     */
    public function create(array $data): ItemData
    {
        $item = Item::create($data);
        
        return ItemData::from($item);
    }
    
    /**
     * Update an item
     */
    public function update(int $id, array $data): ItemData
    {
        $item = Item::findOrFail($id);
        $item->update($data);
        
        return ItemData::from($item->fresh());
    }
    
    /**
     * Update stock quantity
     */
    public function updateStock(int $id, int $quantity, ?int $variantId = null, ?int $locationId = null): bool
    {
        if ($locationId) {
            $stock = ItemLocationStock::firstOrCreate([
                'item_id' => $id,
                'item_variant_id' => $variantId,
                'location_id' => $locationId,
            ]);
            
            $stock->quantity = $quantity;
            return $stock->save();
        }
        
        if ($variantId) {
            $variant = ItemVariant::find($variantId);
            if ($variant && $variant->item_id === $id) {
                $variant->stock_quantity = $quantity;
                return $variant->save();
            }
            return false;
        }
        
        $item = Item::find($id);
        if ($item) {
            $item->stock_quantity = $quantity;
            return $item->save();
        }
        
        return false;
    }
    
    /**
     * Decrement stock quantity
     */
    public function decrementStock(int $id, int $quantity, ?int $variantId = null, ?int $locationId = null): bool
    {
        if ($locationId) {
            return DB::table('item_location_stock')
                ->where('item_id', $id)
                ->where('item_variant_id', $variantId)
                ->where('location_id', $locationId)
                ->where('quantity', '>=', $quantity)
                ->decrement('quantity', $quantity) > 0;
        }
        
        if ($variantId) {
            return ItemVariant::where('id', $variantId)
                ->where('item_id', $id)
                ->where('stock_quantity', '>=', $quantity)
                ->decrement('stock_quantity', $quantity) > 0;
        }
        
        return Item::where('id', $id)
            ->where('stock_quantity', '>=', $quantity)
            ->decrement('stock_quantity', $quantity) > 0;
    }
    
    /**
     * Increment stock quantity
     */
    public function incrementStock(int $id, int $quantity, ?int $variantId = null, ?int $locationId = null): bool
    {
        if ($locationId) {
            $stock = ItemLocationStock::firstOrCreate([
                'item_id' => $id,
                'item_variant_id' => $variantId,
                'location_id' => $locationId,
            ]);
            
            return $stock->increment('quantity', $quantity) > 0;
        }
        
        if ($variantId) {
            return ItemVariant::where('id', $variantId)
                ->where('item_id', $id)
                ->increment('stock_quantity', $quantity) > 0;
        }
        
        return Item::where('id', $id)
            ->increment('stock_quantity', $quantity) > 0;
    }
    
    /**
     * Soft delete an item
     */
    public function delete(int $id): bool
    {
        $item = Item::find($id);
        
        return $item ? $item->delete() : false;
    }
    
    /**
     * Restore a soft deleted item
     */
    public function restore(int $id): bool
    {
        $item = Item::withTrashed()->find($id);
        
        return $item ? $item->restore() : false;
    }
    
    /**
     * Duplicate an item
     */
    public function duplicate(int $id, array $overrides = []): ItemData
    {
        $originalItem = Item::findOrFail($id);
        
        // Create new item data
        $newData = $originalItem->toArray();
        unset($newData['id'], $newData['created_at'], $newData['updated_at'], $newData['deleted_at']);
        
        // Apply overrides
        $newData = array_merge($newData, $overrides);
        
        // Ensure unique slug
        if (!isset($overrides['slug'])) {
            $newData['slug'] = Str::slug($newData['name']) . '-copy-' . uniqid();
        }
        
        // Create the new item
        $newItem = Item::create($newData);
        
        // Duplicate variants
        $variants = ItemVariant::where('item_id', $id)->get();
        foreach ($variants as $variant) {
            $variantData = $variant->toArray();
            unset($variantData['id'], $variantData['created_at'], $variantData['updated_at']);
            $variantData['item_id'] = $newItem->id;
            ItemVariant::create($variantData);
        }
        
        // Duplicate modifier group associations
        $modifierGroups = ItemModifierGroup::where('item_id', $id)->get();
        foreach ($modifierGroups as $group) {
            ItemModifierGroup::create([
                'item_id' => $newItem->id,
                'modifier_group_id' => $group->modifier_group_id,
                'sort_order' => $group->sort_order,
            ]);
        }
        
        // Duplicate category associations
        $categories = ItemCategory::where('item_id', $id)->get();
        foreach ($categories as $category) {
            ItemCategory::create([
                'item_id' => $newItem->id,
                'category_id' => $category->category_id,
                'is_primary' => $category->is_primary,
            ]);
        }
        
        return ItemData::from($newItem);
    }
    
    /**
     * Get filter options for a specific field
     */
    public function getFilterOptions(string $field): array
    {
        switch ($field) {
            case 'status':
                return [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                    ['value' => 'available', 'label' => 'Available'],
                    ['value' => 'unavailable', 'label' => 'Unavailable'],
                ];
                
            case 'type':
                return [
                    ['value' => 'single', 'label' => 'Single Item'],
                    ['value' => 'compound', 'label' => 'Compound Item'],
                ];
                
            case 'featured':
                return [
                    ['value' => 'yes', 'label' => 'Featured'],
                    ['value' => 'no', 'label' => 'Not Featured'],
                ];
                
            case 'inventory':
                return [
                    ['value' => 'tracked', 'label' => 'Inventory Tracked'],
                    ['value' => 'not_tracked', 'label' => 'Not Tracked'],
                    ['value' => 'low_stock', 'label' => 'Low Stock'],
                    ['value' => 'out_of_stock', 'label' => 'Out of Stock'],
                ];
                
            default:
                return [];
        }
    }
    
    /**
     * Paginate items with filters
     */
    public function paginateWithFilters(
        array $filters = [],
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        $perPage = $this->validatePerPage($perPage);
        
        $query = Item::query();
        
        $this->applyFilters($query, $filters);
        
        return $query->paginate($perPage, $columns, $pageName, $page);
    }
    
    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters): void
    {
        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
        
        // Status filters
        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
            
            foreach ($statuses as $status) {
                switch ($status) {
                    case 'active':
                        $query->where('is_active', true);
                        break;
                    case 'inactive':
                        $query->where('is_active', false);
                        break;
                    case 'available':
                        $query->where('is_available', true);
                        break;
                    case 'unavailable':
                        $query->where('is_available', false);
                        break;
                }
            }
        }
        
        // Type filter
        if (!empty($filters['type'])) {
            $types = is_array($filters['type']) ? $filters['type'] : [$filters['type']];
            $query->whereIn('item_type', $types);
        }
        
        // Featured filter
        if (isset($filters['featured'])) {
            $query->where('is_featured', $filters['featured'] === 'yes');
        }
        
        // Inventory filters
        if (!empty($filters['inventory'])) {
            $inventoryFilters = is_array($filters['inventory']) ? $filters['inventory'] : [$filters['inventory']];
            
            foreach ($inventoryFilters as $filter) {
                switch ($filter) {
                    case 'tracked':
                        $query->where('track_inventory', true);
                        break;
                    case 'not_tracked':
                        $query->where('track_inventory', false);
                        break;
                    case 'low_stock':
                        $query->lowStock();
                        break;
                    case 'out_of_stock':
                        $query->outOfStock();
                        break;
                }
            }
        }
        
        // Category filter
        if (!empty($filters['category_id'])) {
            $categoryIds = is_array($filters['category_id']) ? $filters['category_id'] : [$filters['category_id']];
            $itemIds = ItemCategory::whereIn('category_id', $categoryIds)->pluck('item_id');
            $query->whereIn('id', $itemIds);
        }
        
        // Price range filter
        if (!empty($filters['min_price'])) {
            $query->where('base_price', '>=', $filters['min_price']);
        }
        if (!empty($filters['max_price'])) {
            $query->where('base_price', '<=', $filters['max_price']);
        }
        
        // Sort
        if (!empty($filters['sort'])) {
            $sortField = ltrim($filters['sort'], '-');
            $sortDirection = Str::startsWith($filters['sort'], '-') ? 'desc' : 'asc';
            
            $allowedSortFields = ['name', 'base_price', 'created_at', 'updated_at', 'sort_order'];
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection);
            }
        } else {
            $query->orderBy('sort_order')->orderBy('name');
        }
    }
}