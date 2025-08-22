<?php

return [
    'offer' => [
        'management' => env('FEATURE_OFFER_MANAGEMENT', true),
        'advanced_scheduling' => env('FEATURE_OFFER_ADVANCED_SCHEDULING', true),
        'analytics' => env('FEATURE_OFFER_ANALYTICS', true),
        'auto_application' => env('FEATURE_OFFER_AUTO_APPLICATION', true),
        'stacking' => env('FEATURE_OFFER_STACKING', true),
        'code_redemption' => env('FEATURE_OFFER_CODE_REDEMPTION', true),
        'customer_segments' => env('FEATURE_OFFER_CUSTOMER_SEGMENTS', true),
        'location_specific' => env('FEATURE_OFFER_LOCATION_SPECIFIC', true),
        'bulk_operations' => env('FEATURE_OFFER_BULK_OPERATIONS', true),
        'api_access' => env('FEATURE_OFFER_API_ACCESS', true),
    ],
];