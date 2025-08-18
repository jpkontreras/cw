<?php

namespace Colame\Staff\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Colame\Staff\Enums\ShiftStatus;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Lazy;

class ShiftData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $staffMemberId,
        public readonly int $locationId,
        public readonly Carbon $startTime,
        public readonly Carbon $endTime,
        public readonly int $breakDuration, // in minutes
        public readonly ShiftStatus $status,
        public readonly ?Carbon $actualStart,
        public readonly ?Carbon $actualEnd,
        public readonly ?string $notes,
        public readonly ?int $createdBy,
        public readonly ?int $approvedBy,
        public Lazy|StaffMemberData $staffMember,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}

    #[Computed]
    public function duration(): float
    {
        return $this->startTime->diffInHours($this->endTime);
    }

    #[Computed]
    public function workingHours(): float
    {
        return $this->duration() - ($this->breakDuration / 60);
    }

    #[Computed]
    public function actualDuration(): ?float
    {
        if (!$this->actualStart || !$this->actualEnd) {
            return null;
        }
        return $this->actualStart->diffInHours($this->actualEnd);
    }

    #[Computed]
    public function isToday(): bool
    {
        return $this->startTime->isToday();
    }

    #[Computed]
    public function isPast(): bool
    {
        return $this->endTime->isPast();
    }

    #[Computed]
    public function isFuture(): bool
    {
        return $this->startTime->isFuture();
    }

    #[Computed]
    public function isOngoing(): bool
    {
        $now = now();
        return $this->startTime->lte($now) && $this->endTime->gte($now);
    }

    public static function fromModel($shift): self
    {
        return new self(
            id: $shift->id,
            staffMemberId: $shift->staff_member_id,
            locationId: $shift->location_id,
            startTime: Carbon::parse($shift->start_time),
            endTime: Carbon::parse($shift->end_time),
            breakDuration: $shift->break_duration,
            status: ShiftStatus::from($shift->status),
            actualStart: $shift->actual_start ? Carbon::parse($shift->actual_start) : null,
            actualEnd: $shift->actual_end ? Carbon::parse($shift->actual_end) : null,
            notes: $shift->notes,
            createdBy: $shift->created_by,
            approvedBy: $shift->approved_by,
            staffMember: Lazy::whenLoaded('staffMember', $shift,
                fn() => StaffMemberData::fromModel($shift->staffMember)
            ),
            createdAt: Carbon::parse($shift->created_at),
            updatedAt: Carbon::parse($shift->updated_at),
        );
    }
}