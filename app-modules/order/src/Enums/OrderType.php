<?php

declare(strict_types=1);

namespace Colame\Order\Enums;

enum OrderType: string
{
    case DINE_IN = 'dine_in';
    case TAKEOUT = 'takeout';
    case DELIVERY = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::DINE_IN => 'Dine In',
            self::TAKEOUT => 'Takeout',
            self::DELIVERY => 'Delivery',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DINE_IN => 'utensils',
            self::TAKEOUT => 'shopping-bag',
            self::DELIVERY => 'truck',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DINE_IN => 'blue',
            self::TAKEOUT => 'green',
            self::DELIVERY => 'purple',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DINE_IN => 'Customer dining at the restaurant',
            self::TAKEOUT => 'Customer picking up order',
            self::DELIVERY => 'Order delivered to customer',
        };
    }

    public function requiresTable(): bool
    {
        return $this === self::DINE_IN;
    }

    public function requiresAddress(): bool
    {
        return $this === self::DELIVERY;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'icon' => $type->icon(),
                'color' => $type->color(),
            ],
            self::cases()
        );
    }
}