<?php

declare(strict_types=1);

namespace Colame\OrderEs\Commands;

// Search & Browse Commands
final readonly class RecordSearch
{
    public function __construct(
        public string $sessionId,
        public string $query,
        public array $filters = [],
        public int $resultsCount = 0,
        public ?string $searchId = null
    ) {}
}

final readonly class BrowseCategory
{
    public function __construct(
        public string $sessionId,
        public int $categoryId,
        public string $categoryName,
        public int $itemsViewed = 0,
        public int $timeSpentSeconds = 0
    ) {}
}

final readonly class ViewItem
{
    public function __construct(
        public string $sessionId,
        public int $itemId,
        public string $itemName,
        public float $price,
        public ?string $category = null,
        public string $viewSource = 'browse',
        public int $viewDurationSeconds = 0
    ) {}
}

// Cart Commands
final readonly class AddToCart
{
    public function __construct(
        public string $sessionId,
        public int $itemId,
        public string $itemName,
        public int $quantity,
        public float $unitPrice,
        public ?string $category = null,
        public array $modifiers = [],
        public ?string $notes = null,
        public string $addedFrom = 'browse'
    ) {}
}

final readonly class RemoveFromCart
{
    public function __construct(
        public string $sessionId,
        public int $itemId,
        public string $itemName,
        public int $removedQuantity,
        public string $removalReason = 'user_action'
    ) {}
}

final readonly class ModifyCartItem
{
    public function __construct(
        public string $sessionId,
        public int $itemId,
        public string $itemName,
        public string $modificationType,
        public array $changes = []
    ) {}
}

// Session Configuration Commands
final readonly class SetServingType
{
    public function __construct(
        public string $sessionId,
        public string $servingType,
        public ?string $tableNumber = null,
        public ?string $deliveryAddress = null
    ) {}
}

final readonly class EnterCustomerInfo
{
    public function __construct(
        public string $sessionId,
        public array $fields,
        public array $validationErrors = [],
        public bool $isComplete = false
    ) {}
}

final readonly class SelectPaymentMethod
{
    public function __construct(
        public string $sessionId,
        public string $paymentMethod
    ) {}
}

// Session Management Commands
final readonly class SaveDraft
{
    public function __construct(
        public string $sessionId,
        public bool $autoSaved = false
    ) {}
}

final readonly class AbandonSession
{
    public function __construct(
        public string $sessionId,
        public string $reason,
        public int $sessionDurationSeconds,
        public string $lastActivity = ''
    ) {}
}

final readonly class ConvertSessionToOrder
{
    public function __construct(
        public string $sessionId
    ) {}
}