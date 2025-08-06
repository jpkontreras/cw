<?php

declare(strict_types=1);

namespace Colame\Location\Enums;

enum LocationType: string
{
    case RESTAURANT = 'restaurant';
    case KIOSK = 'kiosk';
    case FOOD_TRUCK = 'food_truck';
    case CLOUD_KITCHEN = 'cloud_kitchen';
    case DELIVERY_ONLY = 'delivery_only';
    case FRANCHISE = 'franchise';
    case HEADQUARTERS = 'headquarters';
    case WAREHOUSE = 'warehouse';
    
    /**
     * Get the display label for the location type.
     */
    public function label(): string
    {
        return match ($this) {
            self::RESTAURANT => 'Restaurant',
            self::KIOSK => 'Kiosk',
            self::FOOD_TRUCK => 'Food Truck',
            self::CLOUD_KITCHEN => 'Cloud Kitchen',
            self::DELIVERY_ONLY => 'Delivery Only',
            self::FRANCHISE => 'Franchise',
            self::HEADQUARTERS => 'Headquarters',
            self::WAREHOUSE => 'Warehouse',
        };
    }
    
    /**
     * Get the description for the location type.
     */
    public function description(): string
    {
        return match ($this) {
            self::RESTAURANT => 'Traditional dine-in restaurant with full service',
            self::KIOSK => 'Small booth or stand for quick service',
            self::FOOD_TRUCK => 'Mobile food service vehicle',
            self::CLOUD_KITCHEN => 'Kitchen for online orders only, no dine-in',
            self::DELIVERY_ONLY => 'Delivery-only operation without physical kitchen',
            self::FRANCHISE => 'Franchised location operating under main brand',
            self::HEADQUARTERS => 'Corporate headquarters for administrative operations',
            self::WAREHOUSE => 'Storage facility for inventory and supplies',
        };
    }
    
    /**
     * Get the icon for the location type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::RESTAURANT => 'utensils',
            self::KIOSK => 'store',
            self::FOOD_TRUCK => 'truck',
            self::CLOUD_KITCHEN => 'cloud',
            self::DELIVERY_ONLY => 'bike',
            self::FRANCHISE => 'building',
            self::HEADQUARTERS => 'building-2',
            self::WAREHOUSE => 'package',
        };
    }
    
    /**
     * Get the capabilities typically associated with this location type.
     */
    public function defaultCapabilities(): array
    {
        return match ($this) {
            self::RESTAURANT => ['dine_in', 'takeout', 'delivery', 'reservations'],
            self::KIOSK => ['takeout', 'quick_service'],
            self::FOOD_TRUCK => ['takeout', 'mobile', 'catering'],
            self::CLOUD_KITCHEN => ['delivery', 'online_orders'],
            self::DELIVERY_ONLY => ['delivery'],
            self::FRANCHISE => ['dine_in', 'takeout', 'delivery'],
            self::HEADQUARTERS => ['administration'],
            self::WAREHOUSE => ['inventory', 'distribution'],
        };
    }
    
    /**
     * Check if this location type supports customer orders.
     */
    public function supportsOrders(): bool
    {
        return !in_array($this, [self::HEADQUARTERS, self::WAREHOUSE]);
    }
    
    /**
     * Get all location types as options for select inputs.
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
                'icon' => $type->icon(),
            ],
            self::cases()
        );
    }
}