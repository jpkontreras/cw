<?php

declare(strict_types=1);

namespace Colame\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MenuSection extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'display_name',
        'is_active',
        'is_featured',
        'sort_order',
        'available_from',
        'available_until',
        'availability_days',
        'metadata',
    ];
    
    protected $casts = [
        'menu_id' => 'integer',
        'parent_id' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'available_from' => 'datetime:H:i',
        'available_until' => 'datetime:H:i',
        'availability_days' => 'array',
        'metadata' => 'array',
    ];
    
    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
        'sort_order' => 0,
    ];
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (MenuSection $section) {
            if (empty($section->slug)) {
                $section->slug = Str::slug($section->name);
            }
            
            if (empty($section->display_name)) {
                $section->display_name = $section->name;
            }
        });
        
        static::updating(function (MenuSection $section) {
            if ($section->isDirty('name') && !$section->isDirty('slug')) {
                $section->slug = Str::slug($section->name);
            }
        });
    }
    
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
    
    public function parent()
    {
        return $this->belongsTo(MenuSection::class, 'parent_id');
    }
    
    public function children()
    {
        return $this->hasMany(MenuSection::class, 'parent_id')->orderBy('sort_order');
    }
    
    public function items()
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }
    
    public function activeItems()
    {
        return $this->items()->where('is_active', true);
    }
    
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        $now = now();
        
        // Check time availability
        if ($this->available_from || $this->available_until) {
            $currentTime = $now->format('H:i');
            
            if ($this->available_from && $currentTime < $this->available_from) {
                return false;
            }
            
            if ($this->available_until && $currentTime > $this->available_until) {
                return false;
            }
        }
        
        // Check day availability
        if ($this->availability_days && count($this->availability_days) > 0) {
            $currentDay = strtolower($now->format('l'));
            if (!in_array($currentDay, $this->availability_days)) {
                return false;
            }
        }
        
        return true;
    }
}