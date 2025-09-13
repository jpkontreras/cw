<?php

declare(strict_types=1);

namespace Colame\OrderEs\Commands;

// Order Modification Commands
final readonly class ModifyOrderItems
{
    public function __construct(
        public string $orderId,
        public array $toAdd = [],
        public array $toRemove = [],
        public array $toModify = [],
        public string $modifiedBy = '',
        public string $reason = ''
    ) {}
}

final readonly class ChangeItemModifiers
{
    public function __construct(
        public string $orderId,
        public string $lineItemId,
        public array $modifiers,
        public string $modifiedBy
    ) {}
}

// Pricing & Promotion Commands
final readonly class ApplyPromotion
{
    public function __construct(
        public string $orderId,
        public string $promotionId,
        public float $discountAmount
    ) {}
}

final readonly class RemovePromotion
{
    public function __construct(
        public string $orderId,
        public string $promotionId
    ) {}
}

final readonly class AdjustPrice
{
    public function __construct(
        public string $orderId,
        public string $adjustmentType, // discount, surcharge, correction, tip
        public float $amount,
        public string $reason,
        public string $authorizedBy,
        public ?string $authorizationCode = null
    ) {}
}

final readonly class AddTip
{
    public function __construct(
        public string $orderId,
        public float $tipAmount
    ) {}
}

// Status Transition Commands
final readonly class TransitionOrderStatus
{
    public function __construct(
        public string $orderId,
        public string $newStatus,
        public ?string $reason = null,
        public ?string $transitionedBy = null
    ) {}
}

// Payment Commands
final readonly class ProcessPayment
{
    public function __construct(
        public string $orderId,
        public string $paymentId,
        public string $paymentMethod,
        public float $amount,
        public string $status,
        public ?string $transactionId = null,
        public ?array $metadata = null
    ) {}
}

final readonly class FailPayment
{
    public function __construct(
        public string $orderId,
        public string $paymentId,
        public string $reason,
        public string $errorCode,
        public ?array $metadata = null
    ) {}
}

// Customer Info Commands
final readonly class UpdateCustomerInfo
{
    public function __construct(
        public string $orderId,
        public array $customerData,
        public string $updatedBy
    ) {}
}

// Order Completion Commands
final readonly class CompleteOrder
{
    public function __construct(
        public string $orderId,
        public string $completedBy
    ) {}
}

final readonly class RefundOrder
{
    public function __construct(
        public string $orderId,
        public float $refundAmount,
        public string $reason,
        public string $authorizedBy
    ) {}
}