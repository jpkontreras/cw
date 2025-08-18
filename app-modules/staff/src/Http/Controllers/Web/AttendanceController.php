<?php

namespace Colame\Staff\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Staff\Services\AttendanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['date', 'staff_member', 'location', 'status']);
        $perPage = $request->get('per_page', 20);
        
        $data = $this->attendanceService->getPaginatedAttendance($filters, $perPage);
        
        return Inertia::render('staff/attendance/index', [
            'attendance' => $data['data'] ?? [],
            'pagination' => $data['pagination'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'filters' => $filters,
            'activeStaff' => [], // TODO: Get active staff from service
            'locations' => [], // TODO: Get locations from location service
            'currentClockIns' => $this->attendanceService->getCurrentClockIns()->toArray(),
            'stats' => $this->attendanceService->getDailyStats(),
            'features' => [
                'biometric_clock' => false,
                'mobile_clock' => true,
                'facial_recognition' => false,
            ],
        ]);
    }

    public function clockIn(Request $request)
    {
        $validated = $request->validate([
            'staff_member_id' => 'required|exists:staff_members,id',
            'location_id' => 'required|exists:locations,id',
            'notes' => 'nullable|string',
        ]);
        
        $record = $this->attendanceService->clockIn($validated);
        
        if (!$record) {
            return redirect()
                ->back()
                ->with('error', 'Already clocked in or error occurred');
        }
        
        return redirect()
            ->back()
            ->with('success', 'Clocked in successfully');
    }

    public function clockOut(Request $request)
    {
        $validated = $request->validate([
            'staff_member_id' => 'required|exists:staff_members,id',
            'notes' => 'nullable|string',
        ]);
        
        $record = $this->attendanceService->clockOut($validated);
        
        if (!$record) {
            return redirect()
                ->back()
                ->with('error', 'Not clocked in or error occurred');
        }
        
        return redirect()
            ->back()
            ->with('success', 'Clocked out successfully');
    }

    public function show(int $id): Response
    {
        $record = $this->attendanceService->getRecordById($id);
        
        if (!$record) {
            abort(404, 'Attendance record not found');
        }
        
        return Inertia::render('staff/attendance/show', [
            'record' => $record,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'clock_in' => 'required|date',
            'clock_out' => 'nullable|date|after:clock_in',
            'break_duration' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);
        
        $record = $this->attendanceService->updateRecord($id, $validated);
        
        if (!$record) {
            abort(404, 'Attendance record not found');
        }
        
        return redirect()
            ->back()
            ->with('success', 'Attendance record updated successfully');
    }
}