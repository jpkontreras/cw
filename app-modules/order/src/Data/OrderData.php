<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Colame\Order\Models\Order;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Order data transfer object
 */
#[TypeScript]
class OrderData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $uuid,
        public readonly ?string $orderNumber,
        public readonly ?int $userId,
        public readonly int $locationId,
        public readonly string $status,
        public readonly string $type,
        public readonly string $priority,
        public readonly int $subtotal,
        public readonly int $tax,
        public readonly int $tip,
        public readonly int $discount,
        public readonly int $total,
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
        public readonly Lazy|DataCollection|null $items = null,
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
     * Get items count
     */
    #[Computed]
    public function itemsCount(): int
    {
        if ($this->items instanceof DataCollection) {
            return $this->items->count();
        }
        
        return 0;
    }

    /**
     * Get items count for display
     */
    #[Computed]
    public function items(): int
    {
        return $this->itemsCount();
    }

    /**
     * Create from Eloquent model
     */
    public static function fromModel(Order $order): self
    {
        return new self(
            id: $order->id,
            uuid: $order->uuid,
            orderNumber: $order->order_number,
            userId: $order->user_id,
            locationId: $order->location_id,
            status: $order->status instanceof \Spatie\ModelStates\State 
                ? $order->status->getValue() 
                : (string) $order->status,
            type: $order->type,
            priority: $order->priority,
            subtotal: $order->subtotal,
            tax: $order->tax,
            tip: $order->tip,
            discount: $order->discount,
            total: $order->total,
            paymentStatus: $order->payment_status,
            customerName: $order->customer_name,
            customerPhone: $order->customer_phone,
            customerEmail: $order->customer_email,
            deliveryAddress: $order->delivery_address,
            tableNumber: $order->table_number,
            waiterId: $order->waiter_id,
            notes: $order->notes,
            specialInstructions: $order->special_instructions,
            cancelReason: $order->cancel_reason,
            metadata: $order->metadata,
            items: Lazy::whenLoaded('items', $order, 
                fn() => OrderItemData::collect($order->items, DataCollection::class)
            ),
            placedAt: $order->placed_at,
            confirmedAt: $order->confirmed_at,
            preparingAt: $order->preparing_at,
            readyAt: $order->ready_at,
            deliveringAt: $order->delivering_at,
            deliveredAt: $order->delivered_at,
            completedAt: $order->completed_at,
            cancelledAt: $order->cancelled_at,
            scheduledAt: $order->scheduled_at,
            createdAt: $order->created_at,
            updatedAt: $order->updated_at,
        );
    }

    /**
     * Get order status label
     */
    #[Computed]
    public function statusLabel(): string
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
    #[Computed]
    public function typeLabel(): string
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
    #[Computed]
    public function paymentStatusLabel(): string
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
    #[Computed]
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'placed', 'confirmed']);
    }

    /**
     * Check if order can be modified
     */
    #[Computed]
    public function canBeModified(): bool
    {
        return in_array($this->status, ['draft', 'placed']);
    }

    /**
     * Get order duration in minutes
     */
    #[Computed]
    public function durationInMinutes(): ?int
    {
        if (!$this->placedAt || !$this->completedAt) {
            return null;
        }

        return (int) $this->placedAt->diff($this->completedAt)->i;
    }

    /**
     * Check if order is active
     */
    #[Computed]
    public function isActive(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled', 'refunded']);
    }

    /**
     * Check if order is paid
     */
    #[Computed]
    public function isPaid(): bool
    {
        return $this->paymentStatus === 'paid';
    }

    /**
     * Check if order is high priority
     */
    #[Computed]
    public function isHighPriority(): bool
    {
        return $this->priority === 'high';
    }

    /**
     * Check if order requires delivery
     */
    #[Computed]
    public function requiresDelivery(): bool
    {
        return $this->type === 'delivery';
    }

    /**
     * Check if order requires table
     */
    #[Computed]
    public function requiresTable(): bool
    {
        return $this->type === 'dine_in';
    }

    /**
     * Get remaining amount to pay
     */
    #[Computed]
    public function remainingAmount(): int
    {
        // This would need to be calculated based on payment transactions
        // For now, return total if not paid
        return $this->isPaid() ? 0 : $this->total;
    }
}
