<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;

class ItemImage extends Model
{
    protected $fillable = [
        'item_id',
        'image_path',
        'thumbnail_path',
        'alt_text',
        'is_primary',
        'sort_order',
    ];
    
    protected $casts = [
        'item_id' => 'integer',
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    protected $attributes = [
        'is_primary' => false,
        'sort_order' => 0,
    ];
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        // Ensure only one primary image per item
        static::saving(function ($image) {
            if ($image->is_primary) {
                static::where('item_id', $image->item_id)
                    ->where('id', '!=', $image->id)
                    ->update(['is_primary' => false]);
            }
        });
    }
    
    /**
     * Scope for primary images
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}