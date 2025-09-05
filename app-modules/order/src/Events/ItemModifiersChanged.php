<?php

declare(strict_types=1);

namespace Colame\Order\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Carbon\Carbon;

/**
 * Event for tracking individual item modifier changes
 */
class ItemModifiersChanged extends ShouldBeStored
{
    public function __construct(
        public string $aggregateRootUuid,
        public int $orderItemId, // Which item in the order
        public string $itemName, // For reference
        public array $addedModifiers, // Array of ItemModifierData as arrays
        public array $removedModifiers, // Array of modifier IDs removed
        public array $updatedModifiers, // Array of updated ItemModifierData
        public int $oldPrice, // Previous item total price
        public int $newPrice, // New item total price after modifiers
        public string $modifiedBy, // Who made the change
        public string $reason, // Why the change was made
        public Carbon $modifiedAt,
        public bool $requiresKitchenNotification = true,
        public ?array $metadata = null // Additional context
    ) {
        $this->modifiedAt = $modifiedAt ?? now();
    }
    
    /**
     * Get the price difference
     */
    public function getPriceDifference(): int
    {
        return $this->newPrice - $this->oldPrice;
    }
    
    /**
     * Check if modifiers were added
     */
    public function hasAdditions(): bool
    {
        return !empty($this->addedModifiers);
    }
    
    /**
     * Check if modifiers were removed
     */
    public function hasRemovals(): bool
    {
        return !empty($this->removedModifiers);
    }
    
    /**
     * Check if modifiers were updated
     */
    public function hasUpdates(): bool
    {
        return !empty($this->updatedModifiers);
    }
    
    /**
     * Get all modifier changes for kitchen display
     */
    public function getKitchenChanges(): array
    {
        $changes = [];
        
        // Added modifiers
        foreach ($this->addedModifiers as $modifier) {
            if ($modifier['affectsKitchen'] ?? true) {
                $changes[] = [
                    'action' => 'add',
                    'display' => $this->formatModifierForKitchen($modifier)
                ];
            }
        }
        
        // Removed modifiers
        foreach ($this->removedModifiers as $modifierId) {
            $changes[] = [
                'action' => 'remove',
                'display' => "Remove: {$modifierId}"
            ];
        }
        
        // Updated modifiers
        foreach ($this->updatedModifiers as $modifier) {
            if ($modifier['affectsKitchen'] ?? true) {
                $changes[] = [
                    'action' => 'update',
                    'display' => $this->formatModifierForKitchen($modifier)
                ];
            }
        }
        
        return $changes;
    }
    
    /**
     * Format modifier for kitchen display
     */
    private function formatModifierForKitchen(array $modifier): string
    {
        $action = $modifier['action'] ?? 'add';
        $name = $modifier['name'] ?? '';
        $quantity = $modifier['quantity'] ?? 1;
        
        $prefix = match($action) {
            'add' => '+',
            'remove' => 'NO',
            'replace' => 'REPLACE:',
            default => ''
        };
        
        $quantityStr = $quantity > 1 ? "{$quantity}x " : '';
        
        return trim("{$prefix} {$quantityStr}{$name}");
    }
}