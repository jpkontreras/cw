<?php

declare(strict_types=1);

namespace Colame\Onboarding\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BusinessSetupData extends BaseData
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $businessName,
        
        #[Nullable, StringType, Max(255)]
        public readonly ?string $legalName = null, // Optional - defaults to businessName if not provided
        
        #[Nullable, StringType, Max(50)]
        public readonly ?string $taxId = null, // Optional - can be added later
        
        #[Required, In(['restaurant', 'franchise', 'chain', 'food_truck', 'catering'])]
        public readonly string $businessType,
        
        // Contact information - optional during onboarding
        #[Nullable, Email, Max(255)]
        public readonly ?string $businessEmail = null,
        
        #[Nullable, StringType, Max(20)]
        public readonly ?string $businessPhone = null,
        
        #[Nullable, Url, Max(255)]
        public readonly ?string $website = null,
        
        #[Nullable, StringType, Max(500)]
        public readonly ?string $description = null,
        
        // Additional business details
        #[Nullable, StringType, Max(20)]
        public readonly ?string $fax = null,
        
        #[Nullable]
        public readonly ?string $establishedDate = null,
        
        #[Nullable]
        public readonly ?int $numberOfEmployees = null,
    ) {}
    
    public function getLegalNameOrBusiness(): string
    {
        return $this->legalName ?: $this->businessName;
    }
    
    public static function rules(ValidationContext $context): array
    {
        return [
            'taxId' => ['nullable', 'regex:/^[0-9]{2}\.[0-9]{3}\.[0-9]{3}-[0-9kK]$/'], // Chilean RUT Empresa format (when provided)
            'businessPhone' => ['nullable', 'regex:/^[+]?[0-9\s\-\(\)]+$/'],
            'businessEmail' => ['nullable', 'email'], // Can be same as personal email during onboarding
        ];
    }
}