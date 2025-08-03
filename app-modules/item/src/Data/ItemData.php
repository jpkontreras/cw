<?php

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ItemData extends BaseData
{
    public function __construct(
        public readonly ?int $id,

        #[Required]
        public readonly string $name,

        public readonly string $slug,

        public readonly ?string $description,

        public readonly ?string $sku,

        public readonly ?string $barcode,

        #[Numeric, Min(0)]
        public readonly ?float $base_price,

        #[Numeric, Min(0)]
        public readonly float $base_cost = 0,

        #[Numeric, Min(0)]
        public readonly int $preparation_time = 0,

        public readonly bool $is_active = true,

        public readonly bool $is_available = true,

        public readonly bool $is_featured = false,

        public readonly bool $track_inventory = false,

        #[Numeric, Min(0)]
        public readonly int $stock_quantity = 0,

        #[Numeric, Min(0)]
        public readonly int $low_stock_threshold = 10,

        #[Required, In(['product', 'service', 'combo'])]
        public readonly string $type = 'product',

        public readonly ?array $allergens = null,

        public readonly ?array $nutritional_info = null,

        #[Numeric, Min(0)]
        public readonly int $sort_order = 0,

        public readonly ?Carbon $available_from = null,

        public readonly ?Carbon $available_until = null,

        public readonly ?Carbon $created_at = null,

        public readonly ?Carbon $updated_at = null,

        public readonly ?Carbon $deleted_at = null,
    ) {}

    /**
     * Check if the item is currently available based on time constraints
     */
    public function isCurrentlyAvailable(): bool
    {
        if (!$this->is_available || !$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->available_from && $now->isBefore($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->isAfter($this->available_until)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the item needs reorder
     */
    public function needsReorder(): bool
    {
        return $this->trackInventory && $this->stockQuantity <= $this->lowStockThreshold;
    }

    /**
     * Get the profit margin
     */
    public function getProfitMargin(): float
    {
        if ($this->baseCost <= 0 || $this->basePrice === null) {
            return 0;
        }

        return (($this->basePrice - $this->baseCost) / $this->baseCost) * 100;
    }
}
