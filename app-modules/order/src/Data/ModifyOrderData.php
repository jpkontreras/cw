<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Array;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Numeric;

class ModifyOrderData extends BaseData
{
    public function __construct(
        #[Array]
        #[Nullable]
        public ?array $itemsToAdd = null,
        
        #[Array]
        #[Nullable]
        public ?array $itemsToRemove = null,
        
        #[Array]
        #[Nullable]
        public ?array $itemsToModify = null,
        
        #[Required]
        public string $reason,
        
        #[Required]
        public string $modifiedBy,
        
        #[Nullable]
        public ?array $priceAdjustment = null
    ) {}
    
    /**
     * Check if there are any modifications
     */
    public function hasModifications(): bool
    {
        return !empty($this->itemsToAdd) || 
               !empty($this->itemsToRemove) || 
               !empty($this->itemsToModify);
    }
    
    /**
     * Check if there's a price adjustment
     */
    public function hasPriceAdjustment(): bool
    {
        return !empty($this->priceAdjustment) && 
               isset($this->priceAdjustment['amount']) && 
               $this->priceAdjustment['amount'] > 0;
    }
    
    /**
     * Get the total number of changes
     */
    public function getChangeCount(): int
    {
        return count($this->itemsToAdd ?? []) + 
               count($this->itemsToRemove ?? []) + 
               count($this->itemsToModify ?? []);
    }
    
    /**
     * Custom validation rules
     */
    public static function rules(): array
    {
        return [
            'itemsToAdd.*.item_id' => ['required', 'integer'],
            'itemsToAdd.*.quantity' => ['required', 'integer', 'min:1'],
            'itemsToAdd.*.unit_price' => ['required', 'numeric', 'min:0'],
            'itemsToRemove.*' => ['integer'],
            'itemsToModify.*.item_id' => ['required', 'integer'],
            'itemsToModify.*.quantity' => ['integer', 'min:1'],
            'priceAdjustment.type' => ['in:discount,surcharge,correction,tip'],
            'priceAdjustment.amount' => ['numeric', 'min:0'],
            'priceAdjustment.reason' => ['required_with:priceAdjustment.amount'],
        ];
    }
}