<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;

class ItemVariant extends Model
{
    protected $fillable = [
        'item_id',
        'name',
        'sku',
        'price_adjustment',
        'size_multiplier',
        'is_default',
        'is_active',
        'stock_quantity',
        'sort_order',
    ];
    
    protected $casts = [
        'item_id' => 'integer',
        'price_adjustment' => 'integer',  // Store as integer (minor units)
        'size_multiplier' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'stock_quantity' => 'integer',
        'sort_order' => 'integer',
    ];
    
    protected $attributes = [
        'price_adjustment' => 0,
        'size_multiplier' => 1,
        'is_default' => false,
        'is_active' => true,
        'stock_quantity' => 0,
        'sort_order' => 0,
    ];
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        // Ensure only one default variant per item
        static::saving(function ($variant) {
            if ($variant->is_default) {
                static::where('item_id', $variant->item_id)
                    ->where('id', '!=', $variant->id)
                    ->update(['is_default' => false]);
            }
        });
    }
    
    /**
     * Scope for active variants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope for default variant
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}