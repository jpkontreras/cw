<?php

declare(strict_types=1);

namespace Colame\Business\Repositories;

use App\Core\Data\PaginatedResourceData;
use App\Core\Traits\ValidatesPagination;
use Colame\Business\Contracts\BusinessRepositoryInterface;
use Colame\Business\Data\BusinessData;
use Colame\Business\Data\CreateBusinessData;
use Colame\Business\Data\UpdateBusinessData;
use Colame\Business\Models\Business;
use Spatie\LaravelData\DataCollection;

class BusinessRepository implements BusinessRepositoryInterface
{
    use ValidatesPagination;

    /**
     * Find a business by ID
     */
    public function find(int $id): ?BusinessData
    {
        $business = Business::find($id);
        return $business ? BusinessData::fromModel($business) : null;
    }

    /**
     * Find a business by slug
     */
    public function findBySlug(string $slug): ?BusinessData
    {
        $business = Business::where('slug', $slug)->first();
        return $business ? BusinessData::fromModel($business) : null;
    }

    /**
     * Get all businesses for a user
     */
    public function getUserBusinesses(int $userId): DataCollection
    {
        $businesses = Business::whereHas('users', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->where('status', 'active');
        })->orWhere('owner_id', $userId)->get();

        return BusinessData::collect($businesses, DataCollection::class);
    }

    /**
     * Get all businesses
     */
    public function all(): DataCollection
    {
        return BusinessData::collect(Business::all(), DataCollection::class);
    }

    /**
     * Get paginated businesses
     */
    public function paginate(array $filters = [], int $perPage = 15): array
    {
        $perPage = $this->validatePerPage($perPage);
        
        $query = Business::query();

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('slug', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['subscription_tier'])) {
            $query->where('subscription_tier', $filters['subscription_tier']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $paginator = $query->paginate($perPage);

        return PaginatedResourceData::fromPaginator($paginator, BusinessData::class)->toArray();
    }

    /**
     * Create a new business
     */
    public function create(CreateBusinessData $data): BusinessData
    {
        $business = Business::create($data->toDatabaseArray());

        // Add the owner as a user of the business
        $business->users()->attach($data->ownerId, [
            'role' => 'owner',
            'is_owner' => true,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return BusinessData::fromModel($business->fresh(['users']));
    }

    /**
     * Update a business
     */
    public function update(int $id, UpdateBusinessData $data): BusinessData
    {
        $business = Business::findOrFail($id);
        $business->update($data->toDatabaseArray());

        return BusinessData::fromModel($business->fresh());
    }

    /**
     * Delete a business
     */
    public function delete(int $id): bool
    {
        $business = Business::find($id);
        
        if (!$business) {
            return false;
        }

        return (bool) $business->delete();
    }

    /**
     * Check if a slug exists
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = Business::where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get business with all relations
     */
    public function findWithRelations(int $id): ?BusinessData
    {
        $business = Business::with([
            'businessUsers',
            'currentSubscription',
            'owner',
        ])->find($id);

        return $business ? BusinessData::fromModel($business) : null;
    }

    /**
     * Get businesses by type
     */
    public function getByType(string $type): DataCollection
    {
        $businesses = Business::where('type', $type)->get();
        return BusinessData::collect($businesses, DataCollection::class);
    }

    /**
     * Get active businesses
     */
    public function getActive(): DataCollection
    {
        $businesses = Business::where('status', 'active')->get();
        return BusinessData::collect($businesses, DataCollection::class);
    }

    /**
     * Count businesses for a user
     */
    public function countUserBusinesses(int $userId): int
    {
        return Business::whereHas('users', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->where('status', 'active');
        })->orWhere('owner_id', $userId)->count();
    }

    /**
     * Find businesses by owner ID
     */
    public function findByOwnerId(int $ownerId): DataCollection
    {
        $businesses = Business::where('owner_id', $ownerId)->get();
        return BusinessData::collect($businesses, DataCollection::class);
    }
}