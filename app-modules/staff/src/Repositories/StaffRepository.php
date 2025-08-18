<?php

namespace Colame\Staff\Repositories;

use Colame\Staff\Contracts\StaffRepositoryInterface;
use Colame\Staff\Models\StaffMember;
use Colame\Staff\Data\StaffMemberData;
use Colame\Staff\Data\CreateStaffMemberData;
use Colame\Staff\Data\UpdateStaffMemberData;
use Colame\Staff\Data\RoleData;
use App\Core\Data\PaginatedResourceData;
use Spatie\LaravelData\DataCollection;
use App\Core\Traits\ValidatesPagination;
use Colame\Staff\Enums\StaffStatus;

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

    public function create(CreateStaffMemberData $data): StaffMemberData
    {
        $staff = StaffMember::create($data->toArray());
        return StaffMemberData::from($staff);
    }

    public function update(int $id, UpdateStaffMemberData $data): StaffMemberData
    {
        $staff = StaffMember::findOrFail($id);
        
        // Convert to array and filter out Optional values
        $updateData = collect($data->toArray())
            ->filter(fn($value) => !$value instanceof \Spatie\LaravelData\Optional)
            ->toArray();
        
        $staff->update($updateData);
        return StaffMemberData::from($staff);
    }

    public function delete(int $id): bool
    {
        return StaffMember::destroy($id) > 0;
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): PaginatedResourceData
    {
        $perPage = $this->validatePerPage($perPage);
        
        $query = StaffMember::with(['roles', 'locations']);
        
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

    public function assignRole(int $staffId, int $roleId, ?int $locationId = null): void
    {
        $staff = StaffMember::findOrFail($staffId);
        
        // Using the custom pivot table for location-based roles
        $staff->locationRoles()->attach($roleId, [
            'location_id' => $locationId,
            'assigned_at' => now(),
            'assigned_by' => auth()->id(),
        ]);
    }

    public function removeRole(int $staffId, int $roleId, ?int $locationId = null): void
    {
        $staff = StaffMember::findOrFail($staffId);
        
        $query = $staff->locationRoles();
        
        if ($locationId) {
            $query->wherePivot('location_id', $locationId);
        }
        
        $query->detach($roleId);
    }

    public function getByRole(int $roleId): DataCollection
    {
        $query = StaffMember::whereHas('locationRoles', function ($q) use ($roleId) {
            $q->where('role_id', $roleId);
        });
        
        return StaffMemberData::collection($query->get());
    }

    public function getByLocation(int $locationId): DataCollection
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

    public function getActiveStaff(): DataCollection
    {
        $query = StaffMember::where('status', StaffStatus::ACTIVE->value);
        return StaffMemberData::collection($query->get());
    }

    public function getRoles(int $staffId): DataCollection
    {
        $staff = StaffMember::with('locationRoles')->findOrFail($staffId);
        return RoleData::collection($staff->locationRoles);
    }
    
    public function getAllRoles(): DataCollection
    {
        // Get all unique roles that have been assigned or are available
        // Join with roles table to get role names and metadata for hierarchy
        $roles = \DB::table('roles')
            ->leftJoin('staff_role_metadata', 'roles.id', '=', 'staff_role_metadata.role_id')
            ->select(
                'roles.id',
                'roles.name',
                \DB::raw('COALESCE(staff_role_metadata.hierarchy_level, 10) as hierarchy_level'),
                'staff_role_metadata.description',
                'staff_role_metadata.is_system',
                'roles.created_at',
                'roles.updated_at'
            )
            ->orderBy(\DB::raw('COALESCE(staff_role_metadata.hierarchy_level, 10)'))
            ->orderBy('roles.name')
            ->get();
        
        // RoleData::collection expects objects, not arrays
        return RoleData::collection($roles);
    }
}