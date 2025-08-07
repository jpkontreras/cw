<?php

declare(strict_types=1);

namespace Colame\Item\Data;

use App\Core\Data\BaseData;
use Colame\Item\Models\Item;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;

/**
 * Data Transfer Object for menu display
 * Provides minimal item information needed by the menu module
 */
class ItemMenuData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $sku,
        public readonly float $basePrice,
        public readonly ?float $baseCost,
        public readonly int $preparationTime,
        public readonly bool $isActive,
        public readonly bool $isAvailable,
        public readonly string $type,
        public readonly ?array $allergens,
        public readonly ?array $nutritionalInfo,
        public readonly ?string $imageUrl,
        
        #[DataCollectionOf(ModifierGroupWithModifiersData::class)]
        public readonly Lazy|DataCollection|null $modifierGroups = null,
        
        public readonly ?float $locationPrice = null,
        public readonly ?int $stockQuantity = null,
        public readonly bool $trackInventory = false,
    ) {}
    
    public static function fromModel(Item $item): self
    {
        return new self(
            id: $item->id,
            name: $item->name,
            description: $item->description,
            sku: $item->sku,
            basePrice: $item->base_price,
            baseCost: $item->base_cost,
            preparationTime: $item->preparation_time ?? 0,
            isActive: $item->is_active,
            isAvailable: $item->is_available,
            type: $item->type,
            allergens: $item->allergens,
            nutritionalInfo: $item->nutritional_info,
            imageUrl: $item->images()->first()?->url,
            modifierGroups: Lazy::whenLoaded('modifierGroups', $item,
                fn() => ModifierGroupWithModifiersData::collect($item->modifierGroups, DataCollection::class)
            ),
            stockQuantity: $item->stock_quantity,
            trackInventory: $item->track_inventory,
        );
    }
    
    public static function fromModelForLocation(Item $item, int $locationId): self
    {
        $locationPrice = $item->locationPrices()
            ->where('location_id', $locationId)
            ->first();
        
        $locationStock = $item->locationStock()
            ->where('location_id', $locationId)
            ->first();
        
        return new self(
            id: $item->id,
            name: $item->name,
            description: $item->description,
            sku: $item->sku,
            basePrice: $item->base_price,
            baseCost: $item->base_cost,
            preparationTime: $item->preparation_time ?? 0,
            isActive: $item->is_active,
            isAvailable: $item->is_available,
            type: $item->type,
            allergens: $item->allergens,
            nutritionalInfo: $item->nutritional_info,
            imageUrl: $item->images()->first()?->url,
            modifierGroups: Lazy::whenLoaded('modifierGroups', $item,
                fn() => ModifierGroupWithModifiersData::collect($item->modifierGroups, DataCollection::class)
            ),
            locationPrice: $locationPrice?->price,
            stockQuantity: $locationStock?->quantity ?? $item->stock_quantity,
            trackInventory: $item->track_inventory,
        );
    }
}