<?php

namespace Colame\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderPromotion model for tracking promotions applied to orders
 * This is a projection model, only modified through event sourcing
 */
class OrderPromotion extends Model
{
    protected $table = 'order_promotions';
    
    protected $fillable = [
        'order_id',
        'promotion_id',
        'discount_amount',
        'type',
        'auto_applied',
        'metadata',
    ];
    
    protected $casts = [
        'discount_amount' => 'integer',
        'auto_applied' => 'boolean',
        'metadata' => 'array',
    ];
    
    /**
     * Get the order this promotion belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}