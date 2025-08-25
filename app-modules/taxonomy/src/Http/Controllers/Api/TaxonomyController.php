<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Taxonomy\Contracts\TaxonomyServiceInterface;
use Colame\Taxonomy\Data\CreateTaxonomyData;
use Colame\Taxonomy\Data\UpdateTaxonomyData;
use Colame\Taxonomy\Enums\TaxonomyType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxonomyController extends Controller
{
    public function __construct(
        private TaxonomyServiceInterface $taxonomyService
    ) {}
    
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type') ? TaxonomyType::from($request->query('type')) : null;
        $locationId = $request->query('location_id', $request->user()?->location_id);
        
        if (!$type) {
            return response()->json([
                'message' => 'Type parameter is required',
                'available_types' => array_map(fn($case) => $case->value, TaxonomyType::cases()),
            ], 400);
        }
        
        $taxonomies = $this->taxonomyService->getTaxonomiesByType($type, $locationId);
        
        return response()->json([
            'data' => $taxonomies->toArray(),
        ]);
    }
    
    public function store(Request $request): JsonResponse
    {
        $data = CreateTaxonomyData::validateAndCreate($request);
        
        // Add location if type requires it
        if ($data->type->requiresLocation() && !$data->locationId) {
            $data->locationId = $request->user()->location_id;
        }
        
        $taxonomy = $this->taxonomyService->createTaxonomy($data);
        
        return response()->json([
            'data' => $taxonomy->toArray(),
            'message' => 'Taxonomy created successfully',
        ], 201);
    }
    
    public function show(int $id): JsonResponse
    {
        $taxonomy = $this->taxonomyService->getTaxonomy($id);
        
        if (!$taxonomy) {
            return response()->json([
                'message' => 'Taxonomy not found',
            ], 404);
        }
        
        return response()->json([
            'data' => $taxonomy->toArray(),
        ]);
    }
    
    public function update(Request $request, int $id): JsonResponse
    {
        $data = UpdateTaxonomyData::validateAndCreate($request);
        
        try {
            $taxonomy = $this->taxonomyService->updateTaxonomy($id, $data);
            
            return response()->json([
                'data' => $taxonomy->toArray(),
                'message' => 'Taxonomy updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->taxonomyService->deleteTaxonomy($id);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'Taxonomy not found',
            ], 404);
        }
        
        return response()->json([
            'message' => 'Taxonomy deleted successfully',
        ]);
    }
    
    public function tree(Request $request): JsonResponse
    {
        $type = TaxonomyType::from($request->query('type', TaxonomyType::ITEM_CATEGORY->value));
        $locationId = $request->query('location_id', $request->user()?->location_id);
        
        if (!$type->isHierarchical()) {
            return response()->json([
                'message' => 'This taxonomy type does not support hierarchical structure',
            ], 400);
        }
        
        $tree = $this->taxonomyService->getTaxonomyTree($type, $locationId);
        
        return response()->json([
            'data' => $tree,
        ]);
    }
    
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $type = $request->query('type') ? TaxonomyType::from($request->query('type')) : null;
        
        if (strlen($query) < 2) {
            return response()->json([
                'message' => 'Query must be at least 2 characters',
            ], 400);
        }
        
        $results = $this->taxonomyService->searchTaxonomies($query, $type);
        
        return response()->json([
            'data' => $results->toArray(),
        ]);
    }
    
    public function popular(Request $request): JsonResponse
    {
        $type = TaxonomyType::from($request->query('type', TaxonomyType::GENERAL_TAG->value));
        $limit = min((int) $request->query('limit', 10), 50);
        
        $popular = $this->taxonomyService->getPopularTaxonomies($type, $limit);
        
        return response()->json([
            'data' => $popular->toArray(),
        ]);
    }
    
    public function bulkCreate(Request $request): JsonResponse
    {
        $request->validate([
            'taxonomies' => 'required|array',
            'taxonomies.*.name' => 'required|string',
            'taxonomies.*.type' => 'required|string',
        ]);
        
        try {
            $created = $this->taxonomyService->bulkCreateTaxonomies($request->input('taxonomies'));
            
            return response()->json([
                'data' => $created->toArray(),
                'message' => count($created) . ' taxonomies created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create taxonomies: ' . $e->getMessage(),
            ], 400);
        }
    }
}