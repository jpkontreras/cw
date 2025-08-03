<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Illuminate\Http\Request;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Data transfer object for creating orders
 */
#[TypeScript]
class CreateOrderData extends BaseData
{
    public function __construct(
        #[Required, Numeric]
        public readonly int $locationId,
        
        #[Required, StringType]
        public readonly string $type,
        
        #[Required, ArrayType, Min(1)]
        #[DataCollectionOf(CreateOrderItemData::class)]
        public readonly DataCollection $items,
        
        #[Numeric]
        public readonly ?int $userId = null,
        
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
    #[Computed]
    public function estimatedSubtotal(): float
    {
        return $this->items->sum(fn($item) => $item->quantity * $item->unitPrice);
    }

    /**
     * Get total items count
     */
    #[Computed]
    public function totalItemsCount(): int
    {
        return $this->items->sum(fn($item) => $item->quantity);
    }

    /**
     * Create from request with authenticated user
     */
    public static function fromRequest(Request $request): static
    {
        return self::from(
            array_merge($request->all(), [
                'userId' => $request->user()?->id,
            ])
        );
    }

    /**
     * Custom validation rules
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'locationId' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:dine_in,takeout,delivery,catering'],
            'tableNumber' => ['nullable', 'integer', 'min:1', 'required_if:type,dine_in'],
            'deliveryAddress' => ['nullable', 'string', 'required_if:type,delivery'],
            'items' => ['required', 'array', 'min:1'],
            'customerPhone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]+$/'],
            'customerEmail' => ['nullable', 'email'],
        ];
    }

    /**
     * Custom validation attributes (field names for error messages)
     */
    public static function attributes(): array
    {
        return [
            'locationId' => 'location',
            'items.*.itemId' => 'item',
            'customerName' => 'customer name',
            'customerPhone' => 'customer phone',
            'customerEmail' => 'customer email',
            'deliveryAddress' => 'delivery address',
            'tableNumber' => 'table number',
            'specialInstructions' => 'special instructions',
        ];
    }
}