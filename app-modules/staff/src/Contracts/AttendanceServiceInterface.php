<?php

namespace Colame\Staff\Contracts;

use App\Core\Data\PaginatedResourceData;
use Carbon\Carbon;
use Colame\Staff\Data\AttendanceRecordData;
use Spatie\LaravelData\DataCollection;

interface AttendanceServiceInterface
{
    public function getAttendanceRecord(int $id): ?AttendanceRecordData;
    
    public function getPaginatedAttendance(array $filters, int $perPage): PaginatedResourceData;
    
    public function getStaffAttendance(int $staffId, Carbon $from, Carbon $to): DataCollection;
    
    public function getLocationAttendance(int $locationId, Carbon $date): DataCollection;
    
    public function getTodayAttendance(int $locationId): DataCollection;
    
    public function getAttendanceSummary(int $staffId, Carbon $from, Carbon $to): array;
    
    public function generateAttendanceReport(array $filters, Carbon $from, Carbon $to): string;
    
    public function calculateOvertimeHours(int $staffId, Carbon $from, Carbon $to): float;
    
    public function getAbsenteeReport(int $locationId, Carbon $date): DataCollection;
    
    public function getLateArrivalsReport(int $locationId, Carbon $from, Carbon $to): DataCollection;
}