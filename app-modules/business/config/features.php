<?php

return [
    'business' => [
        'enabled' => true,
        
        // Multi-tenancy settings
        'multi_tenancy' => [
            'enabled' => true,
            'enforce_business_context' => false, // Phase 1: Don't enforce yet
            'auto_switch_business' => true, // Auto-switch to first business if only one
        ],
        
        // Subscription settings
        'subscriptions' => [
            'enabled' => true,
            'trial_days' => 14,
            'enforce_limits' => true,
            'allow_overage' => false,
        ],
        
        // Default limits for new businesses
        'default_limits' => [
            'basic' => [
                'locations' => 1,
                'users' => 5,
                'items' => 100,
                'orders_per_month' => 1000,
            ],
            'pro' => [
                'locations' => 5,
                'users' => 25,
                'items' => 1000,
                'orders_per_month' => 10000,
            ],
            'enterprise' => [
                'locations' => null, // unlimited
                'users' => null,
                'items' => null,
                'orders_per_month' => null,
            ],
        ],
        
        // Features by subscription tier
        'tier_features' => [
            'basic' => [
                'basic_reporting',
                'inventory_management',
                'order_management',
                'staff_management',
            ],
            'pro' => [
                'basic_reporting',
                'advanced_reporting',
                'inventory_management',
                'order_management',
                'staff_management',
                'multi_location',
                'api_access',
                'custom_branding',
            ],
            'enterprise' => [
                'basic_reporting',
                'advanced_reporting',
                'custom_reporting',
                'inventory_management',
                'order_management',
                'staff_management',
                'multi_location',
                'api_access',
                'custom_branding',
                'white_label',
                'dedicated_support',
                'custom_integrations',
            ],
        ],
        
        // Invitation settings
        'invitations' => [
            'expiry_days' => 7,
            'allow_self_registration' => false,
            'require_approval' => false,
        ],
        
        // Demo business settings
        'demo' => [
            'enabled' => true,
            'auto_cleanup_days' => 30,
            'data_limit_percentage' => 10, // Limit demo businesses to 10% of normal limits
        ],
    ],
];