<?php

declare(strict_types=1);

namespace Colame\Location\Exceptions;

class LocationNotFoundException extends LocationException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Location not found', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}