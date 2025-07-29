<?php

namespace Colame\Item\Events;

use Colame\Item\Data\ItemData;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public readonly ItemData $item
    ) {}
    
    /**
     * Get the item ID
     */
    public function getItemId(): int
    {
        return $this->item->id;
    }
    
    /**
     * Get the item data as array
     */
    public function getItemData(): array
    {
        return $this->item->toArray();
    }
}