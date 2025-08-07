<?php

declare(strict_types=1);

namespace Colame\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Menu extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'is_active',
        'is_default',
        'sort_order',
        'available_from',
        'available_until',
        'metadata',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'metadata' => 'array',
    ];
    
    protected $attributes = [
        'type' => 'regular',
        'is_active' => true,
        'is_default' => false,
        'sort_order' => 0,
    ];
    
    public const TYPE_REGULAR = 'regular';
    public const TYPE_BREAKFAST = 'breakfast';
    public const TYPE_LUNCH = 'lunch';
    public const TYPE_DINNER = 'dinner';
    public const TYPE_EVENT = 'event';
    public const TYPE_SEASONAL = 'seasonal';
    
    public const VALID_TYPES = [
        self::TYPE_REGULAR,
        self::TYPE_BREAKFAST,
        self::TYPE_LUNCH,
        self::TYPE_DINNER,
        self::TYPE_EVENT,
        self::TYPE_SEASONAL,
    ];
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (Menu $menu) {
            if (empty($menu->slug)) {
                $menu->slug = static::generateUniqueSlug($menu->name);
            }
            
            // Ensure only one default menu
            if ($menu->is_default) {
                static::where('is_default', true)->update(['is_default' => false]);
            }
        });
        
        static::updating(function (Menu $menu) {
            if ($menu->isDirty('name') && !$menu->isDirty('slug')) {
                $menu->slug = static::generateUniqueSlug($menu->name);
            }
            
            // Ensure only one default menu
            if ($menu->isDirty('is_default') && $menu->is_default) {
                static::where('id', '!=', $menu->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
    
    protected static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;
        
        while (static::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }
        
        return $slug;
    }
    
    public function sections()
    {
        return $this->hasMany(MenuSection::class)->orderBy('sort_order');
    }
    
    public function items()
    {
        return $this->hasMany(MenuItem::class);
    }
    
    public function availabilityRules()
    {
        return $this->hasMany(MenuAvailabilityRule::class);
    }
    
    public function locations()
    {
        return $this->hasMany(MenuLocation::class);
    }
    
    public function versions()
    {
        return $this->hasMany(MenuVersion::class)->orderBy('version_number', 'desc');
    }
    
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        $now = now();
        
        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }
        
        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }
        
        return true;
    }
}