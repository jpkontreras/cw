<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Traits;

use Colame\Taxonomy\Contracts\TaxonomizableInterface;
use Colame\Taxonomy\Enums\TaxonomyType;
use Colame\Taxonomy\Models\Taxonomy;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait HasTaxonomies
{
    /**
     * Get all taxonomies for this model
     */
    public function taxonomies(): MorphToMany
    {
        return $this->morphToMany(Taxonomy::class, 'taxonomizable', 'taxonomizables')
            ->withPivot(['metadata', 'sort_order'])
            ->withTimestamps()
            ->orderBy('sort_order');
    }
    
    /**
     * Sync taxonomies for the model
     */
    public function syncTaxonomies(array $taxonomyIds): void
    {
        $sync = [];
        foreach ($taxonomyIds as $id => $data) {
            if (is_numeric($data)) {
                // Simple ID array
                $sync[$data] = ['sort_order' => 0];
            } else {
                // ID with metadata
                $sync[$id] = [
                    'metadata' => $data['metadata'] ?? null,
                    'sort_order' => $data['sort_order'] ?? 0,
                ];
            }
        }
        
        $this->taxonomies()->sync($sync);
    }
    
    /**
     * Check if model has a specific taxonomy
     */
    public function hasTaxonomy(int|string $taxonomyIdOrSlug): bool
    {
        if (is_numeric($taxonomyIdOrSlug)) {
            return $this->taxonomies()->where('taxonomies.id', $taxonomyIdOrSlug)->exists();
        }
        
        return $this->taxonomies()->where('taxonomies.slug', $taxonomyIdOrSlug)->exists();
    }
    
    /**
     * Get taxonomies of a specific type
     */
    public function getTaxonomiesByType(TaxonomyType $type): Collection
    {
        return $this->taxonomies()->where('type', $type->value)->get();
    }
    
    /**
     * Attach a taxonomy with metadata
     */
    public function attachTaxonomy(int $taxonomyId, array $metadata = []): void
    {
        $this->taxonomies()->attach($taxonomyId, [
            'metadata' => $metadata ?: null,
            'sort_order' => $metadata['sort_order'] ?? 0,
        ]);
    }
    
    /**
     * Detach a taxonomy
     */
    public function detachTaxonomy(int $taxonomyId): void
    {
        $this->taxonomies()->detach($taxonomyId);
    }
    
    /**
     * Sync taxonomies of a specific type
     */
    public function syncTaxonomiesByType(TaxonomyType $type, array $taxonomyIds): void
    {
        // Get existing taxonomies not of this type
        $existing = $this->taxonomies()
            ->where('type', '!=', $type->value)
            ->pluck('taxonomies.id')
            ->toArray();
        
        // Merge with new taxonomies
        $all = array_merge($existing, $taxonomyIds);
        
        $this->syncTaxonomies($all);
    }
    
    /**
     * Get primary taxonomy of a type (first one)
     */
    public function getPrimaryTaxonomy(TaxonomyType $type): ?Taxonomy
    {
        return $this->taxonomies()
            ->where('type', $type->value)
            ->orderBy('pivot_sort_order')
            ->first();
    }
    
    /**
     * Scope to filter by taxonomy
     */
    public function scopeWithTaxonomy($query, int|string $taxonomyIdOrSlug)
    {
        return $query->whereHas('taxonomies', function ($q) use ($taxonomyIdOrSlug) {
            if (is_numeric($taxonomyIdOrSlug)) {
                $q->where('taxonomies.id', $taxonomyIdOrSlug);
            } else {
                $q->where('taxonomies.slug', $taxonomyIdOrSlug);
            }
        });
    }
    
    /**
     * Scope to filter by multiple taxonomies (AND condition)
     */
    public function scopeWithAllTaxonomies($query, array $taxonomyIds)
    {
        foreach ($taxonomyIds as $id) {
            $query->whereHas('taxonomies', function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('taxonomies.id', $id);
                } else {
                    $q->where('taxonomies.slug', $id);
                }
            });
        }
        
        return $query;
    }
    
    /**
     * Scope to filter by any of the taxonomies (OR condition)
     */
    public function scopeWithAnyTaxonomies($query, array $taxonomyIds)
    {
        return $query->whereHas('taxonomies', function ($q) use ($taxonomyIds) {
            $q->whereIn('taxonomies.id', array_filter($taxonomyIds, 'is_numeric'))
              ->orWhereIn('taxonomies.slug', array_filter($taxonomyIds, 'is_string'));
        });
    }
}