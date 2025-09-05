<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Colame\Order\Models\OrderItem;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Order item data transfer object
 */
#[TypeScript]
class OrderItemData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $orderId,
        public readonly int $itemId,
        public readonly string $itemName,
        public readonly int $quantity,
        public readonly int $unitPrice,  // In minor units
        public readonly int $totalPrice,  // In minor units
        public readonly string $status,
        public readonly string $kitchenStatus,
        public readonly ?string $course = null,
        public readonly ?string $notes = null,
        public readonly ?array $modifiers = null,
        public readonly ?array $metadata = null,
        public readonly ?\DateTimeInterface $preparedAt = null,
        public readonly ?\DateTimeInterface $servedAt = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}

    /**
     * Create from Eloquent model
     */
    public static function fromModel(OrderItem $item): self
    {
        return new self(
            id: $item->id,
            orderId: $item->order_id,
            itemId: $item->item_id,
            itemName: $item->item_name,
            quantity: $item->quantity,
            unitPrice: $item->unit_price,
            totalPrice: $item->total_price,
            status: $item->status,
            kitchenStatus: $item->kitchen_status,
            course: $item->course,
            notes: $item->notes,
            modifiers: $item->modifiers,
            metadata: $item->metadata,
            preparedAt: $item->prepared_at,
            servedAt: $item->served_at,
            createdAt: $item->created_at,
            updatedAt: $item->updated_at,
        );
    }

    /**
     * Calculate line total
     * @return int Total in minor units
     */
    #[Computed]
    public function lineTotal(): int
    {
        return $this->quantity * $this->unitPrice;
    }

    /**
     * Get modifier names
     */
    #[Computed]
    public function modifierNames(): array
    {
        if (!$this->modifiers) {
            return [];
        }

        return array_map(fn($mod) => $mod['name'] ?? '', $this->modifiers);
    }

    /**
     * Get total modifiers price
     * @return int Total in minor units
     */
    #[Computed]
    public function modifiersTotal(): int
    {
        if (!$this->modifiers) {
            return 0;
        }

        return array_sum(array_map(fn($mod) => (int)($mod['price'] ?? 0), $this->modifiers));
    }

    /**
     * Check if item is prepared
     */
    #[Computed]
    public function isPrepared(): bool
    {
        return $this->status === 'prepared' || $this->preparedAt !== null;
    }

    /**
     * Get status label
     */
    #[Computed]
    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'preparing' => 'Preparing',
            'prepared' => 'Prepared',
            'served' => 'Served',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get kitchen status label
     */
    #[Computed]
    public function kitchenStatusLabel(): string
    {
        return match ($this->kitchenStatus) {
            'pending' => 'Pending',
            'preparing' => 'Preparing',
            'ready' => 'Ready',
            'served' => 'Served',
            default => ucfirst($this->kitchenStatus),
        };
    }

    /**
     * Get course label
     */
    #[Computed]
    public function courseLabel(): string
    {
        if (!$this->course) {
            return 'N/A';
        }

        return match ($this->course) {
            'starter' => 'Starter',
            'main' => 'Main Course',
            'dessert' => 'Dessert',
            'beverage' => 'Beverage',
            default => ucfirst($this->course),
        };
    }

    /**
     * Check if item is ready
     */
    #[Computed]
    public function isReady(): bool
    {
        return $this->kitchenStatus === 'ready';
    }

    /**
     * Check if item is served
     */
    #[Computed]
    public function isServed(): bool
    {
        return $this->kitchenStatus === 'served' || $this->servedAt !== null;
    }
}
