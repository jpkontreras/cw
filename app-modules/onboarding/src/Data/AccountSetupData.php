<?php

declare(strict_types=1);

namespace Colame\Onboarding\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AccountSetupData extends BaseData
{
    public function __construct(
        #[Required, StringType, Min(2), Max(100)]
        public readonly string $firstName,
        
        #[Required, StringType, Min(2), Max(100)]
        public readonly string $lastName,
        
        #[Nullable, Email, Max(255)]
        public readonly ?string $email,
        
        #[Required, StringType, Max(20)]
        public readonly string $phone,
    ) {}
    
    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}