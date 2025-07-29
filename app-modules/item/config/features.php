<?php

return [
    'item' => [
        'inventory_tracking' => [
            'enabled' => env('FEATURE_ITEM_INVENTORY_TRACKING', true),
            'description' => 'Enable inventory tracking for items',
        ],
        'dynamic_pricing' => [
            'enabled' => env('FEATURE_ITEM_DYNAMIC_PRICING', true),
            'description' => 'Enable location and time-based dynamic pricing',
        ],
        'modifiers' => [
            'enabled' => env('FEATURE_ITEM_MODIFIERS', true),
            'description' => 'Enable item modifiers and customization options',
        ],
        'compound_items' => [
            'enabled' => env('FEATURE_ITEM_COMPOUND_ITEMS', true),
            'description' => 'Enable compound items (combos and bundles)',
        ],
        'recipes' => [
            'enabled' => env('FEATURE_ITEM_RECIPES', true),
            'description' => 'Enable recipe management and cost tracking',
        ],
        'import_export' => [
            'enabled' => env('FEATURE_ITEM_IMPORT_EXPORT', true),
            'description' => 'Enable bulk import/export functionality',
        ],
        'nutritional_info' => [
            'enabled' => env('FEATURE_ITEM_NUTRITIONAL_INFO', true),
            'description' => 'Enable nutritional information tracking',
        ],
        'allergen_tracking' => [
            'enabled' => env('FEATURE_ITEM_ALLERGEN_TRACKING', true),
            'description' => 'Enable allergen information and warnings',
        ],
    ],
];