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
        // Handle both Eloquent models and stdClass objects from DB queries
        $id = is_array($role) ? $role['id'] : $role->id;
        $name = is_array($role) ? $role['name'] : $role->name;
        
        // Handle optional fields
        $slug = is_array($role) 
            ? ($role['slug'] ?? $name) 
            : (property_exists($role, 'slug') ? $role->slug : $name);
            
        $description = is_array($role) 
            ? ($role['description'] ?? null) 
            : (property_exists($role, 'description') ? $role->description : null);
            
        $hierarchyLevel = is_array($role) 
            ? ($role['hierarchy_level'] ?? $role['hierarchyLevel'] ?? 10) 
            : (property_exists($role, 'hierarchy_level') ? $role->hierarchy_level : 
               (property_exists($role, 'hierarchyLevel') ? $role->hierarchyLevel : 10));
               
        $isSystem = is_array($role) 
            ? ($role['is_system'] ?? false) 
            : (property_exists($role, 'is_system') ? $role->is_system : false);
            
        // Handle timestamps - they might not exist on DB query results
        $createdAt = is_array($role) 
            ? ($role['created_at'] ?? now()) 
            : (property_exists($role, 'created_at') ? $role->created_at : now());
            
        $updatedAt = is_array($role) 
            ? ($role['updated_at'] ?? now()) 
            : (property_exists($role, 'updated_at') ? $role->updated_at : now());
        
        return new self(
            id: $id,
            name: $name,
            slug: $slug,
            description: $description,
            hierarchyLevel: $hierarchyLevel,
            isSystem: $isSystem,
            permissions: Lazy::create(fn() => new DataCollection(\Colame\Staff\Data\PermissionData::class, [])),
            createdAt: Carbon::parse($createdAt),
            updatedAt: Carbon::parse($updatedAt),
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