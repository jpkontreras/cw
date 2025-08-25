<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Models;

use Colame\Location\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Builder;

class Taxonomy extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'parent_id',
        'location_id',
        'metadata',
        'sort_order',
        'is_active',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'parent_id' => 'integer',
        'location_id' => 'integer',
    ];
    
    /**
     * Get the parent taxonomy
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'parent_id');
    }
    
    /**
     * Get the child taxonomies
     */
    public function children(): HasMany
    {
        return $this->hasMany(Taxonomy::class, 'parent_id')->orderBy('sort_order');
    }
    
    /**
     * Get all descendants (recursive children)
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }
    
    /**
     * Get the attributes for this taxonomy
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(TaxonomyAttribute::class);
    }
    
    /**
     * Get the location this taxonomy belongs to
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
    
    /**
     * Get all items with this taxonomy
     */
    public function items(): MorphToMany
    {
        return $this->morphedByMany(\Colame\Item\Models\Item::class, 'taxonomizable', 'taxonomizables')
            ->withPivot(['metadata', 'sort_order'])
            ->withTimestamps();
    }
    
    /**
     * Get all menu items with this taxonomy
     */
    public function menuItems(): MorphToMany
    {
        return $this->morphedByMany(\Colame\Menu\Models\MenuItem::class, 'taxonomizable', 'taxonomizables')
            ->withPivot(['metadata', 'sort_order'])
            ->withTimestamps();
    }
    
    /**
     * Scope to filter by type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
    
    /**
     * Scope to filter active taxonomies
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope to filter root taxonomies (no parent)
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
    
    /**
     * Scope to filter by location or global
     */
    public function scopeForLocation(Builder $query, ?int $locationId): Builder
    {
        if ($locationId === null) {
            return $query->whereNull('location_id');
        }
        
        return $query->where(function ($q) use ($locationId) {
            $q->whereNull('location_id')
              ->orWhere('location_id', $locationId);
        });
    }
    
    /**
     * Get the full path from root to this taxonomy
     */
    public function getPathAttribute(): array
    {
        $path = [];
        $current = $this;
        
        while ($current) {
            array_unshift($path, $current);
            $current = $current->parent;
        }
        
        return $path;
    }
    
    /**
     * Check if this taxonomy is a descendant of another
     */
    public function isDescendantOf(Taxonomy $taxonomy): bool
    {
        $current = $this->parent;
        
        while ($current) {
            if ($current->id === $taxonomy->id) {
                return true;
            }
            $current = $current->parent;
        }
        
        return false;
    }
    
    /**
     * Get taxonomy attribute value by key
     */
    public function getTaxonomyAttribute(string $key, $default = null)
    {
        $attribute = $this->attributes()->where('key', $key)->first();
        
        if (!$attribute) {
            return $default;
        }
        
        return $attribute->getTypedValue();
    }
    
    /**
     * Set taxonomy attribute value
     */
    public function setTaxonomyAttribute(string $key, $value, string $type = 'string'): void
    {
        $this->attributes()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'type' => $type]
        );
    }
}