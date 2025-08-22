<?php

return [
    'enabled' => env('ONBOARDING_ENABLED', true),
    
    // Auto redirect to onboarding if not completed
    'auto_redirect' => env('ONBOARDING_AUTO_REDIRECT', false),
    
    'steps' => [
        'account' => [
            'enabled' => true,
            'required' => true,
            'order' => 1,
        ],
        'business' => [
            'enabled' => true,
            'required' => true,
            'order' => 2,
        ],
        'location' => [
            'enabled' => true,
            'required' => true,
            'order' => 3,
        ],
        'configuration' => [
            'enabled' => true,
            'required' => true,
            'order' => 4,
        ],
    ],
    
    'skip_allowed' => env('ONBOARDING_SKIP_ALLOWED', false),
    'redirect_after_complete' => '/dashboard',
    'redirect_if_incomplete' => '/onboarding',
];