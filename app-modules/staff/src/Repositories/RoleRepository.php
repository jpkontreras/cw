<?php

namespace Colame\Staff\Repositories;

use Colame\Staff\Contracts\RoleRepositoryInterface;
use Colame\Staff\Models\Role;
use Colame\Staff\Data\RoleData;
use Colame\Staff\Data\PermissionData;
use Spatie\LaravelData\DataCollection;
use Spatie\Permission\Models\Permission;

class RoleRepository implements RoleRepositoryInterface
{
    public function find(int $id): ?RoleData
    {
        $role = Role::find($id);
        return $role ? RoleData::fromModel($role) : null;
    }

    public function all(): DataCollection
    {
        return RoleData::collection(Role::with('metadata')->get()->map(fn($role) => RoleData::fromModel($role)));
    }

    public function create(array $data): RoleData
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);
        
        // Update metadata
        $role->metadata()->updateOrCreate(
            ['role_id' => $role->id],
            [
                'description' => $data['description'] ?? null,
                'hierarchy_level' => $data['hierarchy_level'] ?? 10,
                'is_system' => $data['is_system'] ?? false,
            ]
        );
        
        // Sync permissions if provided
        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }
        
        return RoleData::fromModel($role->load('metadata'));
    }

    public function update(int $id, array $data): ?RoleData
    {
        $role = Role::find($id);
        if (!$role) {
            return null;
        }
        
        $role->update(['name' => $data['name']]);
        
        // Update metadata
        $role->metadata()->updateOrCreate(
            ['role_id' => $role->id],
            [
                'description' => $data['description'] ?? null,
                'hierarchy_level' => $data['hierarchy_level'] ?? 10,
                'is_system' => $data['is_system'] ?? false,
            ]
        );
        
        // Sync permissions if provided
        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }
        
        return RoleData::fromModel($role->load('metadata'));
    }

    public function delete(int $id): bool
    {
        return Role::destroy($id) > 0;
    }

    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        $role = Role::find($roleId);
        if (!$role) {
            return false;
        }
        
        $role->syncPermissions($permissionIds);
        return true;
    }

    public function getAllPermissions(): DataCollection
    {
        return PermissionData::collection(Permission::all());
    }

    public function getStaffWithRole(int $roleId): DataCollection
    {
        $role = Role::find($roleId);
        if (!$role) {
            return DataCollection::empty();
        }
        
        return DataCollection::empty(); // Would use StaffMemberData::collection
    }
}