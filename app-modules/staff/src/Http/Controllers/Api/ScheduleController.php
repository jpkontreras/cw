<?php

namespace Colame\Staff\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Staff\Services\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleService $scheduleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['location', 'date_from', 'date_to', 'staff_member']);
        
        $shifts = $this->scheduleService->getShifts($filters);
        
        return response()->json([
            'data' => $shifts,
        ]);
    }

    public function myShifts(Request $request): JsonResponse
    {
        $staffId = $request->user()->staff_member_id ?? null;
        
        if (!$staffId) {
            return response()->json([
                'message' => 'User is not associated with a staff member'
            ], 400);
        }
        
        $filters = [
            'staff_member' => $staffId,
            'date_from' => now()->startOfDay(),
            'date_to' => now()->addDays(14)->endOfDay(),
        ];
        
        $shifts = $this->scheduleService->getShifts($filters);
        
        return response()->json([
            'data' => $shifts,
        ]);
    }
}