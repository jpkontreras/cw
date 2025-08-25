<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Enums;

enum TaxonomyType: string
{
    // Product Organization
    case ITEM_CATEGORY = 'item_category';
    case MENU_SECTION = 'menu_section';
    case INGREDIENT_TYPE = 'ingredient_type';
    
    // Attributes
    case DIETARY_LABEL = 'dietary_label';
    case ALLERGEN = 'allergen';
    case CUISINE_TYPE = 'cuisine_type';
    case PREPARATION_METHOD = 'prep_method';
    case SPICE_LEVEL = 'spice_level';
    
    // Business
    case CUSTOMER_SEGMENT = 'customer_segment';
    case PRICE_RANGE = 'price_range';
    case LOCATION_ZONE = 'location_zone';
    case PROMOTION_TYPE = 'promotion_type';
    
    // Tags
    case GENERAL_TAG = 'general_tag';
    case SEASONAL_TAG = 'seasonal_tag';
    case FEATURE_TAG = 'feature_tag';
    
    public function label(): string
    {
        return match ($this) {
            self::ITEM_CATEGORY => 'Item Categories',
            self::MENU_SECTION => 'Menu Sections',
            self::INGREDIENT_TYPE => 'Ingredient Types',
            self::DIETARY_LABEL => 'Dietary Labels',
            self::ALLERGEN => 'Allergens',
            self::CUISINE_TYPE => 'Cuisine Types',
            self::PREPARATION_METHOD => 'Preparation Methods',
            self::SPICE_LEVEL => 'Spice Levels',
            self::CUSTOMER_SEGMENT => 'Customer Segments',
            self::PRICE_RANGE => 'Price Ranges',
            self::LOCATION_ZONE => 'Location Zones',
            self::PROMOTION_TYPE => 'Promotion Types',
            self::GENERAL_TAG => 'General Tags',
            self::SEASONAL_TAG => 'Seasonal Tags',
            self::FEATURE_TAG => 'Feature Tags',
        };
    }
    
    public function description(): string
    {
        return match ($this) {
            self::ITEM_CATEGORY => 'Hierarchical categories for organizing items',
            self::MENU_SECTION => 'Sections for organizing menu display',
            self::INGREDIENT_TYPE => 'Types of ingredients and raw materials',
            self::DIETARY_LABEL => 'Dietary restrictions and preferences',
            self::ALLERGEN => 'Food allergen information',
            self::CUISINE_TYPE => 'Types of cuisine offered',
            self::PREPARATION_METHOD => 'How items are prepared',
            self::SPICE_LEVEL => 'Spiciness levels for items',
            self::CUSTOMER_SEGMENT => 'Customer categorization for targeting',
            self::PRICE_RANGE => 'Price tier classifications',
            self::LOCATION_ZONE => 'Geographic zones for locations',
            self::PROMOTION_TYPE => 'Types of promotional offers',
            self::GENERAL_TAG => 'General purpose tags',
            self::SEASONAL_TAG => 'Seasonal and time-based tags',
            self::FEATURE_TAG => 'Featured item markers',
        };
    }
    
    public function isHierarchical(): bool
    {
        return match ($this) {
            self::ITEM_CATEGORY,
            self::MENU_SECTION,
            self::INGREDIENT_TYPE,
            self::LOCATION_ZONE => true,
            default => false,
        };
    }
    
    public function allowsMultiple(): bool
    {
        return match ($this) {
            self::ITEM_CATEGORY,
            self::MENU_SECTION => false, // Only one primary category/section
            default => true,
        };
    }
    
    public function requiresLocation(): bool
    {
        return match ($this) {
            self::LOCATION_ZONE,
            self::MENU_SECTION => true,
            default => false,
        };
    }
}