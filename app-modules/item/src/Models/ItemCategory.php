<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    protected $fillable = [
        'item_id',
        'category_id',
        'is_primary',
    ];
    
    protected $casts = [
        'item_id' => 'integer',
        'category_id' => 'integer',
        'is_primary' => 'boolean',
    ];
    
    protected $attributes = [
        'is_primary' => false,
    ];
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        // Ensure only one primary category per item
        static::saving(function ($itemCategory) {
            if ($itemCategory->is_primary) {
                static::where('item_id', $itemCategory->item_id)
                    ->where('id', '!=', $itemCategory->id)
                    ->update(['is_primary' => false]);
            }
        });
    }
}