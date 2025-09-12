<?php

declare(strict_types=1);

namespace Colame\Order\Enums;

enum OrderPlatform: string
{
    case WEB = 'web';
    case MOBILE = 'mobile';
    case KIOSK = 'kiosk';

    public function label(): string
    {
        return match ($this) {
            self::WEB => 'Web',
            self::MOBILE => 'Mobile',
            self::KIOSK => 'Kiosk',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::WEB => 'globe',
            self::MOBILE => 'smartphone',
            self::KIOSK => 'tablet',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::WEB => 'Order placed through web interface',
            self::MOBILE => 'Order placed through mobile app',
            self::KIOSK => 'Order placed through self-service kiosk',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(
            fn (self $platform) => [
                'value' => $platform->value,
                'label' => $platform->label(),
                'icon' => $platform->icon(),
            ],
            self::cases()
        );
    }
}