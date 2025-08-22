<?php

declare(strict_types=1);

namespace Colame\Settings\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class OrganizationSettingsData extends BaseData
{
    public function __construct(
        #[Required, Max(255)] public readonly string $businessName,
        #[Max(255)] public readonly ?string $legalName,
        #[Max(255)] public readonly ?string $taxId,
        #[Required, Email] public readonly string $email,
        #[Required, Max(20)] public readonly string $phone,
        #[Max(20)] public readonly ?string $fax,
        #[Url] public readonly ?string $website,
        #[Required, Max(255)] public readonly string $address,
        #[Max(255)] public readonly ?string $addressLine2,
        #[Required, Max(100)] public readonly string $city,
        #[Required, Max(100)] public readonly string $state,
        #[Required, Max(20)] public readonly string $postalCode,
        #[Required, Max(2)] public readonly string $country,
        #[Max(5)] public readonly string $currency,
        #[Max(50)] public readonly string $timezone,
        #[Max(10)] public readonly string $dateFormat,
        #[Max(10)] public readonly string $timeFormat,
        #[Url] public readonly ?string $logoUrl,
    ) {}

    public static function fromSettings(array $settings): self
    {
        return new self(
            businessName: $settings['organization.business_name'] ?? '',
            legalName: $settings['organization.legal_name'] ?? null,
            taxId: $settings['organization.tax_id'] ?? null,
            email: $settings['organization.email'] ?? '',
            phone: $settings['organization.phone'] ?? '',
            fax: $settings['organization.fax'] ?? null,
            website: $settings['organization.website'] ?? null,
            address: $settings['organization.address'] ?? '',
            addressLine2: $settings['organization.address_line_2'] ?? null,
            city: $settings['organization.city'] ?? '',
            state: $settings['organization.state'] ?? '',
            postalCode: $settings['organization.postal_code'] ?? '',
            country: $settings['organization.country'] ?? 'CL',
            currency: $settings['localization.currency'] ?? 'CLP',
            timezone: $settings['localization.timezone'] ?? 'America/Santiago',
            dateFormat: $settings['localization.date_format'] ?? 'd/m/Y',
            timeFormat: $settings['localization.time_format'] ?? 'H:i',
            logoUrl: $settings['organization.logo_url'] ?? null,
        );
    }

    public function toSettings(): array
    {
        return [
            'organization.business_name' => $this->businessName,
            'organization.legal_name' => $this->legalName,
            'organization.tax_id' => $this->taxId,
            'organization.email' => $this->email,
            'organization.phone' => $this->phone,
            'organization.fax' => $this->fax,
            'organization.website' => $this->website,
            'organization.address' => $this->address,
            'organization.address_line_2' => $this->addressLine2,
            'organization.city' => $this->city,
            'organization.state' => $this->state,
            'organization.postal_code' => $this->postalCode,
            'organization.country' => $this->country,
            'localization.currency' => $this->currency,
            'localization.timezone' => $this->timezone,
            'localization.date_format' => $this->dateFormat,
            'localization.time_format' => $this->timeFormat,
            'organization.logo_url' => $this->logoUrl,
        ];
    }
}