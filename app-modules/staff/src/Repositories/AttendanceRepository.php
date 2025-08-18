<?php

namespace Colame\Staff\Repositories;

use Colame\Staff\Contracts\AttendanceRepositoryInterface;
use Colame\Staff\Models\AttendanceRecord;
use Colame\Staff\Data\AttendanceRecordData;
use App\Core\Data\PaginatedResourceData;
use Spatie\LaravelData\DataCollection;
use App\Core\Traits\ValidatesPagination;

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
            $query->whereDate('clock_in', $filters['date']);
        }
        
        if (!empty($filters['staff_member'])) {
            $query->where('staff_member_id', $filters['staff_member']);
        }
        
        if (!empty($filters['location'])) {
            $query->where('location_id', $filters['location']);
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'clocked_in') {
                $query->whereNull('clock_out');
            } elseif ($filters['status'] === 'clocked_out') {
                $query->whereNotNull('clock_out');
            }
        }
        
        $paginator = $query->orderBy('clock_in', 'desc')->paginate($perPage);
        
        return PaginatedResourceData::fromPaginator($paginator, AttendanceRecordData::class);
    }

    public function clockIn(array $data): AttendanceRecordData
    {
        $record = AttendanceRecord::create([
            'staff_member_id' => $data['staff_member_id'],
            'location_id' => $data['location_id'],
            'clock_in' => now(),
            'notes' => $data['notes'] ?? null,
        ]);
        
        return AttendanceRecordData::from($record);
    }

    public function clockOut(int $recordId, array $data): ?AttendanceRecordData
    {
        $record = AttendanceRecord::find($recordId);
        if (!$record) {
            return null;
        }
        
        $record->update([
            'clock_out' => now(),
            'notes' => ($record->notes ? $record->notes . "\n" : "") . ($data['notes'] ?? ''),
        ]);
        
        return AttendanceRecordData::from($record);
    }

    public function getCurrentClockIn(int $staffId): ?AttendanceRecordData
    {
        $record = AttendanceRecord::where('staff_member_id', $staffId)
            ->whereNull('clock_out')
            ->latest('clock_in')
            ->first();
            
        return $record ? AttendanceRecordData::from($record) : null;
    }

    public function getCurrentClockIns(): DataCollection
    {
        $records = AttendanceRecord::whereNull('clock_out')
            ->with('staffMember')
            ->get();
            
        return AttendanceRecordData::collection($records);
    }

    public function getRecordsByStaff(int $staffId, ?string $fromDate = null, ?string $toDate = null): DataCollection
    {
        $query = AttendanceRecord::where('staff_member_id', $staffId);
        
        if ($fromDate) {
            $query->where('clock_in', '>=', $fromDate);
        }
        
        if ($toDate) {
            $query->where('clock_in', '<=', $toDate);
        }
        
        return AttendanceRecordData::collection($query->get());
    }

    public function countPresentToday(): int
    {
        return AttendanceRecord::whereDate('clock_in', today())->distinct('staff_member_id')->count();
    }

    public function countOnTimeToday(): int
    {
        // This would check against scheduled shift times
        return AttendanceRecord::whereDate('clock_in', today())
            ->whereTime('clock_in', '<=', '09:00:00')
            ->distinct('staff_member_id')
            ->count();
    }

    public function countLateToday(): int
    {
        return AttendanceRecord::whereDate('clock_in', today())
            ->whereTime('clock_in', '>', '09:00:00')
            ->distinct('staff_member_id')
            ->count();
    }

    public function countAbsentToday(): int
    {
        // This would check against scheduled staff
        return 0; // Placeholder
    }
}