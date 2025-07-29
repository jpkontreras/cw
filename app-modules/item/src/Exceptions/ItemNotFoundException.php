<?php

namespace Colame\Item\Exceptions;

class ItemNotFoundException extends ItemException
{
    public function __construct(string $message = "Item not found", int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}