<?php

namespace Colame\Staff\Contracts;

use App\Core\Data\PaginatedResourceData;
use Colame\Staff\Data\CreateStaffMemberData;
use Colame\Staff\Data\StaffMemberData;
use Colame\Staff\Data\UpdateStaffMemberData;
use Spatie\LaravelData\DataCollection;

interface StaffRepositoryInterface
{
    public function find(int $id): ?StaffMemberData;
    
    public function findByEmployeeCode(string $code): ?StaffMemberData;
    
    public function findByEmail(string $email): ?StaffMemberData;
    
    public function create(CreateStaffMemberData $data): StaffMemberData;
    
    public function update(int $id, UpdateStaffMemberData $data): StaffMemberData;
    
    public function delete(int $id): bool;
    
    public function paginateWithFilters(array $filters, int $perPage): PaginatedResourceData;
    
    public function getByLocation(int $locationId): DataCollection;
    
    public function getByRole(int $roleId): DataCollection;
    
    public function getActiveStaff(): DataCollection;
    
    public function assignRole(int $staffId, int $roleId, ?int $locationId = null): void;
    
    public function removeRole(int $staffId, int $roleId, ?int $locationId = null): void;
    
    public function getRoles(int $staffId): DataCollection;
}