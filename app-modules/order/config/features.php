<?php

return [
    'order' => [
        'split_bill' => [
            'enabled' => env('FEATURE_ORDER_SPLIT_BILL', false),
            'description' => 'Allow splitting bills between multiple payments',
            'rollout' => [
                'type' => 'percentage',
                'value' => 0, // Start with 0% rollout
            ],
        ],
        'order_notes' => [
            'enabled' => env('FEATURE_ORDER_NOTES', true),
            'description' => 'Allow adding notes to orders and order items',
        ],
        'quick_order' => [
            'enabled' => env('FEATURE_ORDER_QUICK_ORDER', false),
            'description' => 'Quick order creation for frequent items',
            'rollout' => [
                'type' => 'locations',
                'locations' => [], // Add location IDs here
            ],
        ],
        'order_modifications' => [
            'enabled' => env('FEATURE_ORDER_MODIFICATIONS', true),
            'description' => 'Allow modifying orders after placement',
        ],
        'kitchen_display' => [
            'enabled' => env('FEATURE_ORDER_KITCHEN_DISPLAY', true),
            'description' => 'Kitchen display system for order management',
        ],
        'order_tracking' => [
            'enabled' => env('FEATURE_ORDER_TRACKING', false),
            'description' => 'Real-time order tracking for customers',
            'rollout' => [
                'type' => 'percentage',
                'value' => 25,
            ],
        ],
        'bulk_orders' => [
            'enabled' => env('FEATURE_ORDER_BULK_ORDERS', false),
            'description' => 'Support for bulk order creation',
        ],
        'order_templates' => [
            'enabled' => env('FEATURE_ORDER_TEMPLATES', false),
            'description' => 'Save and reuse order templates',
        ],
        'advanced_analytics' => [
            'enabled' => env('FEATURE_ORDER_ADVANCED_ANALYTICS', false),
            'description' => 'Advanced order analytics and reporting',
            'rollout' => [
                'type' => 'users',
                'users' => [], // Add user IDs here
            ],
        ],
        'order_queue_management' => [
            'enabled' => env('FEATURE_ORDER_QUEUE_MANAGEMENT', false),
            'description' => 'Advanced queue management for kitchen operations',
        ],
    ],
];