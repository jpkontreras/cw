<?php

declare(strict_types=1);

namespace Colame\Offer\Data;

use App\Core\Data\BaseData;

class ValidationErrorData extends BaseData
{
    public function __construct(
        public readonly string $field,
        public readonly string $message,
        public readonly ?string $code,
        public readonly ?array $context,
    ) {}
}