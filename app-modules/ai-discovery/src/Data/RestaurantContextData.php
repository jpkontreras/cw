<?php

namespace Colame\AiDiscovery\Data;

use App\Core\Data\BaseData;

class RestaurantContextData extends BaseData
{
    public function __construct(
        public readonly ?string $cuisineType = 'general',
        public readonly ?string $location = null,
        public readonly ?string $priceTier = 'medium',
        public readonly ?string $language = 'en',
        public readonly ?array $specializations = [],
    ) {}
}