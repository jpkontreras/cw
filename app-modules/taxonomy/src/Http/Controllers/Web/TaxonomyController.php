<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Taxonomy\Contracts\TaxonomyServiceInterface;
use Colame\Taxonomy\Data\CreateTaxonomyData;
use Colame\Taxonomy\Data\TaxonomyData;
use Colame\Taxonomy\Data\UpdateTaxonomyData;
use Colame\Taxonomy\Enums\TaxonomyType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\DataCollection;

class TaxonomyController extends Controller
{
    public function __construct(
        private TaxonomyServiceInterface $taxonomyService
    ) {}
    
    public function index(Request $request): Response
    {
        $query = \Colame\Taxonomy\Models\Taxonomy::query()
            ->with(['parent'])
            ->withCount('children');
        
        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }
        
        if ($request->filled('search')) {
            $searchTerm = $request->query('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('slug', 'like', "%{$searchTerm}%");
            });
        }
        
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        // Apply sorting
        $sortBy = $request->query('sort', 'name');
        $sortDirection = 'asc';
        
        if (str_starts_with($sortBy, '-')) {
            $sortBy = substr($sortBy, 1);
            $sortDirection = 'desc';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        // Get all results without pagination for now
        $perPage = $request->query('per_page', 1000);
        $paginator = $query->paginate($perPage);
        
        // Transform to data objects
        $taxonomies = TaxonomyData::collect($paginator->items(), DataCollection::class);
        
        // Build metadata for the data table
        $metadata = [
            'columns' => [
                'name' => [
                    'key' => 'name',
                    'label' => 'Name',
                    'sortable' => true,
                    'visible' => true,
                    'type' => 'string',
                ],
                'type' => [
                    'key' => 'type',
                    'label' => 'Type',
                    'sortable' => true,
                    'visible' => true,
                    'type' => 'enum',
                    'filter' => [
                        'filterType' => 'select',
                        'label' => 'Filter by Type',
                        'placeholder' => 'All Types',
                        'options' => array_map(fn($case) => [
                            'value' => $case->value,
                            'label' => $case->label(),
                        ], TaxonomyType::cases()),
                    ],
                ],
                'parent' => [
                    'key' => 'parent',
                    'label' => 'Parent',
                    'sortable' => false,
                    'visible' => true,
                    'type' => 'string',
                ],
                'sortOrder' => [
                    'key' => 'sortOrder',
                    'label' => 'Order',
                    'sortable' => true,
                    'visible' => true,
                    'type' => 'number',
                ],
                'isActive' => [
                    'key' => 'isActive',
                    'label' => 'Status',
                    'sortable' => true,
                    'visible' => true,
                    'type' => 'boolean',
                    'filter' => [
                        'filterType' => 'select',
                        'label' => 'Filter by Status',
                        'placeholder' => 'All Status',
                        'options' => [
                            ['value' => '1', 'label' => 'Active'],
                            ['value' => '0', 'label' => 'Inactive'],
                        ],
                    ],
                ],
            ],
            'filters' => [
                [
                    'key' => 'search',
                    'label' => 'Search',
                    'placeholder' => 'Search taxonomies...',
                    'filterType' => 'search',
                ],
            ],
            'defaultFilters' => ['search'],
            'perPageOptions' => [10, 20, 50, 100],
        ];
        
        return Inertia::render('taxonomy/index', [
            'taxonomies' => $taxonomies->toArray(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'metadata' => $metadata,
            'filters' => $request->only(['type', 'search', 'is_active', 'sort']),
        ]);
    }
    
    public function create(Request $request): Response
    {
        $type = $request->query('type') ? TaxonomyType::from($request->query('type')) : null;
        $parentId = $request->query('parent');
        
        // If parent is specified, get its type
        if ($parentId) {
            $parent = $this->taxonomyService->getTaxonomy((int)$parentId);
            if ($parent) {
                // parent->type is already a TaxonomyType enum or a string
                $type = is_string($parent->type) 
                    ? TaxonomyType::from($parent->type) 
                    : $parent->type;
            }
        }
        
        return Inertia::render('taxonomy/create', [
            'types' => array_map(fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
                'isHierarchical' => $case->isHierarchical(),
            ], TaxonomyType::cases()),
            'selectedType' => $type?->value,
            'selectedParent' => $parentId ? (int)$parentId : null,
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
            $data->location_id = $request->user()->location_id;
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
            'types' => array_map(fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
                'isHierarchical' => $case->isHierarchical(),
            ], TaxonomyType::cases()),
            'parentTaxonomies' => $taxonomy->type->isHierarchical()
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