<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the main feature flags configuration.
    | Each module can also define its own features in {module}/config/features.php
    |
    | Feature structure:
    | - enabled: boolean or env() for simple on/off
    | - description: what the feature does
    | - rollout: advanced rollout strategies
    |   - type: percentage, locations, users, gradual
    |   - value: rollout configuration
    |
    */

    'core' => [
        'api_versioning' => [
            'enabled' => env('FEATURE_CORE_API_VERSIONING', true),
            'description' => 'Enable API versioning support',
        ],
        'advanced_logging' => [
            'enabled' => env('FEATURE_CORE_ADVANCED_LOGGING', false),
            'description' => 'Enable detailed logging for debugging',
        ],
        'maintenance_mode' => [
            'enabled' => env('FEATURE_CORE_MAINTENANCE_MODE', false),
            'description' => 'Enable maintenance mode',
        ],
    ],

    'ui' => [
        'dark_mode' => [
            'enabled' => env('FEATURE_UI_DARK_MODE', true),
            'description' => 'Enable dark mode theme support',
        ],
        'new_navigation' => [
            'enabled' => env('FEATURE_UI_NEW_NAVIGATION', false),
            'description' => 'New navigation design',
            'rollout' => [
                'type' => 'percentage',
                'value' => 25, // 25% of users
            ],
        ],
    ],
];