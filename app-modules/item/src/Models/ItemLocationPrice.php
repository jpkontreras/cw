<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;

class ItemLocationPrice extends Model
{
    protected $fillable = [
        'item_id',
        'item_variant_id',
        'location_id',
        'price',
        'currency',
        'valid_from',
        'valid_until',
        'available_days',
        'available_from_time',
        'available_until_time',
        'is_active',
        'priority',
    ];
    
    protected $casts = [
        'item_id' => 'integer',
        'item_variant_id' => 'integer',
        'location_id' => 'integer',
        'price' => 'integer',  // Store as integer (minor units)
        'valid_from' => 'date',
        'valid_until' => 'date',
        'available_days' => 'array',
        'available_from_time' => 'string',
        'available_until_time' => 'string',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];
    
    protected $attributes = [
        'currency' => 'CLP',
        'is_active' => true,
        'priority' => 0,
    ];
    
    /**
     * Scope for active pricing rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope for currently valid pricing rules
     */
    public function scopeCurrentlyValid($query)
    {
        $now = now();
        $currentDay = strtolower($now->format('l'));
        $currentTime = $now->format('H:i:s');
        
        return $query->active()
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now->toDateString());
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now->toDateString());
            })
            ->where(function ($q) use ($currentDay) {
                $q->whereNull('available_days')
                    ->orWhereJsonContains('available_days', $currentDay);
            })
            ->where(function ($q) use ($currentTime) {
                $q->whereNull('available_from_time')
                    ->orWhere('available_from_time', '<=', $currentTime);
            })
            ->where(function ($q) use ($currentTime) {
                $q->whereNull('available_until_time')
                    ->orWhere('available_until_time', '>=', $currentTime);
            });
    }
}