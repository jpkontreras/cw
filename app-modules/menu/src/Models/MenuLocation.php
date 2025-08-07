<?php

declare(strict_types=1);

namespace Colame\Menu\Models;

use Illuminate\Database\Eloquent\Model;

class MenuLocation extends Model
{
    protected $fillable = [
        'menu_id',
        'location_id',
        'is_active',
        'is_primary',
        'activated_at',
        'deactivated_at',
        'overrides',
    ];
    
    protected $casts = [
        'menu_id' => 'integer',
        'location_id' => 'integer',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'overrides' => 'array',
    ];
    
    protected $attributes = [
        'is_active' => true,
        'is_primary' => false,
    ];
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (MenuLocation $menuLocation) {
            if ($menuLocation->is_active && !$menuLocation->activated_at) {
                $menuLocation->activated_at = now();
            }
            
            // Ensure only one primary menu per location
            if ($menuLocation->is_primary) {
                static::where('location_id', $menuLocation->location_id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
        
        static::updating(function (MenuLocation $menuLocation) {
            // Track activation/deactivation times
            if ($menuLocation->isDirty('is_active')) {
                if ($menuLocation->is_active) {
                    $menuLocation->activated_at = now();
                    $menuLocation->deactivated_at = null;
                } else {
                    $menuLocation->deactivated_at = now();
                }
            }
            
            // Ensure only one primary menu per location
            if ($menuLocation->isDirty('is_primary') && $menuLocation->is_primary) {
                static::where('location_id', $menuLocation->location_id)
                    ->where('id', '!=', $menuLocation->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }
    
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}