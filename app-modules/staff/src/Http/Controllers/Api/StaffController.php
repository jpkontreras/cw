<?php

namespace Colame\Staff\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Staff\Services\StaffService;
use Colame\Staff\Data\CreateStaffMemberData;
use Colame\Staff\Data\UpdateStaffMemberData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StaffController extends Controller
{
    public function __construct(
        private StaffService $staffService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'role', 'status', 'location']);
        $perPage = $request->get('per_page', 15);
        
        $data = $this->staffService->getPaginatedStaff($filters, $perPage);
        
        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $data = CreateStaffMemberData::validateAndCreate($request);
        $staff = $this->staffService->createStaffMember($data);
        
        return response()->json($staff, 201);
    }

    public function show(int $id): JsonResponse
    {
        $staff = $this->staffService->getStaffMemberById($id);
        
        if (!$staff) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }
        
        return response()->json($staff);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = UpdateStaffMemberData::validateAndCreate($request);
        $staff = $this->staffService->updateStaffMember($id, $data);
        
        if (!$staff) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }
        
        return response()->json($staff);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->staffService->deleteStaffMember($id);
        
        if (!$result) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }
        
        return response()->json(['message' => 'Staff member deleted successfully']);
    }

    public function assignRole(Request $request, int $staffId): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'location_id' => 'nullable|exists:locations,id',
        ]);
        
        $result = $this->staffService->assignRole(
            $staffId,
            $validated['role_id'],
            $validated['location_id'] ?? null
        );
        
        if (!$result) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }
        
        return response()->json(['message' => 'Role assigned successfully']);
    }

    public function removeRole(Request $request, int $staffId, int $roleId): JsonResponse
    {
        $locationId = $request->get('location_id');
        
        $result = $this->staffService->removeRole($staffId, $roleId, $locationId);
        
        if (!$result) {
            return response()->json(['message' => 'Staff member or role not found'], 404);
        }
        
        return response()->json(['message' => 'Role removed successfully']);
    }
}