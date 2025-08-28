<?php

declare(strict_types=1);

namespace Colame\Onboarding\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Same;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AccountSetupData extends BaseData
{
    public function __construct(
        #[Required, StringType, Min(2), Max(100)]
        public readonly string $firstName,
        
        #[Required, StringType, Min(2), Max(100)]
        public readonly string $lastName,
        
        #[Required, Email, Max(255)]
        public readonly string $email,
        
        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone,
        
        // For Chilean market - RUT is optional during onboarding
        #[Nullable, StringType, Max(20)]
        public readonly ?string $nationalId,
        
        // Password fields for new users (optional if user already exists)
        #[Nullable, StringType, Min(8)]
        public readonly ?string $password = null,
        
        #[Nullable, Same('password')]
        public readonly ?string $passwordConfirmation = null,
        
        // Role for initial permission assignment
        #[Required, In(['owner', 'manager', 'admin'])]
        public readonly string $primaryRole = 'owner',
        
        // Employee code (auto-generated if not provided)
        #[Nullable, StringType, Max(20)]
        public readonly ?string $employeeCode = null,
    ) {}
    
    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
    
    public static function rules(ValidationContext $context): array
    {
        return [
            'nationalId' => ['nullable', 'string', 'max:20'], // Generic ID validation - format depends on country
            'password' => ['nullable', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'], // Strong password
            'passwordConfirmation' => ['nullable', 'required_with:password', 'same:password'],
        ];
    }
}