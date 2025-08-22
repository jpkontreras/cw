<?php

declare(strict_types=1);

namespace Colame\Settings\Data;

use App\Core\Data\BaseData;
use Colame\Settings\Enums\SettingCategory;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SettingGroupData extends BaseData
{
    public function __construct(
        #[WithCast(EnumCast::class)] public readonly SettingCategory $category,
        public readonly string $label,
        public readonly string $description,
        public readonly string $icon,
        #[DataCollectionOf(SettingData::class)] public readonly DataCollection $settings,
        public readonly int $totalSettings,
        public readonly int $configuredSettings,
        public readonly bool $isComplete,
    ) {}

    public static function fromCategoryAndSettings(
        SettingCategory $category,
        DataCollection $settings
    ): self {
        $configuredCount = $settings->filter(fn (SettingData $setting) => $setting->isConfigured)->count();
        $requiredSettings = $settings->filter(fn (SettingData $setting) => $setting->isRequired);
        $requiredConfigured = $requiredSettings->filter(fn (SettingData $setting) => $setting->isConfigured);

        return new self(
            category: $category,
            label: $category->label(),
            description: $category->description(),
            icon: $category->icon(),
            settings: $settings,
            totalSettings: $settings->count(),
            configuredSettings: $configuredCount,
            isComplete: $requiredSettings->isEmpty() || $requiredSettings->count() === $requiredConfigured->count(),
        );
    }
}