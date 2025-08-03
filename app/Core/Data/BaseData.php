<?php

declare(strict_types=1);

namespace App\Core\Data;

use Illuminate\Contracts\Support\Arrayable;
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
 *     items: Lazy::whenLoaded('items', $model, fn() => ItemData::collect($model->items, DataCollection::class)),
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
     * Create and validate a data object from array or request
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validateAndCreate(Arrayable|array $payload): static
    {
        if ($payload instanceof Request) {
            $payload = $payload->all();
        }
        
        return parent::validateAndCreate($payload);
    }
    
    /**
     * Create and validate a data object from request
     * Helper method for convenience
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function fromRequest(Request $request): static
    {
        return static::validateAndCreate($request->all());
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
}