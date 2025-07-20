<?php

declare(strict_types=1);

namespace Colame\Order\Exceptions;

/**
 * Exception thrown when order is not found
 */
class OrderNotFoundException extends OrderException
{
    protected string $errorCode = 'ORDER_NOT_FOUND';
    protected int $statusCode = 404;
}