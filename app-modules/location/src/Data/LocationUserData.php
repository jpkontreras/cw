<?php

declare(strict_types=1);

namespace Colame\Location\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class LocationUserData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
        public readonly bool $isPrimary,
        public readonly ?\DateTimeInterface $assignedAt = null,
    ) {}

    /**
     * Get role label
     */
    #[Computed]
    public function roleLabel(): string
    {
        return match ($this->role) {
            'manager' => 'Manager',
            'staff' => 'Staff',
            'viewer' => 'Viewer',
            default => ucfirst($this->role),
        };
    }

    /**
     * Get role color for UI
     */
    #[Computed]
    public function roleColor(): string
    {
        return match ($this->role) {
            'manager' => 'blue',
            'staff' => 'green',
            'viewer' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if user is manager
     */
    #[Computed]
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }
}