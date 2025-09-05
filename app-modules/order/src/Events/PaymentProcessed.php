<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Colame\Order\Constants\PaymentStatus;

class PaymentProcessed extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $paymentId,
        public string $paymentMethod,
        public int $amount,
        public string $currency,
        public string $status,
        public ?string $transactionId = null,
        public ?array $metadata = null,
        public ?\DateTimeInterface $processedAt = null
    ) {
        $this->processedAt = $this->processedAt ?? now();
    }
    
    public function isSuccessful(): bool
    {
        return PaymentStatus::isSuccessful($this->status);
    }
}