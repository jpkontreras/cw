<?php

declare(strict_types=1);

namespace Colame\Settings\Data;

use App\Core\Data\BaseData;
use Colame\Settings\Enums\SettingCategory;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BulkUpdateSettingData extends BaseData
{
    public function __construct(
        #[Required] public readonly array $settings,
        #[WithCast(EnumCast::class)] public readonly ?SettingCategory $category = null,
        public readonly bool $validateBeforeUpdate = true,
    ) {}

    /**
     * Validate that all required keys are present
     */
    public static function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*' => ['required'],
            'category' => ['nullable', 'string'],
            'validateBeforeUpdate' => ['boolean'],
        ];
    }
}