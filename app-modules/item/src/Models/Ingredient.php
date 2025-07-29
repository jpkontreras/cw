<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'unit',
        'cost_per_unit',
        'supplier_id',
        'storage_requirements',
        'shelf_life_days',
        'current_stock',
        'reorder_level',
        'reorder_quantity',
        'is_active',
    ];
    
    protected $casts = [
        'cost_per_unit' => 'decimal:2',
        'supplier_id' => 'integer',
        'shelf_life_days' => 'integer',
        'current_stock' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'reorder_quantity' => 'decimal:3',
        'is_active' => 'boolean',
    ];
    
    protected $attributes = [
        'current_stock' => 0,
        'reorder_level' => 0,
        'reorder_quantity' => 0,
        'is_active' => true,
    ];
    
    /**
     * Scope for active ingredients
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope for ingredients needing reorder
     */
    public function scopeNeedsReorder($query)
    {
        return $query->whereRaw('current_stock <= reorder_level');
    }
    
    /**
     * Scope for out of stock ingredients
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', '<=', 0);
    }
}