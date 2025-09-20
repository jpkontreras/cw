<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;
use Colame\Order\Models\Order;
use Colame\Location\Contracts\LocationRepositoryInterface;

class OrderData extends BaseData
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

    /**
     * Get currency configuration from location repository
     */
    #[Computed]
    public function currencyConfig(): ?array
    {
        $locationRepository = app(LocationRepositoryInterface::class);
        return $locationRepository->getCurrencyConfig($this->locationId);
    }

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
     * Total amount alias for compatibility
     */
    #[Computed]
    public function totalAmount(): int
    {
        return $this->total;
    }

    /**
     * Convert to array ensuring all fields are included
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sessionId' => $this->sessionId,
            'orderNumber' => $this->orderNumber,
            'userId' => $this->userId,
            'locationId' => $this->locationId,
            'currency' => $this->currency,
            'menuId' => $this->menuId,
            'menuVersion' => $this->menuVersion,
            'status' => $this->status,
            'type' => $this->type,
            'priority' => $this->priority,
            'customerName' => $this->customerName,
            'customerPhone' => $this->customerPhone,
            'customerEmail' => $this->customerEmail,
            'deliveryAddress' => $this->deliveryAddress,
            'tableNumber' => $this->tableNumber,
            'waiterId' => $this->waiterId,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'tip' => $this->tip,
            'discount' => $this->discount,
            'total' => $this->total,
            'totalAmount' => $this->total, // Alias for compatibility
            'paymentStatus' => $this->paymentStatus,
            'paymentMethod' => $this->paymentMethod,
            'notes' => $this->notes,
            'specialInstructions' => $this->specialInstructions,
            'cancellationReason' => $this->cancellationReason,
            'metadata' => $this->metadata,
            'viewCount' => $this->viewCount,
            'modificationCount' => $this->modificationCount,
            'lastModifiedAt' => $this->lastModifiedAt?->format('c'),
            'lastModifiedBy' => $this->lastModifiedBy,
            'placedAt' => $this->placedAt?->format('c'),
            'confirmedAt' => $this->confirmedAt?->format('c'),
            'preparingAt' => $this->preparingAt?->format('c'),
            'readyAt' => $this->readyAt?->format('c'),
            'deliveringAt' => $this->deliveringAt?->format('c'),
            'deliveredAt' => $this->deliveredAt?->format('c'),
            'completedAt' => $this->completedAt?->format('c'),
            'cancelledAt' => $this->cancelledAt?->format('c'),
            'scheduledAt' => $this->scheduledAt?->format('c'),
            'createdAt' => $this->createdAt?->format('c'),
            'updatedAt' => $this->updatedAt?->format('c'),
            'items' => $this->items instanceof DataCollection ? $this->items->toArray() : [],
            'itemsCount' => $this->itemsCount(), // Include computed property
        ];
    }
    
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
                OrderItemData::collect($order->items, DataCollection::class)
            ),
        );
    }
}