<?php

namespace Colame\Staff\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Computed;

class PermissionData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $module,
        public readonly ?string $description,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}

    #[Computed]
    public function displayName(): string
    {
        return ucwords(str_replace('_', ' ', $this->name));
    }

    #[Computed]
    public function fullSlug(): string
    {
        return "{$this->module}.{$this->slug}";
    }

    public static function fromModel($permission): self
    {
        return new self(
            id: $permission->id,
            name: $permission->name,
            slug: $permission->slug,
            module: $permission->module,
            description: $permission->description,
            createdAt: Carbon::parse($permission->created_at),
            updatedAt: Carbon::parse($permission->updated_at),
        );
    }
}