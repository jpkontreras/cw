<?php

declare(strict_types=1);

namespace Colame\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order session model - Tracks user journey from browsing to order
 * Matches original order module structure exactly
 */
class OrderSession extends Model
{
    protected $table = 'order_es_sessions';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'staff_id',
        'location_id',
        'status',
        'type',
        'table_number',
        'customer_count',
        'order_id',
        'metadata',
        'started_at',
        'converted_at',
        'closed_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'location_id' => 'integer',
        'device_info' => 'array',
        'metadata' => 'array',
        'cart_items' => 'array',
        'cart_subtotal' => 'integer',
        'customer_info' => 'array',
        'search_history' => 'array',
        'viewed_items' => 'array',
        'browsed_categories' => 'array',
        'items_viewed_count' => 'integer',
        'searches_count' => 'integer',
        'categories_browsed_count' => 'integer',
        'session_duration_seconds' => 'integer',
        'converted_at' => 'datetime',
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'draft_saved_at' => 'datetime',
        'abandoned_at' => 'datetime',
    ];

    /**
     * Converted order relationship
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->status === 'cart_building';
    }

    /**
     * Check if session was converted
     */
    public function isConverted(): bool
    {
        return $this->status === 'converted' && $this->converted_order_id !== null;
    }

    /**
     * Check if session was abandoned
     */
    public function isAbandoned(): bool
    {
        return $this->status === 'abandoned';
    }

    /**
     * Get cart value
     */
    public function getCartValue(): float
    {
        return $this->cart_subtotal / 100;
    }

    /**
     * Get session duration in minutes
     */
    public function getDurationInMinutes(): int
    {
        return (int) round($this->session_duration_seconds / 60);
    }
}