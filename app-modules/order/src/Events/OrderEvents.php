<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

// Order Modification Events
final class OrderItemsModified extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly array $addedItems,
        public readonly array $removedItems,
        public readonly array $modifiedItems,
        public readonly string $modifiedBy,
        public readonly string $reason,
        public readonly bool $requiresKitchenNotification,
        public readonly float $previousTotal,
        public readonly float $newTotal,
        public readonly \DateTimeImmutable $modifiedAt
    ) {}
}

final class ItemModifiersChanged extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $lineItemId,
        public readonly array $previousModifiers,
        public readonly array $newModifiers,
        public readonly string $modifiedBy,
        public readonly \DateTimeImmutable $changedAt
    ) {}
}

// Pricing & Promotion Events
final class PromotionsCalculated extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly array $availablePromotions,
        public readonly array $autoApplied,
        public readonly float $totalDiscount,
        public readonly \DateTimeImmutable $calculatedAt
    ) {}
}

final class PromotionApplied extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $promotionId,
        public readonly float $discountAmount,
        public readonly string $currency,
        public readonly \DateTimeImmutable $appliedAt
    ) {}
}

final class PromotionRemoved extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $promotionId,
        public readonly \DateTimeImmutable $removedAt
    ) {}
}

final class PriceAdjusted extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $adjustmentType,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $reason,
        public readonly string $authorizedBy,
        public readonly bool $affectsPayment,
        public readonly ?string $authorizationCode,
        public readonly array $metadata,
        public readonly \DateTimeImmutable $adjustedAt
    ) {}
}

final class PriceCalculated extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $tax,
        public readonly float $tip,
        public readonly float $total,
        public readonly string $currency,
        public readonly \DateTimeImmutable $calculatedAt
    ) {}
}

final class TipAdded extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly float $tipAmount,
        public readonly string $currency,
        public readonly \DateTimeImmutable $addedAt
    ) {}
}

// Status Events
final class OrderStatusTransitioned extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly ?string $reason,
        public readonly ?string $transitionedBy,
        public readonly \DateTimeImmutable $transitionedAt
    ) {}
}

// Payment Events
final class PaymentProcessed extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $paymentId,
        public readonly string $paymentMethod,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly ?string $transactionId,
        public readonly ?array $metadata,
        public readonly \DateTimeImmutable $processedAt
    ) {}
}

final class PaymentFailed extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $paymentId,
        public readonly string $reason,
        public readonly string $errorCode,
        public readonly ?array $metadata,
        public readonly \DateTimeImmutable $failedAt
    ) {}
}

// Customer Events
final class CustomerInfoUpdated extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly array $previousData,
        public readonly array $newData,
        public readonly string $updatedBy,
        public readonly \DateTimeImmutable $updatedAt
    ) {}
}

// Completion Events
final class OrderCompleted extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $completedBy,
        public readonly \DateTimeImmutable $completedAt
    ) {}
}

final class OrderRefunded extends ShouldBeStored
{
    public function __construct(
        public readonly string $orderId,
        public readonly float $refundAmount,
        public readonly string $currency,
        public readonly string $reason,
        public readonly string $authorizedBy,
        public readonly \DateTimeImmutable $refundedAt
    ) {}
}