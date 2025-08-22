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
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class LocationSetupData extends BaseData
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,
        
        #[Required, In(['restaurant', 'kitchen', 'warehouse', 'central_kitchen'])]
        public readonly string $type,
        
        #[Required, StringType, Max(255)]
        public readonly string $address,
        
        #[Required, StringType, Max(100)]
        public readonly string $city,
        
        #[Nullable, StringType, Max(100)]
        public readonly ?string $state,
        
        #[StringType, Max(2)]
        public readonly string $country = 'CL',
        
        #[Nullable, StringType, Max(20)]
        public readonly ?string $postalCode,
        
        #[Required, StringType, Max(50)]
        public readonly string $phone,
        
        #[Nullable, Email, Max(255)]
        public readonly ?string $email,
        
        #[StringType]
        public readonly string $timezone = 'America/Santiago',
        
        #[StringType, Max(3)]
        public readonly string $currency = 'CLP',
        
        #[ArrayType]
        public readonly array $capabilities = ['dine_in', 'takeout'],
        
        #[Nullable, ArrayType]
        public readonly ?array $openingHours = null,
        
        #[Nullable, Numeric, Min(0)]
        public readonly ?float $deliveryRadius = null,
        
        #[BooleanType]
        public readonly bool $isDefault = true,
    ) {}
}