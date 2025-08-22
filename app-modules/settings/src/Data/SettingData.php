<?php

declare(strict_types=1);

namespace Colame\Settings\Data;

use App\Core\Data\BaseData;
use Colame\Settings\Enums\SettingCategory;
use Colame\Settings\Enums\SettingType;
use Colame\Settings\Models\Setting;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SettingData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        #[Required] public readonly string $key,
        public readonly mixed $value,
        #[Required, WithCast(EnumCast::class)] public readonly SettingType $type,
        #[Required, WithCast(EnumCast::class)] public readonly SettingCategory $category,
        public readonly string $label,
        public readonly ?string $description,
        public readonly ?string $group,
        public readonly ?array $options,
        public readonly ?array $validation,
        public readonly mixed $defaultValue,
        public readonly bool $isRequired,
        public readonly bool $isPublic,
        public readonly bool $isEncrypted,
        public readonly ?int $sortOrder,
        public readonly ?array $metadata,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}

    #[Computed]
    public function displayValue(): string
    {
        if ($this->isEncrypted && $this->value !== null) {
            return '••••••••';
        }

        if ($this->value === null) {
            return '';
        }

        return match ($this->type) {
            SettingType::BOOLEAN => $this->value ? 'Yes' : 'No',
            SettingType::JSON, SettingType::ARRAY => json_encode($this->value),
            SettingType::DATE => $this->value instanceof \DateTimeInterface 
                ? $this->value->format('Y-m-d') 
                : $this->value,
            SettingType::DATETIME => $this->value instanceof \DateTimeInterface 
                ? $this->value->format('Y-m-d H:i:s') 
                : $this->value,
            SettingType::TIME => $this->value instanceof \DateTimeInterface 
                ? $this->value->format('H:i:s') 
                : $this->value,
            default => (string) $this->value,
        };
    }

    #[Computed]
    public function categoryLabel(): string
    {
        return $this->category->label();
    }

    #[Computed]
    public function inputType(): string
    {
        return $this->type->inputType();
    }

    #[Computed]
    public function hasOptions(): bool
    {
        return $this->type->requiresOptions() && !empty($this->options);
    }

    #[Computed]
    public function isConfigured(): bool
    {
        return $this->value !== null && $this->value !== '' && $this->value !== [];
    }

    /**
     * Get typed value based on type field
     */
    public function getTypedValue(): mixed
    {
        if ($this->value === null) {
            return $this->defaultValue;
        }

        return match ($this->type) {
            SettingType::BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            SettingType::INTEGER => (int) $this->value,
            SettingType::FLOAT => (float) $this->value,
            SettingType::JSON, SettingType::ARRAY => is_string($this->value) 
                ? json_decode($this->value, true) 
                : $this->value,
            SettingType::DATE, SettingType::DATETIME, SettingType::TIME => $this->value instanceof \DateTimeInterface 
                ? $this->value 
                : new \DateTime($this->value),
            default => $this->value,
        };
    }

    public static function fromModel(Setting $setting): self
    {
        return new self(
            id: $setting->id,
            key: $setting->key,
            value: $setting->value,
            type: $setting->type,
            category: $setting->category,
            label: $setting->label,
            description: $setting->description,
            group: $setting->group,
            options: $setting->options,
            validation: $setting->validation,
            defaultValue: $setting->default_value,
            isRequired: $setting->is_required,
            isPublic: $setting->is_public,
            isEncrypted: $setting->is_encrypted,
            sortOrder: $setting->sort_order,
            metadata: $setting->metadata,
            createdAt: $setting->created_at,
            updatedAt: $setting->updated_at,
        );
    }
}