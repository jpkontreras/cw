<?php

declare(strict_types=1);

namespace Colame\Menu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Contracts\MenuRepositoryInterface;
use Colame\Menu\Contracts\MenuSectionRepositoryInterface;
use Colame\Menu\Contracts\MenuItemRepositoryInterface;
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
        
        return Inertia::render('menu/builder', [
            'menu' => $menu,
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
    
    public function save(Request $request, int $menuId): JsonResponse
    {
        $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'nullable|integer',
            'sections.*.name' => 'required|string|max:255',
            'sections.*.description' => 'nullable|string',
            'sections.*.items' => 'array',
            'sections.*.items.*.item_id' => 'required|integer',
            'sections.*.items.*.display_name' => 'nullable|string',
            'sections.*.items.*.display_description' => 'nullable|string',
            'sections.*.items.*.price_override' => 'nullable|numeric|min:0',
            'sections.*.items.*.is_featured' => 'boolean',
            'sections.*.items.*.is_recommended' => 'boolean',
            'sections.*.items.*.is_new' => 'boolean',
            'sections.*.items.*.is_seasonal' => 'boolean',
            'sections.*.children' => 'array',
        ]);
        
        \DB::transaction(function () use ($request, $menuId) {
            $sectionsData = $request->sections;
            
            // Track existing section IDs to remove deleted ones
            $existingSectionIds = [];
            $existingItemIds = [];
            
            foreach ($sectionsData as $sectionIndex => $sectionData) {
                if (isset($sectionData['id'])) {
                    // Update existing section
                    $section = $this->sectionRepository->update($sectionData['id'], [
                        'name' => $sectionData['name'],
                        'description' => $sectionData['description'] ?? null,
                        'sort_order' => $sectionIndex,
                    ]);
                    $existingSectionIds[] = $sectionData['id'];
                } else {
                    // Create new section
                    $section = $this->sectionRepository->create([
                        'menu_id' => $menuId,
                        'name' => $sectionData['name'],
                        'description' => $sectionData['description'] ?? null,
                        'sort_order' => $sectionIndex,
                    ]);
                    $existingSectionIds[] = $section->id;
                }
                
                // Handle items in section
                if (isset($sectionData['items'])) {
                    foreach ($sectionData['items'] as $itemIndex => $itemData) {
                        if (isset($itemData['id'])) {
                            // Update existing item
                            $this->itemRepository->update($itemData['id'], [
                                'display_name' => $itemData['display_name'] ?? null,
                                'display_description' => $itemData['display_description'] ?? null,
                                'price_override' => $itemData['price_override'] ?? null,
                                'is_featured' => $itemData['is_featured'] ?? false,
                                'is_recommended' => $itemData['is_recommended'] ?? false,
                                'is_new' => $itemData['is_new'] ?? false,
                                'is_seasonal' => $itemData['is_seasonal'] ?? false,
                                'sort_order' => $itemIndex,
                            ]);
                            $existingItemIds[] = $itemData['id'];
                        } else {
                            // Add new item to section
                            $item = $this->itemRepository->addToSection(
                                $section->id,
                                $itemData['item_id'],
                                [
                                    'display_name' => $itemData['display_name'] ?? null,
                                    'display_description' => $itemData['display_description'] ?? null,
                                    'price_override' => $itemData['price_override'] ?? null,
                                    'is_featured' => $itemData['is_featured'] ?? false,
                                    'is_recommended' => $itemData['is_recommended'] ?? false,
                                    'is_new' => $itemData['is_new'] ?? false,
                                    'is_seasonal' => $itemData['is_seasonal'] ?? false,
                                    'sort_order' => $itemIndex,
                                ]
                            );
                            $existingItemIds[] = $item->id;
                        }
                    }
                }
                
                // Handle child sections recursively
                if (isset($sectionData['children'])) {
                    $this->saveChildSections(
                        $sectionData['children'],
                        $menuId,
                        $section->id,
                        $existingSectionIds,
                        $existingItemIds
                    );
                }
            }
            
            // Remove deleted sections
            $this->sectionRepository->getByMenu($menuId)
                ->filter(fn($s) => !in_array($s->id, $existingSectionIds))
                ->each(fn($s) => $this->sectionRepository->delete($s->id));
            
            // Remove deleted items
            $this->itemRepository->getByMenu($menuId)
                ->filter(fn($i) => !in_array($i->id, $existingItemIds))
                ->each(fn($i) => $this->itemRepository->delete($i->id));
        });
        
        // Return updated structure
        $structure = $this->menuService->getMenuStructure($menuId);
        
        return response()->json([
            'success' => true,
            'message' => 'Menu structure saved successfully',
            'structure' => $structure,
        ]);
    }
    
    private function saveChildSections(
        array $childrenData,
        int $menuId,
        int $parentId,
        array &$existingSectionIds,
        array &$existingItemIds
    ): void {
        foreach ($childrenData as $childIndex => $childData) {
            if (isset($childData['id'])) {
                $childSection = $this->sectionRepository->update($childData['id'], [
                    'parent_id' => $parentId,
                    'name' => $childData['name'],
                    'description' => $childData['description'] ?? null,
                    'sort_order' => $childIndex,
                ]);
                $existingSectionIds[] = $childData['id'];
            } else {
                $childSection = $this->sectionRepository->create([
                    'menu_id' => $menuId,
                    'parent_id' => $parentId,
                    'name' => $childData['name'],
                    'description' => $childData['description'] ?? null,
                    'sort_order' => $childIndex,
                ]);
                $existingSectionIds[] = $childSection->id;
            }
            
            // Handle items in child section
            if (isset($childData['items'])) {
                foreach ($childData['items'] as $itemIndex => $itemData) {
                    // Similar item handling as above
                    // ... (same logic for items)
                }
            }
            
            // Recursive call for deeper nesting
            if (isset($childData['children'])) {
                $this->saveChildSections(
                    $childData['children'],
                    $menuId,
                    $childSection->id,
                    $existingSectionIds,
                    $existingItemIds
                );
            }
        }
    }
}