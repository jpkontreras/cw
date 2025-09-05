<?php

declare(strict_types=1);

namespace Colame\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Colame\Menu\Data\MenuItemMetadataData;
use Colame\Menu\Data\MenuItemModifiersConfigData;
use Colame\Menu\Data\DietaryLabelsData;
use Colame\Menu\Data\AllergenInfoData;
use Colame\Menu\Data\NutritionalInfoData;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'menu_section_id',
        'item_id',
        'display_name',
        'display_description',
        'price',
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
        'price' => 'integer',  // Store as integer (minor units)
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_recommended' => 'boolean',
        'is_new' => 'boolean',
        'is_seasonal' => 'boolean',
        'sort_order' => 'integer',
        'preparation_time_override' => 'integer',
        'available_modifiers' => MenuItemModifiersConfigData::class,
        'dietary_labels' => DietaryLabelsData::class,
        'allergen_info' => AllergenInfoData::class,
        'calorie_count' => 'integer',
        'nutritional_info' => NutritionalInfoData::class,
        'metadata' => MenuItemMetadataData::class,
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
     * Note: Direct item relationship removed to maintain module boundaries.
     * Use MenuService methods to fetch item details through proper interfaces.
     * 
     * To get item details, use:
     * $menuService->getMenuItemWithDetails($menuItemId)
     */
    // Relationship removed - use service layer instead
    
    /**
     * Display name accessor - returns override or null
     * Item data should be fetched through MenuService when needed
     */
    public function getDisplayNameAttribute($value)
    {
        return $value;
    }
    
    /**
     * Display description accessor - returns override or null
     * Item data should be fetched through MenuService when needed
     */
    public function getDisplayDescriptionAttribute($value)
    {
        return $value;
    }
    
    /**
     * Price accessor - returns override or null
     * Base item price should be fetched through MenuService when needed
     */
    public function getPriceAttribute()
    {
        return $this->price_override;
    }
    
    /**
     * Preparation time accessor - returns override or null
     * Base preparation time should be fetched through MenuService when needed
     */
    public function getPreparationTimeAttribute()
    {
        return $this->preparation_time_override;
    }
    
    public function isAvailable(): bool
    {
        return $this->is_active && $this->menu->isAvailable() && $this->section->isAvailable();
    }
}