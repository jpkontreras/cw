<?php

declare(strict_types=1);

namespace Colame\Order\Exceptions;

/**
 * Exception thrown when order data is invalid
 */
class InvalidOrderException extends OrderException
{
    protected string $errorCode = 'INVALID_ORDER_DATA';
    protected int $statusCode = 422;
}