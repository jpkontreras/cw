<?php

declare(strict_types=1);

namespace Colame\OrderEs\ReadModels;

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
        'unit_price',
        'quantity',
        'modifiers',
        'notes',
        'total_price',
    ];
    
    protected $casts = [
        'item_id' => 'integer',
        'unit_price' => 'float',
        'quantity' => 'integer',
        'modifiers' => 'array',
        'total_price' => 'float',
    ];
    
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}