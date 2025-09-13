<?php

declare(strict_types=1);

namespace Colame\OrderEs\Aggregates;

use Colame\OrderEs\Events\SessionEvents\SessionInitiated;
use Colame\OrderEs\Events\ItemSearched;
use Colame\OrderEs\Events\CategoryBrowsed;
use Colame\OrderEs\Events\ItemViewed;
use Colame\OrderEs\Events\ItemAddedToCart;
use Colame\OrderEs\Events\ItemRemovedFromCart;
use Colame\OrderEs\Events\CartItemModified;
use Colame\OrderEs\Events\ServingTypeSelected;
use Colame\OrderEs\Events\CustomerInfoEntered;
use Colame\OrderEs\Events\PaymentMethodSelected;
use Colame\OrderEs\Events\DraftSaved;
use Colame\OrderEs\Events\SessionAbandoned;
use Colame\OrderEs\Events\SessionConverted;
use Colame\OrderEs\Events\OrderStarted;
use Colame\OrderEs\Events\ItemAddedToOrder;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;
use Illuminate\Support\Str;

/**
 * OrderSession Aggregate - Handles the complete order lifecycle from session to order
 * This is a unified aggregate that manages both session and order states
 */
final class OrderSession extends AggregateRoot
{
    // Session State
    private string $status = 'initiated'; // initiated, cart_building, converting, converted, abandoned
    private ?int $userId = null;
    private int $locationId;
    private array $deviceInfo = [];
    private ?string $referrer = null;
    private array $metadata = [];
    
    // Cart State
    private array $cartItems = [];
    private float $cartSubtotal = 0;
    private ?string $servingType = null;
    private ?string $tableNumber = null;
    private ?string $deliveryAddress = null;
    private array $customerInfo = [];
    private ?string $paymentMethod = null;
    
    // Analytics State
    private array $searchHistory = [];
    private array $viewedItems = [];
    private array $browsedCategories = [];
    private int $sessionDurationSeconds = 0;
    private string $lastActivity = '';
    
    // Order State (after conversion)
    private ?string $orderId = null;
    private string $orderStatus = 'draft';
    private array $orderItems = [];
    private array $appliedPromotions = [];
    private float $subtotal = 0;
    private float $discount = 0;
    private float $tax = 0;
    private float $tip = 0;
    private float $total = 0;
    
    // Session Management
    
    public function initiateSession(
        ?int $userId,
        int $locationId,
        array $deviceInfo = [],
        ?string $referrer = null,
        array $metadata = []
    ): self {
        if ($this->status !== 'initiated') {
            return $this; // Already initiated
        }
        
        $this->recordThat(new SessionInitiated(
            sessionId: $this->uuid(),
            staffId: $userId,
            locationId: $locationId,
            type: $metadata['type'] ?? 'dine_in',
            tableNumber: $metadata['table_number'] ?? null,
            customerCount: $metadata['customer_count'] ?? 1,
            metadata: $metadata
        ));
        
        return $this;
    }
    
    // Search & Browse
    
    public function recordSearch(
        string $query,
        array $filters = [],
        int $resultsCount = 0,
        ?string $searchId = null
    ): self {
        $this->guardAgainstAbandonedSession();
        
        $this->recordThat(new ItemSearched(
            sessionId: $this->uuid(),
            query: $query,
            filters: $filters,
            resultsCount: $resultsCount,
            searchId: $searchId,
            searchedAt: new \DateTimeImmutable()
        ));
        
        return $this;
    }
    
    public function browseCategory(
        int $categoryId,
        string $categoryName,
        int $itemsViewed = 0,
        int $timeSpentSeconds = 0
    ): self {
        $this->guardAgainstAbandonedSession();
        
        $this->recordThat(new CategoryBrowsed(
            sessionId: $this->uuid(),
            categoryId: $categoryId,
            categoryName: $categoryName,
            itemsViewed: $itemsViewed,
            timeSpentSeconds: $timeSpentSeconds,
            browsedAt: new \DateTimeImmutable()
        ));
        
        return $this;
    }
    
    public function viewItem(
        int $itemId,
        string $itemName,
        float $price,
        ?string $category = null,
        string $viewSource = 'browse',
        int $viewDurationSeconds = 0
    ): self {
        $this->guardAgainstAbandonedSession();
        
        $this->recordThat(new ItemViewed(
            sessionId: $this->uuid(),
            itemId: $itemId,
            itemName: $itemName,
            price: $price,
            category: $category,
            viewSource: $viewSource,
            viewDurationSeconds: $viewDurationSeconds,
            viewedAt: new \DateTimeImmutable()
        ));
        
        return $this;
    }
    
    // Cart Management
    
    public function addToCart(
        int $itemId,
        string $itemName,
        int $quantity,
        float $basePrice,
        float $unitPrice,
        ?string $category = null,
        array $modifiers = [],
        ?string $notes = null,
        string $addedFrom = 'browse'
    ): self {
        $this->guardAgainstAbandonedSession();
        $this->guardAgainstConvertedSession();
        
        if ($quantity <= 0) {
            throw new \DomainException('Quantity must be greater than 0');
        }
        
        $this->recordThat(new ItemAddedToCart(
            sessionId: $this->uuid(),
            itemId: $itemId,
            itemName: $itemName,
            quantity: $quantity,
            basePrice: $basePrice,
            unitPrice: $unitPrice,
            category: $category,
            modifiers: $modifiers,
            notes: $notes,
            addedFrom: $addedFrom,
            addedAt: new \DateTimeImmutable()
        ));
        
        return $this;
    }
    
    public function removeFromCart(
        int $itemId,
        string $itemName,
        int $removedQuantity,
        string $removalReason = 'user_action'
    ): self {
        $this->guardAgainstAbandonedSession();
        $this->guardAgainstConvertedSession();
        
        $this->recordThat(new ItemRemovedFromCart(
            sessionId: $this->uuid(),
            itemId: $itemId,
            itemName: $itemName,
            removedQuantity: $removedQuantity,
            removalReason: $removalReason,
            removedAt: new \DateTimeImmutable()
        ));
        
        return $this;
    }
    
    public function modifyCartItem(
        int $itemId,
        string $itemName,
        string $modificationType,
        array $changes = []
    ): self {
        $this->guardAgainstAbandonedSession();
        $this->guardAgainstConvertedSession();
        
        $this->recordThat(new CartItemModified(
            sessionId: $this->uuid(),
            itemId: $itemId,
            itemName: $itemName,
            modificationType: $modificationType,
            changes: $changes,
            modifiedAt: new \DateTimeImmutable()
        ));
        
        return $this;
    }
    
    
    // Session Configuration
    
    public function setServingType(
        string $servingType,
        ?string $tableNumber = null,
        ?string $deliveryAddress = null
    ): self {
        $this->guardAgainstAbandonedSession();
        $this->guardAgainstConvertedSession();
        
        $this->recordThat(new ServingTypeSelected(
            sessionId: $this->uuid(),
            servingType: $servingType,
            previousType: $this->servingType,
            tableNumber: $tableNumber,
            deliveryAddress: $deliveryAddress,
            selectedAt: new \DateTimeImmutable()
        ));
        
        return $this;
    }
    
    public function enterCustomerInfo(
        array $fields,
        array $validationErrors = [],
        bool $isComplete = false
    ): self {
        $this->guardAgainstAbandonedSession();
        $this->guardAgainstConvertedSession();
        
        $this->recordThat(new CustomerInfoEntered(
            sessionId: $this->uuid(),
            fields: $fields,
            validationErrors: $validationErrors,
            isComplete: $isComplete,
            enteredAt: new \DateTimeImmutable()
        ));
        
        return $this;
    }
    
    public function selectPaymentMethod(string $paymentMethod): self
    {
        $this->guardAgainstAbandonedSession();
        $this->guardAgainstConvertedSession();
        
        $this->recordThat(new PaymentMethodSelected(
            sessionId: $this->uuid(),
            paymentMethod: $paymentMethod,
            previousMethod: $this->paymentMethod,
            selectedAt: new \DateTimeImmutable()
        ));
        
        return $this;
    }
    
    // Session Lifecycle
    
    public function saveDraft(bool $autoSaved = false): self
    {
        $this->guardAgainstAbandonedSession();
        $this->guardAgainstConvertedSession();
        
        $this->recordThat(new DraftSaved(
            sessionId: $this->uuid(),
            cartItems: $this->cartItems,
            customerInfo: $this->customerInfo,
            servingType: $this->servingType,
            paymentMethod: $this->paymentMethod,
            subtotal: $this->cartSubtotal,
            autoSaved: $autoSaved,
            savedAt: new \DateTimeImmutable()
        ));
        
        return $this;
    }
    
    public function abandonSession(
        string $reason,
        int $sessionDurationSeconds,
        string $lastActivity = ''
    ): self {
        if ($this->status === 'abandoned' || $this->status === 'converted') {
            return $this;
        }
        
        $this->recordThat(new SessionAbandoned(
            sessionId: $this->uuid(),
            reason: $reason,
            sessionDurationSeconds: $sessionDurationSeconds,
            itemsInCart: count($this->cartItems),
            cartValue: $this->cartSubtotal,
            lastActivity: $lastActivity,
            abandonedAt: new \DateTimeImmutable()
        ));
        
        return $this;
    }
    
    public function convertToOrder(): self
    {
        $this->guardAgainstAbandonedSession();
        
        if ($this->status === 'converted') {
            return $this; // Already converted
        }
        
        if (empty($this->cartItems)) {
            throw new \DomainException('Cannot convert empty cart to order');
        }
        
        if (!$this->paymentMethod) {
            throw new \DomainException('Payment method must be selected before converting to order');
        }
        
        $orderId = (string) Str::uuid();
        
        // Record conversion
        $this->recordThat(new SessionConverted(
            sessionId: $this->uuid(),
            orderId: $orderId,
            convertedAt: new \DateTimeImmutable()
        ));
        
        // Start the actual order
        $this->recordThat(new OrderStarted(
            orderId: $orderId,
            userId: $this->userId ?? 0, // Staff member who created the order
            locationId: $this->locationId,
            type: $this->servingType ?? 'dine_in',
            orderNumber: $this->generateOrderNumber(),
            startedAt: new \DateTimeImmutable()
        ));
        
        // Add items to order
        foreach ($this->cartItems as $item) {
            $this->recordThat(new ItemAddedToOrder(
                orderId: $orderId,
                lineItemId: (string) Str::uuid(),
                itemId: $item['itemId'],
                itemName: $item['itemName'],
                basePrice: $item['basePrice'],
                unitPrice: $item['unitPrice'],
                quantity: $item['quantity'],
                modifiers: $item['modifiers'] ?? [],
                notes: $item['notes'] ?? null
            ));
        }
        
        return $this;
    }
    
    // Event Handlers
    
    protected function applySessionInitiated(SessionInitiated $event): void
    {
        $this->status = 'cart_building';
        $this->userId = $event->staffId;
        $this->locationId = $event->locationId;
        $this->deviceInfo = [];
        $this->referrer = null;
        $this->metadata = $event->metadata;
        $this->servingType = $event->type;
        $this->tableNumber = $event->tableNumber ? (string) $event->tableNumber : null;
    }
    
    protected function applyItemAddedToCart(ItemAddedToCart $event): void
    {
        $this->cartItems[] = [
            'itemId' => $event->itemId,
            'itemName' => $event->itemName,
            'quantity' => $event->quantity,
            'basePrice' => $event->basePrice,
            'unitPrice' => $event->unitPrice,
            'category' => $event->category,
            'modifiers' => $event->modifiers,
            'notes' => $event->notes,
        ];
        
        $this->cartSubtotal += $event->quantity * $event->unitPrice;
        $this->lastActivity = 'item_added';
    }
    
    protected function applyItemRemovedFromCart(ItemRemovedFromCart $event): void
    {
        $this->cartItems = array_filter($this->cartItems, function ($item) use ($event) {
            return $item['itemId'] !== $event->itemId;
        });
        
        $this->recalculateCartSubtotal();
        $this->lastActivity = 'item_removed';
    }
    
    protected function applyServingTypeSelected(ServingTypeSelected $event): void
    {
        $this->servingType = $event->servingType;
        $this->tableNumber = $event->tableNumber;
        $this->deliveryAddress = $event->deliveryAddress;
    }
    
    protected function applyCustomerInfoEntered(CustomerInfoEntered $event): void
    {
        $this->customerInfo = array_merge($this->customerInfo, $event->fields);
    }
    
    protected function applyPaymentMethodSelected(PaymentMethodSelected $event): void
    {
        $this->paymentMethod = $event->paymentMethod;
    }
    
    protected function applySessionAbandoned(SessionAbandoned $event): void
    {
        $this->status = 'abandoned';
        $this->sessionDurationSeconds = $event->sessionDurationSeconds;
    }
    
    protected function applySessionConverted(SessionConverted $event): void
    {
        $this->status = 'converted';
        $this->orderId = $event->orderId;
    }
    
    protected function applyOrderStarted(OrderStarted $event): void
    {
        $this->orderStatus = 'started';
    }
    
    // Guards
    
    private function guardAgainstAbandonedSession(): void
    {
        if ($this->status === 'abandoned') {
            throw new \DomainException('Cannot modify abandoned session');
        }
    }
    
    private function guardAgainstConvertedSession(): void
    {
        if ($this->status === 'converted') {
            throw new \DomainException('Cannot modify session after conversion to order');
        }
    }
    
    // Helpers
    
    private function recalculateCartSubtotal(): void
    {
        $this->cartSubtotal = array_reduce($this->cartItems, function ($sum, $item) {
            return $sum + ($item['quantity'] * $item['unitPrice']);
        }, 0);
    }
    
    private function generateOrderNumber(): string
    {
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        return "L{$this->locationId}-{$date}-{$random}";
    }
}