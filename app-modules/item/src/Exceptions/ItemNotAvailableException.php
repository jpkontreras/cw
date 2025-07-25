<?php

declare(strict_types=1);

namespace Colame\Item\Exceptions;

/**
 * Exception thrown when an item is not available
 */
class ItemNotAvailableException extends ItemException
{
    public function __construct(
        string $itemName,
        int $requested,
        int $available
    ) {
        $message = "Item '{$itemName}' is not available. " .
                  "Requested: {$requested}, Available: {$available}";
        parent::__construct($message);
    }
}