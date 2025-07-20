<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\DataCollection;

/**
 * Data transfer object for creating orders
 */
class CreateOrderData extends BaseData
{
    public function __construct(
        #[Numeric]
        public readonly ?int $userId,
        
        #[Required, Numeric]
        public readonly int $locationId,
        
        #[Required, StringType]
        public readonly string $type,
        
        #[Numeric]
        public readonly ?int $tableNumber = null,
        
        #[StringType]
        public readonly ?string $customerName = null,
        
        #[StringType]
        public readonly ?string $customerPhone = null,
        
        #[StringType]
        public readonly ?string $customerEmail = null,
        
        #[StringType]
        public readonly ?string $deliveryAddress = null,
        
        #[Required, ArrayType, Min(1)]
        #[DataCollectionOf(CreateOrderItemData::class)]
        public readonly DataCollection $items,
        
        #[StringType]
        public readonly ?string $notes = null,
        
        #[StringType]
        public readonly ?string $specialInstructions = null,
        
        #[ArrayType]
        public readonly ?array $offerCodes = null,
        
        #[ArrayType]
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Calculate estimated subtotal
     */
    public function getEstimatedSubtotal(): float
    {
        return $this->items->sum(fn($item) => $item->quantity * $item->unit_price);
    }

    /**
     * Get total items count
     */
    public function getTotalItemsCount(): int
    {
        return $this->items->sum(fn($item) => $item->quantity);
    }

    /**
     * Custom validation rules
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', 'in:dine_in,takeout,delivery,catering'],
            'tableNumber' => ['nullable', 'integer', 'min:1', 'required_if:type,dine_in'],
            'deliveryAddress' => ['nullable', 'string', 'required_if:type,delivery'],
            'items.*.item_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'customerPhone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]+$/'],
            'customerEmail' => ['nullable', 'email'],
        ];
    }
}