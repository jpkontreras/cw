<?php

declare(strict_types=1);

namespace App\Core\Data;

use Spatie\LaravelData\Data;

/**
 * DTO for filter preset configuration
 */
class FilterPresetData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly array $filters = [],
        public readonly bool $isDefault = false,
        public readonly ?string $icon = null,
    ) {}
}