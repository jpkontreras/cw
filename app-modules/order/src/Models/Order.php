<?php

declare(strict_types=1);

namespace Colame\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

/**
 * Order model - Read model projection from events
 * Matches original order module structure exactly
 */
class Order extends Model
{
    use SoftDeletes, Searchable;

    protected $table = 'order_es_orders';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'session_id',
        'order_number',
        'user_id',
        'location_id',
        'currency',
        'menu_id',
        'menu_version',
        'status',
        'type',
        'priority',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_address',
        'table_number',
        'waiter_id',
        'subtotal',
        'tax',
        'tip',
        'discount',
        'total',
        'payment_status',
        'payment_method',
        'notes',
        'special_instructions',
        'cancellation_reason',
        'metadata',
        'view_count',
        'modification_count',
        'last_modified_at',
        'last_modified_by',
        'started_at',
        'placed_at',
        'confirmed_at',
        'preparing_at',
        'ready_at',
        'delivering_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'cancelled_by',
        'scheduled_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'location_id' => 'integer',
        'menu_id' => 'integer',
        'menu_version' => 'integer',
        'table_number' => 'integer',
        'waiter_id' => 'integer',
        'subtotal' => 'integer',
        'tax' => 'integer',
        'tip' => 'integer',
        'discount' => 'integer',
        'total' => 'integer',
        'metadata' => 'array',
        'view_count' => 'integer',
        'modification_count' => 'integer',
        'last_modified_at' => 'datetime',
        'started_at' => 'datetime',
        'placed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivering_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancelled_by' => 'integer',
        'scheduled_at' => 'datetime',
    ];

    /**
     * Order items relationship
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Status history relationship
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    /**
     * Promotions relationship
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(OrderPromotion::class, 'order_id');
    }

    /**
     * Session relationship
     */
    public function session(): HasOne
    {
        return $this->hasOne(OrderSession::class, 'converted_order_id');
    }

    /**
     * Get searchable array for Laravel Scout
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'status' => $this->status,
            'type' => $this->type,
            'total' => $this->total,
            'location_id' => $this->location_id,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Check if order can be modified
     */
    public function canBeModified(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled', 'refunded']);
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled', 'refunded']);
    }
}