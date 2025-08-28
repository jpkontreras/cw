<?php

declare(strict_types=1);

namespace Colame\Onboarding\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class LocationSetupData extends BaseData
{
    public function __construct(
        // Identification
        #[Required, StringType, Max(255)]
        public readonly string $name,
        
        #[Nullable, StringType, Max(20)]
        public readonly ?string $code = null, // Auto-generated if not provided
        
        #[Required, In(['restaurant', 'kitchen', 'warehouse', 'central_kitchen'])]
        public readonly string $type = 'restaurant',
        
        // Complete Address Structure - Now optional
        #[Nullable, StringType, Max(255)]
        public readonly ?string $address = null,
        
        #[Nullable, StringType, Max(255)]
        public readonly ?string $addressLine2 = null,
        
        #[Nullable, StringType, Max(100)]
        public readonly ?string $city = null,
        
        #[Nullable, StringType, Max(100)]
        public readonly ?string $state = null,
        
        #[Nullable, StringType, Max(2)]
        public readonly ?string $country = 'CL',
        
        #[Nullable, StringType, Max(20)]
        public readonly ?string $postalCode = null,
        
        // Contact Information
        #[Nullable, StringType, Max(50)]
        public readonly ?string $phone = null,
        
        #[Nullable, Email, Max(255)]
        public readonly ?string $email = null,
        
        // Operational Configuration
        #[StringType]
        public readonly string $timezone = 'America/Santiago',
        
        #[StringType, Max(3)]
        public readonly string $currency = 'CLP',
        
        #[Required, ArrayType]
        public readonly array $capabilities = ['dine_in', 'takeout'],
        
        #[Nullable, ArrayType]
        public readonly ?array $openingHours = [],
        
        // Payment Configuration
        #[Nullable, ArrayType]
        public readonly ?array $paymentMethods = ['cash', 'credit_card', 'debit_card'],
        
        // Service Configuration
        #[Nullable, Numeric, Min(0)]
        public readonly ?float $deliveryRadius = null,
        
        #[Nullable, Numeric, Min(0)]
        public readonly ?int $seatingCapacity = null,
        
        #[ArrayType]
        public readonly array $kitchenCapabilities = [],
        
        // Tax & Financial
        #[Nullable, Numeric, Min(0), Max(100)]
        public readonly ?float $taxRate = 19.0, // Chilean IVA
        
        #[BooleanType]
        public readonly bool $taxIncluded = true,
        
        #[Nullable, Numeric, Min(0)]
        public readonly ?float $serviceCharge = null,
        
        // Status
        #[BooleanType]
        public readonly bool $isDefault = true,
        
        #[In(['active', 'inactive', 'maintenance'])]
        public readonly string $status = 'active',
    ) {}
    
    public static function rules(ValidationContext $context): array
    {
        return [
            'openingHours' => ['nullable', 'array'],
            'openingHours.*.day' => ['required_with:openingHours', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'openingHours.*.open' => ['required_with:openingHours', 'date_format:H:i'],
            'openingHours.*.close' => ['required_with:openingHours', 'date_format:H:i', 'after:openingHours.*.open'],
            'paymentMethods' => ['nullable', 'array'],
            'paymentMethods.*' => ['in:cash,credit_card,debit_card,bank_transfer,mobile_payment'],
            'capabilities' => ['required', 'array', 'min:1'],
            'capabilities.*' => ['in:dine_in,takeout,delivery,catering,events'],
        ];
    }
}