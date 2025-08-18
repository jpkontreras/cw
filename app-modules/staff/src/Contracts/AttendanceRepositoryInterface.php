<?php

namespace Colame\Staff\Contracts;

use App\Core\Data\PaginatedResourceData;
use Carbon\Carbon;
use Colame\Staff\Data\AttendanceRecordData;
use Colame\Staff\Data\ClockInData;
use Colame\Staff\Data\ClockOutData;
use Spatie\LaravelData\DataCollection;

interface AttendanceRepositoryInterface
{
    public function find(int $id): ?AttendanceRecordData;
    
    public function clockIn(ClockInData $data): AttendanceRecordData;
    
    public function clockOut(int $attendanceId, ClockOutData $data): AttendanceRecordData;
    
    public function getActiveClockIn(int $staffId): ?AttendanceRecordData;
    
    public function getByStaffMember(int $staffId, ?Carbon $from = null, ?Carbon $to = null): DataCollection;
    
    public function getByLocation(int $locationId, Carbon $date): DataCollection;
    
    public function paginateWithFilters(array $filters, int $perPage): PaginatedResourceData;
    
    public function getAttendanceSummary(int $staffId, Carbon $from, Carbon $to): array;
    
    public function getTodayAttendance(int $locationId): DataCollection;
}