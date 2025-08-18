<?php

namespace Colame\Staff\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case LATE = 'late';
    case ABSENT = 'absent';
    case HOLIDAY = 'holiday';
    case LEAVE = 'leave';
    case HALF_DAY = 'half_day';
    
    public function label(): string
    {
        return match($this) {
            self::PRESENT => 'Present',
            self::LATE => 'Late',
            self::ABSENT => 'Absent',
            self::HOLIDAY => 'Holiday',
            self::LEAVE => 'On Leave',
            self::HALF_DAY => 'Half Day',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::PRESENT => 'green',
            self::LATE => 'yellow',
            self::ABSENT => 'red',
            self::HOLIDAY => 'blue',
            self::LEAVE => 'purple',
            self::HALF_DAY => 'orange',
        };
    }
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}