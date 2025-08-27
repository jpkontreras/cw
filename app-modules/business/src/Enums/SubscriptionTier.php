<?php

declare(strict_types=1);

namespace Colame\Business\Enums;

enum SubscriptionTier: string
{
    case BASIC = 'basic';
    case PRO = 'pro';
    case ENTERPRISE = 'enterprise';

    public function label(): string
    {
        return match($this) {
            self::BASIC => 'Basic',
            self::PRO => 'Professional',
            self::ENTERPRISE => 'Enterprise',
        };
    }

    public function limits(): array
    {
        return match($this) {
            self::BASIC => [
                'locations' => 1,
                'users' => 5,
                'items' => 100,
                'orders_per_month' => 1000,
                'support' => 'email',
            ],
            self::PRO => [
                'locations' => 5,
                'users' => 25,
                'items' => 1000,
                'orders_per_month' => 10000,
                'support' => 'priority',
            ],
            self::ENTERPRISE => [
                'locations' => null, // unlimited
                'users' => null,
                'items' => null,
                'orders_per_month' => null,
                'support' => 'dedicated',
            ],
        };
    }

    public function features(): array
    {
        return match($this) {
            self::BASIC => [
                'basic_reporting',
                'inventory_management',
                'order_management',
                'staff_management',
            ],
            self::PRO => [
                'basic_reporting',
                'advanced_reporting',
                'inventory_management',
                'order_management',
                'staff_management',
                'multi_location',
                'api_access',
                'custom_branding',
            ],
            self::ENTERPRISE => [
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
        };
    }

    public function monthlyPrice(): float
    {
        return match($this) {
            self::BASIC => 29900, // CLP
            self::PRO => 99900,
            self::ENTERPRISE => 299900,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}