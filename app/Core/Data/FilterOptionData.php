<?php

declare(strict_types=1);

namespace App\Core\Data;

use Spatie\LaravelData\Data;

/**
 * DTO for filter option configuration
 */
class FilterOptionData extends Data
{
    public function __construct(
        public readonly string $value,
        public readonly string $label,
        public readonly ?string $icon = null,
        public readonly bool $disabled = false,
        public readonly ?array $metadata = null,
    ) {}
}