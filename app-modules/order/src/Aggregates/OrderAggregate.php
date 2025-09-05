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
use Colame\Order\Events\OrderStatusTransitioned;
use Colame\Order\Events\PaymentProcessed;
use Colame\Order\Events\PaymentFailed;
use Colame\Order\Events\CustomerInfoUpdated;
use Colame\Order\Events\OrderItemsUpdated;
use Colame\Order\Events\ItemModifiersChanged;
use Colame\Order\Exceptions\InvalidOrderStateException;
use Akaunting\Money\Money;
use Akaunting\Money\Currency;
use Colame\Order\States\DraftState;
use Colame\Order\States\StartedState;
use Colame\Order\States\ItemsAddedState;
use Colame\Order\States\ConfirmedState;
use Colame\Order\States\PreparingState;
use Colame\Order\States\CancelledState;

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
        $this->subtotal = Money::CLP(0);
        $this->discount = Money::CLP(0);
        $this->tax = Money::CLP(0);
        $this->tip = Money::CLP(0);
        $this->total = Money::CLP(0);
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
        if (!in_array($this->status, ['draft', 'started', 'items_added'])) {
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
            currency: $subtotal->getCurrency()
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
            currency: $discountAmount->getCurrency()
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
            currency: $total->getCurrency()
        ));

        return $this;
    }

    public function addTip(Money $tipAmount): self {
        $this->recordThat(new TipAdded(
            aggregateRootUuid: $this->uuid(),
            tipAmount: $tipAmount->getAmount(),
            currency: $tipAmount->getCurrency()
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
            currency: $amount->getCurrency(),
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
     * Transition order status
     */
    public function transitionStatus(
        string $newStatus,
        ?string $reason = null,
        ?string $transitionedBy = null
    ): self {
        // Validate status transition
        if (!$this->isValidStatusTransition($this->status, $newStatus)) {
            throw new InvalidOrderStateException(
                "Cannot transition from status '{$this->status}' to '{$newStatus}'"
            );
        }

        $this->recordThat(new OrderStatusTransitioned(
            aggregateRootUuid: $this->uuid(),
            previousStatus: $this->status,
            newStatus: $newStatus,
            reason: $reason,
            transitionedBy: $transitionedBy,
            transitionedAt: now()
        ));

        return $this;
    }

    /**
     * Process payment for the order
     */
    public function processPayment(
        string $paymentId,
        string $paymentMethod,
        Money $amount,
        string $status,
        ?string $transactionId = null,
        ?array $metadata = null
    ): self {
        if ($this->status !== 'confirmed' && $this->status !== 'completed') {
            throw new InvalidOrderStateException(
                "Cannot process payment for order in status: {$this->status}"
            );
        }

        $this->recordThat(new PaymentProcessed(
            aggregateRootUuid: $this->uuid(),
            paymentId: $paymentId,
            paymentMethod: $paymentMethod,
            amount: $amount->getAmount(),
            currency: $amount->getCurrency(),
            status: $status,
            transactionId: $transactionId,
            metadata: $metadata,
            processedAt: now()
        ));

        return $this;
    }

    /**
     * Record payment failure
     */
    public function recordPaymentFailure(
        string $paymentId,
        string $paymentMethod,
        Money $amount,
        string $failureReason,
        ?string $errorCode = null,
        ?array $metadata = null
    ): self {
        $this->recordThat(new PaymentFailed(
            aggregateRootUuid: $this->uuid(),
            paymentId: $paymentId,
            paymentMethod: $paymentMethod,
            amount: $amount->getAmount(),
            currency: $amount->getCurrency(),
            failureReason: $failureReason,
            errorCode: $errorCode,
            metadata: $metadata,
            failedAt: now()
        ));

        return $this;
    }

    /**
     * Update customer information
     */
    public function updateCustomerInfo(
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?string $customerEmail = null,
        ?string $deliveryAddress = null,
        ?string $tableNumber = null,
        ?string $notes = null,
        ?string $specialInstructions = null,
        ?string $updatedBy = null
    ): self {
        if (!$this->canBeModified()) {
            throw new InvalidOrderStateException(
                "Cannot update customer info for order in status: {$this->status}"
            );
        }

        $this->recordThat(new CustomerInfoUpdated(
            aggregateRootUuid: $this->uuid(),
            customerName: $customerName,
            customerPhone: $customerPhone,
            customerEmail: $customerEmail,
            deliveryAddress: $deliveryAddress,
            tableNumber: $tableNumber,
            notes: $notes,
            specialInstructions: $specialInstructions,
            updatedBy: $updatedBy,
            updatedAt: now()
        ));

        return $this;
    }

    /**
     * Update order items (for web interface)
     */
    public function updateOrderItems(
        array $updatedItems,
        array $deletedItemIds,
        ?string $updatedBy = null
    ): self {
        if (!$this->canBeModified()) {
            throw new InvalidOrderStateException(
                "Cannot update items for order in status: {$this->status}"
            );
        }

        // Calculate new total
        $previousTotal = $this->total->getAmount();
        $newTotal = $this->calculateNewTotalAfterUpdate($updatedItems, $deletedItemIds);

        $this->recordThat(new OrderItemsUpdated(
            aggregateRootUuid: $this->uuid(),
            updatedItems: $updatedItems,
            deletedItemIds: $deletedItemIds,
            previousTotal: $previousTotal,
            newTotal: $newTotal,
            updatedBy: $updatedBy,
            updatedAt: now()
        ));

        return $this;
    }

    /**
     * Check if order can be modified
     */
    public function canBeModified(): bool
    {
        // Keep the aggregate's own logic for now
        // The state objects are for the Model/database layer
        return in_array($this->status, [
            'draft', 'started', 'items_added', 'items_validated',
            'promotions_calculated', 'price_calculated', 'confirmed'
        ]);
    }

    /**
     * Modify modifiers for a specific item
     */
    public function modifyItemModifiers(
        int $orderItemId,
        string $itemName,
        array $addedModifiers = [],
        array $removedModifiers = [],
        array $updatedModifiers = [],
        string $modifiedBy = '',
        string $reason = ''
    ): self {
        // Check if order can be modified
        if (!$this->canBeModified()) {
            throw new InvalidOrderStateException("Cannot modify item in order status: {$this->status}");
        }

        // Find the item and calculate price changes
        $oldPrice = $this->calculateItemPrice($orderItemId);
        $newPrice = $this->calculateItemPriceWithModifiers(
            $orderItemId,
            $addedModifiers,
            $removedModifiers,
            $updatedModifiers
        );

        // Record the item modifier change event
        $this->recordThat(new ItemModifiersChanged(
            aggregateRootUuid: $this->uuid,
            orderItemId: $orderItemId,
            itemName: $itemName,
            addedModifiers: $addedModifiers,
            removedModifiers: $removedModifiers,
            updatedModifiers: $updatedModifiers,
            oldPrice: $oldPrice,
            newPrice: $newPrice,
            modifiedBy: $modifiedBy,
            reason: $reason,
            modifiedAt: now(),
            requiresKitchenNotification: $this->shouldNotifyKitchen(),
            metadata: [
                'order_status' => $this->status,
                'modification_count' => $this->modificationCount + 1
            ]
        ));

        // Update order totals
        $priceDifference = $newPrice - $oldPrice;
        if ($priceDifference !== 0) {
            $this->updateTotalsAfterModification($priceDifference);
        }

        $this->modificationCount++;

        return $this;
    }

    /**
     * Calculate item price with current modifiers
     */
    private function calculateItemPrice(int $orderItemId): int
    {
        // Find item in current items array
        foreach ($this->items as $item) {
            if (($item['id'] ?? 0) === $orderItemId) {
                return $item['total_price'] ?? 0;
            }
        }
        return 0;
    }

    /**
     * Calculate new item price with modifier changes
     */
    private function calculateItemPriceWithModifiers(
        int $orderItemId,
        array $addedModifiers,
        array $removedModifiers,
        array $updatedModifiers
    ): int {
        $basePrice = 0;
        $modifierTotal = 0;
        $quantity = 1;

        // Find the base item
        foreach ($this->items as $item) {
            if (($item['id'] ?? 0) === $orderItemId) {
                $basePrice = $item['base_price'] ?? $item['unit_price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                $modifierTotal = $item['modifiers_total'] ?? 0;
                break;
            }
        }

        // Add new modifiers
        foreach ($addedModifiers as $modifier) {
            $modifierTotal += ($modifier['priceAdjustment'] ?? 0) * ($modifier['quantity'] ?? 1);
        }

        // Remove modifiers (subtract their price adjustments)
        // In a real implementation, you'd look up the removed modifiers' prices
        foreach ($removedModifiers as $modifierId) {
            // This would need to look up the modifier's price from current state
            // For now, we'll assume it's passed in metadata
        }

        // Update modifiers
        foreach ($updatedModifiers as $modifier) {
            // Calculate the difference from the old modifier state
            $modifierTotal += ($modifier['priceAdjustment'] ?? 0) * ($modifier['quantity'] ?? 1);
        }

        return ($basePrice + $modifierTotal) * $quantity;
    }

    /**
     * Update order totals after modification
     */
    private function updateTotalsAfterModification(int $priceDifference): void
    {
        $this->subtotal = Money::CLP($this->subtotal->getAmount() + $priceDifference);
        $this->recalculateTotals();
    }

    /**
     * Check if kitchen should be notified
     */
    private function shouldNotifyKitchen(): bool
    {
        return in_array($this->status, ['confirmed', 'preparing', 'ready']);
    }

    /**
     * Check if status transition is valid
     */
    private function isValidStatusTransition(string $from, string $to): bool
    {
        $validTransitions = [
            'draft' => ['started', 'cancelled'],
            'started' => ['items_added', 'cancelled'],
            'items_added' => ['items_validated', 'cancelled'],
            'items_validated' => ['promotions_calculated', 'cancelled'],
            'promotions_calculated' => ['price_calculated', 'cancelled'],
            'price_calculated' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['completed', 'cancelled'],
            'completed' => ['refunded'],
            'cancelled' => [],
            'refunded' => [],
        ];

        return isset($validTransitions[$from]) && in_array($to, $validTransitions[$from]);
    }

    /**
     * Calculate new total after item updates
     */
    private function calculateNewTotalAfterUpdate(array $updatedItems, array $deletedItemIds): int
    {
        $total = 0;
        
        // Add updated items
        foreach ($this->items as $item) {
            $itemId = $item['item_id'] ?? $item['id'] ?? null;
            
            // Skip deleted items
            if (in_array($itemId, $deletedItemIds)) {
                continue;
            }
            
            // Check if item is updated
            $updated = false;
            foreach ($updatedItems as $updatedItem) {
                if (($updatedItem['id'] ?? null) === $itemId) {
                    $total += ($updatedItem['quantity'] ?? 1) * ($updatedItem['unit_price'] ?? 0);
                    $updated = true;
                    break;
                }
            }
            
            // If not updated, use existing values
            if (!$updated) {
                $total += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
            }
        }
        
        return $total;
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
        $this->subtotal = Money::CLP($event->subtotal);
        $this->itemsValidated = true;
    }

    protected function applyPromotionsCalculated(PromotionsCalculated $event): void
    {
        $this->status = 'promotions_calculated';
        $this->appliedPromotions = $event->autoApplied;
        $this->discount = Money::CLP($event->totalDiscount);
        $this->promotionsCalculated = true;
    }

    protected function applyPromotionApplied(PromotionApplied $event): void
    {
        $this->appliedPromotions[] = $event->promotionId;
        $discountAmount = Money::CLP($event->discountAmount);
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
        $this->subtotal = Money::CLP($event->subtotal, $currency);
        $this->discount = Money::CLP($event->discount, $currency);
        $this->tax = Money::CLP($event->tax, $currency);
        $this->tip = Money::CLP($event->tip, $currency);
        $this->total = Money::CLP($event->total, $currency);
    }

    protected function applyTipAdded(TipAdded $event): void
    {
        $this->tip = Money::CLP($event->tipAmount);
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
        $this->total = Money::CLP($event->newTotal);
        
        // Mark that items need re-validation if already validated
        if ($this->itemsValidated) {
            $this->itemsValidated = false;
        }
    }

    protected function applyPriceAdjusted(PriceAdjusted $event): void
    {
        $adjustment = Money::CLP($event->amount);
        
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
                $this->total = Money::CLP($event->amount);
                break;
                
            case 'tip':
                $this->tip = $this->tip->add($adjustment);
                $this->total = $this->total->add($adjustment);
                break;
        }
    }

    protected function applyOrderStatusTransitioned(OrderStatusTransitioned $event): void
    {
        $this->status = $event->newStatus;
    }

    protected function applyPaymentProcessed(PaymentProcessed $event): void
    {
        $this->paymentMethod = $event->paymentMethod;
        // Update payment status in metadata
        $this->metadata['payment_status'] = $event->status;
        $this->metadata['payment_id'] = $event->paymentId;
        $this->metadata['transaction_id'] = $event->transactionId;
    }

    protected function applyPaymentFailed(PaymentFailed $event): void
    {
        // Store failure in metadata
        $this->metadata['last_payment_failure'] = [
            'payment_id' => $event->paymentId,
            'reason' => $event->failureReason,
            'error_code' => $event->errorCode,
            'failed_at' => $event->failedAt,
        ];
    }

    protected function applyCustomerInfoUpdated(CustomerInfoUpdated $event): void
    {
        // Update metadata with customer info
        if ($event->customerName !== null) {
            $this->metadata['customer_name'] = $event->customerName;
        }
        if ($event->customerPhone !== null) {
            $this->metadata['customer_phone'] = $event->customerPhone;
        }
        if ($event->customerEmail !== null) {
            $this->metadata['customer_email'] = $event->customerEmail;
        }
        if ($event->deliveryAddress !== null) {
            $this->metadata['delivery_address'] = $event->deliveryAddress;
        }
        if ($event->tableNumber !== null) {
            $this->tableNumber = $event->tableNumber;
        }
        if ($event->notes !== null) {
            $this->metadata['notes'] = $event->notes;
        }
        if ($event->specialInstructions !== null) {
            $this->metadata['special_instructions'] = $event->specialInstructions;
        }
    }

    protected function applyOrderItemsUpdated(OrderItemsUpdated $event): void
    {
        // Remove deleted items
        $this->items = array_filter($this->items, function($item) use ($event) {
            $itemId = $item['item_id'] ?? $item['id'] ?? null;
            return !in_array($itemId, $event->deletedItemIds);
        });
        
        // Update existing items
        foreach ($event->updatedItems as $updatedItem) {
            $found = false;
            foreach ($this->items as &$item) {
                if (($item['item_id'] ?? $item['id'] ?? null) === ($updatedItem['id'] ?? null)) {
                    $item = array_merge($item, $updatedItem);
                    $found = true;
                    break;
                }
            }
        }
        
        // Update total
        $this->total = Money::CLP($event->newTotal);
        
        // Mark that items need re-validation
        $this->itemsValidated = false;
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