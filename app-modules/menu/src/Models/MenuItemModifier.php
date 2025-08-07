<?php

declare(strict_types=1);

namespace Colame\Menu\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItemModifier extends Model
{
    protected $fillable = [
        'menu_item_id',
        'modifier_group_id',
        'modifier_id',
        'is_required',
        'is_available',
        'price_override',
        'min_selections',
        'max_selections',
        'is_default',
        'sort_order',
        'metadata',
    ];
    
    protected $casts = [
        'menu_item_id' => 'integer',
        'modifier_group_id' => 'integer',
        'modifier_id' => 'integer',
        'is_required' => 'boolean',
        'is_available' => 'boolean',
        'price_override' => 'decimal:2',
        'min_selections' => 'integer',
        'max_selections' => 'integer',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];
    
    protected $attributes = [
        'is_required' => false,
        'is_available' => true,
        'is_default' => false,
        'sort_order' => 0,
    ];
    
    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
    
    public function getPriceAttribute()
    {
        return $this->price_override ?: $this->getModifierPrice();
    }
    
    /**
     * This method will be replaced with actual modifier data retrieval
     * when integrating with the item module
     */
    protected function getModifierPrice(): ?float
    {
        // TODO: Fetch from item module's modifier repository
        return null;
    }
}