<?php

namespace Colame\Staff\Repositories;

use Colame\Staff\Contracts\StaffRepositoryInterface;
use Colame\Staff\Models\StaffMember;
use Colame\Staff\Data\StaffMemberData;
use App\Core\Data\PaginatedResourceData;
use Spatie\LaravelData\DataCollection;
use App\Core\Traits\ValidatesPagination;

class StaffRepository implements StaffRepositoryInterface
{
    use ValidatesPagination;

    public function find(int $id): ?StaffMemberData
    {
        $staff = StaffMember::find($id);
        return $staff ? StaffMemberData::from($staff) : null;
    }

    public function all(): DataCollection
    {
        return StaffMemberData::collection(StaffMember::all());
    }

    public function create(array $data): StaffMemberData
    {
        $staff = StaffMember::create($data);
        return StaffMemberData::from($staff);
    }

    public function update(int $id, array $data): ?StaffMemberData
    {
        $staff = StaffMember::find($id);
        if (!$staff) {
            return null;
        }
        
        $staff->update($data);
        return StaffMemberData::from($staff);
    }

    public function delete(int $id): bool
    {
        return StaffMember::destroy($id) > 0;
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): PaginatedResourceData
    {
        $perPage = $this->validatePerPage($perPage);
        
        $query = StaffMember::query();
        
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('roles.id', $filters['role']);
            });
        }
        
        if (!empty($filters['location'])) {
            $query->whereHas('locations', function ($q) use ($filters) {
                $q->where('locations.id', $filters['location']);
            });
        }
        
        $paginator = $query->paginate($perPage);
        
        return PaginatedResourceData::fromPaginator($paginator, StaffMemberData::class);
    }

    public function findByEmail(string $email): ?StaffMemberData
    {
        $staff = StaffMember::where('email', $email)->first();
        return $staff ? StaffMemberData::from($staff) : null;
    }

    public function findByEmployeeCode(string $code): ?StaffMemberData
    {
        $staff = StaffMember::where('employee_code', $code)->first();
        return $staff ? StaffMemberData::from($staff) : null;
    }

    public function assignRole(int $staffId, int $roleId, ?int $locationId = null): bool
    {
        $staff = StaffMember::find($staffId);
        if (!$staff) {
            return false;
        }
        
        // Using the custom pivot table for location-based roles
        $staff->locationRoles()->attach($roleId, [
            'location_id' => $locationId,
            'assigned_at' => now(),
            'assigned_by' => auth()->id(),
        ]);
        
        return true;
    }

    public function removeRole(int $staffId, int $roleId, ?int $locationId = null): bool
    {
        $staff = StaffMember::find($staffId);
        if (!$staff) {
            return false;
        }
        
        $query = $staff->locationRoles();
        
        if ($locationId) {
            $query->wherePivot('location_id', $locationId);
        }
        
        $query->detach($roleId);
        
        return true;
    }

    public function getStaffByRole(int $roleId, ?int $locationId = null): DataCollection
    {
        $query = StaffMember::whereHas('locationRoles', function ($q) use ($roleId, $locationId) {
            $q->where('role_id', $roleId);
            if ($locationId) {
                $q->where('location_id', $locationId);
            }
        });
        
        return StaffMemberData::collection($query->get());
    }

    public function getStaffByLocation(int $locationId): DataCollection
    {
        $query = StaffMember::whereHas('locationRoles', function ($q) use ($locationId) {
            $q->where('location_id', $locationId);
        });
        
        return StaffMemberData::collection($query->get());
    }

    public function count(): int
    {
        return StaffMember::count();
    }

    public function countByStatus(string $status): int
    {
        return StaffMember::where('status', $status)->count();
    }
}