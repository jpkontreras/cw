<?php

namespace Colame\Staff\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;

class RoleData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly int $hierarchyLevel,
        public readonly bool $isSystem,
        public Lazy|DataCollection $permissions,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}

    #[Computed]
    public function displayName(): string
    {
        return ucwords(str_replace('_', ' ', $this->name));
    }

    #[Computed]
    public function permissionCount(): int
    {
        if ($this->permissions instanceof Lazy) {
            return 0;
        }
        return $this->permissions->count();
    }

    public static function fromModel($role): self
    {
        // Ensure metadata is loaded if it exists
        $role->loadMissing('metadata');
        
        return new self(
            id: $role->id,
            name: $role->name,
            slug: $role->slug ?? $role->name, // Spatie roles don't have slug by default
            description: $role->metadata?->description,
            hierarchyLevel: $role->metadata?->hierarchy_level ?? 10,
            isSystem: $role->metadata?->is_system ?? false,
            permissions: Lazy::whenLoaded('permissions', $role,
                fn() => PermissionData::collection($role->permissions)
            ),
            createdAt: Carbon::parse($role->created_at),
            updatedAt: Carbon::parse($role->updated_at),
        );
    }
}