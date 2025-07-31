<?php

namespace Colame\Item\Repositories;

use App\Core\Traits\ValidatesPagination;
use Colame\Item\Contracts\PricingRepositoryInterface;
use Colame\Item\Data\ItemLocationPriceData;
use Colame\Item\Models\ItemLocationPrice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PricingRepository implements PricingRepositoryInterface
{
    use ValidatesPagination;
    
    /**
     * Find a pricing rule by ID
     */
    public function find(int $id): ?ItemLocationPriceData
    {
        $price = ItemLocationPrice::find($id);
        
        return $price ? ItemLocationPriceData::from($price) : null;
    }
    
    /**
     * Get all pricing rules for an item
     */
    public function getPricingRulesForItem(int $itemId, ?int $variantId = null): Collection
    {
        $query = ItemLocationPrice::where('item_id', $itemId);
        
        if ($variantId !== null) {
            $query->where('item_variant_id', $variantId);
        }
        
        return $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($price) => ItemLocationPriceData::from($price));
    }
    
    /**
     * Get pricing rules for an item at a specific location
     */
    public function getPricingForItemAtLocation(int $itemId, int $locationId, ?int $variantId = null): Collection
    {
        $query = ItemLocationPrice::where('item_id', $itemId)
            ->where('location_id', $locationId);
        
        if ($variantId !== null) {
            $query->where('item_variant_id', $variantId);
        }
        
        return $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($price) => ItemLocationPriceData::from($price));
    }
    
    /**
     * Get the current applicable price for an item
     */
    public function getCurrentPrice(int $itemId, int $locationId, ?int $variantId = null, ?\DateTime $dateTime = null): ?ItemLocationPriceData
    {
        $dateTime = $dateTime ?? now();
        $currentDay = strtolower($dateTime->format('l'));
        $currentTime = $dateTime->format('H:i:s');
        
        $query = ItemLocationPrice::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->where('is_active', true);
        
        if ($variantId !== null) {
            $query->where('item_variant_id', $variantId);
        }
        
        // Apply date constraints
        $query->where(function ($q) use ($dateTime) {
            $q->whereNull('valid_from')
                ->orWhere('valid_from', '<=', $dateTime->toDateString());
        })->where(function ($q) use ($dateTime) {
            $q->whereNull('valid_until')
                ->orWhere('valid_until', '>=', $dateTime->toDateString());
        });
        
        // Apply day constraints
        $query->where(function ($q) use ($currentDay) {
            $q->whereNull('available_days')
                ->orWhereJsonContains('available_days', $currentDay);
        });
        
        // Apply time constraints
        $query->where(function ($q) use ($currentTime) {
            $q->whereNull('available_from_time')
                ->orWhere('available_from_time', '<=', $currentTime);
        })->where(function ($q) use ($currentTime) {
            $q->whereNull('available_until_time')
                ->orWhere('available_until_time', '>=', $currentTime);
        });
        
        // Get the highest priority rule
        $price = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $price ? ItemLocationPriceData::from($price) : null;
    }
    
    /**
     * Get active pricing rules for a location
     */
    public function getActivePricingForLocation(int $locationId): Collection
    {
        return ItemLocationPrice::where('location_id', $locationId)
            ->where('is_active', true)
            ->currentlyValid()
            ->orderBy('item_id')
            ->orderBy('priority', 'desc')
            ->get()
            ->map(fn($price) => ItemLocationPriceData::from($price));
    }
    
    /**
     * Create a new pricing rule
     */
    public function create(array $data): ItemLocationPriceData
    {
        $price = ItemLocationPrice::create($data);
        
        return ItemLocationPriceData::from($price);
    }
    
    /**
     * Update a pricing rule
     */
    public function update(int $id, array $data): bool
    {
        $price = ItemLocationPrice::findOrFail($id);
        return $price->update($data);
    }
    
    public function updateAndReturn(int $id, array $data): ItemLocationPriceData
    {
        $price = ItemLocationPrice::findOrFail($id);
        $price->update($data);
        
        return ItemLocationPriceData::from($price->fresh());
    }
    
    /**
     * Create bulk pricing rules
     */
    public function createBulk(array $rules): Collection
    {
        $created = collect();
        
        DB::transaction(function () use ($rules, &$created) {
            foreach ($rules as $rule) {
                $price = ItemLocationPrice::create($rule);
                $created->push(ItemLocationPriceData::from($price));
            }
        });
        
        return $created;
    }
    
    /**
     * Check for pricing conflicts
     */
    public function checkConflicts(array $data): Collection
    {
        $query = ItemLocationPrice::where('item_id', $data['item_id'])
            ->where('location_id', $data['location_id'])
            ->where('is_active', true);
        
        if (!empty($data['item_variant_id'])) {
            $query->where('item_variant_id', $data['item_variant_id']);
        }
        
        if (!empty($data['id'])) {
            $query->where('id', '!=', $data['id']);
        }
        
        // Check for overlapping date ranges
        if (!empty($data['valid_from']) || !empty($data['valid_until'])) {
            $query->where(function ($q) use ($data) {
                $q->where(function ($q2) use ($data) {
                    // Check if new range overlaps with existing ranges
                    if (!empty($data['valid_from'])) {
                        $q2->where('valid_until', '>=', $data['valid_from']);
                    }
                    if (!empty($data['valid_until'])) {
                        $q2->where('valid_from', '<=', $data['valid_until']);
                    }
                });
            });
        }
        
        // Check for overlapping time ranges
        if (!empty($data['available_from_time']) || !empty($data['available_until_time'])) {
            $query->where(function ($q) use ($data) {
                if (!empty($data['available_from_time'])) {
                    $q->where('available_until_time', '>=', $data['available_from_time']);
                }
                if (!empty($data['available_until_time'])) {
                    $q->where('available_from_time', '<=', $data['available_until_time']);
                }
            });
        }
        
        // Check for overlapping days
        if (!empty($data['available_days'])) {
            $query->where(function ($q) use ($data) {
                foreach ($data['available_days'] as $day) {
                    $q->orWhereJsonContains('available_days', $day);
                }
            });
        }
        
        return $query->get()->map(fn($price) => ItemLocationPriceData::from($price));
    }
    
    /**
     * Activate a pricing rule
     */
    public function activate(int $id): bool
    {
        return ItemLocationPrice::where('id', $id)->update(['is_active' => true]) > 0;
    }
    
    /**
     * Deactivate a pricing rule
     */
    public function deactivate(int $id): bool
    {
        return ItemLocationPrice::where('id', $id)->update(['is_active' => false]) > 0;
    }
    
    /**
     * Delete a pricing rule
     */
    public function delete(int $id): bool
    {
        return ItemLocationPrice::where('id', $id)->delete() > 0;
    }
    
    /**
     * Delete expired pricing rules
     */
    public function deleteExpired(): int
    {
        return ItemLocationPrice::where('valid_until', '<', now()->toDateString())->delete();
    }
    
    /**
     * Get pricing rules expiring soon
     */
    public function getExpiringSoon(int $days = 7): Collection
    {
        $expiryDate = now()->addDays($days)->toDateString();
        
        return ItemLocationPrice::where('is_active', true)
            ->whereNotNull('valid_until')
            ->where('valid_until', '<=', $expiryDate)
            ->where('valid_until', '>=', now()->toDateString())
            ->orderBy('valid_until')
            ->get()
            ->map(fn($price) => ItemLocationPriceData::from($price));
    }
    
    /**
     * Clone pricing rules from one location to another
     */
    public function clonePricingToLocation(int $fromLocationId, int $toLocationId, array $itemIds = []): Collection
    {
        $query = ItemLocationPrice::where('location_id', $fromLocationId)
            ->where('is_active', true);
        
        if (!empty($itemIds)) {
            $query->whereIn('item_id', $itemIds);
        }
        
        $prices = $query->get();
        $cloned = collect();
        
        DB::transaction(function () use ($prices, $toLocationId, &$cloned) {
            foreach ($prices as $price) {
                $newData = $price->toArray();
                unset($newData['id'], $newData['created_at'], $newData['updated_at']);
                $newData['location_id'] = $toLocationId;
                
                $newPrice = ItemLocationPrice::create($newData);
                $cloned->push(ItemLocationPriceData::from($newPrice));
            }
        });
        
        return $cloned;
    }
    
    /**
     * Apply percentage adjustment to prices
     */
    public function applyPercentageAdjustment(array $itemIds, int $locationId, float $percentage): int
    {
        $adjustment = 1 + ($percentage / 100);
        
        return ItemLocationPrice::whereIn('item_id', $itemIds)
            ->where('location_id', $locationId)
            ->where('is_active', true)
            ->update([
                'price' => DB::raw("price * {$adjustment}")
            ]);
    }
    
    /**
     * Paginate pricing rules with filters
     */
    public function paginateWithFilters(
        array $filters = [],
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        $perPage = $this->validatePerPage($perPage);
        
        $query = ItemLocationPrice::query();
        
        // Apply filters
        if (!empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }
        
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        if (!empty($filters['currently_valid'])) {
            $query->currentlyValid();
        }
        
        // Sort
        if (!empty($filters['sort'])) {
            $sortField = ltrim($filters['sort'], '-');
            $sortDirection = str_starts_with($filters['sort'], '-') ? 'desc' : 'asc';
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');
        }
        
        return $query->paginate($perPage, $columns, $pageName, $page);
    }
    
    /**
     * Find entity by ID or throw exception
     */
    public function findOrFail(int $id): object
    {
        return ItemLocationPrice::findOrFail($id);
    }
    
    /**
     * Get all entities
     */
    public function all(): array
    {
        return ItemLocationPrice::all()->map(fn($price) => ItemLocationPriceData::from($price))->toArray();
    }
    
    /**
     * Get paginated entities
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        $perPage = $this->validatePerPage($perPage);
        return ItemLocationPrice::paginate($perPage, $columns, $pageName, $page);
    }
    
    /**
     * Check if entity exists
     */
    public function exists(int $id): bool
    {
        return ItemLocationPrice::where('id', $id)->exists();
    }
}