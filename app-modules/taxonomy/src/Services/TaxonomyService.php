<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Services;

use Colame\Taxonomy\Contracts\TaxonomyRepositoryInterface;
use Colame\Taxonomy\Contracts\TaxonomyServiceInterface;
use Colame\Taxonomy\Data\CreateTaxonomyData;
use Colame\Taxonomy\Data\TaxonomyData;
use Colame\Taxonomy\Data\UpdateTaxonomyData;
use Colame\Taxonomy\Enums\TaxonomyType;
use Colame\Taxonomy\Models\Taxonomy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;

class TaxonomyService implements TaxonomyServiceInterface
{
    public function __construct(
        private TaxonomyRepositoryInterface $repository
    ) {}
    
    public function createTaxonomy(CreateTaxonomyData $data): TaxonomyData
    {
        $taxonomyData = DB::transaction(function () use ($data) {
            $taxonomy = $this->repository->create($data->toArray());
            
            // Clear cache for this taxonomy type
            $this->clearTypeCache($data->type);
            
            return $taxonomy;
        });
        
        return $taxonomyData;
    }
    
    public function updateTaxonomy(int $id, UpdateTaxonomyData $data): TaxonomyData
    {
        $existing = $this->repository->find($id);
        
        if (!$existing) {
            throw new \Exception("Taxonomy with ID {$id} not found");
        }
        
        $updated = DB::transaction(function () use ($id, $data, $existing) {
            $taxonomy = $this->repository->update($id, array_filter($data->toArray()));
            
            // Clear cache for this taxonomy type
            $this->clearTypeCache($existing->type);
            
            return $taxonomy;
        });
        
        return $updated;
    }
    
    public function deleteTaxonomy(int $id): bool
    {
        $existing = $this->repository->find($id);
        
        if (!$existing) {
            return false;
        }
        
        $deleted = DB::transaction(function () use ($id, $existing) {
            $result = $this->repository->delete($id);
            
            // Clear cache for this taxonomy type
            $this->clearTypeCache($existing->type);
            
            return $result;
        });
        
        return $deleted;
    }
    
    public function getTaxonomy(int $id): ?TaxonomyData
    {
        return $this->repository->find($id);
    }
    
    public function getTaxonomiesByType(TaxonomyType $type, ?int $locationId = null): DataCollection
    {
        $cacheKey = "taxonomies.{$type->value}.location.{$locationId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($type, $locationId) {
            return $this->repository->findByType($type, $locationId);
        });
    }
    
    public function getTaxonomyTree(TaxonomyType $type, ?int $locationId = null): array
    {
        $cacheKey = "taxonomy_tree.{$type->value}.location.{$locationId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($type, $locationId) {
            return $this->repository->getHierarchy($type, $locationId);
        });
    }
    
    public function assignTaxonomies(Model $entity, array $taxonomyIds, ?TaxonomyType $type = null): void
    {
        $this->repository->syncForEntity($entity, $taxonomyIds, $type);
        
        // Clear entity cache if applicable
        $this->clearEntityCache($entity);
    }
    
    public function getEntityTaxonomies(Model $entity, ?TaxonomyType $type = null): DataCollection
    {
        return $this->repository->getForEntity($entity, $type);
    }
    
    public function searchTaxonomies(string $query, ?TaxonomyType $type = null): DataCollection
    {
        return $this->repository->search($query, $type);
    }
    
    public function validateCompatibility(array $taxonomyIds): bool
    {
        // Load all taxonomies
        $taxonomies = Taxonomy::whereIn('id', $taxonomyIds)->get();
        
        // Group by type
        $byType = $taxonomies->groupBy('type');
        
        // Check for incompatible combinations
        foreach ($byType as $type => $group) {
            $taxonomyType = TaxonomyType::from($type);
            
            // Check if type allows multiple
            if (!$taxonomyType->allowsMultiple() && $group->count() > 1) {
                return false;
            }
        }
        
        // Check for conflicting dietary labels (e.g., vegan and contains-meat)
        if (isset($byType[TaxonomyType::DIETARY_LABEL->value])) {
            $dietaryLabels = $byType[TaxonomyType::DIETARY_LABEL->value]->pluck('slug')->toArray();
            
            // Define incompatible pairs
            $incompatible = [
                ['vegan', 'contains-meat'],
                ['vegan', 'contains-dairy'],
                ['vegan', 'contains-eggs'],
                ['vegetarian', 'contains-meat'],
                ['dairy-free', 'contains-dairy'],
                ['gluten-free', 'contains-gluten'],
            ];
            
            foreach ($incompatible as $pair) {
                if (count(array_intersect($pair, $dietaryLabels)) === count($pair)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    public function getPopularTaxonomies(TaxonomyType $type, int $limit = 10): DataCollection
    {
        $cacheKey = "popular_taxonomies.{$type->value}.{$limit}";
        
        return Cache::remember($cacheKey, 7200, function () use ($type, $limit) {
            $taxonomies = Taxonomy::withCount('items')
                ->ofType($type->value)
                ->active()
                ->orderByDesc('items_count')
                ->limit($limit)
                ->get();
            
            return TaxonomyData::collection($taxonomies);
        });
    }
    
    public function mergeTaxonomies(int $sourceId, int $targetId): bool
    {
        if ($sourceId === $targetId) {
            return false;
        }
        
        $source = Taxonomy::find($sourceId);
        $target = Taxonomy::find($targetId);
        
        if (!$source || !$target) {
            return false;
        }
        
        if ($source->type !== $target->type) {
            throw new \Exception("Cannot merge taxonomies of different types");
        }
        
        DB::transaction(function () use ($source, $target) {
            // Move all relationships to target
            DB::table('taxonomizables')
                ->where('taxonomy_id', $source->id)
                ->update(['taxonomy_id' => $target->id]);
            
            // Move children to target
            Taxonomy::where('parent_id', $source->id)
                ->update(['parent_id' => $target->id]);
            
            // Delete source
            $source->delete();
            
            // Clear caches
            $this->clearTypeCache(TaxonomyType::from($source->type));
        });
        
        return true;
    }
    
    public function bulkCreateTaxonomies(array $taxonomies): DataCollection
    {
        $created = [];
        
        DB::transaction(function () use ($taxonomies, &$created) {
            foreach ($taxonomies as $taxonomyData) {
                $data = CreateTaxonomyData::from($taxonomyData);
                $created[] = $this->repository->create($data->toArray());
            }
        });
        
        // Clear all type caches
        if (!empty($created)) {
            $types = array_unique(array_map(fn($t) => $t->type, $created));
            foreach ($types as $type) {
                $this->clearTypeCache($type);
            }
        }
        
        return new DataCollection(TaxonomyData::class, $created);
    }
    
    protected function clearTypeCache(TaxonomyType $type): void
    {
        Cache::forget("taxonomies.{$type->value}.location.");
        Cache::forget("taxonomy_tree.{$type->value}.location.");
        Cache::forget("popular_taxonomies.{$type->value}.10");
        
        // Clear location-specific caches (up to 100 locations)
        for ($i = 1; $i <= 100; $i++) {
            Cache::forget("taxonomies.{$type->value}.location.{$i}");
            Cache::forget("taxonomy_tree.{$type->value}.location.{$i}");
        }
    }
    
    protected function clearEntityCache(Model $entity): void
    {
        $class = get_class($entity);
        $id = $entity->getKey();
        
        // Clear any entity-specific caches
        Cache::forget("entity.{$class}.{$id}.taxonomies");
    }
}