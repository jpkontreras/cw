<?php

namespace Colame\Staff\Repositories;

use Colame\Staff\Contracts\RoleRepositoryInterface;
use Colame\Staff\Models\Role;
use Colame\Staff\Data\RoleData;
use Colame\Staff\Data\CreateRoleData;
use Colame\Staff\Data\UpdateRoleData;
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

    public function create(CreateRoleData $data): RoleData
    {
        $role = Role::create([
            'name' => $data->name,
            'guard_name' => 'web',
        ]);
        
        // Update metadata
        $role->metadata()->updateOrCreate(
            ['role_id' => $role->id],
            [
                'description' => $data->description ?? null,
                'hierarchy_level' => $data->hierarchyLevel ?? 10,
                'is_system' => $data->isSystem ?? false,
            ]
        );
        
        // Sync permissions if provided
        if (!empty($data->permissions)) {
            $role->syncPermissions($data->permissions);
        }
        
        return RoleData::fromModel($role->load('metadata'));
    }

    public function update(int $id, UpdateRoleData $data): RoleData
    {
        $role = Role::findOrFail($id);
        
        // Filter out Optional values
        $updateData = collect($data->toArray())
            ->filter(fn($value) => !$value instanceof \Spatie\LaravelData\Optional)
            ->toArray();
        
        // Update role name if provided
        if (!empty($updateData['name'])) {
            $role->update(['name' => $updateData['name']]);
        }
        
        // Update metadata
        $metadataUpdate = [];
        if (isset($updateData['description'])) {
            $metadataUpdate['description'] = $updateData['description'];
        }
        if (isset($updateData['hierarchyLevel'])) {
            $metadataUpdate['hierarchy_level'] = $updateData['hierarchyLevel'];
        }
        if (isset($updateData['isSystem'])) {
            $metadataUpdate['is_system'] = $updateData['isSystem'];
        }
        
        if (!empty($metadataUpdate)) {
            $role->metadata()->updateOrCreate(
                ['role_id' => $role->id],
                $metadataUpdate
            );
        }
        
        // Sync permissions if provided
        if (!empty($updateData['permissions'])) {
            $role->syncPermissions($updateData['permissions']);
        }
        
        return RoleData::fromModel($role->load('metadata'));
    }

    public function delete(int $id): bool
    {
        return Role::destroy($id) > 0;
    }
    
    public function findBySlug(string $slug): ?RoleData
    {
        // Spatie roles don't have slugs, so we search by name
        $role = Role::where('name', $slug)->first();
        return $role ? RoleData::fromModel($role) : null;
    }
    
    public function getWithPermissions(int $id): ?RoleData
    {
        $role = Role::with(['permissions', 'metadata'])->find($id);
        return $role ? RoleData::fromModel($role) : null;
    }
    
    public function attachPermission(int $roleId, int $permissionId): void
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);
        $role->givePermissionTo($permission);
    }
    
    public function detachPermission(int $roleId, int $permissionId): void
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);
        $role->revokePermissionTo($permission);
    }

    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $role = Role::findOrFail($roleId);
        $role->syncPermissions($permissionIds);
    }

    public function getAllPermissions(): DataCollection
    {
        return PermissionData::collection(Permission::all());
    }

    public function getStaffWithRole(int $roleId): DataCollection
    {
        $role = Role::find($roleId);
        if (!$role) {
            return new DataCollection(\Colame\Staff\Data\StaffMemberData::class, []);
        }
        
        // Would get staff members with this role
        return new DataCollection(\Colame\Staff\Data\StaffMemberData::class, []);
    }
}