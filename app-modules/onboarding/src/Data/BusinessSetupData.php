<?php

declare(strict_types=1);

namespace Colame\Onboarding\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BusinessSetupData extends BaseData
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $businessName,
        
        #[Nullable, StringType, Max(255)]
        public readonly ?string $legalName,
        
        #[Nullable, StringType, Max(50)]
        public readonly ?string $taxId, // RUT in Chile
        
        #[Required, In(['restaurant', 'franchise', 'chain', 'food_truck', 'catering'])]
        public readonly string $businessType,
        
        #[Nullable, Url, Max(255)]
        public readonly ?string $website,
        
        #[Nullable, StringType, Max(500)]
        public readonly ?string $description,
    ) {}
    
    public function getLegalNameOrBusiness(): string
    {
        return $this->legalName ?? $this->businessName;
    }
}