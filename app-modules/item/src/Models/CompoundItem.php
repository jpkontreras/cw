<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;

class CompoundItem extends Model
{
    protected $fillable = [
        'parent_item_id',
        'child_item_id',
        'quantity',
        'is_required',
        'allow_substitution',
        'sort_order',
    ];
    
    protected $casts = [
        'parent_item_id' => 'integer',
        'child_item_id' => 'integer',
        'quantity' => 'integer',
        'is_required' => 'boolean',
        'allow_substitution' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    protected $attributes = [
        'quantity' => 1,
        'is_required' => true,
        'allow_substitution' => false,
        'sort_order' => 0,
    ];
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($compoundItem) {
            // Prevent circular references
            if ($compoundItem->parent_item_id === $compoundItem->child_item_id) {
                throw new \Exception('An item cannot be its own component');
            }
        });
    }
}