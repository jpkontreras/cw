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
class ConfigurationSetupData extends BaseData
{
    public function __construct(
        #[Required, In(['d/m/Y', 'm/d/Y', 'Y-m-d'])]
        public readonly string $dateFormat = 'd/m/Y',
        
        #[Required, In(['H:i', 'h:i A', 'h:i a'])]
        public readonly string $timeFormat = 'H:i',
        
        #[Required, In(['es', 'en'])]
        public readonly string $language = 'es',
        
        #[Required, StringType, Max(5)]
        public readonly string $currency = 'CLP',
        
        #[Required, StringType, Max(50)]
        public readonly string $timezone = 'America/Santiago',
        
        #[Nullable, Url]
        public readonly ?string $logoUrl = null,
        
        #[Nullable, StringType, Max(7)]
        public readonly ?string $primaryColor = null,
        
        #[Nullable, StringType, Max(7)]
        public readonly ?string $secondaryColor = null,
    ) {}
}