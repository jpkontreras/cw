<?php

declare(strict_types=1);

namespace Colame\OrderEs\Data;

use App\Core\Data\BaseData;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Data transfer object for creating order items through event sourcing
 */
#[TypeScript]
class CreateOrderItemData extends BaseData
{
    public function __construct(
        public readonly int $itemId,
        public readonly ?string $name = null,
        public readonly int $quantity = 1,
        public readonly int $unitPrice = 0,
        public readonly ?string $notes = null,
        public readonly ?array $modifiers = null,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Calculate line total
     */
    public function getLineTotal(): int
    {
        $modifiersTotal = 0;
        if ($this->modifiers) {
            $modifiersTotal = array_sum(array_map(fn($mod) => (int)($mod['price'] ?? 0), $this->modifiers));
        }
        
        return ($this->unitPrice + $modifiersTotal) * $this->quantity;
    }

    /**
     * Validation rules
     */
    public static function rules(): array
    {
        return [
            'itemId' => ['required', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unitPrice' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'modifiers' => ['nullable', 'array'],
            'modifiers.*.id' => ['required_with:modifiers', 'integer'],
            'modifiers.*.name' => ['required_with:modifiers', 'string'],
            'modifiers.*.price' => ['required_with:modifiers', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}