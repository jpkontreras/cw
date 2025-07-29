<?php

namespace Colame\Item\Exceptions;

class InsufficientStockException extends ItemException
{
    public function __construct(
        string $itemName = '', 
        int $requested = 0, 
        int $available = 0,
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        if ($itemName) {
            $message = "Insufficient stock for {$itemName}. Requested: {$requested}, Available: {$available}";
        } else {
            $message = "Insufficient stock available";
        }
        
        parent::__construct($message, $code, $previous);
    }
}