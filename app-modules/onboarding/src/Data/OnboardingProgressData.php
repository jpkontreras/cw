<?php

declare(strict_types=1);

namespace Colame\Onboarding\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class OnboardingProgressData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        
        #[Required]
        #[MapInputName('user_id')]
        public readonly int $userId,
        
        #[Required, StringType]
        public readonly string $step,
        
        #[Json]
        #[MapInputName('completed_steps')]
        public readonly array $completedSteps,
        
        #[Nullable, Json]
        public readonly ?array $data,
        
        #[BooleanType]
        #[MapInputName('is_completed')]
        public readonly bool $isCompleted,
        
        #[Nullable, WithCast(DateTimeInterfaceCast::class)]
        #[MapInputName('completed_at')]
        public readonly ?Carbon $completedAt,
        
        #[Nullable, StringType]
        #[MapInputName('skip_reason')]
        public readonly ?string $skipReason,
        
        #[WithCast(DateTimeInterfaceCast::class)]
        #[MapInputName('created_at')]
        public readonly ?Carbon $createdAt,
        
        #[WithCast(DateTimeInterfaceCast::class)]
        #[MapInputName('updated_at')]
        public readonly ?Carbon $updatedAt,
    ) {}
    
    public function hasCompletedStep(string $stepIdentifier): bool
    {
        return in_array($stepIdentifier, $this->completedSteps);
    }
    
    public function getProgressPercentage(): int
    {
        $totalSteps = 5; // This should be configurable based on active steps
        $completedCount = count($this->completedSteps);
        
        return (int) (($completedCount / $totalSteps) * 100);
    }
}