<?php

namespace Colame\Staff\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Colame\Staff\Enums\AttendanceStatus;
use Colame\Staff\Enums\ClockMethod;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Lazy;

class AttendanceRecordData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $staffMemberId,
        public readonly ?int $shiftId,
        public readonly int $locationId,
        public readonly Carbon $clockInTime,
        public readonly ?Carbon $clockOutTime,
        public readonly ClockMethod $clockInMethod,
        public readonly ?ClockMethod $clockOutMethod,
        public readonly ?array $clockInLocation, // GPS coordinates
        public readonly ?array $clockOutLocation,
        public readonly ?Carbon $breakStart,
        public readonly ?Carbon $breakEnd,
        public readonly int $overtimeMinutes,
        public readonly AttendanceStatus $status,
        public readonly ?string $notes,
        public Lazy|StaffMemberData $staffMember,
        public Lazy|ShiftData $shift,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}

    #[Computed]
    public function totalHours(): ?float
    {
        if (!$this->clockOutTime) {
            return null;
        }
        
        $totalMinutes = $this->clockInTime->diffInMinutes($this->clockOutTime);
        
        if ($this->breakStart && $this->breakEnd) {
            $breakMinutes = $this->breakStart->diffInMinutes($this->breakEnd);
            $totalMinutes -= $breakMinutes;
        }
        
        return round($totalMinutes / 60, 2);
    }

    #[Computed]
    public function regularHours(): ?float
    {
        if (!$this->totalHours()) {
            return null;
        }
        
        $regularHours = $this->totalHours() - ($this->overtimeMinutes / 60);
        return max(0, $regularHours);
    }

    #[Computed]
    public function overtimeHours(): float
    {
        return round($this->overtimeMinutes / 60, 2);
    }

    #[Computed]
    public function isActive(): bool
    {
        return $this->clockInTime && !$this->clockOutTime;
    }

    #[Computed]
    public function isLate(): bool
    {
        if (!$this->shift instanceof ShiftData) {
            return false;
        }
        
        return $this->clockInTime->gt($this->shift->startTime->addMinutes(5));
    }

    public static function fromModel($attendance): self
    {
        return new self(
            id: $attendance->id,
            staffMemberId: $attendance->staff_member_id,
            shiftId: $attendance->shift_id,
            locationId: $attendance->location_id,
            clockInTime: Carbon::parse($attendance->clock_in_time),
            clockOutTime: $attendance->clock_out_time ? Carbon::parse($attendance->clock_out_time) : null,
            clockInMethod: ClockMethod::from($attendance->clock_in_method),
            clockOutMethod: $attendance->clock_out_method ? ClockMethod::from($attendance->clock_out_method) : null,
            clockInLocation: $attendance->clock_in_location,
            clockOutLocation: $attendance->clock_out_location,
            breakStart: $attendance->break_start ? Carbon::parse($attendance->break_start) : null,
            breakEnd: $attendance->break_end ? Carbon::parse($attendance->break_end) : null,
            overtimeMinutes: $attendance->overtime_minutes ?? 0,
            status: AttendanceStatus::from($attendance->status),
            notes: $attendance->notes,
            staffMember: Lazy::whenLoaded('staffMember', $attendance,
                fn() => StaffMemberData::fromModel($attendance->staffMember)
            ),
            shift: Lazy::whenLoaded('shift', $attendance,
                fn() => ShiftData::fromModel($attendance->shift)
            ),
            createdAt: Carbon::parse($attendance->created_at),
            updatedAt: Carbon::parse($attendance->updated_at),
        );
    }
}