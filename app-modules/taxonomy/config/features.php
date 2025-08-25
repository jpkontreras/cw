<?php

return [
    'taxonomy' => [
        'enabled' => env('FEATURE_TAXONOMY_ENABLED', true),
        'hierarchical_categories' => env('FEATURE_TAXONOMY_HIERARCHICAL', true),
        'multi_location' => env('FEATURE_TAXONOMY_MULTI_LOCATION', true),
        'custom_attributes' => env('FEATURE_TAXONOMY_CUSTOM_ATTRIBUTES', true),
        'bulk_operations' => env('FEATURE_TAXONOMY_BULK_OPS', true),
        'import_export' => env('FEATURE_TAXONOMY_IMPORT_EXPORT', false),
        'api_enabled' => env('FEATURE_TAXONOMY_API', true),
        'cache_enabled' => env('FEATURE_TAXONOMY_CACHE', true),
        'cache_ttl' => env('TAXONOMY_CACHE_TTL', 3600),
        'max_depth' => env('TAXONOMY_MAX_DEPTH', 5),
        'max_attributes' => env('TAXONOMY_MAX_ATTRIBUTES', 20),
    ],
];