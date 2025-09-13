<?php

declare(strict_types=1);

namespace Colame\OrderEs\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Colame\OrderEs\Models\OrderItem;

class OrderItemData extends Data
{
    public function __construct(
        public readonly ?string $id,
        #[Required] public readonly string $orderId,
        #[Required] public readonly int $itemId,
        public readonly ?int $menuSectionId,
        public readonly ?int $menuItemId,
        #[Required] public readonly string $itemName,
        public readonly ?string $baseItemName,
        #[Required] public readonly int $quantity,
        #[Required] public readonly int $basePrice,
        #[Required] public readonly int $unitPrice,
        public readonly int $modifiersTotal,
        #[Required] public readonly int $totalPrice,
        public readonly string $status,
        public readonly string $kitchenStatus,
        public readonly ?string $course,
        public readonly ?string $notes,
        public readonly ?string $specialInstructions,
        public readonly ?array $modifiers,
        public readonly ?array $modifierHistory,
        public readonly int $modifierCount,
        public readonly ?array $metadata,
        public readonly ?\DateTimeInterface $modifiedAt,
        public readonly ?\DateTimeInterface $preparedAt,
        public readonly ?\DateTimeInterface $servedAt,
        public readonly ?\DateTimeInterface $createdAt,
        public readonly ?\DateTimeInterface $updatedAt,
    ) {}

    public static function fromModel(OrderItem $item): self
    {
        return new self(
            id: $item->id,
            orderId: $item->order_id,
            itemId: $item->item_id,
            menuSectionId: $item->menu_section_id,
            menuItemId: $item->menu_item_id,
            itemName: $item->item_name,
            baseItemName: $item->base_item_name,
            quantity: $item->quantity,
            basePrice: $item->base_price,
            unitPrice: $item->unit_price,
            modifiersTotal: $item->modifiers_total,
            totalPrice: $item->total_price,
            status: $item->status,
            kitchenStatus: $item->kitchen_status,
            course: $item->course,
            notes: $item->notes,
            specialInstructions: $item->special_instructions,
            modifiers: $item->modifiers,
            modifierHistory: $item->modifier_history,
            modifierCount: $item->modifier_count,
            metadata: $item->metadata,
            modifiedAt: $item->modified_at,
            preparedAt: $item->prepared_at,
            servedAt: $item->served_at,
            createdAt: $item->created_at,
            updatedAt: $item->updated_at,
        );
    }
}