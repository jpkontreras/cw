<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Payment transaction data transfer object
 */
#[TypeScript]
class PaymentTransactionData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $orderId,
        public readonly string $method,
        public readonly float $amount,
        public readonly string $status,
        public readonly ?string $referenceNumber = null,
        public readonly ?array $processorResponse = null,
        public readonly ?\DateTimeInterface $processedAt = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}

    /**
     * Get payment method label
     */
    public function getMethodLabel(): string
    {
        return match ($this->method) {
            'cash' => 'Cash',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'mobile_payment' => 'Mobile Payment',
            'gift_card' => 'Gift Card',
            'other' => 'Other',
            default => ucfirst($this->method),
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if transaction was refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }
}