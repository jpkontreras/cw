<?php

declare(strict_types=1);

namespace Colame\Menu\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'menu_section_id',
        'item_id',
        'display_name',
        'display_description',
        'price_override',
        'is_active',
        'is_featured',
        'is_recommended',
        'is_new',
        'is_seasonal',
        'sort_order',
        'preparation_time_override',
        'available_modifiers',
        'dietary_labels',
        'allergen_info',
        'calorie_count',
        'nutritional_info',
        'image_url',
        'metadata',
    ];
    
    protected $casts = [
        'menu_id' => 'integer',
        'menu_section_id' => 'integer',
        'item_id' => 'integer',
        'price_override' => 'float',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_recommended' => 'boolean',
        'is_new' => 'boolean',
        'is_seasonal' => 'boolean',
        'sort_order' => 'integer',
        'preparation_time_override' => 'integer',
        'available_modifiers' => 'array',
        'dietary_labels' => 'array',
        'allergen_info' => 'array',
        'calorie_count' => 'integer',
        'nutritional_info' => 'array',
        'metadata' => 'array',
    ];
    
    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
        'is_recommended' => false,
        'is_new' => false,
        'is_seasonal' => false,
        'sort_order' => 0,
    ];
    
    public const DIETARY_VEGAN = 'vegan';
    public const DIETARY_VEGETARIAN = 'vegetarian';
    public const DIETARY_GLUTEN_FREE = 'gluten_free';
    public const DIETARY_DAIRY_FREE = 'dairy_free';
    public const DIETARY_NUT_FREE = 'nut_free';
    public const DIETARY_HALAL = 'halal';
    public const DIETARY_KOSHER = 'kosher';
    public const DIETARY_ORGANIC = 'organic';
    public const DIETARY_LOW_CARB = 'low_carb';
    public const DIETARY_KETO = 'keto';
    
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
    
    public function section()
    {
        return $this->belongsTo(MenuSection::class, 'menu_section_id');
    }
    
    public function modifiers()
    {
        return $this->hasMany(MenuItemModifier::class);
    }
    
    /**
     * Relationship to the actual item in the items table
     */
    public function item()
    {
        return $this->belongsTo(\Colame\Item\Models\Item::class, 'item_id');
    }
    
    public function getDisplayNameAttribute($value)
    {
        return $value ?: $this->getItemName();
    }
    
    public function getDisplayDescriptionAttribute($value)
    {
        return $value ?: $this->getItemDescription();
    }
    
    public function getPriceAttribute()
    {
        return $this->price_override ?: $this->getItemPrice();
    }
    
    public function getPreparationTimeAttribute()
    {
        return $this->preparation_time_override ?: $this->getItemPreparationTime();
    }
    
    /**
     * These methods will be replaced with actual item data retrieval
     * when integrating with the item module
     */
    protected function getItemName(): ?string
    {
        return $this->item ? $this->item->name : null;
    }
    
    protected function getItemDescription(): ?string
    {
        return $this->item ? $this->item->description : null;
    }
    
    protected function getItemPrice(): ?float
    {
        return $this->item ? (float) $this->item->price : null;
    }
    
    protected function getItemPreparationTime(): ?int
    {
        return $this->item ? $this->item->preparation_time : null;
    }
    
    public function isAvailable(): bool
    {
        return $this->is_active && $this->menu->isAvailable() && $this->section->isAvailable();
    }
}