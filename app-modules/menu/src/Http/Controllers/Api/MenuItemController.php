<?php

declare(strict_types=1);

namespace Colame\Menu\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class MenuItemController extends Controller
{
    public function __construct(
        private MenuServiceInterface $menuService,
    ) {}
    
    /**
     * Get all items in a menu
     */
    public function index(int $menuId): JsonResponse
    {
        $items = MenuItem::whereHas('section', function ($query) use ($menuId) {
            $query->where('menu_id', $menuId);
        })
            ->with(['section', 'modifiers'])
            ->orderBy('sort_order')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
    
    /**
     * Get featured items from a menu
     */
    public function featured(int $menuId): JsonResponse
    {
        $items = MenuItem::whereHas('section', function ($query) use ($menuId) {
            $query->where('menu_id', $menuId);
        })
            ->where('is_featured', true)
            ->where('is_available', true)
            ->with(['section', 'modifiers'])
            ->orderBy('sort_order')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
    
    /**
     * Create a new menu item
     */
    public function store(Request $request, int $menuId): JsonResponse
    {
        $request->validate([
            'section_id' => 'required|integer',
            'item_id' => 'required|integer',
            'price' => 'nullable|numeric|min:0',
            'custom_name' => 'nullable|string|max:255',
            'custom_description' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_available' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        
        try {
            // Verify section belongs to menu
            $sectionExists = MenuItem::whereHas('section', function ($query) use ($menuId, $request) {
                $query->where('menu_id', $menuId)
                    ->where('id', $request->section_id);
            })->exists();
            
            if (!$sectionExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section not found in this menu',
                ], 404);
            }
            
            $item = MenuItem::create([
                'menu_section_id' => $request->section_id,
                'item_id' => $request->item_id,
                'price' => $request->price,
                'custom_name' => $request->custom_name,
                'custom_description' => $request->custom_description,
                'is_featured' => $request->is_featured ?? false,
                'is_available' => $request->is_available ?? true,
                'sort_order' => $request->sort_order ?? 0,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Menu item created successfully',
                'data' => $item->fresh(['section', 'modifiers']),
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
    public function destroy(int $menuId, int $itemId): Response
    {
        try {
            $item = MenuItem::where('id', $itemId)
                ->whereHas('section', function ($query) use ($menuId) {
                    $query->where('menu_id', $menuId);
                })
                ->first();
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu item not found',
                ], 404);
            }
            
            $item->delete();
            
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
        $items = MenuItem::where('menu_section_id', $sectionId)
            ->whereHas('section', function ($query) use ($menuId) {
                $query->where('menu_id', $menuId);
            })
            ->with(['modifiers'])
            ->orderBy('sort_order')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
    
    /**
     * Get a specific menu item
     */
    public function show(int $menuId, int $sectionId, int $itemId): JsonResponse
    {
        $item = MenuItem::where('id', $itemId)
            ->where('menu_section_id', $sectionId)
            ->whereHas('section', function ($query) use ($menuId) {
                $query->where('menu_id', $menuId);
            })
            ->with(['modifiers'])
            ->first();
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Menu item not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }
    
    /**
     * Update a menu item
     */
    public function update(Request $request, int $menuId, int $sectionId, int $itemId): JsonResponse
    {
        $request->validate([
            'price' => 'nullable|numeric|min:0',
            'isAvailable' => 'nullable|boolean',
            'isFeatured' => 'nullable|boolean',
            'sortOrder' => 'nullable|integer|min:0',
            'customName' => 'nullable|string|max:255',
            'customDescription' => 'nullable|string',
            'preparationTime' => 'nullable|integer|min:0',
            'maxQuantity' => 'nullable|integer|min:0',
        ]);
        
        try {
            $item = MenuItem::where('id', $itemId)
                ->where('menu_section_id', $sectionId)
                ->whereHas('section', function ($query) use ($menuId) {
                    $query->where('menu_id', $menuId);
                })
                ->first();
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu item not found',
                ], 404);
            }
            
            $item->update($request->only([
                'price',
                'is_available',
                'is_featured',
                'sort_order',
                'custom_name',
                'custom_description',
                'preparation_time',
                'max_quantity',
            ]));
            
            return response()->json([
                'success' => true,
                'message' => 'Menu item updated successfully',
                'data' => $item->fresh(['modifiers']),
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
            $item = MenuItem::where('id', $itemId)
                ->where('menu_section_id', $sectionId)
                ->whereHas('section', function ($query) use ($menuId) {
                    $query->where('menu_id', $menuId);
                })
                ->first();
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu item not found',
                ], 404);
            }
            
            $item->is_available = !$item->is_available;
            $item->save();
            
            return response()->json([
                'success' => true,
                'message' => $item->is_available ? 'Item marked as available' : 'Item marked as unavailable',
                'data' => ['is_available' => $item->is_available],
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
        $request->validate([
            'modifiers' => 'required|array',
            'modifiers.*.modifierId' => 'required|integer',
            'modifiers.*.isAvailable' => 'nullable|boolean',
            'modifiers.*.priceOverride' => 'nullable|numeric|min:0',
            'modifiers.*.isDefault' => 'nullable|boolean',
        ]);
        
        try {
            $item = MenuItem::where('id', $itemId)
                ->where('menu_section_id', $sectionId)
                ->whereHas('section', function ($query) use ($menuId) {
                    $query->where('menu_id', $menuId);
                })
                ->first();
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu item not found',
                ], 404);
            }
            
            $this->menuService->updateItemModifiers($itemId, $request->input('modifiers'));
            
            return response()->json([
                'success' => true,
                'message' => 'Item modifiers updated successfully',
                'data' => $item->fresh(['modifiers']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item modifiers',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Bulk update item availability
     */
    public function bulkUpdateAvailability(Request $request, int $menuId): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.isAvailable' => 'required|boolean',
        ]);
        
        try {
            $updatedCount = 0;
            
            foreach ($request->input('items') as $itemData) {
                $item = MenuItem::where('id', $itemData['id'])
                    ->whereHas('section', function ($query) use ($menuId) {
                        $query->where('menu_id', $menuId);
                    })
                    ->first();
                
                if ($item) {
                    $item->is_available = $itemData['isAvailable'];
                    $item->save();
                    $updatedCount++;
                }
            }
            
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
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
        ]);
        
        $limit = $request->input('limit', 10);
        
        $items = MenuItem::whereHas('section', function ($query) use ($menuId) {
            $query->where('menu_id', $menuId);
        })
            ->where('is_available', true)
            ->where('is_featured', true)
            ->with(['section', 'modifiers'])
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
    
    /**
     * Search items within a menu
     */
    public function search(int $menuId, Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'section_id' => 'nullable|integer',
        ]);
        
        $query = $request->input('query');
        $sectionId = $request->input('section_id');
        
        $items = MenuItem::whereHas('section', function ($q) use ($menuId) {
            $q->where('menu_id', $menuId);
        })
            ->when($sectionId, function ($q) use ($sectionId) {
                $q->where('menu_section_id', $sectionId);
            })
            ->where(function ($q) use ($query) {
                $q->where('custom_name', 'like', "%{$query}%")
                    ->orWhere('custom_description', 'like', "%{$query}%");
            })
            ->with(['section', 'modifiers'])
            ->orderBy('sort_order')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'query' => $query,
                'count' => $items->count(),
            ],
        ]);
    }
}