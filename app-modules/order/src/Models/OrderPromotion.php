<?php

declare(strict_types=1);

namespace Colame\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order promotion model - Tracks applied promotions
 */
class OrderPromotion extends Model
{
    protected $table = 'order_es_promotions';

    protected $fillable = [
        'order_id',
        'promotion_id',
        'promotion_code',
        'promotion_name',
        'discount_type',
        'discount_value',
        'discount_amount',
        'applied_to',
        'applied_items',
        'metadata',
    ];

    protected $casts = [
        'order_id' => 'string',
        'promotion_id' => 'integer',
        'discount_value' => 'integer',
        'discount_amount' => 'integer',
        'applied_items' => 'array',
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