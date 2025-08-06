<?php

declare(strict_types=1);

namespace Colame\Location\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class LocationOperatingHoursData extends BaseData
{
    public function __construct(
        #[Required, DateFormat('H:i')]
        public readonly string $open,

        #[Required, DateFormat('H:i')]
        public readonly string $close,

        public readonly bool $isClosed = false,
    ) {}

    /**
     * Create a closed day instance
     */
    public static function closed(): self
    {
        return new self(
            open: '00:00',
            close: '00:00',
            isClosed: true,
        );
    }

    /**
     * Check if the time slot is valid
     */
    public function isValid(): bool
    {
        if ($this->isClosed) {
            return true;
        }

        // Basic validation - ensure times are different unless it's 24-hour operation
        return $this->open !== $this->close || ($this->open === '00:00' && $this->close === '00:00');
    }

    /**
     * Check if it's 24-hour operation
     */
    public function is24Hours(): bool
    {
        return !$this->isClosed && $this->open === '00:00' && $this->close === '00:00';
    }

    /**
     * Get display format
     */
    public function display(): string
    {
        if ($this->isClosed) {
            return 'Closed';
        }

        if ($this->is24Hours()) {
            return '24 Hours';
        }

        return "{$this->open} - {$this->close}";
    }
}