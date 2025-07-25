<?php

declare(strict_types=1);

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Item variant model
 */
class ItemVariant extends Model
{
    use HasFactory;

    /**
     * The table associated with the model
     */
    protected $table = 'item_variants';

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'item_id',
        'name',
        'sku',
        'attribute_type',
        'attribute_value',
        'price_adjustment',
        'weight',
        'is_available',
        'is_default',
        'current_stock',
        'images',
        'metadata',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'item_id' => 'integer',
        'price_adjustment' => 'float',
        'weight' => 'float',
        'is_available' => 'boolean',
        'is_default' => 'boolean',
        'current_stock' => 'integer',
        'images' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the item (internal use only)
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Check if variant is available for purchase
     */
    public function isAvailableForPurchase(): bool
    {
        if (!$this->is_available) {
            return false;
        }

        // If stock tracking is enabled at variant level
        if ($this->current_stock !== null && $this->current_stock <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Get the effective price for this variant
     */
    public function getEffectivePrice(float $basePrice): float
    {
        return $basePrice + $this->price_adjustment;
    }
}