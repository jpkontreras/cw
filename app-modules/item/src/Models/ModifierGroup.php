<?php

namespace Colame\Item\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModifierGroup extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'description',
        'selection_type',
        'is_required',
        'min_selections',
        'max_selections',
        'is_active',
    ];
    
    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'min_selections' => 'integer',
        'max_selections' => 'integer',
    ];
    
    protected $attributes = [
        'selection_type' => 'multiple',
        'is_required' => false,
        'is_active' => true,
        'min_selections' => 0,
    ];
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($group) {
            // Ensure single selection groups have appropriate limits
            if ($group->selection_type === 'single') {
                $group->max_selections = 1;
                if ($group->is_required) {
                    $group->min_selections = 1;
                }
            }
            
            // Ensure min doesn't exceed max
            if ($group->max_selections !== null && $group->min_selections > $group->max_selections) {
                $group->min_selections = $group->max_selections;
            }
        });
    }
    
    /**
     * Scope for active groups
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope for required groups
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}