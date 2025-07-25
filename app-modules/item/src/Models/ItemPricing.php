<?php

declare(strict_types=1);

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Item pricing model
 */
class ItemPricing extends Model
{
    use HasFactory;

    /**
     * The table associated with the model
     */
    protected $table = 'item_pricing';

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'item_id',
        'location_id',
        'price',
        'cost_price',
        'sale_price',
        'is_on_sale',
        'sale_price_starts_at',
        'sale_price_ends_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'item_id' => 'integer',
        'location_id' => 'integer',
        'price' => 'float',
        'cost_price' => 'float',
        'sale_price' => 'float',
        'is_on_sale' => 'boolean',
        'sale_price_starts_at' => 'datetime',
        'sale_price_ends_at' => 'datetime',
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
     * Get the effective price (considering sale)
     */
    public function getEffectivePrice(): float
    {
        if ($this->is_on_sale && $this->sale_price !== null && $this->isSaleActive()) {
            return $this->sale_price;
        }

        return $this->price;
    }

    /**
     * Check if sale is currently active
     */
    public function isSaleActive(): bool
    {
        if (!$this->is_on_sale) {
            return false;
        }

        $now = now();

        if ($this->sale_price_starts_at && $now->lt($this->sale_price_starts_at)) {
            return false;
        }

        if ($this->sale_price_ends_at && $now->gt($this->sale_price_ends_at)) {
            return false;
        }

        return true;
    }
}