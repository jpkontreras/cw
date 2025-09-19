<?php

declare(strict_types=1);

namespace Colame\Order\Aggregates;

use Colame\Order\Events\{
    OrderStarted,
    ItemAddedToOrder,
    ItemRemovedFromOrder,
    OrderConfirmed,
    OrderCancelled,
    OrderSlipPrinted,
    SlipScannedReady
};
use Colame\Order\Exceptions\{
    OrderAlreadyConfirmedException,
    OrderAlreadyCancelledException,
    ItemNotFoundException,
    InvalidQuantityException,
    EmptyOrderException
};
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;
use Illuminate\Support\Str;

final class Order extends AggregateRoot
{
    // State
    private array $items = [];
    private string $status = 'draft';
    private ?int $customerId = null;
    private ?int $locationId = null;
    private string $type = 'dine_in';
    private ?string $orderNumber = null;
    private bool $slipPrinted = false;
    private bool $kitchenReady = false;
    
    // Business Methods
    
    public function start(int $customerId, int $locationId, string $type = 'dine_in'): self
    {
        if ($this->status !== 'draft') {
            throw new \DomainException('Order already started');
        }
        
        $this->recordThat(new OrderStarted(
            orderId: $this->uuid(),
            customerId: $customerId,
            locationId: $locationId,
            type: $type,
            orderNumber: $this->generateOrderNumber($locationId),
            startedAt: new \DateTimeImmutable()
        ));
        
        return $this;
    }
    
    public function addItem(
        int $itemId,
        string $itemName,
        float $unitPrice,
        int $quantity,
        array $modifiers = [],
        ?string $notes = null
    ): self {
        $this->guardAgainstConfirmedOrder();
        $this->guardAgainstCancelledOrder();
        
        if ($quantity <= 0) {
            throw new InvalidQuantityException('Quantity must be greater than 0');
        }
        
        $lineItemId = (string) Str::uuid();
        
        $this->recordThat(new ItemAddedToOrder(
            orderId: $this->uuid(),
            lineItemId: $lineItemId,
            itemId: $itemId,
            itemName: $itemName,
            unitPrice: $unitPrice,
            quantity: $quantity,
            modifiers: $modifiers,
            notes: $notes
        ));
        
        return $this;
    }
    
    public function removeItem(string $lineItemId): self
    {
        $this->guardAgainstConfirmedOrder();
        $this->guardAgainstCancelledOrder();
        
        if (!isset($this->items[$lineItemId])) {
            throw new ItemNotFoundException("Item {$lineItemId} not found in order");
        }
        
        $this->recordThat(new ItemRemovedFromOrder(
            orderId: $this->uuid(),
            lineItemId: $lineItemId
        ));
        
        return $this;
    }
    
    public function confirm(string $paymentMethod, float $tipAmount = 0): self
    {
        $this->guardAgainstConfirmedOrder();
        $this->guardAgainstCancelledOrder();
        $this->guardAgainstEmptyOrder();

        $subtotal = $this->calculateSubtotal();
        $tax = $this->calculateTax($subtotal);
        $total = $subtotal + $tax + $tipAmount;

        $this->recordThat(new OrderConfirmed(
            orderId: $this->uuid(),
            paymentMethod: $paymentMethod,
            subtotal: $subtotal,
            tax: $tax,
            tip: $tipAmount,
            total: $total,
            confirmedAt: new \DateTimeImmutable()
        ));

        // Automatically print slip after confirmation (reduces friction)
        $this->recordThat(new OrderSlipPrinted(
            orderId: $this->uuid(),
            orderNumber: $this->orderNumber,
            printedAt: new \DateTimeImmutable()
        ));

        return $this;
    }
    
    public function cancel(string $reason, int $cancelledBy): self
    {
        $this->guardAgainstCancelledOrder();

        if ($this->status === 'completed') {
            throw new \DomainException('Cannot cancel completed order');
        }

        $this->recordThat(new OrderCancelled(
            orderId: $this->uuid(),
            reason: $reason,
            cancelledBy: $cancelledBy,
            cancelledAt: new \DateTimeImmutable()
        ));

        return $this;
    }

    public function printSlip(): self
    {
        if ($this->status !== 'confirmed') {
            throw new \DomainException('Can only print slip for confirmed orders');
        }

        if ($this->slipPrinted) {
            // Allow reprinting - just record the event
            // This handles lost slips or printer issues
        }

        $this->recordThat(new OrderSlipPrinted(
            orderId: $this->uuid(),
            orderNumber: $this->orderNumber,
            printedAt: new \DateTimeImmutable()
        ));

        return $this;
    }

    public function markReadyViaSlipScan(string $scannedOrderNumber): self
    {
        if ($this->orderNumber !== $scannedOrderNumber) {
            throw new \DomainException('Scanned order number does not match');
        }

        if ($this->status !== 'confirmed' && $this->status !== 'preparing') {
            throw new \DomainException('Order must be confirmed or preparing to mark as ready');
        }

        $this->recordThat(new SlipScannedReady(
            orderId: $this->uuid(),
            orderNumber: $this->orderNumber,
            scannedAt: new \DateTimeImmutable()
        ));

        return $this;
    }

    // Event Handlers (Update internal state)
    
    protected function applyOrderStarted(OrderStarted $event): void
    {
        $this->customerId = $event->customerId;
        $this->locationId = $event->locationId;
        $this->type = $event->type;
        $this->orderNumber = $event->orderNumber;
        $this->status = 'started';
    }
    
    protected function applyItemAddedToOrder(ItemAddedToOrder $event): void
    {
        $this->items[$event->lineItemId] = [
            'itemId' => $event->itemId,
            'itemName' => $event->itemName,
            'unitPrice' => $event->unitPrice,
            'quantity' => $event->quantity,
            'modifiers' => $event->modifiers,
            'notes' => $event->notes,
        ];
    }
    
    protected function applyItemRemovedFromOrder(ItemRemovedFromOrder $event): void
    {
        unset($this->items[$event->lineItemId]);
    }
    
    protected function applyOrderConfirmed(OrderConfirmed $event): void
    {
        $this->status = 'confirmed';
    }
    
    protected function applyOrderCancelled(OrderCancelled $event): void
    {
        $this->status = 'cancelled';
    }

    protected function applyOrderSlipPrinted(OrderSlipPrinted $event): void
    {
        $this->slipPrinted = true;
    }

    protected function applySlipScannedReady(SlipScannedReady $event): void
    {
        $this->kitchenReady = true;
        $this->status = 'ready';
    }

    // Guards (Business Rules)
    
    private function guardAgainstConfirmedOrder(): void
    {
        if ($this->status === 'confirmed') {
            throw new OrderAlreadyConfirmedException('Cannot modify confirmed order');
        }
    }
    
    private function guardAgainstCancelledOrder(): void
    {
        if ($this->status === 'cancelled') {
            throw new OrderAlreadyCancelledException('Cannot modify cancelled order');
        }
    }
    
    private function guardAgainstEmptyOrder(): void
    {
        if (empty($this->items)) {
            throw new EmptyOrderException('Cannot confirm order without items');
        }
    }
    
    // Calculations
    
    private function calculateSubtotal(): float
    {
        return array_reduce($this->items, function ($carry, $item) {
            return $carry + ($item['unitPrice'] * $item['quantity']);
        }, 0.0);
    }
    
    private function calculateTax(float $subtotal): float
    {
        // Chilean IVA is 19%
        return $subtotal * 0.19;
    }
    
    private function generateOrderNumber(int $locationId): string
    {
        // Format: LOC-YYYYMMDD-XXXX
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        return "L{$locationId}-{$date}-{$random}";
    }
}