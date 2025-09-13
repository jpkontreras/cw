<?php

declare(strict_types=1);

namespace Colame\OrderEs\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Colame\OrderEs\Models\Order;

class OrderData extends Data
{
    public function __construct(
        #[Uuid] public readonly string $id,
        public readonly ?string $sessionId,
        public readonly ?string $orderNumber,
        public readonly ?int $userId,
        #[Required] public readonly int $locationId,
        public readonly string $currency,
        public readonly ?int $menuId,
        public readonly ?int $menuVersion,
        public readonly string $status,
        public readonly string $type,
        public readonly string $priority,
        public readonly ?string $customerName,
        public readonly ?string $customerPhone,
        public readonly ?string $customerEmail,
        public readonly ?string $deliveryAddress,
        public readonly ?int $tableNumber,
        public readonly ?int $waiterId,
        public readonly int $subtotal,
        public readonly int $tax,
        public readonly int $tip,
        public readonly int $discount,
        public readonly int $total,
        public readonly string $paymentStatus,
        public readonly ?string $paymentMethod,
        public readonly ?string $notes,
        public readonly ?string $specialInstructions,
        public readonly ?string $cancellationReason,
        public readonly ?array $metadata,
        public readonly int $viewCount,
        public readonly int $modificationCount,
        public readonly ?\DateTimeInterface $lastModifiedAt,
        public readonly ?string $lastModifiedBy,
        public readonly ?\DateTimeInterface $placedAt,
        public readonly ?\DateTimeInterface $confirmedAt,
        public readonly ?\DateTimeInterface $preparingAt,
        public readonly ?\DateTimeInterface $readyAt,
        public readonly ?\DateTimeInterface $deliveringAt,
        public readonly ?\DateTimeInterface $deliveredAt,
        public readonly ?\DateTimeInterface $completedAt,
        public readonly ?\DateTimeInterface $cancelledAt,
        public readonly ?\DateTimeInterface $scheduledAt,
        public readonly ?\DateTimeInterface $createdAt,
        public readonly ?\DateTimeInterface $updatedAt,
        #[DataCollectionOf(OrderItemData::class)]
        public readonly Lazy|DataCollection $items,
    ) {}

    public static function fromModel(Order $order): self
    {
        return new self(
            id: $order->id,
            sessionId: $order->session_id,
            orderNumber: $order->order_number,
            userId: $order->user_id,
            locationId: $order->location_id,
            currency: $order->currency,
            menuId: $order->menu_id,
            menuVersion: $order->menu_version,
            status: $order->status,
            type: $order->type,
            priority: $order->priority,
            customerName: $order->customer_name,
            customerPhone: $order->customer_phone,
            customerEmail: $order->customer_email,
            deliveryAddress: $order->delivery_address,
            tableNumber: $order->table_number,
            waiterId: $order->waiter_id,
            subtotal: $order->subtotal,
            tax: $order->tax,
            tip: $order->tip,
            discount: $order->discount,
            total: $order->total,
            paymentStatus: $order->payment_status,
            paymentMethod: $order->payment_method,
            notes: $order->notes,
            specialInstructions: $order->special_instructions,
            cancellationReason: $order->cancellation_reason,
            metadata: $order->metadata,
            viewCount: $order->view_count,
            modificationCount: $order->modification_count,
            lastModifiedAt: $order->last_modified_at,
            lastModifiedBy: $order->last_modified_by,
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
            items: Lazy::whenLoaded('items', $order, fn() => 
                OrderItemData::collection($order->items)
            ),
        );
    }
}