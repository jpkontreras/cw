<?php

namespace Colame\Staff\Repositories;

use Colame\Staff\Contracts\AttendanceRepositoryInterface;
use Colame\Staff\Models\AttendanceRecord;
use Colame\Staff\Data\AttendanceRecordData;
use Colame\Staff\Data\ClockInData;
use Colame\Staff\Data\ClockOutData;
use App\Core\Data\PaginatedResourceData;
use Spatie\LaravelData\DataCollection;
use App\Core\Traits\ValidatesPagination;
use Carbon\Carbon;

class AttendanceRepository implements AttendanceRepositoryInterface
{
    use ValidatesPagination;

    public function find(int $id): ?AttendanceRecordData
    {
        $record = AttendanceRecord::find($id);
        return $record ? AttendanceRecordData::from($record) : null;
    }

    public function all(): DataCollection
    {
        return AttendanceRecordData::collection(AttendanceRecord::all());
    }

    public function create(array $data): AttendanceRecordData
    {
        $record = AttendanceRecord::create($data);
        return AttendanceRecordData::from($record);
    }

    public function update(int $id, array $data): ?AttendanceRecordData
    {
        $record = AttendanceRecord::find($id);
        if (!$record) {
            return null;
        }
        
        $record->update($data);
        return AttendanceRecordData::from($record);
    }

    public function delete(int $id): bool
    {
        return AttendanceRecord::destroy($id) > 0;
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): PaginatedResourceData
    {
        $perPage = $this->validatePerPage($perPage);
        
        $query = AttendanceRecord::query();
        
        if (!empty($filters['date'])) {
            $query->whereDate('clock_in_time', $filters['date']);
        }
        
        if (!empty($filters['staff_member'])) {
            $query->where('staff_member_id', $filters['staff_member']);
        }
        
        if (!empty($filters['location'])) {
            $query->where('location_id', $filters['location']);
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'clocked_in') {
                $query->whereNull('clock_out_time');
            } elseif ($filters['status'] === 'clocked_out') {
                $query->whereNotNull('clock_out_time');
            }
        }
        
        $paginator = $query->orderBy('clock_in_time', 'desc')->paginate($perPage);
        
        return PaginatedResourceData::fromPaginator($paginator, AttendanceRecordData::class);
    }

    public function clockIn(ClockInData $data): AttendanceRecordData
    {
        $record = AttendanceRecord::create([
            'staff_member_id' => $data->staffMemberId,
            'location_id' => $data->locationId,
            'shift_id' => $data->shiftId ?? null,
            'clock_in_time' => now(),
            'clock_in_method' => $data->clockMethod->value,
            'clock_in_location' => $data->gpsLocation ?? null,
            'notes' => $data->notes ?? null,
            'status' => 'present',
        ]);
        
        return AttendanceRecordData::from($record);
    }

    public function clockOut(int $attendanceId, ClockOutData $data): AttendanceRecordData
    {
        $record = AttendanceRecord::findOrFail($attendanceId);
        
        $record->update([
            'clock_out_time' => now(),
            'clock_out_method' => $data->clockMethod->value,
            'clock_out_location' => $data->gpsLocation ?? null,
            'notes' => ($record->notes ? $record->notes . "\n" : "") . ($data['notes'] ?? ''),
        ]);
        
        return AttendanceRecordData::from($record);
    }

    public function getCurrentClockIn(int $staffId): ?AttendanceRecordData
    {
        $record = AttendanceRecord::where('staff_member_id', $staffId)
            ->whereNull('clock_out_time')
            ->latest('clock_in_time')
            ->first();
            
        return $record ? AttendanceRecordData::from($record) : null;
    }

    public function getCurrentClockIns(): DataCollection
    {
        $records = AttendanceRecord::whereNull('clock_out_time')
            ->with('staffMember')
            ->get();
            
        return AttendanceRecordData::collection($records);
    }

    public function getRecordsByStaff(int $staffId, ?string $fromDate = null, ?string $toDate = null): DataCollection
    {
        $query = AttendanceRecord::where('staff_member_id', $staffId);
        
        if ($fromDate) {
            $query->where('clock_in_time', '>=', $fromDate);
        }
        
        if ($toDate) {
            $query->where('clock_in_time', '<=', $toDate);
        }
        
        return AttendanceRecordData::collection($query->get());
    }

    public function countPresentToday(): int
    {
        return AttendanceRecord::whereDate('clock_in_time', today())->distinct('staff_member_id')->count();
    }

    public function countOnTimeToday(): int
    {
        // This would check against scheduled shift times
        return AttendanceRecord::whereDate('clock_in_time', today())
            ->whereTime('clock_in_time', '<=', '09:00:00')
            ->distinct('staff_member_id')
            ->count();
    }

    public function countLateToday(): int
    {
        return AttendanceRecord::whereDate('clock_in_time', today())
            ->whereTime('clock_in_time', '>', '09:00:00')
            ->distinct('staff_member_id')
            ->count();
    }

    public function countAbsentToday(): int
    {
        // This would check against scheduled staff
        return 0; // Placeholder
    }
    
    // Interface required methods
    public function getActiveClockIn(int $staffId): ?AttendanceRecordData
    {
        return $this->getCurrentClockIn($staffId);
    }
    
    public function getByStaffMember(int $staffId, ?Carbon $from = null, ?Carbon $to = null): DataCollection
    {
        $query = AttendanceRecord::where('staff_member_id', $staffId);
        
        if ($from) {
            $query->where('clock_in_time', '>=', $from);
        }
        
        if ($to) {
            $query->where('clock_in_time', '<=', $to);
        }
        
        return AttendanceRecordData::collection($query->orderBy('clock_in_time', 'desc')->get());
    }
    
    public function getByLocation(int $locationId, Carbon $date): DataCollection
    {
        return AttendanceRecordData::collection(
            AttendanceRecord::where('location_id', $locationId)
                ->whereDate('clock_in_time', $date)
                ->with('staffMember')
                ->orderBy('clock_in_time')
                ->get()
        );
    }
    
    public function getAttendanceSummary(int $staffId, Carbon $from, Carbon $to): array
    {
        $records = AttendanceRecord::where('staff_member_id', $staffId)
            ->whereBetween('clock_in_time', [$from, $to])
            ->get();
        
        $totalDays = $records->count();
        $totalHours = 0;
        $lateCount = 0;
        $earlyLeaveCount = 0;
        
        foreach ($records as $record) {
            if ($record->clock_out_time) {
                $hours = Carbon::parse($record->clock_in_time)->diffInHours(Carbon::parse($record->clock_out_time));
                $totalHours += $hours;
            }
            
            // Check if late (after 9 AM for example)
            if (Carbon::parse($record->clock_in_time)->format('H:i') > '09:00') {
                $lateCount++;
            }
            
            // Check if left early (before 5 PM for example)
            if ($record->clock_out_time && Carbon::parse($record->clock_out_time)->format('H:i') < '17:00') {
                $earlyLeaveCount++;
            }
        }
        
        return [
            'totalDays' => $totalDays,
            'totalHours' => $totalHours,
            'averageHours' => $totalDays > 0 ? round($totalHours / $totalDays, 2) : 0,
            'lateCount' => $lateCount,
            'earlyLeaveCount' => $earlyLeaveCount,
            'attendanceRate' => $totalDays > 0 ? round(($totalDays / $from->diffInDays($to)) * 100, 2) : 0,
        ];
    }
    
    public function getTodayAttendance(int $locationId): DataCollection
    {
        return $this->getByLocation($locationId, now());
    }
}