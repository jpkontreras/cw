<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Data transfer object for creating order items
 */
#[TypeScript]
class CreateOrderItemData extends BaseData
{
    public function __construct(
        #[MapInputName('itemId')]
        public readonly int $itemId,
        public readonly ?string $name = null,  // Item name
        public readonly int $quantity,
        #[MapInputName('unitPrice')]
        public readonly int $unitPrice = 0,  // In minor units
        public readonly ?string $notes = null,
        public readonly ?array $modifiers = null,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Calculate line total
     * @return int Total in minor units
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
     * Validate modifiers structure
     */
    public static function rules(): array
    {
        return [
            'modifiers.*.id' => ['required_with:modifiers', 'integer'],
            'modifiers.*.name' => ['required_with:modifiers', 'string'],
            'modifiers.*.price' => ['required_with:modifiers', 'integer', 'min:0'],
        ];
    }

    /**
     * Custom validation attributes (field names for error messages)
     */
    public static function attributes(): array
    {
        return [
            'itemId' => 'item',
            'unitPrice' => 'unit price',
        ];
    }
}