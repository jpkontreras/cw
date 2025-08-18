<?php

namespace Colame\Staff\Services;

use Colame\Staff\Contracts\StaffRepositoryInterface;
use Colame\Staff\Data\CreateStaffMemberData;
use Colame\Staff\Data\UpdateStaffMemberData;
use Colame\Staff\Data\StaffMemberData;
use App\Core\Data\PaginatedResourceData;
use Spatie\LaravelData\DataCollection;
use Colame\Location\Contracts\LocationRepositoryInterface;

class StaffService
{
    public function __construct(
        private StaffRepositoryInterface $staffRepository,
        private ?LocationRepositoryInterface $locationRepository = null
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
        return $this->staffRepository->create($data);
    }

    public function updateStaffMember(int $id, UpdateStaffMemberData $data): StaffMemberData
    {
        return $this->staffRepository->update($id, $data);
    }

    public function deleteStaffMember(int $id): bool
    {
        return $this->staffRepository->delete($id);
    }

    public function assignRole(int $staffId, int $roleId, ?int $locationId = null): void
    {
        $this->staffRepository->assignRole($staffId, $roleId, $locationId);
    }

    public function removeRole(int $staffId, int $roleId, ?int $locationId = null): void
    {
        $this->staffRepository->removeRole($staffId, $roleId, $locationId);
    }

    public function activateStaffMember(int $id): StaffMemberData
    {
        $updateData = new UpdateStaffMemberData(
            email: new \Spatie\LaravelData\Optional(),
            firstName: new \Spatie\LaravelData\Optional(),
            lastName: new \Spatie\LaravelData\Optional(),
            employeeCode: new \Spatie\LaravelData\Optional(),
            nationalId: new \Spatie\LaravelData\Optional(),
            phone: new \Spatie\LaravelData\Optional(),
            dateOfBirth: new \Spatie\LaravelData\Optional(),
            hireDate: new \Spatie\LaravelData\Optional(),
            status: \Colame\Staff\Enums\StaffStatus::ACTIVE,
            address: new \Spatie\LaravelData\Optional(),
            emergencyContacts: new \Spatie\LaravelData\Optional(),
            profilePhotoUrl: new \Spatie\LaravelData\Optional(),
            metadata: new \Spatie\LaravelData\Optional(),
        );
        return $this->staffRepository->update($id, $updateData);
    }

    public function deactivateStaffMember(int $id): StaffMemberData
    {
        $updateData = new UpdateStaffMemberData(
            email: new \Spatie\LaravelData\Optional(),
            firstName: new \Spatie\LaravelData\Optional(),
            lastName: new \Spatie\LaravelData\Optional(),
            employeeCode: new \Spatie\LaravelData\Optional(),
            nationalId: new \Spatie\LaravelData\Optional(),
            phone: new \Spatie\LaravelData\Optional(),
            dateOfBirth: new \Spatie\LaravelData\Optional(),
            hireDate: new \Spatie\LaravelData\Optional(),
            status: \Colame\Staff\Enums\StaffStatus::INACTIVE,
            address: new \Spatie\LaravelData\Optional(),
            emergencyContacts: new \Spatie\LaravelData\Optional(),
            profilePhotoUrl: new \Spatie\LaravelData\Optional(),
            metadata: new \Spatie\LaravelData\Optional(),
        );
        return $this->staffRepository->update($id, $updateData);
    }

    public function getStaffStats(): array
    {
        return [
            'totalStaff' => $this->staffRepository->count(),
            'activeStaff' => $this->staffRepository->countByStatus(\Colame\Staff\Enums\StaffStatus::ACTIVE->value),
            'onLeave' => $this->staffRepository->countByStatus(\Colame\Staff\Enums\StaffStatus::ON_LEAVE->value),
            'presentToday' => 0, // TODO: Implement attendance tracking
            'scheduledToday' => 0, // TODO: Implement shift scheduling
            'averageAttendance' => 95.5, // TODO: Calculate from actual attendance data
        ];
    }

    public function getAvailableRoles(): array
    {
        // Get all available roles from the repository
        return $this->staffRepository->getAllRoles()->toArray();
    }

    public function getAvailableLocations(): array
    {
        // Return locations from the Location module if available
        if ($this->locationRepository) {
            return $this->locationRepository->getActive()->toArray();
        }
        return [];
    }

    public function getRecentAttendance(int $staffId, int $days = 7): array
    {
        // This would be injected from AttendanceRepositoryInterface
        return [];
    }

    public function getUpcomingShifts(int $staffId, int $days = 7): array
    {
        // This would be injected from ShiftRepositoryInterface
        return [];
    }
}