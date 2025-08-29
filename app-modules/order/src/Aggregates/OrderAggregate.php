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