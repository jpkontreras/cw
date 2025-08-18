<?php

namespace Colame\Staff\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Staff\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    public function index(): Response
    {
        return Inertia::render('staff/reports/index', [
            'availableReports' => $this->reportService->getAvailableReports(),
        ]);
    }

    public function attendance(Request $request): Response
    {
        $filters = $request->only(['date_from', 'date_to', 'staff_member', 'location']);
        
        return Inertia::render('staff/reports/attendance', [
            'report' => $this->reportService->generateAttendanceReport($filters),
            'filters' => $filters,
        ]);
    }

    public function schedule(Request $request): Response
    {
        $filters = $request->only(['date_from', 'date_to', 'location']);
        
        return Inertia::render('staff/reports/schedule', [
            'report' => $this->reportService->generateScheduleReport($filters),
            'filters' => $filters,
        ]);
    }

    public function performance(Request $request): Response
    {
        $filters = $request->only(['date_from', 'date_to', 'staff_member', 'location']);
        
        return Inertia::render('staff/reports/performance', [
            'report' => $this->reportService->generatePerformanceReport($filters),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:attendance,schedule,performance',
            'format' => 'required|in:csv,excel,pdf',
            'filters' => 'nullable|array',
        ]);
        
        $file = $this->reportService->exportReport(
            $validated['report_type'],
            $validated['format'],
            $validated['filters'] ?? []
        );
        
        return response()->download($file);
    }
}