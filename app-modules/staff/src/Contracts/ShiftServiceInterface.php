<?php

namespace Colame\Staff\Contracts;

use App\Core\Data\PaginatedResourceData;
use Carbon\Carbon;
use Colame\Staff\Data\AttendanceRecordData;
use Colame\Staff\Data\ClockInData;
use Colame\Staff\Data\ClockOutData;
use Colame\Staff\Data\CreateShiftData;
use Colame\Staff\Data\ShiftData;
use Colame\Staff\Data\UpdateShiftData;
use Spatie\LaravelData\DataCollection;

interface ShiftServiceInterface
{
    public function scheduleShift(CreateShiftData $data): ShiftData;
    
    public function updateShift(int $id, UpdateShiftData $data): ShiftData;
    
    public function cancelShift(int $id, string $reason): bool;
    
    public function getShift(int $id): ?ShiftData;
    
    public function getPaginatedShifts(array $filters, int $perPage): PaginatedResourceData;
    
    public function getStaffShifts(int $staffId, Carbon $from, Carbon $to): DataCollection;
    
    public function getLocationShifts(int $locationId, Carbon $date): DataCollection;
    
    public function checkShiftConflicts(int $staffId, Carbon $start, Carbon $end): DataCollection;
    
    public function clockIn(ClockInData $data): AttendanceRecordData;
    
    public function clockOut(int $attendanceId, ClockOutData $data): AttendanceRecordData;
    
    public function getActiveClockIn(int $staffId): ?AttendanceRecordData;
    
    public function swapShifts(int $shift1Id, int $shift2Id): bool;
    
    public function generateSchedule(int $locationId, Carbon $weekStart): array;
}