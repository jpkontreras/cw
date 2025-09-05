<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Data transfer object for event statistics
 */
#[TypeScript]
class EventStatisticsData extends BaseData
{
    public function __construct(
        public readonly int $totalEvents,
        public readonly array $eventTypes,
        public readonly array $userActivity,
        public readonly ?string $firstEventAt,
        public readonly ?string $lastEventAt,
        public readonly ?string $duration,
    ) {}
}