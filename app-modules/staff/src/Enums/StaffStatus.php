<?php

namespace Colame\Staff\Enums;

enum StaffStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';
    case ON_LEAVE = 'on_leave';
    
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::TERMINATED => 'Terminated',
            self::ON_LEAVE => 'On Leave',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::SUSPENDED => 'orange',
            self::TERMINATED => 'red',
            self::ON_LEAVE => 'blue',
        };
    }
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}