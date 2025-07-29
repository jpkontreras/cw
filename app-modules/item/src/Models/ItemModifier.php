<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;

class ItemModifier extends Model
{
    protected $fillable = [
        'modifier_group_id',
        'name',
        'price_adjustment',
        'max_quantity',
        'is_default',
        'is_active',
        'sort_order',
    ];
    
    protected $casts = [
        'modifier_group_id' => 'integer',
        'price_adjustment' => 'decimal:2',
        'max_quantity' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    protected $attributes = [
        'price_adjustment' => 0,
        'max_quantity' => 1,
        'is_default' => false,
        'is_active' => true,
        'sort_order' => 0,
    ];
    
    /**
     * Scope for active modifiers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope for default modifiers
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}