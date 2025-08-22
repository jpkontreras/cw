<?php

declare(strict_types=1);

namespace Colame\Offer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Offer extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'max_discount',
        'code',
        'is_active',
        'auto_apply',
        'is_stackable',
        'starts_at',
        'ends_at',
        'recurring_schedule',
        'valid_days',
        'valid_time_start',
        'valid_time_end',
        'minimum_amount',
        'minimum_quantity',
        'usage_limit',
        'usage_per_customer',
        'usage_count',
        'priority',
        'location_ids',
        'target_item_ids',
        'target_category_ids',
        'excluded_item_ids',
        'customer_segments',
        'conditions',
        'metadata',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'auto_apply' => 'boolean',
        'is_stackable' => 'boolean',
        'value' => 'float',
        'max_discount' => 'float',
        'minimum_amount' => 'float',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'valid_days' => 'array',
        'location_ids' => 'array',
        'target_item_ids' => 'array',
        'target_category_ids' => 'array',
        'excluded_item_ids' => 'array',
        'customer_segments' => 'array',
        'conditions' => 'array',
        'metadata' => 'array',
    ];
    
    protected $attributes = [
        'is_active' => true,
        'auto_apply' => false,
        'is_stackable' => false,
        'usage_count' => 0,
    ];
    
    public function usageHistory(): HasMany
    {
        return $this->hasMany(OfferUsage::class);
    }
    
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
    
    public function scopeValid(Builder $query): Builder
    {
        $now = now();
        
        return $query->active()
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            });
    }
    
    public function scopeAutoApply(Builder $query): Builder
    {
        return $query->where('auto_apply', true);
    }
    
    public function scopeForLocation(Builder $query, int $locationId): Builder
    {
        return $query->where(function ($q) use ($locationId) {
            $q->whereNull('location_ids')
                ->orWhereJsonContains('location_ids', $locationId);
        });
    }
    
    public function scopeForItems(Builder $query, array $itemIds): Builder
    {
        return $query->where(function ($q) use ($itemIds) {
            $q->whereNull('target_item_ids');
            
            foreach ($itemIds as $itemId) {
                $q->orWhereJsonContains('target_item_ids', $itemId);
            }
        });
    }
    
    public function scopeForCategories(Builder $query, array $categoryIds): Builder
    {
        return $query->where(function ($q) use ($categoryIds) {
            $q->whereNull('target_category_ids');
            
            foreach ($categoryIds as $categoryId) {
                $q->orWhereJsonContains('target_category_ids', $categoryId);
            }
        });
    }
    
    public function scopeWithCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }
    
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('priority')
            ->orderBy('created_at');
    }
    
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
    
    public function hasUsageRemaining(): bool
    {
        if (!$this->usage_limit) {
            return true;
        }
        
        return $this->usage_count < $this->usage_limit;
    }
    
    public function isValidForDay(?string $day = null): bool
    {
        if (!$this->valid_days || empty($this->valid_days)) {
            return true;
        }
        
        $day = $day ?: strtolower(now()->format('l'));
        
        return in_array($day, $this->valid_days);
    }
    
    public function isValidForTime(?string $time = null): bool
    {
        if (!$this->valid_time_start || !$this->valid_time_end) {
            return true;
        }
        
        $time = $time ?: now()->format('H:i');
        
        return $time >= $this->valid_time_start && $time <= $this->valid_time_end;
    }
}