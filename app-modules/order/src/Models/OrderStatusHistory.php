<?php

declare(strict_types=1);

namespace Colame\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order status history model - Tracks all status transitions
 */
class OrderStatusHistory extends Model
{
    protected $table = 'order_status_histories';

    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'user_id',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'order_id' => 'string',
        'user_id' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Order relationship
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}