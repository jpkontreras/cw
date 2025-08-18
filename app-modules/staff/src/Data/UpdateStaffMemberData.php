<?php

namespace Colame\Staff\Data;

use Carbon\Carbon;
use Colame\Staff\Enums\StaffStatus;
use Spatie\LaravelData\Attributes\Validation\Before;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateStaffMemberData extends Data
{
    public function __construct(
        #[Email]
        public readonly string|Optional $email,
        
        #[Min(2), Max(50)]
        public readonly string|Optional $firstName,
        
        #[Min(2), Max(50)]
        public readonly string|Optional $lastName,
        
        public readonly string|Optional $employeeCode,
        
        public readonly string|Optional $nationalId,
        
        #[Nullable, Rule('regex:/^[+]?[0-9]{10,15}$/')]
        public readonly string|null|Optional $phone,
        
        #[Date, Before('today')]
        public readonly Carbon|Optional $dateOfBirth,
        
        #[Date]
        public readonly Carbon|Optional $hireDate,
        
        #[In(StaffStatus::class)]
        public readonly StaffStatus|Optional $status,
        
        public readonly array|Optional $address,
        public readonly array|Optional $emergencyContacts,
        public readonly string|null|Optional $profilePhotoUrl,
        public readonly array|Optional $metadata,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $staffId = $context->payload['staff_id'] ?? new RouteParameterReference('staff');
        
        return [
            'email' => [
                'sometimes',
                'email',
                "unique:staff_members,email,{$staffId}",
            ],
            'employeeCode' => [
                'sometimes',
                "unique:staff_members,employee_code,{$staffId}",
            ],
            'dateOfBirth' => [
                'sometimes',
                'before:' . now()->subYears(16)->format('Y-m-d'),
            ],
            'emergencyContacts' => ['sometimes', 'array'],
            'emergencyContacts.*.name' => ['required_with:emergencyContacts.*', 'string', 'max:100'],
            'emergencyContacts.*.phone' => ['required_with:emergencyContacts.*', 'regex:/^[+]?[0-9]{10,15}$/'],
            'emergencyContacts.*.relationship' => ['required_with:emergencyContacts.*', 'string', 'max:50'],
        ];
    }
}