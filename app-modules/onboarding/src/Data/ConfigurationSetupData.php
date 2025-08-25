<?php

declare(strict_types=1);

namespace Colame\Onboarding\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ConfigurationSetupData extends BaseData
{
    public function __construct(
        // Localization Settings
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
        
        #[Required, In(['.', ','])]
        public readonly string $decimalSeparator = ',',
        
        #[Required, In(['.', ',', ' '])]
        public readonly string $thousandsSeparator = '.',
        
        #[Required, Numeric, Min(0), Max(6)]
        public readonly int $firstDayOfWeek = 1, // 0=Sunday, 1=Monday
        
        // Order Configuration
        #[Nullable, StringType, Max(10)]
        public readonly ?string $orderPrefix = null,
        
        #[Required, BooleanType]
        public readonly bool $requireCustomerPhone = true,
        
        #[Required, BooleanType]
        public readonly bool $printAutomatically = false,
        
        #[Required, BooleanType]
        public readonly bool $autoConfirmOrders = false,
        
        // Tipping Configuration
        #[Required, BooleanType]
        public readonly bool $enableTips = true,
        
        #[ArrayType]
        public readonly array $tipOptions = [10, 15, 20], // Percentage options
        
        // Notification Settings
        #[Required, BooleanType]
        public readonly bool $emailNotifications = true,
        
        #[Required, BooleanType]
        public readonly bool $smsNotifications = false,
        
        #[Required, BooleanType]
        public readonly bool $pushNotifications = false,
        
        // Branding (optional)
        #[Nullable, Url]
        public readonly ?string $logoUrl = null,
        
        #[Nullable, StringType, Max(7)]
        public readonly ?string $primaryColor = null,
        
        #[Nullable, StringType, Max(7)]
        public readonly ?string $secondaryColor = null,
        
        // Quick Start Options
        #[Required, BooleanType]
        public readonly bool $useTemplate = false,
        
        #[Nullable, In(['restaurant', 'fastfood', 'cafe', 'bar'])]
        public readonly ?string $templateType = null,
        
        #[Required, BooleanType]
        public readonly bool $createSampleMenu = false,
        
        #[Required, BooleanType]
        public readonly bool $createSampleCategories = true,
    ) {}
}