<?php

namespace Colame\Staff\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Staff\Services\StaffService;
use Colame\Staff\Data\CreateStaffMemberData;
use Colame\Staff\Data\UpdateStaffMemberData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(
        private StaffService $staffService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'role', 'status', 'location']);
        $perPage = $request->get('per_page', 15);
        
        $data = $this->staffService->getPaginatedStaff($filters, $perPage);
        
        return Inertia::render('staff/index', [
            'staff' => $data['data'],
            'pagination' => $data['pagination'],
            'metadata' => [
                'filters' => $filters,
                'per_page' => $perPage,
            ],
            'features' => [
                'biometric_clock' => false,
                'mobile_clock' => true,
                'shift_swapping' => false,
                'performance_tracking' => false,
                'training_modules' => false,
                'payroll_integration' => false,
            ],
            'stats' => $this->staffService->getStaffStats(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('staff/create', [
            'roles' => $this->staffService->getAvailableRoles(),
            'locations' => $this->staffService->getAvailableLocations(),
        ]);
    }

    public function store(Request $request)
    {
        $data = CreateStaffMemberData::validateAndCreate($request);
        $staff = $this->staffService->createStaffMember($data);
        
        return redirect()
            ->route('staff.show', $staff->id)
            ->with('success', 'Staff member created successfully');
    }

    public function show(int $id): Response
    {
        $staff = $this->staffService->getStaffMemberById($id);
        
        if (!$staff) {
            abort(404, 'Staff member not found');
        }
        
        return Inertia::render('staff/show', [
            'staffMember' => $staff,
            'attendance' => $this->staffService->getRecentAttendance($id),
            'schedule' => $this->staffService->getUpcomingShifts($id),
        ]);
    }

    public function edit(int $id): Response
    {
        $staff = $this->staffService->getStaffMemberById($id);
        
        if (!$staff) {
            abort(404, 'Staff member not found');
        }
        
        return Inertia::render('staff/edit', [
            'staffMember' => $staff,
            'roles' => $this->staffService->getAvailableRoles(),
            'locations' => $this->staffService->getAvailableLocations(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $data = UpdateStaffMemberData::validateAndCreate($request);
        $staff = $this->staffService->updateStaffMember($id, $data);
        
        if (!$staff) {
            abort(404, 'Staff member not found');
        }
        
        return redirect()
            ->route('staff.show', $id)
            ->with('success', 'Staff member updated successfully');
    }

    public function destroy(int $id)
    {
        $result = $this->staffService->deleteStaffMember($id);
        
        if (!$result) {
            abort(404, 'Staff member not found');
        }
        
        return redirect()
            ->route('staff.index')
            ->with('success', 'Staff member deleted successfully');
    }

    public function assignRole(Request $request, int $id)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'location_id' => 'nullable|exists:locations,id',
        ]);
        
        $result = $this->staffService->assignRole(
            $id,
            $request->role_id,
            $request->location_id
        );
        
        if (!$result) {
            abort(404, 'Staff member not found');
        }
        
        return redirect()
            ->back()
            ->with('success', 'Role assigned successfully');
    }

    public function removeRole(Request $request, int $id)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'location_id' => 'nullable|exists:locations,id',
        ]);
        
        $result = $this->staffService->removeRole(
            $id,
            $request->role_id,
            $request->location_id
        );
        
        if (!$result) {
            abort(404, 'Staff member not found');
        }
        
        return redirect()
            ->back()
            ->with('success', 'Role removed successfully');
    }

    public function activate(int $id)
    {
        $result = $this->staffService->activateStaffMember($id);
        
        if (!$result) {
            abort(404, 'Staff member not found');
        }
        
        return redirect()
            ->back()
            ->with('success', 'Staff member activated successfully');
    }

    public function deactivate(int $id)
    {
        $result = $this->staffService->deactivateStaffMember($id);
        
        if (!$result) {
            abort(404, 'Staff member not found');
        }
        
        return redirect()
            ->back()
            ->with('success', 'Staff member deactivated successfully');
    }
}