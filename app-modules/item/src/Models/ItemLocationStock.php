<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;

class ItemLocationStock extends Model
{
    protected $table = 'item_location_stock';
    
    protected $fillable = [
        'item_id',
        'item_variant_id',
        'location_id',
        'quantity',
        'reserved_quantity',
        'reorder_point',
        'reorder_quantity',
    ];
    
    protected $casts = [
        'item_id' => 'integer',
        'item_variant_id' => 'integer',
        'location_id' => 'integer',
        'quantity' => 'decimal:3',
        'reserved_quantity' => 'decimal:3',
        'reorder_point' => 'decimal:3',
        'reorder_quantity' => 'decimal:3',
    ];
    
    protected $attributes = [
        'quantity' => 0,
        'reserved_quantity' => 0,
        'reorder_point' => 0,
        'reorder_quantity' => 0,
    ];
    
    protected $appends = [
        'available_quantity'
    ];
    
    /**
     * Get available quantity (calculated attribute)
     */
    public function getAvailableQuantityAttribute()
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }
    
    /**
     * Scope for items needing reorder
     */
    public function scopeNeedsReorder($query)
    {
        return $query->whereRaw('quantity <= reorder_point');
    }
    
    /**
     * Scope for out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }
    
    /**
     * Scope for items with reserved stock
     */
    public function scopeHasReservedStock($query)
    {
        return $query->where('reserved_quantity', '>', 0);
    }
}