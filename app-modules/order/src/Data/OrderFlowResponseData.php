<?php

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Colame\Order\Models\Order;

class OrderFlowResponseData extends BaseData
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $status,
        public readonly ?string $orderNumber,
        public readonly string $staffId,
        public readonly string $locationId,
        public readonly ?string $tableNumber,
        public readonly int $subtotal,
        public readonly int $discount,
        public readonly int $tax,
        public readonly int $tip,
        public readonly int $total,
        public readonly ?string $paymentMethod,
        
        #[DataCollectionOf(OrderItemData::class)]
        public Lazy|DataCollection $items,
        
        public Lazy|array $promotions,
        public Lazy|array $availablePromotions,
        public readonly array $metadata = [],
    ) {}

    public static function fromModel(Order $order): self
    {
        return new self(
            uuid: $order->uuid,
            status: $order->status,
            orderNumber: $order->order_number,
            staffId: $order->staff_id,
            locationId: $order->location_id,
            tableNumber: $order->table_number,
            subtotal: $order->subtotal,
            discount: $order->discount,
            tax: $order->tax,
            tip: $order->tip,
            total: $order->total,
            paymentMethod: $order->payment_method,
            items: Lazy::whenLoaded('items', $order, fn() => 
                OrderItemData::collect($order->items, DataCollection::class)
            ),
            promotions: Lazy::whenLoaded('promotions', $order, fn() => 
                $order->promotions->toArray()
            ),
            availablePromotions: Lazy::when(fn() => 
                !empty($order->available_promotions), fn() => 
                $order->available_promotions
            ),
            metadata: $order->metadata ?? [],
        );
    }

    #[Computed]
    public function nextStep(): string
    {
        return match($this->status) {
            'started' => 'add_items',
            'items_added' => 'validating',
            'items_validated' => 'calculating_promotions',
            'promotions_calculated' => !empty($this->availablePromotions) 
                ? 'select_promotions' 
                : 'set_payment',
            'price_calculated' => 'confirm_order',
            'confirmed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'unknown',
        };
    }

    #[Computed]
    public function canModify(): bool
    {
        return in_array($this->status, ['started', 'items_added', 'items_validated', 'promotions_calculated']);
    }

    #[Computed]
    public function requiresAction(): bool
    {
        return $this->status === 'promotions_calculated' && !empty($this->availablePromotions);
    }
}