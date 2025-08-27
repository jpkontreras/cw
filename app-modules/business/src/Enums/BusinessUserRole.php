<?php

declare(strict_types=1);

namespace Colame\Business\Enums;

enum BusinessUserRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case MEMBER = 'member';

    public function label(): string
    {
        return match($this) {
            self::OWNER => 'Owner',
            self::ADMIN => 'Administrator',
            self::MANAGER => 'Manager',
            self::MEMBER => 'Member',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::OWNER => 'Full control over the business',
            self::ADMIN => 'Can manage all aspects except ownership',
            self::MANAGER => 'Can manage operations and staff',
            self::MEMBER => 'Basic access to business resources',
        };
    }

    public function level(): int
    {
        return match($this) {
            self::OWNER => 100,
            self::ADMIN => 75,
            self::MANAGER => 50,
            self::MEMBER => 25,
        };
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = [
            'manage_business' => [self::OWNER],
            'manage_subscription' => [self::OWNER],
            'manage_users' => [self::OWNER, self::ADMIN],
            'manage_settings' => [self::OWNER, self::ADMIN],
            'manage_locations' => [self::OWNER, self::ADMIN, self::MANAGER],
            'manage_staff' => [self::OWNER, self::ADMIN, self::MANAGER],
            'view_reports' => [self::OWNER, self::ADMIN, self::MANAGER],
            'manage_operations' => [self::OWNER, self::ADMIN, self::MANAGER],
            'access_business' => [self::OWNER, self::ADMIN, self::MANAGER, self::MEMBER],
        ];

        return in_array($this, $permissions[$permission] ?? []);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}