<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\In;

/**
 * Data object for item modifiers (toppings, size changes, customizations)
 */
class ItemModifierData extends BaseData
{
    public function __construct(
        #[Required]
        public string $id, // Unique identifier for this modifier
        
        #[Required]
        #[In(['size', 'topping', 'ingredient', 'preparation', 'customization'])]
        public string $type,
        
        #[Required]
        public string $name, // e.g., "Extra Cheese", "Large Size", "No Onions"
        
        #[In(['add', 'remove', 'replace', 'modify'])]
        public string $action, // What to do with this modifier
        
        #[Numeric]
        public int $priceAdjustment, // In cents - can be positive or negative
        
        public ?int $quantity = 1, // For countable modifiers like extra toppings
        
        public ?string $group = null, // e.g., "Size", "Toppings", "Sauce"
        
        public bool $isRequired = false, // Whether this modifier is mandatory
        
        public bool $affectsKitchen = true, // Whether kitchen needs to know about this
        
        public ?array $metadata = null // Additional data like allergen info, prep time
    ) {}
    
    /**
     * Calculate total price adjustment for this modifier
     */
    public function getTotalPriceAdjustment(): int
    {
        return $this->priceAdjustment * ($this->quantity ?? 1);
    }
    
    /**
     * Check if this is a removal modifier
     */
    public function isRemoval(): bool
    {
        return $this->action === 'remove';
    }
    
    /**
     * Check if this modifier affects price
     */
    public function affectsPrice(): bool
    {
        return $this->priceAdjustment !== 0;
    }
    
    /**
     * Get display string for kitchen
     */
    public function getKitchenDisplay(): string
    {
        $prefix = match($this->action) {
            'add' => '+',
            'remove' => 'NO',
            'replace' => 'REPLACE WITH',
            'modify' => '',
            default => ''
        };
        
        $quantity = $this->quantity > 1 ? "{$this->quantity}x " : '';
        
        return trim("{$prefix} {$quantity}{$this->name}");
    }
    
    /**
     * Create from menu modifier
     */
    public static function fromMenuModifier(array $menuModifier): self
    {
        return new self(
            id: $menuModifier['id'] ?? uniqid('mod_'),
            type: $menuModifier['type'] ?? 'customization',
            name: $menuModifier['name'],
            action: $menuModifier['action'] ?? 'add',
            priceAdjustment: $menuModifier['price'] ?? 0,
            quantity: $menuModifier['quantity'] ?? 1,
            group: $menuModifier['group'] ?? null,
            isRequired: $menuModifier['required'] ?? false,
            affectsKitchen: $menuModifier['kitchen'] ?? true,
            metadata: $menuModifier['metadata'] ?? null
        );
    }
}