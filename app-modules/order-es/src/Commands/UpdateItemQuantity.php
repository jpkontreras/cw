<?php

declare(strict_types=1);

namespace Colame\OrderEs\Commands;

final readonly class UpdateItemQuantity
{
    public function __construct(
        public string $orderId,
        public string $lineItemId,
        public int $newQuantity
    ) {}
}