<?php

namespace Colame\Order\Aggregates;

use Spatie\EventSourcing\AggregateRoots\AggregateRoot;
use Colame\Order\Events\OrderStarted;
use Colame\Order\Events\ItemsAddedToOrder;
use Colame\Order\Events\ItemsValidated;
use Colame\Order\Events\PromotionsCalculated;
use Colame\Order\Events\PromotionApplied;
use Colame\Order\Events\PromotionRemoved;
use Colame\Order\Events\PriceCalculated;
use Colame\Order\Events\OrderConfirmed;
use Colame\Order\Events\PaymentMethodSet;
use Colame\Order\Events\OrderModified;
use Colame\Order\Events\OrderCancelled;
use Colame\Order\Events\TipAdded;
use Colame\Order\Events\ItemsModified;
use Colame\Order\Events\PriceAdjusted;
use Colame\Order\Exceptions\InvalidOrderStateException;
use Colame\Order\Exceptions\ItemsNotFoundException;
use Colame\Order\Data\OrderItemData;
use Colame\Order\Data\PromotionData;
use Money\Money;
use Money\Currency;

class OrderAggregate extends AggregateRoot
{
    protected string $status = 'draft';
    protected string $staffId;
    protected string $locationId;
    protected ?string $tableNumber = null;
    protected array $items = [];
    protected array $appliedPromotions = [];
    protected Money $subtotal;
    protected Money $discount;
    protected Money $tax;
    protected Money $tip;
    protected Money $total;
    protected ?string $paymentMethod = null;
    protected array $metadata = [];
    protected bool $itemsValidated = false;
    protected bool $promotionsCalculated = false;

    public function __construct()
    {
        $currency = new Currency('CLP');
        $this->subtotal = new Money(0, $currency);
        $this->discount = new Money(0, $currency);
        $this->tax = new Money(0, $currency);
        $this->tip = new Money(0, $currency);
        $this->total = new Money(0, $currency);
    }

    public function startOrder(
        string $staffId, 
        string $locationId, 
        ?string $tableNumber = null,
        array $metadata = []
    ): self {
        if ($this->status !== 'draft') {
            throw new InvalidOrderStateException("Cannot start order in status: {$this->status}");
        }

        $this->recordThat(new OrderStarted(
            aggregateRootUuid: $this->uuid(),
            staffId: $staffId,
            locationId: $locationId,
            tableNumber: $tableNumber,
            metadata: $metadata
        ));

        return $this;
    }

    public function addItems(array $items): self {
        if ($this->status !== 'draft' && $this->status !== 'items_added') {
            throw new InvalidOrderStateException("Cannot add items in status: {$this->status}");
        }

        $this->recordThat(new ItemsAddedToOrder(
            aggregateRootUuid: $this->uuid(),
            items: $items,
            timestamp: now()
        ));

        return $this;
    }

    public function markItemsAsValidated(array $validatedItems, Money $subtotal): self {
        $this->recordThat(new ItemsValidated(
            aggregateRootUuid: $this->uuid(),
            validatedItems: $validatedItems,
            subtotal: $subtotal->getAmount(),
            currency: $subtotal->getCurrency()->getCode()
        ));

        return $this;
    }

    public function setPromotions(array $availablePromotions, array $autoApplied = []): self {
        $this->recordThat(new PromotionsCalculated(
            aggregateRootUuid: $this->uuid(),
            availablePromotions: $availablePromotions,
            autoApplied: $autoApplied,
            totalDiscount: $this->calculatePromotionDiscount($autoApplied)
        ));

        return $this;
    }

    public function applyPromotion(string $promotionId, Money $discountAmount): self {
        if (!$this->promotionsCalculated) {
            throw new InvalidOrderStateException("Promotions must be calculated before applying");
        }

        $this->recordThat(new PromotionApplied(
            aggregateRootUuid: $this->uuid(),
            promotionId: $promotionId,
            discountAmount: $discountAmount->getAmount(),
            currency: $discountAmount->getCurrency()->getCode()
        ));

        return $this;
    }

    public function removePromotion(string $promotionId): self {
        $this->recordThat(new PromotionRemoved(
            aggregateRootUuid: $this->uuid(),
            promotionId: $promotionId
        ));

        return $this;
    }

    public function calculateFinalPrice(Money $tax, Money $total): self {
        $this->recordThat(new PriceCalculated(
            aggregateRootUuid: $this->uuid(),
            subtotal: $this->subtotal->getAmount(),
            discount: $this->discount->getAmount(),
            tax: $tax->getAmount(),
            tip: $this->tip->getAmount(),
            total: $total->getAmount(),
            currency: $total->getCurrency()->getCode()
        ));

        return $this;
    }

    public function addTip(Money $tipAmount): self {
        $this->recordThat(new TipAdded(
            aggregateRootUuid: $this->uuid(),
            tipAmount: $tipAmount->getAmount(),
            currency: $tipAmount->getCurrency()->getCode()
        ));

        return $this;
    }

    public function setPaymentMethod(string $paymentMethod): self {
        $this->recordThat(new PaymentMethodSet(
            aggregateRootUuid: $this->uuid(),
            paymentMethod: $paymentMethod
        ));

        return $this;
    }

    public function confirmOrder(): self {
        if (!$this->itemsValidated) {
            throw new InvalidOrderStateException("Items must be validated before confirming");
        }

        if (empty($this->items)) {
            throw new InvalidOrderStateException("Cannot confirm order without items");
        }

        if (!$this->paymentMethod) {
            throw new InvalidOrderStateException("Payment method must be set before confirming");
        }

        $this->recordThat(new OrderConfirmed(
            aggregateRootUuid: $this->uuid(),
            orderNumber: $this->generateOrderNumber(),
            confirmedAt: now()
        ));

        return $this;
    }

    public function cancelOrder(string $reason): self {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            throw new InvalidOrderStateException("Cannot cancel order in status: {$this->status}");
        }

        $this->recordThat(new OrderCancelled(
            aggregateRootUuid: $this->uuid(),
            reason: $reason,
            cancelledAt: now()
        ));

        return $this;
    }

    /**
     * Modify order items (add, remove, or update)
     */
    public function modifyItems(
        array $toAdd = [],
        array $toRemove = [],
        array $toModify = [],
        string $modifiedBy = '',
        string $reason = ''
    ): self {
        // Check if order can be modified based on status
        if (!$this->canBeModified()) {
            throw new InvalidOrderStateException("Cannot modify order in status: {$this->status}");
        }

        // Calculate previous total
        $previousTotal = $this->total->getAmount();
        
        // Calculate new items and total
        $newItems = $this->calculateModifiedItems($toAdd, $toRemove, $toModify);
        $newTotal = $this->calculateNewTotal($newItems);
        
        // Determine if kitchen notification is needed
        $requiresKitchenNotification = $this->shouldNotifyKitchen();
        
        $this->recordThat(new ItemsModified(
            aggregateRootUuid: $this->uuid(),
            addedItems: $toAdd,
            removedItems: $toRemove,
            modifiedItems: $toModify,
            modifiedBy: $modifiedBy,
            reason: $reason,
            modifiedAt: now(),
            requiresKitchenNotification: $requiresKitchenNotification,
            previousTotal: $previousTotal,
            newTotal: $newTotal
        ));

        return $this;
    }

    /**
     * Adjust order price (discount, surcharge, correction)
     */
    public function adjustPrice(
        string $adjustmentType,
        Money $amount,
        string $reason,
        string $authorizedBy,
        ?string $authorizationCode = null
    ): self {
        // Validate adjustment type
        if (!in_array($adjustmentType, ['discount', 'surcharge', 'correction', 'tip'])) {
            throw new InvalidOrderStateException("Invalid adjustment type: {$adjustmentType}");
        }

        // Check if order can have price adjusted
        if ($this->status === 'cancelled' || $this->status === 'refunded') {
            throw new InvalidOrderStateException("Cannot adjust price for order in status: {$this->status}");
        }

        // Determine if this affects payment
        $affectsPayment = !empty($this->paymentMethod) && $this->status !== 'draft';
        
        $this->recordThat(new PriceAdjusted(
            aggregateRootUuid: $this->uuid(),
            adjustmentType: $adjustmentType,
            amount: $amount->getAmount(),
            currency: $amount->getCurrency()->getCode(),
            reason: $reason,
            authorizedBy: $authorizedBy,
            affectsPayment: $affectsPayment,
            adjustedAt: now(),
            authorizationCode: $authorizationCode,
            metadata: [
                'original_total' => $this->total->getAmount(),
                'original_status' => $this->status,
            ]
        ));

        return $this;
    }

    /**
     * Check if order can be modified
     */
    public function canBeModified(): bool
    {
        return in_array($this->status, [
            'draft', 'started', 'items_added', 'items_validated', 
            'promotions_calculated', 'price_calculated', 'confirmed'
        ]);
    }

    /**
     * Check if kitchen should be notified of changes
     */
    private function shouldNotifyKitchen(): bool
    {
        return in_array($this->status, ['confirmed', 'preparing']);
    }

    /**
     * Calculate modified items list
     */
    private function calculateModifiedItems(array $toAdd, array $toRemove, array $toModify): array
    {
        $items = $this->items;
        
        // Remove items
        foreach ($toRemove as $itemId) {
            $items = array_filter($items, fn($item) => $item['item_id'] !== $itemId);
        }
        
        // Modify existing items
        foreach ($toModify as $modification) {
            $itemId = $modification['item_id'];
            foreach ($items as &$item) {
                if ($item['item_id'] === $itemId) {
                    $item['quantity'] = $modification['quantity'] ?? $item['quantity'];
                    $item['notes'] = $modification['notes'] ?? $item['notes'];
                    $item['modifiers'] = $modification['modifiers'] ?? $item['modifiers'];
                    break;
                }
            }
        }
        
        // Add new items
        foreach ($toAdd as $newItem) {
            $items[] = $newItem;
        }
        
        return $items;
    }

    /**
     * Calculate new total for modified items
     */
    private function calculateNewTotal(array $items): int
    {
        $total = 0;
        foreach ($items as $item) {
            $total += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
        }
        return $total;
    }

    protected function applyOrderStarted(OrderStarted $event): void
    {
        $this->status = 'started';
        $this->staffId = $event->staffId;
        $this->locationId = $event->locationId;
        $this->tableNumber = $event->tableNumber;
        $this->metadata = $event->metadata;
    }

    protected function applyItemsAddedToOrder(ItemsAddedToOrder $event): void
    {
        $this->status = 'items_added';
        $this->items = array_merge($this->items, $event->items);
        $this->itemsValidated = false;
    }

    protected function applyItemsValidated(ItemsValidated $event): void
    {
        $this->status = 'items_validated';
        $this->items = $event->validatedItems;
        $this->subtotal = new Money($event->subtotal, new Currency($event->currency));
        $this->itemsValidated = true;
    }

    protected function applyPromotionsCalculated(PromotionsCalculated $event): void
    {
        $this->status = 'promotions_calculated';
        $this->appliedPromotions = $event->autoApplied;
        $this->discount = new Money($event->totalDiscount, new Currency('CLP'));
        $this->promotionsCalculated = true;
    }

    protected function applyPromotionApplied(PromotionApplied $event): void
    {
        $this->appliedPromotions[] = $event->promotionId;
        $discountAmount = new Money($event->discountAmount, new Currency($event->currency));
        $this->discount = $this->discount->add($discountAmount);
    }

    protected function applyPromotionRemoved(PromotionRemoved $event): void
    {
        $this->appliedPromotions = array_filter(
            $this->appliedPromotions, 
            fn($id) => $id !== $event->promotionId
        );
    }

    protected function applyPriceCalculated(PriceCalculated $event): void
    {
        $this->status = 'price_calculated';
        $currency = new Currency($event->currency);
        $this->subtotal = new Money($event->subtotal, $currency);
        $this->discount = new Money($event->discount, $currency);
        $this->tax = new Money($event->tax, $currency);
        $this->tip = new Money($event->tip, $currency);
        $this->total = new Money($event->total, $currency);
    }

    protected function applyTipAdded(TipAdded $event): void
    {
        $this->tip = new Money($event->tipAmount, new Currency($event->currency));
        $this->total = $this->total->add($this->tip);
    }

    protected function applyPaymentMethodSet(PaymentMethodSet $event): void
    {
        $this->paymentMethod = $event->paymentMethod;
    }

    protected function applyOrderConfirmed(OrderConfirmed $event): void
    {
        $this->status = 'confirmed';
    }

    protected function applyOrderCancelled(OrderCancelled $event): void
    {
        $this->status = 'cancelled';
    }

    protected function applyItemsModified(ItemsModified $event): void
    {
        // Apply added items
        foreach ($event->addedItems as $item) {
            $this->items[] = $item;
        }
        
        // Apply removed items
        $this->items = array_filter($this->items, function($item) use ($event) {
            return !in_array($item['item_id'] ?? $item['id'] ?? null, $event->removedItems);
        });
        
        // Apply modified items
        foreach ($event->modifiedItems as $modification) {
            $itemId = $modification['item_id'];
            foreach ($this->items as &$item) {
                if (($item['item_id'] ?? $item['id'] ?? null) === $itemId) {
                    $item = array_merge($item, $modification);
                    break;
                }
            }
        }
        
        // Update total
        $this->total = new Money($event->newTotal, new Currency('CLP'));
        
        // Mark that items need re-validation if already validated
        if ($this->itemsValidated) {
            $this->itemsValidated = false;
        }
    }

    protected function applyPriceAdjusted(PriceAdjusted $event): void
    {
        $adjustment = new Money($event->amount, new Currency($event->currency));
        
        switch ($event->adjustmentType) {
            case 'discount':
                $this->discount = $this->discount->add($adjustment);
                $this->total = $this->total->subtract($adjustment);
                break;
                
            case 'surcharge':
                $this->total = $this->total->add($adjustment);
                break;
                
            case 'correction':
                // Direct total adjustment
                $this->total = new Money($event->amount, new Currency($event->currency));
                break;
                
            case 'tip':
                $this->tip = $this->tip->add($adjustment);
                $this->total = $this->total->add($adjustment);
                break;
        }
    }

    private function calculatePromotionDiscount(array $promotions): int
    {
        return array_reduce($promotions, function ($total, $promotion) {
            return $total + ($promotion['discount_amount'] ?? 0);
        }, 0);
    }

    private function generateOrderNumber(): string
    {
        return sprintf(
            '%s-%s-%s',
            date('Ymd'),
            substr($this->locationId, 0, 4),
            str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT)
        );
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }
}