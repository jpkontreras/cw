<?php

declare(strict_types=1);

namespace Colame\Order\Data\Session;

use App\Core\Data\BaseData;
use Colame\Business\Data\BusinessData;
use Colame\Location\Data\LocationData;

class OrderContextData extends BaseData
{
    public function __construct(
        public readonly int $locationId,
        public readonly LocationData $locationData,
        public readonly int $businessId,
        public readonly ?BusinessData $businessData,
        public readonly string $currency,
        public readonly string $timezone,
        public readonly ?int $userId = null,
    ) {}

    /**
     * Create a simplified array for storage in metadata
     */
    public function toMetadata(): array
    {
        return [
            'location_id' => $this->locationId,
            'location' => [
                'id' => $this->locationData->id,
                'name' => $this->locationData->name,
                'currency' => $this->locationData->currency,
                'timezone' => $this->locationData->timezone,
            ],
            'business_id' => $this->businessId,
            'business' => $this->businessData ? [
                'id' => $this->businessData->id,
                'name' => $this->businessData->name,
                'currency' => $this->businessData->currency ?? config('money.defaults.currency', 'CLP'),
            ] : null,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'user_id' => $this->userId,
        ];
    }
}