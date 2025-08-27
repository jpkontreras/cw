<?php

declare(strict_types=1);

namespace Colame\Business\Data;

use App\Core\Data\BaseData;
use Colame\Business\Enums\BusinessType;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateBusinessData extends BaseData
{
    public function __construct(
        #[Required, Max(255)]
        public readonly string $name,
        
        #[Required, Max(255), Unique('businesses', 'slug')]
        public readonly string $slug,
        
        #[Max(255)]
        public readonly ?string $legalName,
        
        #[Max(255)]
        public readonly ?string $taxId,
        
        #[Required, In(['corporate', 'franchise', 'independent'])]
        public readonly string $type = 'independent',
        
        public readonly int $ownerId,
        
        #[Email, Max(255)]
        public readonly ?string $email,
        
        #[Max(20)]
        public readonly ?string $phone,
        
        #[Url, Max(255)]
        public readonly ?string $website,
        
        #[Max(255)]
        public readonly ?string $address,
        
        #[Max(255)]
        public readonly ?string $addressLine2,
        
        #[Max(100)]
        public readonly ?string $city,
        
        #[Max(100)]
        public readonly ?string $state,
        
        #[Max(2)]
        public readonly string $country = 'CL',
        
        #[Max(20)]
        public readonly ?string $postalCode,
        
        #[Max(3)]
        public readonly string $currency = 'CLP',
        
        #[Max(50)]
        public readonly string $timezone = 'America/Santiago',
        
        #[Max(10)]
        public readonly string $locale = 'es_CL',
        
        public readonly ?array $settings = null,
        
        public readonly ?array $features = null,
        
        public readonly ?array $limits = null,
        
        #[Url]
        public readonly ?string $logoUrl = null,
        
        #[Max(7)]
        public readonly ?string $primaryColor = null,
        
        #[Max(7)]
        public readonly ?string $secondaryColor = null,
        
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Convert to array for database insertion
     */
    public function toDatabaseArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'legal_name' => $this->legalName,
            'tax_id' => $this->taxId,
            'type' => $this->type,
            'owner_id' => $this->ownerId,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'address_line_2' => $this->addressLine2,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postalCode,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'settings' => $this->settings,
            'features' => $this->features,
            'limits' => $this->limits,
            'logo_url' => $this->logoUrl,
            'primary_color' => $this->primaryColor,
            'secondary_color' => $this->secondaryColor,
            'metadata' => $this->metadata,
            'status' => 'active',
            'subscription_tier' => 'basic',
        ];
    }
}