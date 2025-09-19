<?php

declare(strict_types=1);

namespace Colame\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order item model - Read model projection from events
 * Matches original order module structure exactly
 */
class OrderItem extends Model
{
    protected $table = 'order_es_order_items';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'item_id',
        'menu_section_id',
        'menu_item_id',
        'item_name',
        'base_item_name',
        'quantity',
        'base_price',
        'unit_price',
        'modifiers_total',
        'total_price',
        'status',
        'kitchen_status',
        'course',
        'notes',
        'special_instructions',
        'modifiers',
        'modifier_history',
        'modifier_count',
        'metadata',
        'modified_at',
        'prepared_at',
        'served_at',
    ];

    protected $casts = [
        'order_id' => 'string',
        'item_id' => 'integer',
        'menu_section_id' => 'integer',
        'menu_item_id' => 'integer',
        'quantity' => 'integer',
        'base_price' => 'integer',
        'unit_price' => 'integer',
        'modifiers_total' => 'integer',
        'total_price' => 'integer',
        'modifiers' => 'array',
        'modifier_history' => 'array',
        'modifier_count' => 'integer',
        'metadata' => 'array',
        'modified_at' => 'datetime',
        'prepared_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    /**
     * Order relationship
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Check if item is ready to serve
     */
    public function isReady(): bool
    {
        return $this->kitchen_status === 'ready';
    }

    /**
     * Check if item has been served
     */
    public function isServed(): bool
    {
        return $this->kitchen_status === 'served';
    }
}