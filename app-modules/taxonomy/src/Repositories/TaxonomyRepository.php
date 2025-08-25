<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Repositories;

use Colame\Taxonomy\Contracts\TaxonomyRepositoryInterface;
use Colame\Taxonomy\Data\TaxonomyData;
use Colame\Taxonomy\Enums\TaxonomyType;
use Colame\Taxonomy\Models\Taxonomy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;

class TaxonomyRepository implements TaxonomyRepositoryInterface
{
    public function find(int $id): ?TaxonomyData
    {
        $taxonomy = Taxonomy::with(['parent', 'children', 'attributes'])->find($id);
        
        return $taxonomy ? TaxonomyData::fromModel($taxonomy) : null;
    }
    
    public function findBySlug(string $slug): ?TaxonomyData
    {
        $taxonomy = Taxonomy::with(['parent', 'children', 'attributes'])
            ->where('slug', $slug)
            ->first();
        
        return $taxonomy ? TaxonomyData::fromModel($taxonomy) : null;
    }
    
    public function findByType(TaxonomyType $type, ?int $locationId = null): DataCollection
    {
        $taxonomies = Taxonomy::with(['parent', 'attributes'])
            ->ofType($type->value)
            ->forLocation($locationId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        
        return TaxonomyData::collection($taxonomies);
    }
    
    public function getHierarchy(TaxonomyType $type, ?int $locationId = null): array
    {
        $taxonomies = Taxonomy::with(['children' => function ($query) {
            $query->active()->orderBy('sort_order')->orderBy('name');
        }])
            ->ofType($type->value)
            ->forLocation($locationId)
            ->root()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        
        return $this->buildHierarchyTree($taxonomies);
    }
    
    protected function buildHierarchyTree($taxonomies): array
    {
        $tree = [];
        
        foreach ($taxonomies as $taxonomy) {
            $node = TaxonomyData::fromModel($taxonomy)->toArray();
            
            if ($taxonomy->children->isNotEmpty()) {
                $node['children'] = $this->buildHierarchyTree($taxonomy->children);
            }
            
            $tree[] = $node;
        }
        
        return $tree;
    }
    
    public function getChildren(int $parentId): DataCollection
    {
        $children = Taxonomy::with(['attributes'])
            ->where('parent_id', $parentId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        
        return TaxonomyData::collection($children);
    }
    
    public function getAncestors(int $taxonomyId): DataCollection
    {
        $taxonomy = Taxonomy::find($taxonomyId);
        
        if (!$taxonomy) {
            return new DataCollection(TaxonomyData::class, []);
        }
        
        $ancestors = [];
        $current = $taxonomy->parent;
        
        while ($current) {
            array_unshift($ancestors, $current);
            $current = $current->parent;
        }
        
        return TaxonomyData::collection($ancestors);
    }
    
    public function create(array $data): TaxonomyData
    {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
            
            // Ensure unique slug
            $count = 1;
            $baseSlug = $data['slug'];
            while (Taxonomy::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $baseSlug . '-' . $count++;
            }
        }
        
        $taxonomy = Taxonomy::create($data);
        
        // Create attributes if provided
        if (!empty($data['attributes'])) {
            foreach ($data['attributes'] as $key => $value) {
                $taxonomy->attributes()->create([
                    'key' => $key,
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                    'type' => $this->detectType($value),
                ]);
            }
        }
        
        return TaxonomyData::fromModel($taxonomy->load(['parent', 'attributes']));
    }
    
    protected function detectType($value): string
    {
        if (is_bool($value)) return 'boolean';
        if (is_numeric($value)) return 'number';
        if (is_array($value) || is_object($value)) return 'json';
        return 'string';
    }
    
    public function update(int $id, array $data): TaxonomyData
    {
        $taxonomy = Taxonomy::findOrFail($id);
        
        // Update slug if name changed and slug not explicitly provided
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
            
            // Ensure unique slug (excluding current)
            $count = 1;
            $baseSlug = $data['slug'];
            while (Taxonomy::where('slug', $data['slug'])->where('id', '!=', $id)->exists()) {
                $data['slug'] = $baseSlug . '-' . $count++;
            }
        }
        
        $taxonomy->update($data);
        
        // Update attributes if provided
        if (isset($data['attributes'])) {
            // Remove old attributes not in the new data
            $taxonomy->attributes()->whereNotIn('key', array_keys($data['attributes']))->delete();
            
            // Update or create attributes
            foreach ($data['attributes'] as $key => $value) {
                $taxonomy->attributes()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => is_array($value) ? json_encode($value) : (string) $value,
                        'type' => $this->detectType($value),
                    ]
                );
            }
        }
        
        return TaxonomyData::fromModel($taxonomy->load(['parent', 'children', 'attributes']));
    }
    
    public function delete(int $id): bool
    {
        return Taxonomy::destroy($id) > 0;
    }
    
    public function attachToEntity(int $taxonomyId, Model $entity, array $metadata = []): void
    {
        if (method_exists($entity, 'taxonomies')) {
            $entity->taxonomies()->attach($taxonomyId, [
                'metadata' => $metadata ?: null,
                'sort_order' => $metadata['sort_order'] ?? 0,
            ]);
        }
    }
    
    public function detachFromEntity(int $taxonomyId, Model $entity): void
    {
        if (method_exists($entity, 'taxonomies')) {
            $entity->taxonomies()->detach($taxonomyId);
        }
    }
    
    public function syncForEntity(Model $entity, array $taxonomyIds, ?TaxonomyType $type = null): void
    {
        if (!method_exists($entity, 'taxonomies')) {
            return;
        }
        
        if ($type === null) {
            // Simple sync all
            $entity->taxonomies()->sync($taxonomyIds);
        } else {
            // Sync only taxonomies of specific type
            $existing = $entity->taxonomies()
                ->where('type', '!=', $type->value)
                ->pluck('taxonomies.id')
                ->toArray();
            
            $entity->taxonomies()->sync(array_merge($existing, $taxonomyIds));
        }
    }
    
    public function getForEntity(Model $entity, ?TaxonomyType $type = null): DataCollection
    {
        if (!method_exists($entity, 'taxonomies')) {
            return new DataCollection(TaxonomyData::class, []);
        }
        
        $query = $entity->taxonomies()->with(['parent', 'attributes']);
        
        if ($type !== null) {
            $query->where('type', $type->value);
        }
        
        $taxonomies = $query->orderBy('sort_order')->get();
        
        return TaxonomyData::collection($taxonomies);
    }
    
    public function search(string $query, ?TaxonomyType $type = null): DataCollection
    {
        $taxonomies = Taxonomy::with(['parent', 'attributes'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('slug', 'LIKE', "%{$query}%")
                  ->orWhereJsonContains('metadata->description', $query);
            })
            ->when($type, fn($q) => $q->ofType($type->value))
            ->active()
            ->limit(50)
            ->get();
        
        return TaxonomyData::collection($taxonomies);
    }
}