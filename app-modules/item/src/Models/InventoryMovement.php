<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'inventoriable_type',
        'inventoriable_id',
        'location_id',
        'movement_type',
        'quantity',
        'unit_cost',
        'before_quantity',
        'after_quantity',
        'reference_type',
        'reference_id',
        'reason',
        'user_id',
    ];
    
    protected $casts = [
        'inventoriable_id' => 'integer',
        'location_id' => 'integer',
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'before_quantity' => 'decimal:3',
        'after_quantity' => 'decimal:3',
        'user_id' => 'integer',
    ];
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($movement) {
            // Ensure the quantity change matches before/after values
            $expectedChange = abs($movement->after_quantity - $movement->before_quantity);
            if (abs($movement->quantity) !== $expectedChange) {
                $movement->quantity = $expectedChange;
            }
            
            // Set proper sign for quantity based on movement type
            if (in_array($movement->movement_type, ['sale', 'transfer_out', 'waste', 'adjustment'])) {
                $movement->quantity = -abs($movement->quantity);
            } else {
                $movement->quantity = abs($movement->quantity);
            }
        });
    }
    
    /**
     * Get the inventoriable model
     */
    public function inventoriable()
    {
        return $this->morphTo();
    }
    
    /**
     * Scope for movements by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('movement_type', $type);
    }
    
    /**
     * Scope for movements by location
     */
    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }
    
    /**
     * Scope for movements by reference
     */
    public function scopeForReference($query, $type, $id)
    {
        return $query->where('reference_type', $type)
            ->where('reference_id', $id);
    }
}