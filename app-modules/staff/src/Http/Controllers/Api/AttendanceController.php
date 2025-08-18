<?php

namespace Colame\Staff\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Staff\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

    public function clockIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_member_id' => 'required|exists:staff_members,id',
            'location_id' => 'required|exists:locations,id',
            'notes' => 'nullable|string',
        ]);
        
        $record = $this->attendanceService->clockIn($validated);
        
        if (!$record) {
            return response()->json([
                'message' => 'Already clocked in or error occurred'
            ], 400);
        }
        
        return response()->json([
            'message' => 'Clocked in successfully',
            'data' => $record,
        ], 201);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_member_id' => 'required|exists:staff_members,id',
            'notes' => 'nullable|string',
        ]);
        
        $record = $this->attendanceService->clockOut($validated);
        
        if (!$record) {
            return response()->json([
                'message' => 'Not clocked in or error occurred'
            ], 400);
        }
        
        return response()->json([
            'message' => 'Clocked out successfully',
            'data' => $record,
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $currentClockIns = $this->attendanceService->getCurrentClockIns();
        
        return response()->json([
            'data' => $currentClockIns,
            'stats' => $this->attendanceService->getDailyStats(),
        ]);
    }
}