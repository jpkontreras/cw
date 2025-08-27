<?php

declare(strict_types=1);

namespace Colame\Business\Traits;

use Colame\Business\Contracts\BusinessContextInterface;
use Colame\Business\Models\Business;
use Colame\Business\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for models that belong to a business
 * Automatically scopes queries to the current business context
 */
trait BelongsToBusiness
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToBusiness(): void
    {
        // Add global scope to filter by current business
        static::addGlobalScope(new BusinessScope);
        
        // Automatically set business_id when creating
        static::creating(function ($model) {
            if (!$model->business_id) {
                $context = app(BusinessContextInterface::class);
                if ($businessId = $context->getCurrentBusinessId()) {
                    $model->business_id = $businessId;
                }
            }
        });
    }

    /**
     * Get the business that owns the model
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Scope to a specific business
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope to businesses
     */
    public function scopeForBusinesses($query, array $businessIds)
    {
        return $query->whereIn('business_id', $businessIds);
    }

    /**
     * Check if the model belongs to a specific business
     */
    public function belongsToBusiness(int $businessId): bool
    {
        return $this->business_id === $businessId;
    }

    /**
     * Check if the model belongs to the current business context
     */
    public function belongsToCurrentBusiness(): bool
    {
        $context = app(BusinessContextInterface::class);
        $currentBusinessId = $context->getCurrentBusinessId();
        
        return $currentBusinessId && $this->business_id === $currentBusinessId;
    }
}