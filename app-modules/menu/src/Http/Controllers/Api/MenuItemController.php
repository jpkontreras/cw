<?php

declare(strict_types=1);

namespace Colame\Menu\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Contracts\MenuItemRepositoryInterface;
use Colame\Menu\Data\CreateMenuItemData;
use Colame\Menu\Data\UpdateMenuItemData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class MenuItemController extends Controller
{
    public function __construct(
        private MenuServiceInterface $menuService,
        private MenuItemRepositoryInterface $itemRepository,
    ) {}
    
    /**
     * Get all items in a menu
     */
    public function index(int $menuId): JsonResponse
    {
        $items = $this->itemRepository->getByMenuWithRelations($menuId, ['section', 'modifiers']);
        
        return response()->json([
            'success' => true,
            'data' => $items->toArray(),
        ]);
    }
    
    /**
     * Get featured items from a menu
     */
    public function featured(int $menuId): JsonResponse
    {
        $items = $this->itemRepository->getFeaturedAvailableByMenu($menuId);
        
        return response()->json([
            'success' => true,
            'data' => $items->toArray(),
        ]);
    }
    
    /**
     * Create a new menu item
     */
    public function store(Request $request, int $menuId): JsonResponse
    {
        // Add menu_id to request data
        $request->merge(['menu_id' => $menuId]);
        
        // Create data object with validation
        $data = CreateMenuItemData::validateAndCreate($request);
        
        try {
            // Verify section belongs to menu
            if (!$this->itemRepository->sectionExistsInMenu($data->menuSectionId, $menuId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section not found in this menu',
                ], 404);
            }
            
            // Create the item using repository
            $item = $this->itemRepository->create($data);
            
            // Get item with relations
            $itemWithRelations = $this->itemRepository->findWithModifiers($item->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Menu item created successfully',
                'data' => $itemWithRelations?->toArray(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create menu item',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Delete a menu item
     */
    public function destroy(int $menuId, int $itemId): Response|JsonResponse
    {
        try {
            if (!$this->itemRepository->deleteByIdInMenu($itemId, $menuId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu item not found',
                ], 404);
            }
            
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu item',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get all items in a menu section
     */
    public function indexBySection(int $menuId, int $sectionId): JsonResponse
    {
        $items = $this->itemRepository->getBySectionWithRelations($sectionId, $menuId, ['modifiers']);
        
        return response()->json([
            'success' => true,
            'data' => $items->toArray(),
        ]);
    }
    
    /**
     * Get a specific menu item
     */
    public function show(int $menuId, int $sectionId, int $itemId): JsonResponse
    {
        $item = $this->itemRepository->findWithRelations($itemId, $menuId, $sectionId);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Menu item not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $item->toArray(),
        ]);
    }
    
    /**
     * Update a menu item
     */
    public function update(Request $request, int $menuId, int $sectionId, int $itemId): JsonResponse
    {
        // Create data object with validation
        $data = UpdateMenuItemData::validateAndCreate($request);
        
        try {
            // Verify item exists in menu and section
            $item = $this->itemRepository->findWithRelations($itemId, $menuId, $sectionId);
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu item not found',
                ], 404);
            }
            
            // Update using repository with DTO
            $updatedItem = $this->itemRepository->update($itemId, $data);
            
            return response()->json([
                'success' => true,
                'message' => 'Menu item updated successfully',
                'data' => $updatedItem->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update menu item',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Toggle item availability
     */
    public function toggleAvailability(int $menuId, int $sectionId, int $itemId): JsonResponse
    {
        try {
            $item = $this->itemRepository->toggleAvailability($itemId, $menuId, $sectionId);
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu item not found',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => $item->isActive ? 'Item marked as available' : 'Item marked as unavailable',
                'data' => ['is_available' => $item->isActive],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle item availability',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Update item modifiers
     */
    public function updateModifiers(Request $request, int $menuId, int $sectionId, int $itemId): JsonResponse
    {
        // Modifiers should be handled by the Modifier module when implemented
        // For now, return an error response
        return response()->json([
            'success' => false,
            'message' => 'Modifier management is not yet implemented. This will be handled by the Modifier module.',
        ], 501);
    }
    
    /**
     * Bulk update item availability
     */
    public function bulkUpdateAvailability(Request $request, int $menuId): JsonResponse
    {
        // For bulk operations, we'll validate each item individually
        $items = $request->input('items', []);
        
        if (empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'No items provided for update',
            ], 422);
        }
        
        try {
            $updatedCount = $this->itemRepository->bulkUpdateAvailability($items, $menuId);
            
            return response()->json([
                'success' => true,
                'message' => "Updated availability for {$updatedCount} items",
                'data' => ['updated_count' => $updatedCount],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update item availability',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get popular items from a menu
     */
    public function popular(int $menuId, Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);
        $limit = max(1, min(50, $limit)); // Clamp between 1 and 50
        
        $items = $this->itemRepository->getPopularByMenu($menuId, $limit);
        
        return response()->json([
            'success' => true,
            'data' => $items->toArray(),
        ]);
    }
    
    /**
     * Search items within a menu
     */
    public function search(int $menuId, Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        $sectionId = $request->input('section_id');
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Search query must be at least 2 characters',
            ], 422);
        }
        
        $items = $this->itemRepository->searchInMenu($menuId, $query, $sectionId);
        
        return response()->json([
            'success' => true,
            'data' => $items->toArray(),
            'meta' => [
                'query' => $query,
                'count' => $items->count(),
            ],
        ]);
    }
}