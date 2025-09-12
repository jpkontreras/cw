<?php

namespace Colame\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrderSession extends Model
{
    use HasUuids;

    protected $table = 'order_sessions';
    
    protected $primaryKey = 'uuid';
    
    public $incrementing = false;
    
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'user_id',
        'location_id',
        'business_id',
        'status',
        'device_info',
        'referrer',
        'metadata',
        'cart_items',
        'serving_type',
        'payment_method',
        'customer_info_complete',
        'cart_items_count',
        'abandonment_reason',
        'session_duration',
        'started_at',
        'last_activity_at',
        'abandoned_at',
        'draft_saved_at',
        'converted_at',
        'order_id',
        'table_number',
        'delivery_address',
    ];

    protected $casts = [
        'device_info' => 'array',
        'metadata' => 'array',
        'cart_items' => 'array',
        'customer_info_complete' => 'boolean',
        'cart_items_count' => 'integer',
        'location_id' => 'integer',
        'business_id' => 'integer',
        'session_duration' => 'integer',
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'abandoned_at' => 'datetime',
        'draft_saved_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return in_array($this->status, ['initiated', 'cart_building', 'details_collecting']);
    }

    public function isAbandoned(): bool
    {
        return $this->status === 'abandoned';
    }

    public function getLocationData(): array
    {
        return $this->metadata['location'] ?? [
            'id' => $this->location_id,
            'name' => null,
            'currency' => config('money.defaults.currency', 'CLP'),
            'timezone' => config('app.timezone'),
        ];
    }
}