<?php

declare(strict_types=1);

namespace Colame\Location\Exceptions;

use App\Core\Exceptions\ModuleException;

class LocationException extends ModuleException
{
    /**
     * Get the module name.
     */
    protected function getModule(): string
    {
        return 'location';
    }
}