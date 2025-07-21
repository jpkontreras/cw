<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Colame\Order\Models\Order;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Order data transfer object
 */
#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class OrderData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $orderNumber,
        public readonly ?int $userId,
        public readonly int $locationId,
        public readonly string $status,
        public readonly string $type,
        public readonly string $priority,
        public readonly float $subtotal,
        public readonly float $taxAmount,
        public readonly float $tipAmount,
        public readonly float $discountAmount,
        public readonly float $totalAmount,
        public readonly string $paymentStatus,
        public readonly ?string $customerName = null,
        public readonly ?string $customerPhone = null,
        public readonly ?string $customerEmail = null,
        public readonly ?string $deliveryAddress = null,
        public readonly ?int $tableNumber = null,
        public readonly ?int $waiterId = null,
        public readonly ?string $notes = null,
        public readonly ?string $specialInstructions = null,
        public readonly ?string $cancelReason = null,
        public readonly ?array $metadata = null,
        #[DataCollectionOf(OrderItemData::class)]
        public readonly ?DataCollection $items = null,
        public readonly ?\DateTimeInterface $placedAt = null,
        public readonly ?\DateTimeInterface $confirmedAt = null,
        public readonly ?\DateTimeInterface $preparingAt = null,
        public readonly ?\DateTimeInterface $readyAt = null,
        public readonly ?\DateTimeInterface $deliveringAt = null,
        public readonly ?\DateTimeInterface $deliveredAt = null,
        public readonly ?\DateTimeInterface $completedAt = null,
        public readonly ?\DateTimeInterface $cancelledAt = null,
        public readonly ?\DateTimeInterface $scheduledAt = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}

    /**
     * Get order status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'placed' => 'Placed',
            'confirmed' => 'Confirmed',
            'preparing' => 'Preparing',
            'ready' => 'Ready',
            'delivering' => 'Delivering',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get order type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'dine_in' => 'Dine In',
            'takeout' => 'Takeout',
            'delivery' => 'Delivery',
            'catering' => 'Catering',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get payment status label
     */
    public function getPaymentStatusLabel(): string
    {
        return match ($this->paymentStatus) {
            'pending' => 'Pending',
            'partial' => 'Partially Paid',
            'paid' => 'Paid',
            'refunded' => 'Refunded',
            default => ucfirst($this->paymentStatus),
        };
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'placed', 'confirmed']);
    }

    /**
     * Check if order can be modified
     */
    public function canBeModified(): bool
    {
        return in_array($this->status, ['draft', 'placed']);
    }

    /**
     * Get order duration in minutes
     */
    public function getDurationInMinutes(): ?int
    {
        if (!$this->placedAt || !$this->completedAt) {
            return null;
        }

        return (int) $this->placedAt->diff($this->completedAt)->i;
    }

    /**
     * Check if order is active
     */
    public function isActive(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled', 'refunded']);
    }

    /**
     * Check if order is paid
     */
    public function isPaid(): bool
    {
        return $this->paymentStatus === 'paid';
    }

    /**
     * Check if order is high priority
     */
    public function isHighPriority(): bool
    {
        return $this->priority === 'high';
    }

    /**
     * Check if order requires delivery
     */
    public function requiresDelivery(): bool
    {
        return $this->type === 'delivery';
    }

    /**
     * Check if order requires table
     */
    public function requiresTable(): bool
    {
        return $this->type === 'dine_in';
    }

    /**
     * Get remaining amount to pay
     */
    public function getRemainingAmount(): float
    {
        // This would need to be calculated based on payment transactions
        // For now, return total if not paid
        return $this->isPaid() ? 0.0 : $this->totalAmount;
    }

}