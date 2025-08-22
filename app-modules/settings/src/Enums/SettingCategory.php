<?php

declare(strict_types=1);

namespace Colame\Settings\Enums;

enum SettingCategory: string
{
    case ORGANIZATION = 'organization';
    case ORDER = 'order';
    case RECEIPT = 'receipt';
    case INVENTORY = 'inventory';
    case NOTIFICATION = 'notification';
    case INTEGRATION = 'integration';
    case PAYMENT = 'payment';
    case TAX = 'tax';
    case LOCALIZATION = 'localization';
    case PRINTING = 'printing';
    case SECURITY = 'security';
    case APPEARANCE = 'appearance';

    public function label(): string
    {
        return match ($this) {
            self::ORGANIZATION => 'Organization',
            self::ORDER => 'Order Management',
            self::RECEIPT => 'Receipt & Invoice',
            self::INVENTORY => 'Inventory',
            self::NOTIFICATION => 'Notifications',
            self::INTEGRATION => 'Integrations',
            self::PAYMENT => 'Payment Methods',
            self::TAX => 'Tax Configuration',
            self::LOCALIZATION => 'Localization',
            self::PRINTING => 'Printing',
            self::SECURITY => 'Security',
            self::APPEARANCE => 'Appearance',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ORGANIZATION => 'Business information and general settings',
            self::ORDER => 'Order processing and management settings',
            self::RECEIPT => 'Receipt format and printing options',
            self::INVENTORY => 'Stock management and alerts',
            self::NOTIFICATION => 'Email, SMS, and push notification preferences',
            self::INTEGRATION => 'Third-party service integrations',
            self::PAYMENT => 'Payment gateway and method configuration',
            self::TAX => 'Tax rates and calculation settings',
            self::LOCALIZATION => 'Language, currency, and regional settings',
            self::PRINTING => 'Printer configuration and templates',
            self::SECURITY => 'Security and access control settings',
            self::APPEARANCE => 'UI theme and display preferences',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ORGANIZATION => 'building-2',
            self::ORDER => 'shopping-cart',
            self::RECEIPT => 'receipt',
            self::INVENTORY => 'package',
            self::NOTIFICATION => 'bell',
            self::INTEGRATION => 'plug',
            self::PAYMENT => 'credit-card',
            self::TAX => 'calculator',
            self::LOCALIZATION => 'globe',
            self::PRINTING => 'printer',
            self::SECURITY => 'shield',
            self::APPEARANCE => 'palette',
        };
    }
}