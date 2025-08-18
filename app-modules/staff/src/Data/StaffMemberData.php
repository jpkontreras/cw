<?php

namespace Colame\Staff\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Colame\Staff\Enums\StaffStatus;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;

class StaffMemberData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $employeeCode,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly array $address,
        public readonly ?Carbon $dateOfBirth,
        public readonly Carbon $hireDate,
        public readonly string $nationalId,
        public readonly StaffStatus $status,
        public readonly array $metadata,
        public readonly ?Carbon $terminatedAt,
        public readonly ?string $profilePhotoUrl,
        public Lazy|DataCollection $roles,
        public Lazy|DataCollection $shifts,
        public Lazy|DataCollection $attendanceRecords,
        public Lazy|DataCollection $emergencyContacts,
        public readonly Carbon $createdAt,
        public readonly Carbon $updatedAt,
    ) {}

    #[Computed]
    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    #[Computed]
    public function yearsOfService(): int
    {
        return $this->hireDate->diffInYears(now());
    }

    #[Computed]
    public function age(): ?int
    {
        return $this->dateOfBirth?->age;
    }

    #[Computed]
    public function isActive(): bool
    {
        return $this->status === StaffStatus::ACTIVE;
    }

    #[Computed]
    public function displayStatus(): string
    {
        return match($this->status) {
            StaffStatus::ACTIVE => 'Active',
            StaffStatus::INACTIVE => 'Inactive',
            StaffStatus::SUSPENDED => 'Suspended',
            StaffStatus::TERMINATED => 'Terminated',
            StaffStatus::ON_LEAVE => 'On Leave',
        };
    }

    public static function fromModel($staffMember): self
    {
        return new self(
            id: $staffMember->id,
            employeeCode: $staffMember->employee_code,
            firstName: $staffMember->first_name,
            lastName: $staffMember->last_name,
            email: $staffMember->email,
            phone: $staffMember->phone,
            address: $staffMember->address ?? [],
            dateOfBirth: $staffMember->date_of_birth ? Carbon::parse($staffMember->date_of_birth) : null,
            hireDate: Carbon::parse($staffMember->hire_date),
            nationalId: $staffMember->national_id,
            status: StaffStatus::from($staffMember->status),
            metadata: $staffMember->metadata ?? [],
            terminatedAt: $staffMember->terminated_at ? Carbon::parse($staffMember->terminated_at) : null,
            profilePhotoUrl: $staffMember->profile_photo_url,
            roles: Lazy::whenLoaded('roles', $staffMember, 
                fn() => RoleData::collection($staffMember->roles)
            ),
            shifts: Lazy::whenLoaded('shifts', $staffMember,
                fn() => ShiftData::collection($staffMember->shifts)
            ),
            attendanceRecords: Lazy::whenLoaded('attendanceRecords', $staffMember,
                fn() => AttendanceRecordData::collection($staffMember->attendanceRecords)
            ),
            emergencyContacts: Lazy::whenLoaded('emergencyContacts', $staffMember,
                fn() => EmergencyContactData::collection($staffMember->emergencyContacts)
            ),
            createdAt: Carbon::parse($staffMember->created_at),
            updatedAt: Carbon::parse($staffMember->updated_at),
        );
    }
}