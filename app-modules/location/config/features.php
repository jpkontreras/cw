<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Location Module Features
    |--------------------------------------------------------------------------
    |
    | This file controls feature flags for the location module.
    |
    */
    
    'location' => [
        'enabled' => env('FEATURE_LOCATION_ENABLED', true),
        
        // Multi-location support
        'multi_location' => env('FEATURE_LOCATION_MULTI', true),
        
        // Location hierarchy (parent/child locations)
        'hierarchy' => env('FEATURE_LOCATION_HIERARCHY', true),
        
        // Location-based pricing
        'location_pricing' => env('FEATURE_LOCATION_PRICING', true),
        
        // Location-based inventory
        'location_inventory' => env('FEATURE_LOCATION_INVENTORY', true),
        
        // Operating hours management
        'operating_hours' => env('FEATURE_LOCATION_HOURS', true),
        
        // Delivery radius settings
        'delivery_radius' => env('FEATURE_LOCATION_DELIVERY', true),
        
        // Location settings management
        'custom_settings' => env('FEATURE_LOCATION_SETTINGS', true),
        
        // Location-based access control
        'access_control' => env('FEATURE_LOCATION_ACCESS', true),
        
        // Default location settings
        'defaults' => [
            'timezone' => 'America/Santiago',
            'currency' => 'CLP',
            'tax_rate' => 19.00,
            'country' => 'CL',
        ],
        
        // Available location types
        'types' => [
            'restaurant' => 'Restaurant',
            'kitchen' => 'Kitchen',
            'warehouse' => 'Warehouse',
            'central_kitchen' => 'Central Kitchen',
        ],
        
        // Available capabilities
        'capabilities' => [
            'dine_in' => 'Dine In',
            'takeout' => 'Takeout',
            'delivery' => 'Delivery',
            'catering' => 'Catering',
        ],
        
        // User roles for locations
        'roles' => [
            'manager' => 'Manager',
            'staff' => 'Staff',
            'viewer' => 'Viewer',
        ],
        
        // Location code generation settings
        'code_generation' => [
            'enabled' => env('FEATURE_LOCATION_CODE_AUTO_GENERATE', true),
            'prefix' => env('LOCATION_CODE_PREFIX', 'LOC'),
            'separator' => '-',
            'length' => 4, // Length of random part
            'use_timestamp' => false,
        ],
    ],
    
];