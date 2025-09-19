<?php

declare(strict_types=1);

namespace Colame\Order\CommandHandlers;

use Colame\Order\Aggregates\Order;
use Colame\Order\Commands\{
    StartOrder,
    AddItemToOrder,
    RemoveItemFromOrder,
    UpdateItemQuantity,
    ConfirmOrder,
    CancelOrder
};
use Colame\Item\Contracts\ItemRepositoryInterface;

final class OrderCommandHandler
{
    public function __construct(
        private ?ItemRepositoryInterface $itemRepository = null
    ) {}
    
    public function handleStartOrder(StartOrder $command): void
    {
        Order::retrieve($command->orderId)
            ->start(
                $command->customerId,
                $command->locationId,
                $command->type
            )
            ->persist();
    }
    
    public function handleAddItemToOrder(AddItemToOrder $command): void
    {
        // Fetch item details from item module
        $item = $this->itemRepository?->find($command->itemId);
        
        if (!$item) {
            throw new \DomainException("Item {$command->itemId} not found");
        }
        
        Order::retrieve($command->orderId)
            ->addItem(
                $command->itemId,
                $item->name,
                $item->basePrice,
                $command->quantity,
                $command->modifiers,
                $command->notes
            )
            ->persist();
    }
    
    public function handleRemoveItemFromOrder(RemoveItemFromOrder $command): void
    {
        Order::retrieve($command->orderId)
            ->removeItem($command->lineItemId)
            ->persist();
    }
    
    public function handleUpdateItemQuantity(UpdateItemQuantity $command): void
    {
        // For simplicity, we'll remove and re-add with new quantity
        // In a real system, you might have a specific event for this
        $aggregate = Order::retrieve($command->orderId);
        
        // This is a simplified version - in reality you'd need to get the item details
        // from the current state before removing
        $aggregate
            ->removeItem($command->lineItemId)
            ->persist();
    }
    
    public function handleConfirmOrder(ConfirmOrder $command): void
    {
        Order::retrieve($command->orderId)
            ->confirm(
                $command->paymentMethod,
                $command->tipAmount ?? 0
            )
            ->persist();
    }
    
    public function handleCancelOrder(CancelOrder $command): void
    {
        Order::retrieve($command->orderId)
            ->cancel(
                $command->reason,
                $command->cancelledBy
            )
            ->persist();
    }
}