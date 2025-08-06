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
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UpdateLocationData extends BaseData
{
    public function __construct(
        #[StringType, Max(20), Unique('locations', 'code', ignore: new RouteParameterReference('location'))]
        public readonly string|Optional $code,

        #[StringType, Max(255)]
        public readonly string|Optional $name,

        #[In(['restaurant', 'kitchen', 'warehouse', 'central_kitchen'])]
        public readonly string|Optional $type,

        #[In(['active', 'inactive', 'maintenance'])]
        public readonly string|Optional $status,

        #[StringType, Max(255)]
        public readonly string|Optional $address,

        #[StringType, Max(100)]
        public readonly string|Optional $city,

        #[Nullable, StringType, Max(100)]
        public readonly string|null|Optional $state,

        #[StringType, Max(2)]
        public readonly string|Optional $country,

        #[Nullable, StringType, Max(20)]
        public readonly string|null|Optional $postalCode,

        #[Nullable, StringType, Max(50)]
        public readonly string|null|Optional $phone,

        #[Nullable, Email, Max(255)]
        public readonly string|null|Optional $email,

        #[StringType]
        public readonly string|Optional $timezone,

        #[StringType, Max(3)]
        public readonly string|Optional $currency,


        #[Nullable, ArrayType]
        public readonly array|null|Optional $openingHours,

        #[Nullable, Numeric, Min(0)]
        public readonly float|null|Optional $deliveryRadius,

        #[ArrayType]
        public readonly array|Optional $capabilities,

        #[Nullable, Numeric]
        public readonly int|null|Optional $parentLocationId,

        #[Nullable, Numeric]
        public readonly int|null|Optional $managerId,

        #[Nullable, ArrayType]
        public readonly array|null|Optional $metadata,

        #[BooleanType]
        public readonly bool|Optional $isDefault,
    ) {}
}