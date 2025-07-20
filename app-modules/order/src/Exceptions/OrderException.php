<?php

declare(strict_types=1);

namespace Colame\Order\Exceptions;

use App\Core\Exceptions\ModuleException;

/**
 * Base exception for order module
 */
class OrderException extends ModuleException
{
    protected string $errorCode = 'ORDER_ERROR';
    protected int $statusCode = 400;
}