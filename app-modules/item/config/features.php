<?php

return [
    'item' => [
        'variants' => [
            'enabled' => env('FEATURE_ITEM_VARIANTS', true),
            'description' => 'Enable product variants (size, color, etc.)',
            'rollout' => [
                'type' => 'boolean',
            ],
        ],
        'modifiers' => [
            'enabled' => env('FEATURE_ITEM_MODIFIERS', true),
            'description' => 'Enable item modifiers and modifier groups',
            'rollout' => [
                'type' => 'boolean',
            ],
        ],
        'recipes' => [
            'enabled' => env('FEATURE_ITEM_RECIPES', false),
            'description' => 'Enable recipe/ingredient tracking for compound items',
            'rollout' => [
                'type' => 'boolean',
            ],
        ],
        'location_pricing' => [
            'enabled' => env('FEATURE_ITEM_LOCATION_PRICING', true),
            'description' => 'Enable location-specific pricing',
            'rollout' => [
                'type' => 'boolean',
            ],
        ],
        'inventory_tracking' => [
            'enabled' => env('FEATURE_ITEM_INVENTORY_TRACKING', true),
            'description' => 'Enable inventory tracking and stock management',
            'rollout' => [
                'type' => 'boolean',
            ],
        ],
        'import_export' => [
            'enabled' => env('FEATURE_ITEM_IMPORT_EXPORT', false),
            'description' => 'Enable bulk import/export functionality',
            'rollout' => [
                'type' => 'percentage',
                'value' => 0, // Start with 0% rollout
            ],
        ],
        'advanced_search' => [
            'enabled' => env('FEATURE_ITEM_ADVANCED_SEARCH', true),
            'description' => 'Enable advanced search with filters',
            'rollout' => [
                'type' => 'boolean',
            ],
        ],
    ],
];