<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Discovery Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AI-powered food discovery system
    |
    */

    // AI Model Configuration
    'ai' => [
        // Use 'gemini-2.0-flash-exp' for Gemini, 'gpt-4o' for OpenAI, or 'grok-beta' for X.AI
        'model' => env('AI_MODEL', 'gemini-2.0-flash-exp'),
        'temperature' => 0.7,
        'max_tokens' => 2000,
        'stream_enabled' => true,
    ],

    // Similarity Matching Configuration
    'similarity' => [
        'threshold' => 80,
        'embedding_model' => 'text-embedding-004', // Gemini's embedding model
        'cache_ttl_days' => 30,
    ],

    // Session Configuration
    'session' => [
        'max_messages' => 50,
        'inactive_timeout_minutes' => 30,
        'auto_complete_after_phases' => 5,
    ],

    // Regional Configuration - will be overridden by location context
    'regional' => [
        'default_location' => 'Chile',
        'default_currency' => 'CLP',
        'default_language' => 'en',
        'price_tiers' => [
            'low' => [
                'main_dish' => 4000,
                'appetizer' => 2500,
                'beverage' => 1500,
                'dessert' => 2000,
            ],
            'medium' => [
                'main_dish' => 7000,
                'appetizer' => 4000,
                'beverage' => 2500,
                'dessert' => 3500,
            ],
            'high' => [
                'main_dish' => 12000,
                'appetizer' => 6000,
                'beverage' => 4000,
                'dessert' => 5000,
            ],
        ],
    ],

    // Chilean Food Patterns
    'chilean_foods' => [
        'completo' => [
            'variants' => ['Normal', 'Italiano', 'Especial', 'Dinámico'],
            'base_price' => 3500,
        ],
        'empanada' => [
            'variants' => ['Pino', 'Queso', 'Mariscos', 'Napolitana'],
            'base_price' => 3000,
        ],
        'churrasco' => [
            'variants' => ['Italiano', 'Chacarero', 'Barros Luco', 'Barros Jarpa'],
            'base_price' => 5500,
        ],
        'sopaipilla' => [
            'variants' => ['Simple', 'Con Pebre', 'Pasada'],
            'base_price' => 500,
        ],
    ],

    // Feature Flags
    'features' => [
        'auto_suggestions' => true,
        'similarity_cache' => true,
        'pattern_learning' => true,
        'streaming' => true,
        'embeddings' => true, // Enabled - Gemini supports embeddings
    ],

    // Caching Configuration - use Valkey (Redis) from docker-compose
    'cache' => [
        'driver' => 'redis',
        'ttl' => 3600,
        'prefix' => 'ai_discovery_',
    ],

    // Logging Configuration
    'logging' => [
        'enabled' => true,
        'channel' => 'daily',
        'level' => 'info',
    ],
];