<?php

declare(strict_types=1);

namespace App\Core\Data;

use Spatie\LaravelData\Data;

/**
 * Base data transfer object for all module DTOs
 * 
 * Provides common functionality and ensures consistency
 * across all data transfer objects in the application
 */
abstract class BaseData extends Data
{
    /**
     * Convert the DTO to array with standardized formatting
     */
    public function toArray(): array
    {
        return parent::toArray();
    }
}