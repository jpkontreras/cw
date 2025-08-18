<?php

namespace Colame\Staff\Contracts;

use App\Core\Data\PaginatedResourceData;
use Carbon\Carbon;
use Colame\Staff\Data\CreateStaffMemberData;
use Colame\Staff\Data\StaffMemberData;
use Colame\Staff\Data\UpdateStaffMemberData;
use Spatie\LaravelData\DataCollection;

interface StaffServiceInterface
{
    public function createStaffMember(CreateStaffMemberData $data): StaffMemberData;
    
    public function updateStaffMember(int $id, UpdateStaffMemberData $data): StaffMemberData;
    
    public function deleteStaffMember(int $id): bool;
    
    public function getStaffMember(int $id): ?StaffMemberData;
    
    public function getStaffMemberWithRelations(int $id): ?StaffMemberData;
    
    public function getPaginatedStaff(array $filters, int $perPage): PaginatedResourceData;
    
    public function assignRole(int $staffId, int $roleId, ?int $locationId = null): void;
    
    public function removeRole(int $staffId, int $roleId, ?int $locationId = null): void;
    
    public function getStaffSchedule(int $staffId, Carbon $from, Carbon $to): array;
    
    public function getLocationStaff(int $locationId): DataCollection;
    
    public function importStaff(array $data): array;
    
    public function exportStaff(array $filters): string;
}