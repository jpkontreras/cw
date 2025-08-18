<?php

namespace Colame\Staff\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\DataCollection;

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
        // Handle Spatie permission model which may not have all these fields
        $name = is_array($permission) ? $permission['name'] : $permission->name;
        
        // Extract module from permission name (e.g., "staff.view" -> "staff")
        $parts = explode('.', $name);
        $module = count($parts) > 1 ? $parts[0] : 'general';
        $slug = count($parts) > 1 ? $parts[1] : $name;
        
        return new self(
            id: is_array($permission) ? $permission['id'] : $permission->id,
            name: $name,
            slug: $slug,
            module: $module,
            description: is_array($permission) 
                ? ($permission['description'] ?? null) 
                : (property_exists($permission, 'description') ? $permission->description : null),
            createdAt: Carbon::parse(
                is_array($permission) 
                    ? ($permission['created_at'] ?? now()) 
                    : (property_exists($permission, 'created_at') ? $permission->created_at : now())
            ),
            updatedAt: Carbon::parse(
                is_array($permission) 
                    ? ($permission['updated_at'] ?? now()) 
                    : (property_exists($permission, 'updated_at') ? $permission->updated_at : now())
            ),
        );
    }
    
    public static function collection($items): DataCollection
    {
        $collection = [];
        foreach ($items as $item) {
            $collection[] = self::fromModel($item);
        }
        return new DataCollection(self::class, $collection);
    }
}