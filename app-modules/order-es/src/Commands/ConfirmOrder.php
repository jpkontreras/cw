<?php

declare(strict_types=1);

namespace Colame\OrderEs\Commands;

final readonly class ConfirmOrder
{
    public function __construct(
        public string $orderId,
        public string $paymentMethod,
        public ?float $tipAmount = null
    ) {}
}