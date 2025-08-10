<?php

declare(strict_types=1);

namespace Colame\Menu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Contracts\MenuItemRepositoryInterface;
use Illuminate\Http\Request;
use Colame\Menu\Data\CreateMenuItemData;
use Colame\Menu\Data\UpdateMenuItemData;
use Illuminate\Http\JsonResponse;

class MenuItemController extends Controller
{
    public function __construct(
        private MenuServiceInterface $menuService,
        private MenuItemRepositoryInterface $itemRepository,
    ) {}
    
    /**
     * Get all items in a menu (JSON response for AJAX)
     */
    public function index(int $menuId): JsonResponse
    {
        $items = $this->itemRepository->getByMenu($menuId);
        
        return response()->json([
            'items' => $items,
        ]);
    }
    
    /**
     * Store a new item
     */
    public function store(Request $request, int $menuId): JsonResponse
    {
        // Add menu_id to request data
        $request->merge(['menu_id' => $menuId]);
        
        // Create data object with validation
        $data = CreateMenuItemData::validateAndCreate($request);
        
        // Create item using repository/service
        $item = $this->itemRepository->create($data);
        
        return response()->json([
            'success' => true,
            'item' => $item,
        ]);
    }
    
    /**
     * Update an item
     */
    public function update(Request $request, int $menuId, int $itemId): JsonResponse
    {
        // Create data object with validation
        $data = UpdateMenuItemData::validateAndCreate($request);
        
        // Update item using repository/service
        $item = $this->itemRepository->update($itemId, $data);
        
        return response()->json([
            'success' => true,
            'item' => $item,
        ]);
    }
    
    /**
     * Delete an item
     */
    public function destroy(int $menuId, int $itemId): JsonResponse
    {
        // Verify item belongs to menu
        $item = $this->itemRepository->find($itemId);
        
        if (!$item || $item->menuId !== $menuId) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in this menu',
            ], 404);
        }
        
        $this->itemRepository->delete($itemId);
        
        return response()->json([
            'success' => true,
        ]);
    }
    
    /**
     * Reorder items
     */
    public function reorder(Request $request, int $menuId): JsonResponse
    {
        $items = $request->input('items', []);
        
        if (empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'No items provided for reordering',
            ], 422);
        }
        
        $orders = [];
        foreach ($items as $itemData) {
            if (!isset($itemData['id']) || !isset($itemData['sortOrder'])) {
                continue;
            }
            
            // Verify item belongs to menu
            $item = $this->itemRepository->find($itemData['id']);
            if ($item && $item->menuId === $menuId) {
                $orders[$itemData['id']] = (int) $itemData['sortOrder'];
            }
        }
        
        $this->itemRepository->bulkUpdateOrder($orders);
        
        return response()->json([
            'success' => true,
        ]);
    }
    
    /**
     * Toggle featured status
     */
    public function toggleFeatured(int $menuId, int $itemId): JsonResponse
    {
        // Verify item belongs to menu
        $item = $this->itemRepository->find($itemId);
        
        if (!$item || $item->menuId !== $menuId) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in this menu',
            ], 404);
        }
        
        $newFeaturedStatus = !$item->isFeatured;
        $this->itemRepository->setFeatured($itemId, $newFeaturedStatus);
        
        return response()->json([
            'success' => true,
            'is_featured' => $newFeaturedStatus,
        ]);
    }
    
    /**
     * Toggle availability status
     */
    public function toggleAvailability(int $menuId, int $itemId): JsonResponse
    {
        // Verify item belongs to menu
        $item = $this->itemRepository->find($itemId);
        
        if (!$item || $item->menuId !== $menuId) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in this menu',
            ], 404);
        }
        
        $newAvailabilityStatus = !$item->isActive;
        $this->itemRepository->setAvailability($itemId, $newAvailabilityStatus);
        
        return response()->json([
            'success' => true,
            'is_available' => $newAvailabilityStatus,
        ]);
    }
}