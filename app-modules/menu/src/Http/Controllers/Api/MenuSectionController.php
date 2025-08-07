<?php

declare(strict_types=1);

namespace Colame\Menu\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Contracts\MenuSectionRepositoryInterface;
use Colame\Menu\Data\CreateMenuSectionData;
use Colame\Menu\Data\UpdateMenuSectionData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class MenuSectionController extends Controller
{
    public function __construct(
        private MenuServiceInterface $menuService,
        private MenuSectionRepositoryInterface $sectionRepository,
    ) {}
    
    /**
     * Get all sections for a menu
     */
    public function index(int $menuId): JsonResponse
    {
        $sections = $this->sectionRepository->getByMenu($menuId);
        
        return response()->json([
            'success' => true,
            'data' => $sections,
        ]);
    }
    
    /**
     * Get items for a section
     */
    public function items(int $menuId, int $sectionId): JsonResponse
    {
        $section = $this->sectionRepository->find($sectionId);
        
        if (!$section || $section->menuId !== $menuId) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found',
            ], 404);
        }
        
        // Get items for this section from the menu service
        $items = $this->menuService->getSectionItems($sectionId);
        
        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
    
    /**
     * Get a specific section
     */
    public function show(int $menuId, int $sectionId): JsonResponse
    {
        $section = $this->sectionRepository->find($sectionId);
        
        if (!$section || $section->menuId !== $menuId) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $section,
        ]);
    }
    
    /**
     * Create a new section
     */
    public function store(Request $request, int $menuId): JsonResponse
    {
        try {
            $data = CreateMenuSectionData::validateAndCreate(
                array_merge($request->all(), ['menuId' => $menuId])
            );
            
            $section = $this->sectionRepository->create($data->toArray());
            
            return response()->json([
                'success' => true,
                'message' => 'Section created successfully',
                'data' => $section,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create section',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Update a section
     */
    public function update(Request $request, int $menuId, int $sectionId): JsonResponse
    {
        try {
            $section = $this->sectionRepository->find($sectionId);
            
            if (!$section || $section->menuId !== $menuId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section not found',
                ], 404);
            }
            
            $data = UpdateMenuSectionData::validateAndCreate($request);
            $updated = $this->sectionRepository->update($sectionId, $data->toArray());
            
            return response()->json([
                'success' => true,
                'message' => 'Section updated successfully',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update section',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Delete a section
     */
    public function destroy(int $menuId, int $sectionId): Response
    {
        try {
            $section = $this->sectionRepository->find($sectionId);
            
            if (!$section || $section->menuId !== $menuId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section not found',
                ], 404);
            }
            
            $this->sectionRepository->delete($sectionId);
            
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete section',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Reorder sections
     */
    public function reorder(Request $request, int $menuId): JsonResponse
    {
        $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'required|integer',
            'sections.*.sortOrder' => 'required|integer|min:0',
        ]);
        
        try {
            foreach ($request->input('sections') as $sectionData) {
                $section = $this->sectionRepository->find($sectionData['id']);
                
                if ($section && $section->menuId === $menuId) {
                    $this->sectionRepository->update($sectionData['id'], [
                        'sortOrder' => $sectionData['sortOrder'],
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Sections reordered successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder sections',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Add multiple items to a section
     */
    public function addItems(Request $request, int $menuId, int $sectionId): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.itemId' => 'required|integer',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.sortOrder' => 'nullable|integer|min:0',
        ]);
        
        try {
            $section = $this->sectionRepository->find($sectionId);
            
            if (!$section || $section->menuId !== $menuId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section not found',
                ], 404);
            }
            
            $this->menuService->addItemsToSection($sectionId, $request->input('items'));
            
            return response()->json([
                'success' => true,
                'message' => 'Items added to section successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add items to section',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Remove an item from a section
     */
    public function removeItem(int $menuId, int $sectionId, int $itemId): Response
    {
        try {
            $section = $this->sectionRepository->find($sectionId);
            
            if (!$section || $section->menuId !== $menuId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section not found',
                ], 404);
            }
            
            $this->menuService->removeItemFromSection($sectionId, $itemId);
            
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item from section',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}