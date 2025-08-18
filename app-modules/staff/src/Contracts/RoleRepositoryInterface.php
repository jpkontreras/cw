<?php

namespace Colame\Staff\Contracts;

use App\Core\Data\PaginatedResourceData;
use Colame\Staff\Data\CreateRoleData;
use Colame\Staff\Data\RoleData;
use Colame\Staff\Data\UpdateRoleData;
use Spatie\LaravelData\DataCollection;

interface RoleRepositoryInterface
{
    public function find(int $id): ?RoleData;
    
    public function findBySlug(string $slug): ?RoleData;
    
    public function create(CreateRoleData $data): RoleData;
    
    public function update(int $id, UpdateRoleData $data): RoleData;
    
    public function delete(int $id): bool;
    
    public function all(): DataCollection;
    
    public function getWithPermissions(int $id): ?RoleData;
    
    public function attachPermission(int $roleId, int $permissionId): void;
    
    public function detachPermission(int $roleId, int $permissionId): void;
    
    public function syncPermissions(int $roleId, array $permissionIds): void;
}