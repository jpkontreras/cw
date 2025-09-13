<?php

declare(strict_types=1);

namespace Colame\OrderEs\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

// Session Lifecycle Events
final class SessionInitiated extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly ?int $userId,
        public readonly int $locationId,
        public readonly array $deviceInfo,
        public readonly ?string $referrer,
        public readonly array $metadata,
        public readonly \DateTimeImmutable $initiatedAt
    ) {}
}

final class SessionAbandoned extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $reason,
        public readonly int $sessionDurationSeconds,
        public readonly int $itemsInCart,
        public readonly float $cartValue,
        public readonly string $lastActivity,
        public readonly \DateTimeImmutable $abandonedAt
    ) {}
}

final class SessionConverted extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $orderId,
        public readonly \DateTimeImmutable $convertedAt
    ) {}
}

// Search & Browse Events
final class ItemSearched extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $query,
        public readonly array $filters,
        public readonly int $resultsCount,
        public readonly ?string $searchId,
        public readonly \DateTimeImmutable $searchedAt
    ) {}
}

final class CategoryBrowsed extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $categoryId,
        public readonly string $categoryName,
        public readonly int $itemsViewed,
        public readonly int $timeSpentSeconds,
        public readonly \DateTimeImmutable $browsedAt
    ) {}
}

final class ItemViewed extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $itemId,
        public readonly string $itemName,
        public readonly float $price,
        public readonly ?string $category,
        public readonly string $viewSource,
        public readonly int $viewDurationSeconds,
        public readonly \DateTimeImmutable $viewedAt
    ) {}
}

// Cart Events
final class ItemAddedToCart extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $itemId,
        public readonly string $itemName,
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly ?string $category,
        public readonly array $modifiers,
        public readonly ?string $notes,
        public readonly string $addedFrom,
        public readonly \DateTimeImmutable $addedAt
    ) {}
}

final class ItemRemovedFromCart extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $itemId,
        public readonly string $itemName,
        public readonly int $removedQuantity,
        public readonly string $removalReason,
        public readonly \DateTimeImmutable $removedAt
    ) {}
}

final class CartItemModified extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $itemId,
        public readonly string $itemName,
        public readonly string $modificationType,
        public readonly array $changes,
        public readonly \DateTimeImmutable $modifiedAt
    ) {}
}

// Session Configuration Events
final class ServingTypeSelected extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $servingType,
        public readonly ?string $previousType,
        public readonly ?string $tableNumber,
        public readonly ?string $deliveryAddress,
        public readonly \DateTimeImmutable $selectedAt
    ) {}
}

final class CustomerInfoEntered extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly array $fields,
        public readonly array $validationErrors,
        public readonly bool $isComplete,
        public readonly \DateTimeImmutable $enteredAt
    ) {}
}

final class PaymentMethodSelected extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $paymentMethod,
        public readonly ?string $previousMethod,
        public readonly \DateTimeImmutable $selectedAt
    ) {}
}

final class DraftSaved extends ShouldBeStored
{
    public function __construct(
        public readonly string $sessionId,
        public readonly array $cartItems,
        public readonly array $customerInfo,
        public readonly ?string $servingType,
        public readonly ?string $paymentMethod,
        public readonly float $subtotal,
        public readonly bool $autoSaved,
        public readonly \DateTimeImmutable $savedAt
    ) {}
}