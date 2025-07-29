<?php

namespace Colame\Item\Exceptions;

use App\Core\Exceptions\ModuleException;

class ItemException extends ModuleException
{
    protected string $module = 'item';
}