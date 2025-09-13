<?php

declare(strict_types=1);

namespace Colame\OrderEs\Commands;

final readonly class AddItemToOrder
{
    public function __construct(
        public string $orderId,
        public int $itemId,
        public int $quantity,
        public array $modifiers = [],
        public ?string $notes = null
    ) {}
}