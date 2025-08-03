<?php

declare(strict_types=1);

namespace App\Core\Data;

use Illuminate\Http\Request;
use Spatie\LaravelData\Concerns\WrappableData;
use Spatie\LaravelData\Concerns\IncludeableData;
use Spatie\LaravelData\Concerns\ResponsableData;
use Spatie\LaravelData\Contracts\WrappableData as WrappableDataContract;
use Spatie\LaravelData\Contracts\IncludeableData as IncludeableDataContract;
use Spatie\LaravelData\Contracts\ResponsableData as ResponsableDataContract;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Base data transfer object for all module DTOs
 * 
 * Provides common functionality and ensures consistency
 * across all data transfer objects in the application.
 * 
 * Features:
 * - Automatic TypeScript generation
 * - Lazy loading support
 * - Include/exclude functionality
 * - Wrappable responses
 * - Validation helpers
 * 
 * Example computed property:
 * ```php
 * #[Computed]
 * public function fullName(): string {
 *     return "{$this->firstName} {$this->lastName}";
 * }
 * ```
 * 
 * Example lazy property:
 * ```php
 * public function __construct(
 *     public string $name,
 *     public Lazy|DataCollection $items,
 * ) {}
 * 
 * // Usage:
 * return new self(
 *     name: $model->name,
 *     items: Lazy::whenLoaded('items', $model, fn() => ItemData::collection($model->items)),
 * );
 * ```
 */
#[TypeScript]
abstract class BaseData extends Data implements 
    WrappableDataContract,
    IncludeableDataContract,
    ResponsableDataContract
{
    use WrappableData;
    use IncludeableData;
    use ResponsableData;

    /**
     * Convert the DTO to array with standardized formatting
     */
    public function toArray(): array
    {
        return parent::toArray();
    }

    /**
     * Create and validate a data object from request
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validateAndCreate(Request $request): static
    {
        return static::validate($request->all());
    }

    /**
     * Try to create a data object, returning null on failure
     */
    public static function tryFrom(mixed $payload): ?static
    {
        try {
            return static::from($payload);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Create a lazy property
     */
    protected static function lazy(\Closure $value): Lazy
    {
        return Lazy::create($value);
    }

    /**
     * Create a factory instance for testing
     * Override this method in your data classes to return a proper factory
     * 
     * @return mixed
     */
    public static function factory(): mixed
    {
        throw new \BadMethodCallException(
            'Factory method not implemented. Override this method in ' . static::class
        );
    }

}