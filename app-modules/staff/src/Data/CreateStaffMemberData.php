<?php

namespace Colame\Staff\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Before;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class CreateStaffMemberData extends Data
{
    public function __construct(
        #[Required, Email, Unique('staff_members', 'email')]
        public readonly string $email,
        
        #[Required, Min(2), Max(50)]
        public readonly string $firstName,
        
        #[Required, Min(2), Max(50)]
        public readonly string $lastName,
        
        #[Required, Unique('staff_members', 'employee_code')]
        public readonly string $employeeCode,
        
        #[Required]
        public readonly string $nationalId,
        
        #[Nullable, Rule('regex:/^[+]?[0-9]{10,15}$/')]
        public readonly ?string $phone,
        
        #[Required, Date, Before('today')]
        public readonly Carbon $dateOfBirth,
        
        #[Required, Date]
        public readonly Carbon $hireDate,
        
        public readonly array $address = [],
        public readonly array $emergencyContacts = [],
        public readonly array $roleIds = [],
        public readonly ?int $primaryLocationId = null,
        public readonly ?string $profilePhotoUrl = null,
        public readonly array $metadata = [],
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'dateOfBirth' => ['before:' . now()->subYears(16)->format('Y-m-d')],
            'hireDate' => ['after_or_equal:dateOfBirth'],
            'emergencyContacts' => ['array'],
            'emergencyContacts.*.name' => ['required_with:emergencyContacts.*', 'string', 'max:100'],
            'emergencyContacts.*.phone' => ['required_with:emergencyContacts.*', 'regex:/^[+]?[0-9]{10,15}$/'],
            'emergencyContacts.*.relationship' => ['required_with:emergencyContacts.*', 'string', 'max:50'],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.city' => ['nullable', 'string', 'max:100'],
            'address.state' => ['nullable', 'string', 'max:100'],
            'address.postalCode' => ['nullable', 'string', 'max:20'],
            'address.country' => ['nullable', 'string', 'max:100'],
        ];
    }
}