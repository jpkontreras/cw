<?php

declare(strict_types=1);

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Item model
 * 
 * Note: Following interface-based architecture, this model only stores foreign keys
 * and does not have cross-module relationships
 */
class Item extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model
     */
    protected $table = 'items';

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'name',
        'sku',
        'description',
        'base_price',
        'unit',
        'category_id',
        'type',
        'status',
        'is_available',
        'track_inventory',
        'current_stock',
        'low_stock_threshold',
        'images',
        'metadata',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'category_id' => 'integer',
        'base_price' => 'float',
        'is_available' => 'boolean',
        'track_inventory' => 'boolean',
        'current_stock' => 'integer',
        'low_stock_threshold' => 'integer',
        'images' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Item types
     */
    public const TYPE_SIMPLE = 'simple';
    public const TYPE_VARIANT = 'variant';
    public const TYPE_COMPOUND = 'compound';

    /**
     * Item statuses
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_DISCONTINUED = 'discontinued';

    /**
     * Get variants (internal use only)
     */
    public function variants()
    {
        return $this->hasMany(ItemVariant::class);
    }

    /**
     * Get modifier groups (internal use only)
     */
    public function modifierGroups()
    {
        return $this->belongsToMany(ItemModifierGroup::class, 'item_modifier_group_items')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('pivot_sort_order');
    }

    /**
     * Get pricing (internal use only)
     */
    public function pricing()
    {
        return $this->hasMany(ItemPricing::class);
    }

    /**
     * Get ingredients for compound items (internal use only)
     */
    public function ingredients()
    {
        return $this->belongsToMany(Item::class, 'item_ingredients', 'item_id', 'ingredient_id')
            ->withPivot('quantity', 'unit')
            ->withTimestamps();
    }

    /**
     * Check if item is available for purchase
     */
    public function isAvailableForPurchase(): bool
    {
        if (!$this->is_available || $this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->track_inventory && $this->current_stock <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Decrement stock
     */
    public function decrementStock(int $quantity): bool
    {
        if (!$this->track_inventory) {
            return true;
        }

        if ($this->current_stock < $quantity) {
            return false;
        }

        $this->decrement('current_stock', $quantity);
        return true;
    }

    /**
     * Increment stock
     */
    public function incrementStock(int $quantity): void
    {
        if ($this->track_inventory) {
            $this->increment('current_stock', $quantity);
        }
    }
}