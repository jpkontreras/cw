<?php

namespace Colame\Staff\Enums;

enum ClockMethod: string
{
    case BIOMETRIC = 'biometric';
    case PIN = 'pin';
    case MOBILE = 'mobile';
    case MANUAL = 'manual';
    case CARD = 'card';
    case FACIAL = 'facial';
    
    public function label(): string
    {
        return match($this) {
            self::BIOMETRIC => 'Biometric',
            self::PIN => 'PIN Code',
            self::MOBILE => 'Mobile App',
            self::MANUAL => 'Manual Entry',
            self::CARD => 'ID Card',
            self::FACIAL => 'Facial Recognition',
        };
    }
    
    public function icon(): string
    {
        return match($this) {
            self::BIOMETRIC => 'fingerprint',
            self::PIN => 'dialpad',
            self::MOBILE => 'smartphone',
            self::MANUAL => 'edit',
            self::CARD => 'badge',
            self::FACIAL => 'face',
        };
    }
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}