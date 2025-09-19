<?php

declare(strict_types=1);

namespace Colame\Order\ReadModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'order_es_order_items';
    
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'id',
        'order_id',
        'item_id',
        'item_name',
        'base_price',
        'unit_price',
        'quantity',
        'modifiers',
        'notes',
        'total_price',
    ];
    
    protected $casts = [
        'item_id' => 'integer',
        'base_price' => 'integer',    // Store as cents
        'unit_price' => 'integer',    // Store as cents
        'quantity' => 'integer',
        'modifiers' => 'array',
        'total_price' => 'integer',   // Store as cents
    ];
    
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}