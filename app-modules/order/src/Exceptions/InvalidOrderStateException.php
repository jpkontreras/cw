<?php

declare(strict_types=1);

namespace Colame\Order\Exceptions;

/**
 * Exception thrown when order is in invalid state for operation
 */
class InvalidOrderStateException extends OrderException
{
    protected string $errorCode = 'INVALID_ORDER_STATE';
    protected int $statusCode = 422;
}