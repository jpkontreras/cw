<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Illuminate\Http\Request;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
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
        #[MapInputName('locationId')]
        public readonly int $locationId,
        #[MapInputName('type')]
        public readonly string $type,
        #[DataCollectionOf(CreateOrderItemData::class)]
        public readonly DataCollection $items,
        #[MapInputName('userId')]
        public readonly ?int $userId = null,
        #[MapInputName('tableNumber')]
        public readonly ?int $tableNumber = null,
        #[MapInputName('customerName')]
        public readonly ?string $customerName = null,
        #[MapInputName('customerPhone')]
        public readonly ?string $customerPhone = null,
        #[MapInputName('customerEmail')]
        public readonly ?string $customerEmail = null,
        #[MapInputName('deliveryAddress')]
        public readonly ?string $deliveryAddress = null,
        public readonly ?string $notes = null,
        #[MapInputName('specialInstructions')]
        public readonly ?string $specialInstructions = null,
        #[MapInputName('offerCodes')]
        public readonly ?array $offerCodes = null,
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
            'type' => ['required', 'in:dineIn,takeout,delivery,catering'],
            'tableNumber' => ['nullable', 'integer', 'min:1'],
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