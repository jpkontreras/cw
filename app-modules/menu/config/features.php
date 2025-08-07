<?php

return [
    /**
     * Menu Module Feature Flags
     * These flags control various features of the menu module
     */
    
    // Core menu features
    'menu.enabled' => env('FEATURE_MENU_ENABLED', true),
    'menu.versioning' => env('FEATURE_MENU_VERSIONING', true),
    'menu.multi_location' => env('FEATURE_MENU_MULTI_LOCATION', true),
    'menu.scheduling' => env('FEATURE_MENU_SCHEDULING', true),
    'menu.templates' => env('FEATURE_MENU_TEMPLATES', false),
    
    // Advanced features
    'menu.analytics' => env('FEATURE_MENU_ANALYTICS', false),
    'menu.nutritional_info' => env('FEATURE_MENU_NUTRITIONAL_INFO', true),
    'menu.dietary_labels' => env('FEATURE_MENU_DIETARY_LABELS', true),
    'menu.allergen_info' => env('FEATURE_MENU_ALLERGEN_INFO', true),
    'menu.seasonal_items' => env('FEATURE_MENU_SEASONAL_ITEMS', true),
    
    // Display features
    'menu.featured_items' => env('FEATURE_MENU_FEATURED_ITEMS', true),
    'menu.recommended_items' => env('FEATURE_MENU_RECOMMENDED_ITEMS', true),
    'menu.item_badges' => env('FEATURE_MENU_ITEM_BADGES', true),
    'menu.custom_images' => env('FEATURE_MENU_CUSTOM_IMAGES', true),
    
    // Availability features
    'menu.time_based_availability' => env('FEATURE_MENU_TIME_BASED_AVAILABILITY', true),
    'menu.day_based_availability' => env('FEATURE_MENU_DAY_BASED_AVAILABILITY', true),
    'menu.capacity_based_availability' => env('FEATURE_MENU_CAPACITY_BASED_AVAILABILITY', false),
    
    // Import/Export features
    'menu.export_pdf' => env('FEATURE_MENU_EXPORT_PDF', false),
    'menu.export_csv' => env('FEATURE_MENU_EXPORT_CSV', true),
    'menu.export_json' => env('FEATURE_MENU_EXPORT_JSON', true),
    'menu.import' => env('FEATURE_MENU_IMPORT', false),
    
    // Integration features
    'menu.qr_codes' => env('FEATURE_MENU_QR_CODES', false),
    'menu.digital_signage' => env('FEATURE_MENU_DIGITAL_SIGNAGE', false),
    'menu.third_party_sync' => env('FEATURE_MENU_THIRD_PARTY_SYNC', false),
];