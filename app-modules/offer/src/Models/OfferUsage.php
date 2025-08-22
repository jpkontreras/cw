<?php

declare(strict_types=1);

namespace Colame\Offer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferUsage extends Model
{
    protected $table = 'offer_usages';
    
    protected $fillable = [
        'offer_id',
        'order_id',
        'customer_id',
        'customer_email',
        'discount_amount',
        'order_amount',
        'code',
        'metadata',
        'used_at',
    ];
    
    protected $casts = [
        'discount_amount' => 'float',
        'order_amount' => 'float',
        'metadata' => 'array',
        'used_at' => 'datetime',
    ];
    
    protected $attributes = [
        'used_at' => null,
    ];
    
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($usage) {
            if (!$usage->used_at) {
                $usage->used_at = now();
            }
        });
    }
}