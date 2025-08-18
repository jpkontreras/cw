<?php

namespace Colame\Staff\Services;

use Colame\Staff\Contracts\RoleRepositoryInterface;
use Colame\Staff\Data\RoleData;
use Spatie\LaravelData\DataCollection;

class RoleService
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository
    ) {}

    public function getAllRoles(): DataCollection
    {
        return $this->roleRepository->all();
    }

    public function getRoleById(int $id): ?RoleData
    {
        return $this->roleRepository->find($id);
    }

    public function createRole(array $data): RoleData
    {
        return $this->roleRepository->create($data);
    }

    public function updateRole(int $id, array $data): ?RoleData
    {
        return $this->roleRepository->update($id, $data);
    }

    public function deleteRole(int $id): bool
    {
        return $this->roleRepository->delete($id);
    }

    public function updateRolePermissions(int $roleId, array $permissionIds): bool
    {
        return $this->roleRepository->syncPermissions($roleId, $permissionIds);
    }

    public function getAllPermissions(): DataCollection
    {
        return $this->roleRepository->getAllPermissions();
    }

    public function getStaffWithRole(int $roleId): DataCollection
    {
        return $this->roleRepository->getStaffWithRole($roleId);
    }
}