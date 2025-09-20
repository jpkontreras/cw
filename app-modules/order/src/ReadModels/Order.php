<?php

declare(strict_types=1);

namespace Colame\Order\ReadModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';
    
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'id',
        'session_id',
        'order_number',
        'customer_id',
        'user_id',
        'location_id',
        'type',
        'status',
        'payment_method',
        'subtotal',
        'tax',
        'tip',
        'total',
        'started_at',
        'confirmed_at',
        'cancelled_at',
        'cancelled_reason',
        'cancelled_by',
    ];
    
    protected $casts = [
        'customer_id' => 'integer',
        'location_id' => 'integer',
        'subtotal' => 'integer',  // Store as cents
        'tax' => 'integer',       // Store as cents
        'tip' => 'integer',       // Store as cents
        'total' => 'integer',     // Store as cents
        'started_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];
    
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}