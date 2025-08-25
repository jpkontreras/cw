<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Taxonomy\Contracts\TaxonomyServiceInterface;
use Colame\Taxonomy\Data\CreateTaxonomyData;
use Colame\Taxonomy\Data\UpdateTaxonomyData;
use Colame\Taxonomy\Enums\TaxonomyType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxonomyController extends Controller
{
    public function __construct(
        private TaxonomyServiceInterface $taxonomyService
    ) {}
    
    public function index(Request $request): Response
    {
        $type = $request->query('type') ? TaxonomyType::from($request->query('type')) : null;
        $locationId = $request->user()?->location_id;
        
        $taxonomies = $type 
            ? $this->taxonomyService->getTaxonomiesByType($type, $locationId)
            : collect();
        
        $tree = $type && $type->isHierarchical()
            ? $this->taxonomyService->getTaxonomyTree($type, $locationId)
            : [];
        
        return Inertia::render('taxonomy/index', [
            'taxonomies' => $taxonomies->toArray(),
            'tree' => $tree,
            'types' => array_map(fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
                'isHierarchical' => $case->isHierarchical(),
            ], TaxonomyType::cases()),
            'selectedType' => $type?->value,
        ]);
    }
    
    public function create(Request $request): Response
    {
        $type = $request->query('type') ? TaxonomyType::from($request->query('type')) : null;
        
        return Inertia::render('taxonomy/create', [
            'types' => array_map(fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
                'isHierarchical' => $case->isHierarchical(),
            ], TaxonomyType::cases()),
            'selectedType' => $type?->value,
            'parentOptions' => $type && $type->isHierarchical() 
                ? $this->taxonomyService->getTaxonomiesByType($type)->toArray()
                : [],
        ]);
    }
    
    public function store(Request $request)
    {
        $data = CreateTaxonomyData::validateAndCreate($request);
        
        // Add location if type requires it
        if ($data->type->requiresLocation()) {
            $data->locationId = $request->user()->location_id;
        }
        
        $taxonomy = $this->taxonomyService->createTaxonomy($data);
        
        return redirect()->route('taxonomies.show', $taxonomy->id)
            ->with('success', 'Taxonomy created successfully');
    }
    
    public function show(int $id): Response
    {
        $taxonomy = $this->taxonomyService->getTaxonomy($id);
        
        if (!$taxonomy) {
            abort(404);
        }
        
        return Inertia::render('taxonomy/show', [
            'taxonomy' => $taxonomy->toArray(),
        ]);
    }
    
    public function edit(int $id): Response
    {
        $taxonomy = $this->taxonomyService->getTaxonomy($id);
        
        if (!$taxonomy) {
            abort(404);
        }
        
        return Inertia::render('taxonomy/edit', [
            'taxonomy' => $taxonomy->toArray(),
            'parentOptions' => $taxonomy->type->isHierarchical()
                ? $this->taxonomyService->getTaxonomiesByType($taxonomy->type)
                    ->filter(fn($t) => $t->id !== $id)
                    ->toArray()
                : [],
        ]);
    }
    
    public function update(Request $request, int $id)
    {
        $data = UpdateTaxonomyData::validateAndCreate($request);
        
        $taxonomy = $this->taxonomyService->updateTaxonomy($id, $data);
        
        return redirect()->route('taxonomies.show', $taxonomy->id)
            ->with('success', 'Taxonomy updated successfully');
    }
    
    public function destroy(int $id)
    {
        $this->taxonomyService->deleteTaxonomy($id);
        
        return redirect()->route('taxonomies.index')
            ->with('success', 'Taxonomy deleted successfully');
    }
    
    public function tree(Request $request): Response
    {
        $type = TaxonomyType::from($request->query('type', TaxonomyType::ITEM_CATEGORY->value));
        $locationId = $request->user()?->location_id;
        
        $tree = $this->taxonomyService->getTaxonomyTree($type, $locationId);
        
        return Inertia::render('taxonomy/tree', [
            'tree' => $tree,
            'type' => [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
            ],
        ]);
    }
}