<?php

declare(strict_types=1);

namespace Colame\Menu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Contracts\MenuRepositoryInterface;
use Colame\Menu\Contracts\MenuSectionRepositoryInterface;
use Colame\Menu\Contracts\MenuItemRepositoryInterface;
use Colame\Menu\Data\SaveMenuStructureData;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class MenuBuilderController extends Controller
{
    public function __construct(
        private MenuServiceInterface $menuService,
        private MenuRepositoryInterface $menuRepository,
        private MenuSectionRepositoryInterface $sectionRepository,
        private MenuItemRepositoryInterface $itemRepository,
        private ItemRepositoryInterface $items,
    ) {}
    
    public function index(int $menuId): Response
    {
        $menu = $this->menuRepository->findWithRelations($menuId);
        
        if (!$menu) {
            abort(404, 'Menu not found');
        }
        
        $structure = $this->menuService->getMenuStructure($menuId);
        
        // Get available items from item module
        $availableItems = $this->items->getActiveItems();
        
        // Get all menus for dropdown
        $allMenus = $this->menuRepository->all();
        
        return Inertia::render('menu/builder', [
            'menu' => $menu,
            'allMenus' => $allMenus,
            'structure' => $structure,
            'availableItems' => $availableItems,
            'features' => [
                'nutritionalInfo' => config('features.menu.nutritional_info'),
                'dietaryLabels' => config('features.menu.dietary_labels'),
                'allergenInfo' => config('features.menu.allergen_info'),
                'seasonalItems' => config('features.menu.seasonal_items'),
                'featuredItems' => config('features.menu.featured_items'),
                'recommendedItems' => config('features.menu.recommended_items'),
                'itemBadges' => config('features.menu.item_badges'),
                'customImages' => config('features.menu.custom_images'),
            ],
        ]);
    }
    
    public function globalIndex(): Response
    {
        // Get all menus
        $allMenus = $this->menuRepository->all();
        
        // Get the default menu or the first available menu
        $defaultMenu = null;
        $structure = ['sections' => []];
        $availableItems = [];
        
        if ($allMenus->count() > 0) {
            // Convert to array to work with the data
            $menusArray = $allMenus->toArray();
            
            // Try to find default menu, otherwise use first
            $defaultMenu = null;
            foreach ($menusArray as $menu) {
                if ($menu['isDefault'] ?? false) {
                    $defaultMenu = $menu;
                    break;
                }
            }
            
            // If no default found, use the first menu
            if (!$defaultMenu && count($menusArray) > 0) {
                $defaultMenu = $menusArray[0];
            }
            
            if ($defaultMenu) {
                $structure = $this->menuService->getMenuStructure($defaultMenu['id']);
                $availableItems = $this->items->getActiveItems();
            }
        }
        
        return Inertia::render('menu/builder', [
            'menu' => $defaultMenu,
            'allMenus' => $allMenus,
            'structure' => $structure,
            'availableItems' => $availableItems,
            'features' => [
                'nutritionalInfo' => config('features.menu.nutritional_info'),
                'dietaryLabels' => config('features.menu.dietary_labels'),
                'allergenInfo' => config('features.menu.allergen_info'),
                'seasonalItems' => config('features.menu.seasonal_items'),
                'featuredItems' => config('features.menu.featured_items'),
                'recommendedItems' => config('features.menu.recommended_items'),
                'itemBadges' => config('features.menu.item_badges'),
                'customImages' => config('features.menu.custom_images'),
            ],
        ]);
    }
    
    public function save(Request $request, int $menuId)
    {
        try {
            // Authorization check - ensure user can update menus
            $menu = $this->menuRepository->find($menuId);
            if (!$menu) {
                // For Inertia, we should use back() with errors
                return back()->withErrors(['menu' => 'Menu not found']);
            }
            
            // TODO: Add proper authorization policy check when implemented
            // $this->authorize('update', $menu);
            
            // Validate using Data object - following the order module pattern
            $data = SaveMenuStructureData::validateAndCreate($request->all());
            
            // Save menu structure using service layer
            $structure = $this->menuService->saveMenuStructure($menuId, $data);
            
            // For successful AJAX requests, return JSON
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Menu structure saved successfully',
                    'structure' => $structure->toArray(),
                ]);
            }
            
            // For Inertia requests, redirect back with success message
            // The page will reload with fresh data including correct database IDs
            return redirect()->route('menu.builder', ['menu' => $menuId])
                ->with('success', 'Menu structure saved successfully');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // For AJAX requests, return validation errors as JSON
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            
            // For Inertia requests, let Laravel handle it naturally
            throw $e;
            
        } catch (\Exception $e) {
            // For AJAX requests
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save menu structure',
                    'error' => $e->getMessage(),
                ], 500);
            }
            
            // For Inertia requests
            return back()->withErrors(['error' => 'Failed to save menu structure: ' . $e->getMessage()]);
        }
    }
}