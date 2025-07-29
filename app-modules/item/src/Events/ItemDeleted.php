<?php

namespace Colame\Item\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public readonly int $itemId
    ) {}
    
    /**
     * Get the deleted item ID
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }
}