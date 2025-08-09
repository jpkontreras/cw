<?php

declare(strict_types=1);

namespace Colame\Menu\Services;

use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Contracts\MenuRepositoryInterface;
use Colame\Menu\Contracts\MenuSectionRepositoryInterface;
use Colame\Menu\Contracts\MenuItemRepositoryInterface;
use Colame\Menu\Contracts\MenuVersioningInterface;
use Colame\Menu\Data\CreateMenuData;
use Colame\Menu\Data\UpdateMenuData;
use Colame\Menu\Data\MenuData;
use Colame\Menu\Data\MenuStructureData;
use Colame\Menu\Data\MenuSectionWithItemsData;
use Colame\Menu\Data\SaveMenuStructureData;
use Colame\Menu\Data\SaveMenuSectionData;
use Colame\Menu\Data\SaveMenuItemData;
use Colame\Menu\Models\Menu;
use Colame\Menu\Models\MenuSection;
use Colame\Menu\Models\MenuItem;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;

class MenuService implements MenuServiceInterface
{
    public function __construct(
        private MenuRepositoryInterface $menuRepository,
        private MenuSectionRepositoryInterface $sectionRepository,
        private MenuItemRepositoryInterface $itemRepository,
        private MenuVersioningInterface $versioningService,
    ) {}

    public function createMenu(CreateMenuData $data): MenuData
    {
        return DB::transaction(function () use ($data) {
            // Create the menu
            $menuData = $this->menuRepository->create($data->toArray());

            // Create sections if provided
            if ($data->sections) {
                foreach ($data->sections as $sectionData) {
                    $this->sectionRepository->create([
                        'menu_id' => $menuData->id,
                        ...$sectionData,
                    ]);
                }
            }

            // Assign to locations if provided
            if ($data->locationIds) {
                $this->assignToLocations($menuData->id, $data->locationIds);
            }

            // Create initial version
            if (config('features.menu.versioning')) {
                $this->versioningService->createVersion(
                    $menuData->id,
                    'created',
                    'Initial menu creation'
                );
            }

            return $menuData;
        });
    }

    public function updateMenu(int $id, UpdateMenuData $data): MenuData
    {
        return DB::transaction(function () use ($id, $data) {
            $menuData = $this->menuRepository->update($id, $data->toArray());

            // Create version snapshot if versioning is enabled
            if (config('features.menu.versioning')) {
                $this->versioningService->createVersion(
                    $id,
                    'updated',
                    'Menu updated'
                );
            }

            return $menuData;
        });
    }

    public function deleteMenu(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            // Archive version before deletion
            if (config('features.menu.versioning')) {
                $this->versioningService->createVersion(
                    $id,
                    'archived',
                    'Menu deleted'
                );
            }

            return $this->menuRepository->delete($id);
        });
    }

    public function getMenuStructure(int $id): MenuStructureData
    {
        $menu = Menu::with([
            'sections' => function ($query) {
                $query->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with([
                        'children' => function ($q) {
                            $q->where('is_active', true)
                              ->orderBy('sort_order')
                              ->with('activeItems.item'); // Also load items for child sections
                        }, 
                        'activeItems.item' // Eager load the actual item relationship
                    ]);
            }
        ])->findOrFail($id);

        // Build array of sections first, then create DataCollection
        $sectionsArray = [];
        foreach ($menu->sections as $section) {
            $sectionsArray[] = MenuSectionWithItemsData::fromModel($section);
        }
        
        // Create DataCollection from array using collect() method
        $sections = MenuSectionWithItemsData::collect($sectionsArray, DataCollection::class);

        return new MenuStructureData(
            id: $menu->id,
            name: $menu->name,
            slug: $menu->slug,
            description: $menu->description,
            type: $menu->type,
            isActive: $menu->is_active,
            isAvailable: $menu->isAvailable(),
            sections: $sections,
            availability: null, // Will be populated by availability service
            metadata: $menu->metadata,
        );
    }

    public function saveMenuStructure(int $menuId, SaveMenuStructureData $data): MenuStructureData
    {
        return DB::transaction(function () use ($menuId, $data) {
            // Verify menu exists
            $menu = Menu::findOrFail($menuId);
            
            // Pre-validate structure for duplicate items within sections
            $this->validateSectionItems($data);

            // Track existing section and item IDs to handle deletions
            $existingSectionIds = [];
            $existingItemIds = [];

            // Process sections if provided - handle empty menu case
            if ($data->sections !== null && $data->sections->count() > 0) {
                // Convert DataCollection to array for indexed iteration
                $sectionsArray = $data->sections->toArray();
                foreach ($sectionsArray as $sectionIndex => $sectionData) {
                    // Convert array back to Data object if needed
                    $sectionDataObject = is_array($sectionData)
                        ? SaveMenuSectionData::from($sectionData)
                        : $sectionData;

                    $this->processSaveSection(
                        $menuId,
                        $sectionDataObject,
                        (int) $sectionIndex,
                        null,
                        $existingSectionIds,
                        $existingItemIds
                    );
                }
            }

            // Delete sections that are no longer in the structure
            // If no sections exist, delete all sections for this menu
            if (empty($existingSectionIds)) {
                MenuSection::where('menu_id', $menuId)->delete();
            } else {
                MenuSection::where('menu_id', $menuId)
                    ->whereNotIn('id', $existingSectionIds)
                    ->delete();
            }

            // Delete items that are no longer in the structure
            // If no items exist, delete all items for this menu
            if (empty($existingItemIds)) {
                MenuItem::where('menu_id', $menuId)->delete();
            } else {
                MenuItem::where('menu_id', $menuId)
                    ->whereNotIn('id', $existingItemIds)
                    ->delete();
            }

            // Create version snapshot if versioning is enabled
            if (config('features.menu.versioning')) {
                $this->versioningService->createVersion(
                    $menuId,
                    'structure_updated',
                    'Menu structure updated'
                );
            }

            // Return the updated structure
            return $this->getMenuStructure($menuId);
        });
    }

    private function processSaveSection(
        int $menuId,
        SaveMenuSectionData $sectionData,
        int $sortOrder,
        ?int $parentId,
        array &$existingSectionIds,
        array &$existingItemIds
    ): int {
        // Check if this is an existing section or a new one
        // IDs over 1 billion are likely from Date.now() and should be treated as new
        $isNewSection = !$sectionData->id || $sectionData->id > 1000000000;

        if (!$isNewSection) {
            // Try to find existing section that belongs to this menu
            $section = MenuSection::where('id', $sectionData->id)
                ->where('menu_id', $menuId)
                ->first();
            $isNewSection = !$section;
        }

        if ($isNewSection) {
            // Create new section
            $section = MenuSection::create([
                'menu_id' => $menuId,
                'parent_id' => $parentId,
                'name' => $sectionData->name,
                'description' => $sectionData->description,
                'icon' => $sectionData->icon,
                'is_active' => $sectionData->isActive,
                'is_featured' => $sectionData->isFeatured,
                'sort_order' => $sortOrder,
            ]);
            $existingSectionIds[] = $section->id;
        } else {
            // Update existing section
            $section->update([
                'name' => $sectionData->name,
                'description' => $sectionData->description,
                'icon' => $sectionData->icon,
                'is_active' => $sectionData->isActive,
                'is_featured' => $sectionData->isFeatured,
                'sort_order' => $sortOrder,
                'parent_id' => $parentId,
            ]);
            $existingSectionIds[] = $section->id;
        }

        // Process items in this section - convert DataCollection to array
        $itemsArray = $sectionData->items->toArray();
        foreach ($itemsArray as $itemIndex => $itemData) {
            // Convert array back to Data object if needed
            $itemDataObject = is_array($itemData)
                ? SaveMenuItemData::from($itemData)
                : $itemData;

            $this->processSaveItem(
                $menuId,
                $section->id,
                $itemDataObject,
                (int) $itemIndex,
                $existingItemIds
            );
        }

        // Process child sections recursively
        if ($sectionData->children) {
            $childrenArray = $sectionData->children->toArray();
            foreach ($childrenArray as $childIndex => $childData) {
                // Convert array back to Data object if needed
                $childDataObject = is_array($childData)
                    ? SaveMenuSectionData::from($childData)
                    : $childData;

                $this->processSaveSection(
                    $menuId,
                    $childDataObject,
                    (int) $childIndex,
                    $section->id,
                    $existingSectionIds,
                    $existingItemIds
                );
            }
        }

        return $section->id;
    }

    private function processSaveItem(
        int $menuId,
        int $sectionId,
        SaveMenuItemData $itemData,
        int $sortOrder,
        array &$existingItemIds
    ): void {
        // Check if this is an existing item or a new one
        // IDs over 1 billion are likely from Date.now() and should be treated as new
        $isNewItem = !$itemData->id || $itemData->id > 1000000000;
        $item = null;

        if (!$isNewItem) {
            // Try to find existing item IN THIS MENU AND SECTION
            // Items can exist in multiple sections, so we need to check both menu_id and section_id
            $item = MenuItem::where('id', $itemData->id)
                ->where('menu_id', $menuId)
                ->where('menu_section_id', $sectionId)
                ->first();
            
            // If the item exists but in a different section, treat as new item in this section
            if (!$item) {
                // Check if it exists in a different section
                $itemInDifferentSection = MenuItem::where('id', $itemData->id)
                    ->where('menu_id', $menuId)
                    ->first();
                    
                if ($itemInDifferentSection) {
                    // The item ID exists but in a different section
                    // We'll create a new item in this section
                    $isNewItem = true;
                } else {
                    // The item doesn't exist at all in this menu
                    $isNewItem = true;
                }
            }
        }

        if ($isNewItem) {
            // Check if this item_id already exists in THIS SPECIFIC SECTION
            // Items can exist in multiple sections, but not duplicates within the same section
            $existingMenuItemInSection = MenuItem::where('menu_id', $menuId)
                ->where('menu_section_id', $sectionId)
                ->where('item_id', $itemData->itemId)
                ->first();
                
            if ($existingMenuItemInSection) {
                // Item already exists in this section, update it instead of creating duplicate
                $existingMenuItemInSection->update([
                    'display_name' => $itemData->displayName,
                    'display_description' => $itemData->displayDescription,
                    'price_override' => $itemData->priceOverride,
                    'is_featured' => $itemData->isFeatured,
                    'is_recommended' => $itemData->isRecommended,
                    'is_new' => $itemData->isNew,
                    'is_seasonal' => $itemData->isSeasonal,
                    'sort_order' => $sortOrder,
                ]);
                $existingItemIds[] = $existingMenuItemInSection->id;
            } else {
                // Create new item in this section
                // This allows the same item_id to exist in multiple sections
                $item = MenuItem::create([
                    'menu_id' => $menuId,
                    'menu_section_id' => $sectionId,
                    'item_id' => $itemData->itemId,
                    'display_name' => $itemData->displayName,
                    'display_description' => $itemData->displayDescription,
                    'price_override' => $itemData->priceOverride,
                    'is_active' => true,
                    'is_featured' => $itemData->isFeatured,
                    'is_recommended' => $itemData->isRecommended,
                    'is_new' => $itemData->isNew,
                    'is_seasonal' => $itemData->isSeasonal,
                    'sort_order' => $sortOrder,
                ]);
                $existingItemIds[] = $item->id;
            }
        } else {
            // Update existing item (we already have $item from the query above)
            // Keep it in the same section
            $item->update([
                'display_name' => $itemData->displayName,
                'display_description' => $itemData->displayDescription,
                'price_override' => $itemData->priceOverride,
                'is_featured' => $itemData->isFeatured,
                'is_recommended' => $itemData->isRecommended,
                'is_new' => $itemData->isNew,
                'is_seasonal' => $itemData->isSeasonal,
                'sort_order' => $sortOrder,
            ]);
            $existingItemIds[] = $item->id;
        }
    }

    public function getMenuStructureForLocation(int $menuId, int $locationId): MenuStructureData
    {
        // Get base structure
        $structure = $this->getMenuStructure($menuId);

        // Apply location-specific overrides
        $locationMenu = Menu::find($menuId)
            ->locations()
            ->where('location_id', $locationId)
            ->first();

        if ($locationMenu && $locationMenu->overrides) {
            // Apply overrides to structure
            // This would modify prices, availability, etc. based on location
        }

        return $structure;
    }

    public function duplicateMenu(int $id, string $newName): MenuData
    {
        return $this->menuRepository->clone($id, $newName);
    }

    public function buildFromTemplate(string $templateName, array $customizations = []): MenuData
    {
        // Load template configuration
        $templateConfig = config("menu-templates.{$templateName}");

        if (!$templateConfig) {
            throw new \InvalidArgumentException("Template {$templateName} not found");
        }

        // Merge customizations with template
        $menuData = array_merge($templateConfig, $customizations);

        return $this->createMenu(CreateMenuData::from($menuData));
    }

    public function assignToLocations(int $menuId, array $locationIds): bool
    {
        $menu = Menu::findOrFail($menuId);

        foreach ($locationIds as $locationId) {
            $menu->locations()->updateOrCreate(
                ['location_id' => $locationId],
                ['is_active' => true, 'activated_at' => now()]
            );
        }

        return true;
    }

    public function removeFromLocation(int $menuId, int $locationId): bool
    {
        return Menu::find($menuId)
            ->locations()
            ->where('location_id', $locationId)
            ->delete() > 0;
    }

    public function setPrimaryForLocation(int $menuId, int $locationId): bool
    {
        DB::transaction(function () use ($menuId, $locationId) {
            // Remove primary status from other menus at this location
            DB::table('menu_locations')
                ->where('location_id', $locationId)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            // Set this menu as primary
            DB::table('menu_locations')
                ->where('menu_id', $menuId)
                ->where('location_id', $locationId)
                ->update(['is_primary' => true]);
        });

        return true;
    }

    public function validateMenuStructure(int $menuId): array
    {
        $errors = [];
        $warnings = [];

        $menu = Menu::with(['sections.items'])->find($menuId);

        if (!$menu) {
            return ['errors' => ['Menu not found'], 'warnings' => []];
        }

        // Check for empty sections
        foreach ($menu->sections as $section) {
            if ($section->items->isEmpty()) {
                $warnings[] = "Section '{$section->name}' has no items";
            }
            
            // Check for duplicate items within the same section
            $sectionItemIds = [];
            foreach ($section->items as $item) {
                if (in_array($item->item_id, $sectionItemIds)) {
                    $errors[] = "Section '{$section->name}' contains duplicate item with ID {$item->item_id}";
                }
                $sectionItemIds[] = $item->item_id;
            }
        }

        // Check for items in multiple sections (this is allowed but noted as informational)
        $globalItemCounts = [];
        foreach ($menu->sections as $section) {
            foreach ($section->items as $item) {
                if (!isset($globalItemCounts[$item->item_id])) {
                    $globalItemCounts[$item->item_id] = [];
                }
                $globalItemCounts[$item->item_id][] = $section->name;
            }
        }
        
        foreach ($globalItemCounts as $itemId => $sections) {
            if (count($sections) > 1) {
                $sectionsList = implode(', ', $sections);
                $warnings[] = "Item ID {$itemId} appears in multiple sections: {$sectionsList} (this is allowed)";
            }
        }

        // Check availability rules
        if ($menu->availabilityRules->isEmpty() && !$menu->is_default) {
            $warnings[] = "Menu has no availability rules configured";
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'valid' => empty($errors),
        ];
    }

    public function getMenuAnalytics(int $menuId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        // This would integrate with order data to provide analytics
        // For now, return basic structure
        return [
            'menu_id' => $menuId,
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'metrics' => [
                'total_orders' => 0,
                'total_revenue' => 0,
                'popular_items' => [],
                'performance_by_section' => [],
            ],
        ];
    }

    public function exportMenu(int $menuId, string $format): string
    {
        $menu = $this->menuRepository->findWithRelations($menuId);

        if (!$menu) {
            throw new \InvalidArgumentException("Menu not found");
        }

        switch ($format) {
            case 'json':
                return json_encode($menu->toArray(), JSON_PRETTY_PRINT);

            case 'csv':
                // Implement CSV export
                return $this->exportToCsv($menu);

            case 'pdf':
                // Implement PDF export (would use a package like dompdf)
                return $this->exportToPdf($menu);

            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    public function importMenu(string $filePath, string $format): MenuData
    {
        $content = file_get_contents($filePath);

        switch ($format) {
            case 'json':
                $data = json_decode($content, true);
                return $this->createMenu(CreateMenuData::from($data));

            case 'csv':
                // Implement CSV import
                throw new \Exception("CSV import not yet implemented");

            default:
                throw new \InvalidArgumentException("Unsupported import format: {$format}");
        }
    }

    private function exportToCsv($menu): string
    {
        $csv = "Section,Item,Price,Description\n";

        foreach ($menu->sections as $section) {
            foreach ($section->items as $item) {
                $csv .= sprintf(
                    '"%s","%s",%.2f,"%s"' . "\n",
                    $section->name,
                    $item->displayName,
                    $item->priceOverride ?? 0,
                    $item->displayDescription ?? ''
                );
            }
        }

        return $csv;
    }

    private function exportToPdf($menu): string
    {
        // This would use a PDF library
        // For now, return a placeholder
        return "PDF export not yet implemented";
    }
    
    /**
     * Validate that sections don't contain duplicate items
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateSectionItems(SaveMenuStructureData $data): void
    {
        $errors = [];
        
        if ($data->sections !== null) {
            foreach ($data->sections as $sectionIndex => $section) {
                $sectionData = is_array($section) ? $section : $section->toArray();
                $sectionName = $sectionData['name'] ?? 'Unnamed Section';
                
                // Check for duplicate items within this section
                $itemIds = [];
                if (isset($sectionData['items']) && is_array($sectionData['items'])) {
                    foreach ($sectionData['items'] as $itemIndex => $item) {
                        $itemData = is_array($item) ? $item : $item->toArray();
                        $itemId = $itemData['itemId'] ?? null;
                        
                        if ($itemId !== null) {
                            if (in_array($itemId, $itemIds)) {
                                $errors["sections.{$sectionIndex}.items.{$itemIndex}"] = [
                                    "Section '{$sectionName}' contains duplicate item with ID {$itemId}. Each item can only appear once per section."
                                ];
                            }
                            $itemIds[] = $itemId;
                        }
                    }
                }
                
                // Check child sections recursively
                if (isset($sectionData['children']) && is_array($sectionData['children'])) {
                    foreach ($sectionData['children'] as $childIndex => $child) {
                        $childData = is_array($child) ? $child : $child->toArray();
                        $childName = $childData['name'] ?? 'Unnamed Child Section';
                        
                        $childItemIds = [];
                        if (isset($childData['items']) && is_array($childData['items'])) {
                            foreach ($childData['items'] as $childItemIndex => $childItem) {
                                $childItemData = is_array($childItem) ? $childItem : $childItem->toArray();
                                $childItemId = $childItemData['itemId'] ?? null;
                                
                                if ($childItemId !== null) {
                                    if (in_array($childItemId, $childItemIds)) {
                                        $errors["sections.{$sectionIndex}.children.{$childIndex}.items.{$childItemIndex}"] = [
                                            "Child section '{$childName}' contains duplicate item with ID {$childItemId}."
                                        ];
                                    }
                                    $childItemIds[] = $childItemId;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    public function addItemsToSection(int $sectionId, array $items): void
    {
        foreach ($items as $itemData) {
            MenuItem::create([
                'menu_section_id' => $sectionId,
                'item_id' => $itemData['itemId'],
                'price' => $itemData['price'] ?? null,
                'sort_order' => $itemData['sortOrder'] ?? 0,
                'is_available' => $itemData['isAvailable'] ?? true,
                'is_featured' => $itemData['isFeatured'] ?? false,
            ]);
        }
    }

    public function removeItemFromSection(int $sectionId, int $itemId): bool
    {
        return MenuItem::where('menu_section_id', $sectionId)
            ->where('item_id', $itemId)
            ->delete() > 0;
    }

    public function getSectionItems(int $sectionId): array
    {
        $items = MenuItem::where('menu_section_id', $sectionId)
            ->with(['modifiers'])
            ->orderBy('sort_order')
            ->get();

        return $items->map(function ($item) {
            // Get item details from item module if available
            if (app()->bound(ItemRepositoryInterface::class) && $item->item_id) {
                $itemRepository = app(ItemRepositoryInterface::class);
                $itemData = $itemRepository->find($item->item_id);
                if ($itemData) {
                    $item->item_details = $itemData;
                }
            }

            return $item;
        })->toArray();
    }

    public function updateItemModifiers(int $itemId, array $modifiers): void
    {
        $menuItem = MenuItem::findOrFail($itemId);

        // Clear existing modifiers
        $menuItem->modifiers()->delete();

        // Add new modifiers
        foreach ($modifiers as $modifier) {
            $menuItem->modifiers()->create([
                'modifier_id' => $modifier['modifierId'],
                'is_available' => $modifier['isAvailable'] ?? true,
                'price_override' => $modifier['priceOverride'] ?? null,
                'is_default' => $modifier['isDefault'] ?? false,
            ]);
        }
    }
}
