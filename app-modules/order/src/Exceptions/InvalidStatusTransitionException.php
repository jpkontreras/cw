<?php

declare(strict_types=1);

namespace Colame\Order\Exceptions;

/**
 * Exception thrown when an invalid status transition is attempted
 */
class InvalidStatusTransitionException extends OrderException
{
    public function __construct(string $message = 'Invalid status transition', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}