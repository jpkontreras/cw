<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ItemLocationPriceData extends BaseData
{
    public function __construct(
        public readonly ?int $id,

        #[Required, Numeric]
        public readonly int $itemId,

        public readonly ?int $itemVariantId,

        #[Required, Numeric]
        public readonly int $locationId,

        #[Required, Numeric, Min(0)]
        public readonly int $price,  // In minor units

        #[Size(3)]
        public readonly string $currency = 'CLP',

        public readonly ?Carbon $validFrom,

        public readonly ?Carbon $validUntil,

        public readonly ?array $availableDays = null,

        public readonly ?string $availableFromTime = null,

        public readonly ?string $availableUntilTime = null,

        public readonly bool $isActive = true,

        #[Numeric, Min(0)]
        public readonly int $priority = 0,

        public readonly ?Carbon $createdAt = null,

        public readonly ?Carbon $updatedAt = null,
    ) {}

    /**
     * Check if the pricing rule is currently valid
     */
    public function isCurrentlyValid(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = now();

        // Check date range
        if ($this->validFrom && $now->isBefore($this->validFrom)) {
            return false;
        }

        if ($this->validUntil && $now->isAfter($this->validUntil)) {
            return false;
        }

        // Check day of week
        if ($this->availableDays && !in_array(strtolower($now->format('l')), $this->availableDays)) {
            return false;
        }

        // Check time range
        if ($this->availableFromTime || $this->availableUntilTime) {
            $currentTime = $now->format('H:i:s');

            if ($this->availableFromTime && $currentTime < $this->availableFromTime) {
                return false;
            }

            if ($this->availableUntilTime && $currentTime > $this->availableUntilTime) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get days as readable string
     */
    public function getAvailableDaysDisplay(): string
    {
        if (!$this->availableDays || count($this->availableDays) === 7) {
            return 'Every day';
        }

        if (
            count($this->availableDays) === 5 &&
            !in_array('saturday', $this->availableDays) &&
            !in_array('sunday', $this->availableDays)
        ) {
            return 'Weekdays';
        }

        if (
            count($this->availableDays) === 2 &&
            in_array('saturday', $this->availableDays) &&
            in_array('sunday', $this->availableDays)
        ) {
            return 'Weekends';
        }

        return implode(', ', array_map('ucfirst', $this->availableDays));
    }
}
