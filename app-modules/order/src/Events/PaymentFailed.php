<?php

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class PaymentFailed extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public string $paymentId,
        public string $paymentMethod,
        public int $amount,
        public string $currency,
        public string $failureReason,
        public ?string $errorCode = null,
        public ?array $metadata = null,
        public ?\DateTimeInterface $failedAt = null
    ) {
        $this->failedAt = $this->failedAt ?? now();
    }
}