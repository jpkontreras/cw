<?php

declare(strict_types=1);

namespace Colame\Offer\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class ValidationResultData extends BaseData
{
    public function __construct(
        public readonly bool $isValid,
        public readonly ?string $failureReason,
        
        #[DataCollectionOf(ValidationErrorData::class)]
        public readonly DataCollection $errors,
        
        public readonly array $context,
        public readonly ?array $suggestions,
    ) {}
    
    public static function success(array $context = []): self
    {
        return new self(
            isValid: true,
            failureReason: null,
            errors: ValidationErrorData::collection([]),
            context: $context,
            suggestions: null,
        );
    }
    
    public static function failure(string $reason, array $errors = [], array $suggestions = null): self
    {
        return new self(
            isValid: false,
            failureReason: $reason,
            errors: ValidationErrorData::collection($errors),
            context: [],
            suggestions: $suggestions,
        );
    }
}