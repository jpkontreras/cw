<?php

namespace Colame\Staff\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\After;
use Spatie\LaravelData\Attributes\Validation\Integer;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class CreateShiftData extends Data
{
    public function __construct(
        #[Required, Integer]
        public readonly int $staffMemberId,
        
        #[Required, Integer]
        public readonly int $locationId,
        
        #[Required]
        public readonly Carbon $startTime,
        
        #[Required, After('startTime')]
        public readonly Carbon $endTime,
        
        #[Min(0), Max(240)]
        public readonly int $breakDuration = 30, // in minutes
        
        #[Nullable]
        public readonly ?string $notes,
        
        #[Nullable]
        public readonly ?int $createdBy,
        
        public readonly bool $notifyStaff = true,
    ) {}
    
    public static function rules(ValidationContext $context): array
    {
        return [
            'startTime' => ['after:now'],
            'endTime' => ['after:startTime'],
            'breakDuration' => ['integer', 'min:0', 'max:240'],
        ];
    }
}