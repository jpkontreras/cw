<?php

declare(strict_types=1);

namespace Colame\Order\Casts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

/**
 * Cast integer values to strings
 */
class IntToStringCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): ?string
    {
        if ($value === null) {
            return null;
        }
        
        // Convert numeric values to string
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        // Already a string
        if (is_string($value)) {
            return $value;
        }
        
        // Can't cast this value
        return null;
    }
}