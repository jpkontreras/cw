<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Data transfer object for individual order events
 */
#[TypeScript]
class OrderEventData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly string $eventClass,
        public readonly int $version,
        public readonly array $properties,
        public readonly array $metadata,
        public readonly ?int $userId,
        public readonly string $userName,
        public readonly string $description,
        public readonly string $icon,
        public readonly string $color,
        public readonly string $createdAt,
        public readonly string $timestamp,
        public readonly string $relativeTime,
    ) {}
}