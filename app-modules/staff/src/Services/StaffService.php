<?php

namespace Colame\Staff\Services;

use Colame\Staff\Contracts\StaffRepositoryInterface;
use Colame\Staff\Data\CreateStaffMemberData;
use Colame\Staff\Data\UpdateStaffMemberData;
use Colame\Staff\Data\StaffMemberData;
use App\Core\Data\PaginatedResourceData;
use Spatie\LaravelData\DataCollection;

class StaffService
{
    public function __construct(
        private StaffRepositoryInterface $staffRepository
    ) {}

    public function getPaginatedStaff(array $filters, int $perPage = 15): array
    {
        $paginated = $this->staffRepository->paginateWithFilters($filters, $perPage);
        return $paginated->toArray();
    }

    public function getStaffMemberById(int $id): ?StaffMemberData
    {
        return $this->staffRepository->find($id);
    }

    public function createStaffMember(CreateStaffMemberData $data): StaffMemberData
    {
        return $this->staffRepository->create($data->toArray());
    }

    public function updateStaffMember(int $id, UpdateStaffMemberData $data): ?StaffMemberData
    {
        return $this->staffRepository->update($id, $data->toArray());
    }

    public function deleteStaffMember(int $id): bool
    {
        return $this->staffRepository->delete($id);
    }

    public function assignRole(int $staffId, int $roleId, ?int $locationId = null): bool
    {
        return $this->staffRepository->assignRole($staffId, $roleId, $locationId);
    }

    public function removeRole(int $staffId, int $roleId, ?int $locationId = null): bool
    {
        return $this->staffRepository->removeRole($staffId, $roleId, $locationId);
    }

    public function activateStaffMember(int $id): bool
    {
        return $this->staffRepository->update($id, ['status' => 'active']) !== null;
    }

    public function deactivateStaffMember(int $id): bool
    {
        return $this->staffRepository->update($id, ['status' => 'inactive']) !== null;
    }

    public function getStaffStats(): array
    {
        return [
            'total' => $this->staffRepository->count(),
            'active' => $this->staffRepository->countByStatus('active'),
            'on_leave' => $this->staffRepository->countByStatus('on_leave'),
            'inactive' => $this->staffRepository->countByStatus('inactive'),
        ];
    }

    public function getAvailableRoles(): DataCollection
    {
        // This would be injected from RoleRepositoryInterface
        return DataCollection::empty();
    }

    public function getAvailableLocations(): DataCollection
    {
        // This would be injected from Location module
        return DataCollection::empty();
    }

    public function getRecentAttendance(int $staffId, int $days = 7): DataCollection
    {
        // This would be injected from AttendanceRepositoryInterface
        return DataCollection::empty();
    }

    public function getUpcomingShifts(int $staffId, int $days = 7): DataCollection
    {
        // This would be injected from ShiftRepositoryInterface
        return DataCollection::empty();
    }
}