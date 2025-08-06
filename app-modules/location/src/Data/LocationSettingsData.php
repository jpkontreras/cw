<?php

declare(strict_types=1);

namespace Colame\Location\Data;

use App\Core\Data\BaseData;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class LocationSettingsData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $locationId,
        public readonly string $key,
        public readonly ?string $value,
        public readonly string $type,
        public readonly ?string $description,
        public readonly bool $isEncrypted,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}

    /**
     * Get typed value based on type field
     */
    public function getTypedValue()
    {
        if ($this->value === null) {
            return null;
        }

        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'float', 'decimal' => (float) $this->value,
            'json', 'array' => json_decode($this->value, true),
            'object' => json_decode($this->value),
            default => $this->value,
        };
    }
}