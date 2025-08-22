<?php

return [
    'settings' => [
        'enabled' => true,
        
        // Enable/disable specific setting categories
        'categories' => [
            'organization' => true,
            'order' => true,
            'receipt' => true,
            'inventory' => true,
            'notification' => true,
            'integration' => true,
            'payment' => true,
            'tax' => true,
            'localization' => true,
            'printing' => true,
            'security' => true,
            'appearance' => true,
        ],
        
        // Cache settings
        'cache' => [
            'enabled' => true,
            'ttl' => 3600, // 1 hour
            'prefix' => 'settings:',
        ],
        
        // Security settings
        'security' => [
            'encrypt_sensitive' => true,
            'audit_changes' => true,
        ],
        
        // API settings
        'api' => [
            'enabled' => true,
            'rate_limit' => 60, // requests per minute
        ],
        
        // Export/Import settings
        'export' => [
            'enabled' => true,
            'exclude_encrypted' => true,
        ],
        
        'import' => [
            'enabled' => true,
            'validate_before_import' => true,
        ],
    ],
];