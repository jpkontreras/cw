<?php

declare(strict_types=1);

namespace Colame\Business\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UpdateBusinessData extends BaseData
{
    public function __construct(
        #[Max(255)]
        public readonly Optional|string $name,
        
        #[Max(255), Unique('businesses', 'slug', ignore: new RouteParameterReference('business'))]
        public readonly Optional|string $slug,
        
        #[Max(255)]
        public readonly Optional|string|null $legalName,
        
        #[Max(255)]
        public readonly Optional|string|null $taxId,
        
        #[In(['corporate', 'franchise', 'independent'])]
        public readonly Optional|string $type,
        
        #[In(['active', 'inactive', 'suspended'])]
        public readonly Optional|string $status,
        
        #[Email, Max(255)]
        public readonly Optional|string|null $email,
        
        #[Max(20)]
        public readonly Optional|string|null $phone,
        
        #[Url, Max(255)]
        public readonly Optional|string|null $website,
        
        #[Max(255)]
        public readonly Optional|string|null $address,
        
        #[Max(255)]
        public readonly Optional|string|null $addressLine2,
        
        #[Max(100)]
        public readonly Optional|string|null $city,
        
        #[Max(100)]
        public readonly Optional|string|null $state,
        
        #[Max(2)]
        public readonly Optional|string $country,
        
        #[Max(20)]
        public readonly Optional|string|null $postalCode,
        
        #[Max(3)]
        public readonly Optional|string $currency,
        
        #[Max(50)]
        public readonly Optional|string $timezone,
        
        #[Max(10)]
        public readonly Optional|string $locale,
        
        public readonly Optional|array|null $settings,
        
        public readonly Optional|array|null $features,
        
        public readonly Optional|array|null $limits,
        
        #[Url]
        public readonly Optional|string|null $logoUrl,
        
        #[Max(7)]
        public readonly Optional|string|null $primaryColor,
        
        #[Max(7)]
        public readonly Optional|string|null $secondaryColor,
        
        public readonly Optional|array|null $metadata,
    ) {}

    /**
     * Convert to array for database update, excluding Optional values
     */
    public function toDatabaseArray(): array
    {
        $data = [];

        if (!$this->name instanceof Optional) {
            $data['name'] = $this->name;
        }
        if (!$this->slug instanceof Optional) {
            $data['slug'] = $this->slug;
        }
        if (!$this->legalName instanceof Optional) {
            $data['legal_name'] = $this->legalName;
        }
        if (!$this->taxId instanceof Optional) {
            $data['tax_id'] = $this->taxId;
        }
        if (!$this->type instanceof Optional) {
            $data['type'] = $this->type;
        }
        if (!$this->status instanceof Optional) {
            $data['status'] = $this->status;
        }
        if (!$this->email instanceof Optional) {
            $data['email'] = $this->email;
        }
        if (!$this->phone instanceof Optional) {
            $data['phone'] = $this->phone;
        }
        if (!$this->website instanceof Optional) {
            $data['website'] = $this->website;
        }
        if (!$this->address instanceof Optional) {
            $data['address'] = $this->address;
        }
        if (!$this->addressLine2 instanceof Optional) {
            $data['address_line_2'] = $this->addressLine2;
        }
        if (!$this->city instanceof Optional) {
            $data['city'] = $this->city;
        }
        if (!$this->state instanceof Optional) {
            $data['state'] = $this->state;
        }
        if (!$this->country instanceof Optional) {
            $data['country'] = $this->country;
        }
        if (!$this->postalCode instanceof Optional) {
            $data['postal_code'] = $this->postalCode;
        }
        if (!$this->currency instanceof Optional) {
            $data['currency'] = $this->currency;
        }
        if (!$this->timezone instanceof Optional) {
            $data['timezone'] = $this->timezone;
        }
        if (!$this->locale instanceof Optional) {
            $data['locale'] = $this->locale;
        }
        if (!$this->settings instanceof Optional) {
            $data['settings'] = $this->settings;
        }
        if (!$this->features instanceof Optional) {
            $data['features'] = $this->features;
        }
        if (!$this->limits instanceof Optional) {
            $data['limits'] = $this->limits;
        }
        if (!$this->logoUrl instanceof Optional) {
            $data['logo_url'] = $this->logoUrl;
        }
        if (!$this->primaryColor instanceof Optional) {
            $data['primary_color'] = $this->primaryColor;
        }
        if (!$this->secondaryColor instanceof Optional) {
            $data['secondary_color'] = $this->secondaryColor;
        }
        if (!$this->metadata instanceof Optional) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }
}