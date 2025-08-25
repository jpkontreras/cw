<?php

declare(strict_types=1);

namespace Colame\Location\Data;

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
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateLocationData extends BaseData
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Required, In(['restaurant', 'kitchen', 'warehouse', 'central_kitchen'])]
        public readonly string $type,

        #[Nullable, StringType, Max(20), Unique('locations', 'code')]
        public readonly ?string $code = null,

        #[In(['active', 'inactive', 'maintenance'])]
        public readonly string $status = 'active',

        #[Nullable, StringType, Max(255)]
        public readonly ?string $address = null,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $city = null,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $state = null,

        #[StringType, Max(2)]
        public readonly string $country = 'CL',

        #[Nullable, StringType, Max(20)]
        public readonly ?string $postalCode = null,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $phone = null,

        #[Nullable, Email, Max(255)]
        public readonly ?string $email = null,

        #[StringType]
        public readonly string $timezone = 'America/Santiago',

        #[StringType, Max(3)]
        public readonly string $currency = 'CLP',


        #[Nullable, ArrayType]
        public readonly ?array $openingHours = null,

        #[Nullable, Numeric, Min(0)]
        public readonly ?float $deliveryRadius = null,

        #[ArrayType]
        public readonly array $capabilities = ['dine_in', 'takeout'],

        #[Nullable, Numeric]
        public readonly ?int $parentLocationId = null,

        #[Nullable, Numeric]
        public readonly ?int $managerId = null,

        #[Nullable, ArrayType]
        public readonly ?array $metadata = null,

        #[BooleanType]
        public readonly bool $isDefault = false,
    ) {}
}