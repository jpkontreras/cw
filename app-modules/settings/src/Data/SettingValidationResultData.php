<?php

declare(strict_types=1);

namespace Colame\Settings\Data;

use App\Core\Data\BaseData;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SettingValidationResultData extends BaseData
{
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors,
        public readonly array $warnings,
        public readonly array $validatedSettings,
    ) {}

    public static function success(array $validatedSettings): self
    {
        return new self(
            isValid: true,
            errors: [],
            warnings: [],
            validatedSettings: $validatedSettings,
        );
    }

    public static function failure(array $errors, array $warnings = []): self
    {
        return new self(
            isValid: false,
            errors: $errors,
            warnings: $warnings,
            validatedSettings: [],
        );
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function getErrorsForKey(string $key): array
    {
        return $this->errors[$key] ?? [];
    }

    public function getWarningsForKey(string $key): array
    {
        return $this->warnings[$key] ?? [];
    }
}