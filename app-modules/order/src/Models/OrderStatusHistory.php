<?php

declare(strict_types=1);

namespace Colame\Order\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Order status history model
 * 
 * Tracks all status changes for orders
 */
class OrderStatusHistory extends Model
{
    /**
     * The table associated with the model
     */
    protected $table = 'order_status_history';

    /**
     * Indicates if the model should be timestamped
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'user_id',
        'reason',
        'metadata',
        'created_at',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Default values for attributes
     */
    protected $attributes = [
        'created_at' => null,
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Set created_at on creating
        static::creating(function (OrderStatusHistory $history) {
            if (!$history->created_at) {
                $history->created_at = now();
            }
        });
    }
}