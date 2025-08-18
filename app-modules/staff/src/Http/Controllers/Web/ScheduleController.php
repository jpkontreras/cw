<?php

namespace Colame\Staff\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Staff\Services\ScheduleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleService $scheduleService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['location', 'date_from', 'date_to', 'staff_member']);
        
        return Inertia::render('staff/schedule/index', [
            'shifts' => $this->scheduleService->getShifts($filters),
            'staffMembers' => $this->scheduleService->getAvailableStaff(),
            'locations' => $this->scheduleService->getLocations(),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('staff/schedule/create', [
            'staffMembers' => $this->scheduleService->getAvailableStaff(),
            'locations' => $this->scheduleService->getLocations(),
            'shiftTemplates' => $this->scheduleService->getShiftTemplates(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_member_id' => 'required|exists:staff_members,id',
            'location_id' => 'required|exists:locations,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'break_duration' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);
        
        $shift = $this->scheduleService->createShift($validated);
        
        return redirect()
            ->route('staff.schedule.index')
            ->with('success', 'Shift created successfully');
    }

    public function show(int $id): Response
    {
        $shift = $this->scheduleService->getShiftById($id);
        
        if (!$shift) {
            abort(404, 'Shift not found');
        }
        
        return Inertia::render('staff/schedule/show', [
            'shift' => $shift,
            'swapRequests' => $this->scheduleService->getSwapRequests($id),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'break_duration' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);
        
        $shift = $this->scheduleService->updateShift($id, $validated);
        
        if (!$shift) {
            abort(404, 'Shift not found');
        }
        
        return redirect()
            ->back()
            ->with('success', 'Shift updated successfully');
    }

    public function destroy(int $id)
    {
        $result = $this->scheduleService->deleteShift($id);
        
        if (!$result) {
            abort(404, 'Shift not found');
        }
        
        return redirect()
            ->route('staff.schedule.index')
            ->with('success', 'Shift deleted successfully');
    }

    public function swap(Request $request, int $id)
    {
        $validated = $request->validate([
            'target_staff_id' => 'required|exists:staff_members,id',
            'reason' => 'nullable|string',
        ]);
        
        $result = $this->scheduleService->requestSwap($id, $validated);
        
        if (!$result) {
            abort(404, 'Shift not found');
        }
        
        return redirect()
            ->back()
            ->with('success', 'Swap request submitted successfully');
    }
}