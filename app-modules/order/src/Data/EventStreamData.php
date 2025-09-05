<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Data transfer object for event stream data
 */
#[TypeScript]
class EventStreamData extends BaseData
{
    public function __construct(
        public readonly string $orderUuid,
        #[DataCollectionOf(OrderEventData::class)]
        public readonly DataCollection $events,
        public readonly EventStatisticsData $statistics,
        public readonly ?string $currentTimestamp = null,
        public readonly ?array $snapshot = null,
    ) {}
    
    /**
     * Create from service data
     */
    public static function fromServiceData(string $orderUuid, array $events, array $statistics): self
    {
        return new self(
            orderUuid: $orderUuid,
            events: OrderEventData::collection($events),
            statistics: EventStatisticsData::from($statistics),
            currentTimestamp: now()->toIso8601String(),
            snapshot: null,
        );
    }
}