<?php

declare(strict_types=1);

namespace Colame\Order\Exceptions;

use Exception;

class OrderContextException extends Exception
{
    public static function noBusinessContext(): self
    {
        return new self('No business context available. Please select a business before starting an order session.');
    }

    public static function noLocationAvailable(): self
    {
        return new self('No location available. Please set your current location or configure a default location.');
    }

    public static function locationBusinessMismatch(string $locationName): self
    {
        return new self("Selected location '{$locationName}' does not belong to the current business context.");
    }
}