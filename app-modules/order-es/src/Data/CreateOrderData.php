<?php

declare(strict_types=1);

namespace Colame\OrderEs\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Data transfer object for creating orders through event sourcing
 */
#[TypeScript]
class CreateOrderData extends BaseData
{
    public function __construct(
        public readonly string $type,
        #[DataCollectionOf(CreateOrderItemData::class)]
        public readonly DataCollection $items,
        public readonly ?int $userId = null,
        public readonly ?int $sessionLocationId = null,
        public readonly ?string $tableNumber = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerPhone = null,
        public readonly ?string $customerEmail = null,
        public readonly ?string $deliveryAddress = null,
        public readonly ?string $notes = null,
        public readonly ?string $specialInstructions = null,
        public readonly ?array $offerCodes = null,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Validation rules
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', 'in:dine_in,takeout,delivery,catering'],
            'items' => ['required', 'array', 'min:1'],
            'userId' => ['nullable', 'integer'],
            'sessionLocationId' => ['nullable', 'integer'],
            'tableNumber' => ['nullable', 'string'],
            'customerName' => ['required_if:type,delivery', 'nullable', 'string', 'max:255'],
            'customerPhone' => ['required_if:type,delivery', 'nullable', 'string', 'max:50'],
            'customerEmail' => ['nullable', 'email', 'max:255'],
            'deliveryAddress' => ['required_if:type,delivery', 'nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'specialInstructions' => ['nullable', 'string', 'max:500'],
            'offerCodes' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}