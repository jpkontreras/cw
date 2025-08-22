<?php

namespace Colame\Settings\Database\Seeders;

use Colame\Settings\Enums\SettingCategory;
use Colame\Settings\Enums\SettingType;
use Colame\Settings\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = $this->getDefaultSettings();

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Get default settings configuration
     */
    private function getDefaultSettings(): array
    {
        return [
            // Organization Settings
            [
                'key' => 'organization.business_name',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Business Name',
                'description' => 'The name of your business',
                'group' => 'Basic Information',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'organization.legal_name',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Legal Name',
                'description' => 'Legal business entity name',
                'group' => 'Basic Information',
                'sort_order' => 2,
            ],
            [
                'key' => 'organization.tax_id',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Tax ID / RUT',
                'description' => 'Business tax identification number',
                'group' => 'Basic Information',
                'sort_order' => 3,
            ],
            [
                'key' => 'organization.email',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Business Email',
                'description' => 'Primary email address for business communications',
                'group' => 'Contact Information',
                'is_required' => true,
                'validation' => ['email'],
                'sort_order' => 4,
            ],
            [
                'key' => 'organization.phone',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Business Phone',
                'description' => 'Primary phone number',
                'group' => 'Contact Information',
                'is_required' => true,
                'sort_order' => 5,
            ],
            [
                'key' => 'organization.website',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Website',
                'description' => 'Business website URL',
                'group' => 'Contact Information',
                'validation' => ['url'],
                'sort_order' => 6,
            ],
            [
                'key' => 'organization.address',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Street Address',
                'description' => 'Business street address',
                'group' => 'Address',
                'is_required' => true,
                'sort_order' => 7,
            ],
            [
                'key' => 'organization.city',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'City',
                'description' => 'City',
                'group' => 'Address',
                'is_required' => true,
                'sort_order' => 8,
            ],
            [
                'key' => 'organization.state',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'State/Province',
                'description' => 'State or province',
                'group' => 'Address',
                'is_required' => true,
                'sort_order' => 9,
            ],
            [
                'key' => 'organization.postal_code',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Postal Code',
                'description' => 'Postal or ZIP code',
                'group' => 'Address',
                'is_required' => true,
                'sort_order' => 10,
            ],
            [
                'key' => 'organization.country',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Country',
                'description' => 'Country code',
                'group' => 'Address',
                'default_value' => 'CL',
                'is_required' => true,
                'sort_order' => 11,
            ],
            [
                'key' => 'organization.logo_url',
                'type' => SettingType::FILE,
                'category' => SettingCategory::ORGANIZATION,
                'label' => 'Business Logo',
                'description' => 'Logo image URL',
                'group' => 'Branding',
                'sort_order' => 12,
            ],

            // Tax Settings
            [
                'key' => 'tax.enabled',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::TAX,
                'label' => 'Enable Tax',
                'description' => 'Enable tax calculations on orders',
                'default_value' => '1',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'tax.rate',
                'type' => SettingType::FLOAT,
                'category' => SettingCategory::TAX,
                'label' => 'Tax Rate (%)',
                'description' => 'Default tax rate percentage',
                'default_value' => '19',
                'validation' => ['numeric', 'min:0', 'max:100'],
                'sort_order' => 2,
            ],
            [
                'key' => 'tax.included_in_price',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::TAX,
                'label' => 'Tax Included in Prices',
                'description' => 'Whether tax is included in displayed prices',
                'default_value' => '1',
                'sort_order' => 3,
            ],

            // Order Settings
            [
                'key' => 'order.number_format',
                'type' => SettingType::STRING,
                'category' => SettingCategory::ORDER,
                'label' => 'Order Number Format',
                'description' => 'Format for order numbers (use {number} for sequence)',
                'default_value' => 'ORD-{number}',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'order.starting_number',
                'type' => SettingType::INTEGER,
                'category' => SettingCategory::ORDER,
                'label' => 'Starting Order Number',
                'description' => 'Starting number for order sequence',
                'default_value' => '1000',
                'validation' => ['integer', 'min:1'],
                'sort_order' => 2,
            ],
            [
                'key' => 'order.auto_accept',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::ORDER,
                'label' => 'Auto-Accept Orders',
                'description' => 'Automatically accept new orders',
                'default_value' => '0',
                'sort_order' => 3,
            ],
            [
                'key' => 'order.allow_tips',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::ORDER,
                'label' => 'Allow Tips',
                'description' => 'Allow customers to add tips to orders',
                'default_value' => '1',
                'sort_order' => 4,
            ],
            [
                'key' => 'order.default_tip_percentages',
                'type' => SettingType::ARRAY,
                'category' => SettingCategory::ORDER,
                'label' => 'Default Tip Percentages',
                'description' => 'Suggested tip percentages',
                'default_value' => json_encode([10, 15, 20]),
                'sort_order' => 5,
            ],

            // Receipt Settings
            [
                'key' => 'receipt.header_text',
                'type' => SettingType::STRING,
                'category' => SettingCategory::RECEIPT,
                'label' => 'Receipt Header Text',
                'description' => 'Text displayed at the top of receipts',
                'sort_order' => 1,
            ],
            [
                'key' => 'receipt.footer_text',
                'type' => SettingType::STRING,
                'category' => SettingCategory::RECEIPT,
                'label' => 'Receipt Footer Text',
                'description' => 'Text displayed at the bottom of receipts',
                'default_value' => 'Thank you for your business!',
                'sort_order' => 2,
            ],
            [
                'key' => 'receipt.show_logo',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::RECEIPT,
                'label' => 'Show Logo on Receipt',
                'description' => 'Display business logo on receipts',
                'default_value' => '1',
                'sort_order' => 3,
            ],
            [
                'key' => 'receipt.show_tax_breakdown',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::RECEIPT,
                'label' => 'Show Tax Breakdown',
                'description' => 'Display detailed tax breakdown on receipts',
                'default_value' => '1',
                'sort_order' => 4,
            ],

            // Localization Settings
            [
                'key' => 'localization.currency',
                'type' => SettingType::STRING,
                'category' => SettingCategory::LOCALIZATION,
                'label' => 'Currency',
                'description' => 'Default currency code',
                'default_value' => 'CLP',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'localization.currency_symbol',
                'type' => SettingType::STRING,
                'category' => SettingCategory::LOCALIZATION,
                'label' => 'Currency Symbol',
                'description' => 'Currency symbol to display',
                'default_value' => '$',
                'is_required' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'localization.timezone',
                'type' => SettingType::STRING,
                'category' => SettingCategory::LOCALIZATION,
                'label' => 'Timezone',
                'description' => 'System timezone',
                'default_value' => 'America/Santiago',
                'is_required' => true,
                'sort_order' => 3,
            ],
            [
                'key' => 'localization.date_format',
                'type' => SettingType::STRING,
                'category' => SettingCategory::LOCALIZATION,
                'label' => 'Date Format',
                'description' => 'Default date format',
                'default_value' => 'd/m/Y',
                'is_required' => true,
                'sort_order' => 4,
            ],
            [
                'key' => 'localization.time_format',
                'type' => SettingType::STRING,
                'category' => SettingCategory::LOCALIZATION,
                'label' => 'Time Format',
                'description' => 'Default time format',
                'default_value' => 'H:i',
                'is_required' => true,
                'sort_order' => 5,
            ],
            [
                'key' => 'localization.language',
                'type' => SettingType::SELECT,
                'category' => SettingCategory::LOCALIZATION,
                'label' => 'Language',
                'description' => 'Default system language',
                'default_value' => 'es',
                'options' => ['es' => 'Español', 'en' => 'English'],
                'is_required' => true,
                'sort_order' => 6,
            ],

            // Notification Settings
            [
                'key' => 'notification.email_enabled',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::NOTIFICATION,
                'label' => 'Enable Email Notifications',
                'description' => 'Send email notifications',
                'default_value' => '1',
                'sort_order' => 1,
            ],
            [
                'key' => 'notification.sms_enabled',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::NOTIFICATION,
                'label' => 'Enable SMS Notifications',
                'description' => 'Send SMS notifications',
                'default_value' => '0',
                'sort_order' => 2,
            ],
            [
                'key' => 'notification.push_enabled',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::NOTIFICATION,
                'label' => 'Enable Push Notifications',
                'description' => 'Send push notifications to mobile apps',
                'default_value' => '1',
                'sort_order' => 3,
            ],
            [
                'key' => 'notification.new_order_alert',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::NOTIFICATION,
                'label' => 'New Order Alerts',
                'description' => 'Send alerts for new orders',
                'default_value' => '1',
                'sort_order' => 4,
            ],

            // Inventory Settings
            [
                'key' => 'inventory.track_stock',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::INVENTORY,
                'label' => 'Track Stock Levels',
                'description' => 'Enable stock tracking',
                'default_value' => '1',
                'sort_order' => 1,
            ],
            [
                'key' => 'inventory.low_stock_threshold',
                'type' => SettingType::INTEGER,
                'category' => SettingCategory::INVENTORY,
                'label' => 'Low Stock Threshold',
                'description' => 'Alert when stock falls below this level',
                'default_value' => '10',
                'validation' => ['integer', 'min:0'],
                'sort_order' => 2,
            ],
            [
                'key' => 'inventory.allow_negative_stock',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::INVENTORY,
                'label' => 'Allow Negative Stock',
                'description' => 'Allow orders when stock is zero',
                'default_value' => '0',
                'sort_order' => 3,
            ],

            // Payment Settings
            [
                'key' => 'payment.cash_enabled',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::PAYMENT,
                'label' => 'Accept Cash',
                'description' => 'Accept cash payments',
                'default_value' => '1',
                'sort_order' => 1,
            ],
            [
                'key' => 'payment.card_enabled',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::PAYMENT,
                'label' => 'Accept Cards',
                'description' => 'Accept credit/debit card payments',
                'default_value' => '1',
                'sort_order' => 2,
            ],
            [
                'key' => 'payment.online_enabled',
                'type' => SettingType::BOOLEAN,
                'category' => SettingCategory::PAYMENT,
                'label' => 'Accept Online Payments',
                'description' => 'Accept online payment methods',
                'default_value' => '0',
                'sort_order' => 3,
            ],

            // Integration Settings
            [
                'key' => 'integration.payment_gateway',
                'type' => SettingType::SELECT,
                'category' => SettingCategory::INTEGRATION,
                'label' => 'Payment Gateway',
                'description' => 'Payment processing gateway',
                'options' => ['transbank' => 'Transbank', 'mercadopago' => 'MercadoPago', 'stripe' => 'Stripe'],
                'sort_order' => 1,
            ],
            [
                'key' => 'integration.payment_gateway_key',
                'type' => SettingType::ENCRYPTED,
                'category' => SettingCategory::INTEGRATION,
                'label' => 'Payment Gateway API Key',
                'description' => 'API key for payment gateway',
                'is_encrypted' => true,
                'sort_order' => 2,
            ],
        ];
    }
}