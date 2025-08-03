<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Data transfer object for creating order items
 */
#[TypeScript]
class CreateOrderItemData extends BaseData
{
    public function __construct(
        #[Required, Numeric]
        public readonly int $itemId,
        
        #[Required, Numeric, Min(1)]
        public readonly int $quantity,
        
        #[Numeric, Min(0)]
        public readonly float $unitPrice = 0,
        
        #[StringType]
        public readonly ?string $notes = null,
        
        #[ArrayType]
        public readonly ?array $modifiers = null,
        
        #[ArrayType]
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Calculate line total
     */
    public function getLineTotal(): float
    {
        $modifiersTotal = 0;
        if ($this->modifiers) {
            $modifiersTotal = array_sum(array_map(fn($mod) => (float)($mod['price'] ?? 0), $this->modifiers));
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
            'modifiers.*.price' => ['required_with:modifiers', 'numeric', 'min:0'],
        ];
    }
}