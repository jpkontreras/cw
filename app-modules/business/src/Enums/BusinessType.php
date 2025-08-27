<?php

declare(strict_types=1);

namespace Colame\Business\Enums;

enum BusinessType: string
{
    case CORPORATE = 'corporate';
    case FRANCHISE = 'franchise';
    case INDEPENDENT = 'independent';

    public function label(): string
    {
        return match($this) {
            self::CORPORATE => 'Corporate Chain',
            self::FRANCHISE => 'Franchise',
            self::INDEPENDENT => 'Independent',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::CORPORATE => 'Multi-location corporate-owned chain',
            self::FRANCHISE => 'Franchise with multiple franchisees',
            self::INDEPENDENT => 'Independent single or multi-location business',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}