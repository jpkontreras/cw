<?php

namespace Colame\Staff\Services;

use Colame\Staff\Contracts\AttendanceRepositoryInterface;
use Colame\Staff\Data\AttendanceRecordData;
use App\Core\Data\PaginatedResourceData;
use Spatie\LaravelData\DataCollection;

class AttendanceService
{
    public function __construct(
        private AttendanceRepositoryInterface $attendanceRepository
    ) {}

    public function getPaginatedAttendance(array $filters, int $perPage = 20): array
    {
        $paginated = $this->attendanceRepository->paginateWithFilters($filters, $perPage);
        return $paginated->toArray();
    }

    public function getRecordById(int $id): ?AttendanceRecordData
    {
        return $this->attendanceRepository->find($id);
    }

    public function clockIn(array $data): ?AttendanceRecordData
    {
        // Check if already clocked in
        $existing = $this->attendanceRepository->getCurrentClockIn($data['staff_member_id']);
        if ($existing) {
            return null;
        }
        
        return $this->attendanceRepository->clockIn($data);
    }

    public function clockOut(array $data): ?AttendanceRecordData
    {
        $record = $this->attendanceRepository->getCurrentClockIn($data['staff_member_id']);
        if (!$record) {
            return null;
        }
        
        return $this->attendanceRepository->clockOut($record->id, $data);
    }

    public function updateRecord(int $id, array $data): ?AttendanceRecordData
    {
        return $this->attendanceRepository->update($id, $data);
    }

    public function getCurrentClockIns(): DataCollection
    {
        return $this->attendanceRepository->getCurrentClockIns();
    }

    public function getDailyStats(): array
    {
        return [
            'total_present' => $this->attendanceRepository->countPresentToday(),
            'on_time' => $this->attendanceRepository->countOnTimeToday(),
            'late' => $this->attendanceRepository->countLateToday(),
            'absent' => $this->attendanceRepository->countAbsentToday(),
        ];
    }
}