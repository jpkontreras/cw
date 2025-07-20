<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

/**
 * Order item data transfer object
 */
class OrderItemData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $orderId,
        public readonly int $itemId,
        public readonly string $itemName,
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly float $totalPrice,
        public readonly string $status,
        public readonly string $kitchenStatus,
        public readonly ?string $course = null,
        public readonly ?string $notes = null,
        public readonly ?array $modifiers = null,
        public readonly ?array $metadata = null,
        public readonly ?\DateTimeInterface $preparedAt = null,
        public readonly ?\DateTimeInterface $servedAt = null,
        public readonly \DateTimeInterface $createdAt = new \DateTime(),
        public readonly \DateTimeInterface $updatedAt = new \DateTime(),
    ) {}

    /**
     * Calculate line total
     */
    public function getLineTotal(): float
    {
        return $this->quantity * $this->unitPrice;
    }

    /**
     * Get modifier names
     */
    public function getModifierNames(): array
    {
        if (!$this->modifiers) {
            return [];
        }

        return array_map(fn($mod) => $mod['name'] ?? '', $this->modifiers);
    }

    /**
     * Get total modifiers price
     */
    public function getModifiersTotal(): float
    {
        if (!$this->modifiers) {
            return 0.0;
        }

        return array_sum(array_map(fn($mod) => (float)($mod['price'] ?? 0), $this->modifiers));
    }

    /**
     * Check if item is prepared
     */
    public function isPrepared(): bool
    {
        return $this->status === 'prepared' || $this->preparedAt !== null;
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
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
    public function getKitchenStatusLabel(): string
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
    public function getCourseLabel(): string
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
    public function isReady(): bool
    {
        return $this->kitchenStatus === 'ready';
    }

    /**
     * Check if item is served
     */
    public function isServed(): bool
    {
        return $this->kitchenStatus === 'served' || $this->servedAt !== null;
    }
}