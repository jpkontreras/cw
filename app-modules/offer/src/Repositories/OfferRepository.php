<?php

declare(strict_types=1);

namespace Colame\Offer\Repositories;

use App\Core\Traits\ValidatesPagination;
use Colame\Offer\Contracts\OfferRepositoryInterface;
use Colame\Offer\Data\OfferData;
use Colame\Offer\Models\Offer;
use Colame\Offer\Models\OfferUsage;
use Spatie\LaravelData\DataCollection;
use App\Core\Data\PaginatedResourceData;
use Illuminate\Support\Facades\DB;

class OfferRepository implements OfferRepositoryInterface
{
    use ValidatesPagination;
    
    public function find(int $id): ?OfferData
    {
        $offer = Offer::find($id);
        
        return $offer ? OfferData::fromModel($offer) : null;
    }
    
    public function findByCode(string $code): ?OfferData
    {
        $offer = Offer::withCode($code)->first();
        
        return $offer ? OfferData::fromModel($offer) : null;
    }
    
    public function all(): DataCollection
    {
        return OfferData::collect(
            Offer::ordered()->get(),
            DataCollection::class
        );
    }
    
    public function paginate(int $perPage = 15, array $filters = []): PaginatedResourceData
    {
        $perPage = $this->validatePerPage($perPage);
        
        $query = Offer::query();
        
        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['is_active']) && $filters['is_active'] !== null) {
            $query->where('is_active', $filters['is_active'] === '1');
        }
        
        // Filter by offers with discount codes
        if (isset($filters['has_code']) && $filters['has_code'] !== null) {
            if ($filters['has_code'] === '1') {
                $query->whereNotNull('code');
            }
        }
        
        // Filter by expiring offers
        if (!empty($filters['expiring'])) {
            if ($filters['expiring'] === '7days') {
                $query->where('ends_at', '<=', now()->addDays(7))
                      ->where('ends_at', '>=', now());
            } elseif ($filters['expiring'] === '30days') {
                $query->where('ends_at', '<=', now()->addDays(30))
                      ->where('ends_at', '>=', now());
            }
        }
        
        if (!empty($filters['location_id'])) {
            $query->forLocation($filters['location_id']);
        }
        
        if (!empty($filters['valid_only']) && $filters['valid_only']) {
            $query->valid();
        }
        
        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'priority';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        if ($sortBy === 'priority') {
            $query->ordered();
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }
        
        $paginator = $query->paginate($perPage);
        
        return PaginatedResourceData::fromPaginator($paginator, OfferData::class);
    }
    
    public function getActive(): DataCollection
    {
        return OfferData::collect(
            Offer::active()->ordered()->get(),
            DataCollection::class
        );
    }
    
    public function getActiveForLocation(int $locationId): DataCollection
    {
        return OfferData::collect(
            Offer::active()
                ->forLocation($locationId)
                ->ordered()
                ->get(),
            DataCollection::class
        );
    }
    
    public function getValidOffersForOrder(array $orderData): DataCollection
    {
        $query = Offer::valid()->ordered();
        
        // Filter by location if provided
        if (!empty($orderData['location_id'])) {
            $query->forLocation($orderData['location_id']);
        }
        
        // Filter by items if provided
        if (!empty($orderData['item_ids'])) {
            $query->where(function ($q) use ($orderData) {
                $q->whereNull('target_item_ids')
                    ->orWhere(function ($subQ) use ($orderData) {
                        foreach ($orderData['item_ids'] as $itemId) {
                            $subQ->orWhereJsonContains('target_item_ids', $itemId);
                        }
                    });
            });
        }
        
        // Filter by categories if provided
        if (!empty($orderData['category_ids'])) {
            $query->where(function ($q) use ($orderData) {
                $q->whereNull('target_category_ids')
                    ->orWhere(function ($subQ) use ($orderData) {
                        foreach ($orderData['category_ids'] as $categoryId) {
                            $subQ->orWhereJsonContains('target_category_ids', $categoryId);
                        }
                    });
            });
        }
        
        // Filter by minimum amount
        if (!empty($orderData['total_amount'])) {
            $query->where(function ($q) use ($orderData) {
                $q->whereNull('minimum_amount')
                    ->orWhere('minimum_amount', '<=', $orderData['total_amount']);
            });
        }
        
        // Filter by day and time
        $offers = $query->get()->filter(function ($offer) {
            return $offer->isValidForDay() && $offer->isValidForTime();
        });
        
        return OfferData::collect($offers, DataCollection::class);
    }
    
    public function create(array $data): OfferData
    {
        $offer = Offer::create($this->prepareData($data));
        
        return OfferData::fromModel($offer);
    }
    
    public function update(int $id, array $data): OfferData
    {
        $offer = Offer::findOrFail($id);
        $offer->update($this->prepareData($data));
        
        return OfferData::fromModel($offer->fresh());
    }
    
    public function delete(int $id): bool
    {
        $offer = Offer::find($id);
        
        if (!$offer) {
            return false;
        }
        
        return $offer->delete();
    }
    
    public function activate(int $id): bool
    {
        return Offer::where('id', $id)->update(['is_active' => true]) > 0;
    }
    
    public function deactivate(int $id): bool
    {
        return Offer::where('id', $id)->update(['is_active' => false]) > 0;
    }
    
    public function incrementUsage(int $id): bool
    {
        $offer = Offer::find($id);
        
        if (!$offer) {
            return false;
        }
        
        $offer->incrementUsage();
        
        return true;
    }
    
    public function getUsageStats(int $id): array
    {
        $offer = Offer::find($id);
        
        if (!$offer) {
            return [];
        }
        
        $stats = OfferUsage::where('offer_id', $id)
            ->selectRaw('
                COUNT(*) as total_uses,
                COUNT(DISTINCT customer_id) as unique_customers,
                SUM(discount_amount) as total_discount,
                SUM(order_amount) as total_order_value,
                AVG(discount_amount) as avg_discount,
                MAX(used_at) as last_used
            ')
            ->first();
        
        $dailyUsage = OfferUsage::where('offer_id', $id)
            ->where('used_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(used_at) as date, COUNT(*) as uses, SUM(discount_amount) as discount')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return [
            'offer_id' => $id,
            'offer_name' => $offer->name,
            'total_uses' => $stats->total_uses ?? 0,
            'unique_customers' => $stats->unique_customers ?? 0,
            'total_discount' => $stats->total_discount ?? 0,
            'total_order_value' => $stats->total_order_value ?? 0,
            'avg_discount' => $stats->avg_discount ?? 0,
            'last_used' => $stats->last_used,
            'usage_limit' => $offer->usage_limit,
            'remaining_uses' => $offer->usage_limit ? $offer->usage_limit - $offer->usage_count : null,
            'daily_usage' => $dailyUsage->toArray(),
        ];
    }
    
    private function prepareData(array $data): array
    {
        // Convert camelCase to snake_case for database
        $prepared = [];
        
        $mapping = [
            'maxDiscount' => 'max_discount',
            'isActive' => 'is_active',
            'autoApply' => 'auto_apply',
            'isStackable' => 'is_stackable',
            'startsAt' => 'starts_at',
            'endsAt' => 'ends_at',
            'recurringSchedule' => 'recurring_schedule',
            'validDays' => 'valid_days',
            'validTimeStart' => 'valid_time_start',
            'validTimeEnd' => 'valid_time_end',
            'minimumAmount' => 'minimum_amount',
            'minimumQuantity' => 'minimum_quantity',
            'usageLimit' => 'usage_limit',
            'usagePerCustomer' => 'usage_per_customer',
            'locationIds' => 'location_ids',
            'targetItemIds' => 'target_item_ids',
            'targetCategoryIds' => 'target_category_ids',
            'excludedItemIds' => 'excluded_item_ids',
            'customerSegments' => 'customer_segments',
        ];
        
        foreach ($data as $key => $value) {
            $dbKey = $mapping[$key] ?? $key;
            $prepared[$dbKey] = $value;
        }
        
        return $prepared;
    }
    
    public function getOfferStats(): array
    {
        $totalOffers = Offer::count();
        $activeOffers = Offer::where('is_active', true)->count();
        $offersWithCodes = Offer::whereNotNull('code')->count();
        $expiringSoon = Offer::where('ends_at', '<=', now()->addDays(7))
                             ->where('ends_at', '>=', now())
                             ->count();
        
        return [
            'totalOffers' => $totalOffers,
            'activeOffers' => $activeOffers,
            'mostUsed' => $offersWithCodes, // Using offers with codes count for discount codes stat
            'expiringSoon' => $expiringSoon,
        ];
    }
}