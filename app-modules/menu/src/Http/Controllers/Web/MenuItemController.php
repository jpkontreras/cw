<?php

declare(strict_types=1);

namespace Colame\Menu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MenuItemController extends Controller
{
    public function __construct(
        private MenuServiceInterface $menuService,
    ) {}
    
    /**
     * Get all items in a menu (JSON response for AJAX)
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
            'items' => $items,
        ]);
    }
    
    /**
     * Store a new item
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
            'item' => $item->fresh(['section', 'modifiers']),
        ]);
    }
    
    /**
     * Update an item
     */
    public function update(Request $request, int $menuId, int $itemId): JsonResponse
    {
        $request->validate([
            'price' => 'nullable|numeric|min:0',
            'custom_name' => 'nullable|string|max:255',
            'custom_description' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_available' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        
        $item = MenuItem::where('id', $itemId)
            ->whereHas('section', function ($query) use ($menuId) {
                $query->where('menu_id', $menuId);
            })
            ->firstOrFail();
        
        $item->update($request->only([
            'price',
            'custom_name',
            'custom_description',
            'is_featured',
            'is_available',
            'sort_order',
        ]));
        
        return response()->json([
            'success' => true,
            'item' => $item->fresh(['section', 'modifiers']),
        ]);
    }
    
    /**
     * Delete an item
     */
    public function destroy(int $menuId, int $itemId): JsonResponse
    {
        $item = MenuItem::where('id', $itemId)
            ->whereHas('section', function ($query) use ($menuId) {
                $query->where('menu_id', $menuId);
            })
            ->firstOrFail();
        
        $item->delete();
        
        return response()->json([
            'success' => true,
        ]);
    }
    
    /**
     * Reorder items
     */
    public function reorder(Request $request, int $menuId): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.sortOrder' => 'required|integer|min:0',
        ]);
        
        foreach ($request->input('items') as $itemData) {
            MenuItem::where('id', $itemData['id'])
                ->whereHas('section', function ($query) use ($menuId) {
                    $query->where('menu_id', $menuId);
                })
                ->update(['sort_order' => $itemData['sortOrder']]);
        }
        
        return response()->json([
            'success' => true,
        ]);
    }
    
    /**
     * Toggle featured status
     */
    public function toggleFeatured(int $menuId, int $itemId): JsonResponse
    {
        $item = MenuItem::where('id', $itemId)
            ->whereHas('section', function ($query) use ($menuId) {
                $query->where('menu_id', $menuId);
            })
            ->firstOrFail();
        
        $item->is_featured = !$item->is_featured;
        $item->save();
        
        return response()->json([
            'success' => true,
            'is_featured' => $item->is_featured,
        ]);
    }
    
    /**
     * Toggle availability status
     */
    public function toggleAvailability(int $menuId, int $itemId): JsonResponse
    {
        $item = MenuItem::where('id', $itemId)
            ->whereHas('section', function ($query) use ($menuId) {
                $query->where('menu_id', $menuId);
            })
            ->firstOrFail();
        
        $item->is_available = !$item->is_available;
        $item->save();
        
        return response()->json([
            'success' => true,
            'is_available' => $item->is_available,
        ]);
    }
}