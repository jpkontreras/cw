<?php

declare(strict_types=1);

namespace Colame\Menu\Services;

use Colame\Menu\Contracts\MenuServiceInterface;
use Colame\Menu\Contracts\MenuRepositoryInterface;
use Colame\Menu\Contracts\MenuSectionRepositoryInterface;
use Colame\Menu\Contracts\MenuItemRepositoryInterface;
use Colame\Menu\Contracts\MenuVersioningInterface;
use Colame\Menu\Data\CreateMenuData;
use Colame\Menu\Data\CreateMenuSectionData;
use Colame\Menu\Data\UpdateMenuData;
use Colame\Menu\Data\UpdateMenuSectionData;
use Colame\Menu\Data\MenuData;
use Colame\Menu\Data\MenuStructureData;
use Colame\Menu\Data\SaveMenuStructureData;
use Colame\Menu\Data\SaveMenuSectionData;
use Colame\Menu\Data\SaveMenuItemData;
use Colame\Menu\Data\CreateMenuItemData;
use Colame\Menu\Data\UpdateMenuItemData;
use Colame\Menu\Data\MenuItemData;
use Colame\Menu\Contracts\MenuLocationRepositoryInterface;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Spatie\LaravelData\DataCollection;
use Illuminate\Support\Facades\DB;

class MenuService implements MenuServiceInterface
{
    public function __construct(
        private MenuRepositoryInterface $menuRepository,
        private MenuSectionRepositoryInterface $sectionRepository,
        private MenuItemRepositoryInterface $itemRepository,
        private MenuVersioningInterface $versioningService,
        private MenuLocationRepositoryInterface $locationRepository,
        private ?ItemRepositoryInterface $itemDetailsRepository = null,
    ) {}

    public function createMenu(CreateMenuData $data): MenuData
    {
        return DB::transaction(function () use ($data) {
            // Create the menu
            $menuData = $this->menuRepository->create($data);

            // Create sections if provided
            if ($data->sections) {
                foreach ($data->sections as $sectionData) {
                    $createSectionData = CreateMenuSectionData::from([
                        'menuId' => $menuData->id,
                        ...$sectionData,
                    ]);
                    $this->sectionRepository->create($createSectionData);
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
            $menuData = $this->menuRepository->update($id, $data);

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
        $menuWithRelations = $this->menuRepository->findWithSectionsForStructure($id);
        
        if (!$menuWithRelations) {
            throw new \InvalidArgumentException("Menu with id {$id} not found");
        }

        // Resolve lazy-loaded sections if needed
        $sections = $menuWithRelations->sections instanceof \Spatie\LaravelData\Lazy
            ? $menuWithRelations->sections->resolve()
            : $menuWithRelations->sections;

        // Enrich menu items with base item details if item repository is available
        if ($this->itemDetailsRepository && $sections) {
            $sections = $this->enrichSectionsWithItemDetails($sections);
        }

        // Check availability using the service
        $isAvailable = $this->checkMenuAvailability($id);

        return new MenuStructureData(
            id: $menuWithRelations->id,
            name: $menuWithRelations->name,
            slug: $menuWithRelations->slug,
            description: $menuWithRelations->description,
            type: $menuWithRelations->type,
            isActive: $menuWithRelations->isActive,
            isAvailable: $isAvailable,
            sections: $sections,
            availability: null, // Will be populated by availability service
            metadata: $menuWithRelations->metadata,
        );
    }
    
    private function checkMenuAvailability(int $menuId): bool
    {
        // This would check availability rules - for now return active status
        $menu = $this->menuRepository->find($menuId);
        return $menu ? $menu->isActive : false;
    }

    public function saveMenuStructure(int $menuId, SaveMenuStructureData $data): MenuStructureData
    {
        return DB::transaction(function () use ($menuId, $data) {
            // Verify menu exists
            $menu = $this->menuRepository->findOrFail($menuId);
            
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
                        $sectionDataObject->sortOrder,
                        null,
                        $existingSectionIds,
                        $existingItemIds
                    );
                }
            }

            // Delete sections that are no longer in the structure
            $this->sectionRepository->deleteExcept($menuId, $existingSectionIds);

            // Delete items that are no longer in the structure
            $this->itemRepository->deleteExcept($menuId, $existingItemIds);

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
        $section = null;

        if (!$isNewSection) {
            // Try to find existing section that belongs to this menu
            $sectionDto = $this->sectionRepository->findByIdAndMenuId($sectionData->id, $menuId);
            $isNewSection = !$sectionDto;
            $sectionId = $sectionDto ? $sectionDto->id : null;
        }

        if ($isNewSection) {
            // Create new section
            $createSectionData = CreateMenuSectionData::from([
                'menuId' => $menuId,
                'parentId' => $parentId,
                'name' => $sectionData->name,
                'description' => $sectionData->description,
                'icon' => $sectionData->icon,
                'isActive' => $sectionData->isActive,
                'isFeatured' => $sectionData->isFeatured,
                'sortOrder' => $sectionData->sortOrder,
            ]);
            $sectionDto = $this->sectionRepository->create($createSectionData);
            $sectionId = $sectionDto->id;
            $existingSectionIds[] = $sectionId;
        } else {
            // Update existing section
            $updateSectionData = UpdateMenuSectionData::from([
                'parentId' => $parentId,
                'name' => $sectionData->name,
                'description' => $sectionData->description,
                'icon' => $sectionData->icon,
                'isActive' => $sectionData->isActive,
                'isFeatured' => $sectionData->isFeatured,
                'sortOrder' => $sectionData->sortOrder,
            ]);
            $sectionDto = $this->sectionRepository->update($sectionId, $updateSectionData);
            $existingSectionIds[] = $sectionId;
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
                $sectionId,
                $itemDataObject,
                $itemDataObject->sortOrder,
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
                    $childDataObject->sortOrder,
                    $sectionId,
                    $existingSectionIds,
                    $existingItemIds
                );
            }
        }

        return $sectionId;
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
        $itemDto = null;
        $itemId = null;

        if (!$isNewItem) {
            // Try to find existing item IN THIS MENU AND SECTION
            $itemDto = $this->itemRepository->findByIdInMenuAndSection($itemData->id, $menuId, $sectionId);
            
            if (!$itemDto) {
                // Check if it exists in a different section
                $itemInDifferentSection = $this->itemRepository->find($itemData->id);
                if ($itemInDifferentSection && $itemInDifferentSection->menuId === $menuId) {
                    // The item ID exists but in a different section
                    $isNewItem = true;
                } else {
                    // The item doesn't exist at all in this menu
                    $isNewItem = true;
                }
            } else {
                $itemId = $itemDto->id;
            }
        }

        // Use camelCase keys since SnakeCaseMapper will convert them to snake_case for validation
        // and then back to camelCase for the DTO properties
        $itemValues = [
            'menuId' => $menuId,
            'menuSectionId' => $sectionId,
            'itemId' => $itemData->itemId,
            'displayName' => $itemData->displayName,
            'displayDescription' => $itemData->displayDescription,
            'priceOverride' => $itemData->priceOverride,
            'isActive' => true,
            'isFeatured' => $itemData->isFeatured,
            'isRecommended' => $itemData->isRecommended,
            'isNew' => $itemData->isNew,
            'isSeasonal' => $itemData->isSeasonal,
            'sortOrder' => $itemData->sortOrder,
        ];

        if ($isNewItem) {
            // Check if this item_id already exists in THIS SPECIFIC SECTION
            $existingMenuItemInSection = $this->itemRepository->findByItemIdInMenuAndSection(
                $itemData->itemId, 
                $menuId, 
                $sectionId
            );
                
            if ($existingMenuItemInSection) {
                // Item already exists in this section, update it instead of creating duplicate
                $updateData = UpdateMenuItemData::from($itemValues);
                $updatedItem = $this->itemRepository->update($existingMenuItemInSection->id, $updateData);
                $existingItemIds[] = $updatedItem->id;
            } else {
                // Create new item in this section
                $createData = CreateMenuItemData::from($itemValues);
                $newItem = $this->itemRepository->create($createData);
                $existingItemIds[] = $newItem->id;
            }
        } else {
            // Update existing item
            $updateData = UpdateMenuItemData::from($itemValues);
            $updatedItem = $this->itemRepository->update($itemId, $updateData);
            $existingItemIds[] = $updatedItem->id;
        }
    }

    public function getMenuStructureForLocation(int $menuId, int $locationId): MenuStructureData
    {
        // Get base structure
        $structure = $this->getMenuStructure($menuId);

        // Apply location-specific overrides
        $locations = $this->locationRepository->getMenuLocations($menuId);
        $locationMenu = $locations->first(fn($loc) => $loc->locationId === $locationId);

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
        // Verify menu exists
        $this->menuRepository->findOrFail($menuId);
        
        return $this->locationRepository->assignToLocations($menuId, $locationIds);
    }

    public function removeFromLocation(int $menuId, int $locationId): bool
    {
        return $this->locationRepository->removeFromLocation($menuId, $locationId);
    }

    public function setPrimaryForLocation(int $menuId, int $locationId): bool
    {
        return $this->locationRepository->setPrimaryForLocation($menuId, $locationId);
    }

    public function validateMenuStructure(int $menuId): array
    {
        $errors = [];
        $warnings = [];

        $menu = $this->menuRepository->findWithRelations($menuId);

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
            $createData = new CreateMenuItemData(
                menuId: 0, // Will be set by repository based on section
                menuSectionId: $sectionId,
                itemId: $itemData['itemId'],
                priceOverride: $itemData['price'] ?? null,
                sortOrder: $itemData['sortOrder'] ?? 0,
                isActive: $itemData['isAvailable'] ?? true,
                isFeatured: $itemData['isFeatured'] ?? false,
            );
            
            $this->itemRepository->createInSection($sectionId, $createData);
        }
    }

    public function removeItemFromSection(int $sectionId, int $itemId): bool
    {
        return $this->itemRepository->deleteFromSection($sectionId, $itemId);
    }

    /**
     * Get menu items for a section.
     * 
     * Note: Item enrichment should be handled at the application layer
     * or through event-driven architecture to maintain module boundaries.
     * 
     * @param int $sectionId
     * @return array
     */
    public function getSectionItems(int $sectionId): array
    {
        $items = $this->itemRepository->findBySection($sectionId);
        return $items->toArray();
    }

    public function updateItemModifiers(int $itemId, array $modifiers): void
    {
        // This method should be moved to a ModifierService when the modifier module is implemented
        // For now, we'll throw an exception as modifiers should be handled by their own module
        // Parameters are kept for interface compatibility but not used
        unset($itemId, $modifiers);
        
        throw new \BadMethodCallException(
            'Modifier management should be handled by the Modifier module. ' .
            'This method will be removed once the Modifier module is implemented.'
        );
    }
    
    /**
     * Get menu item with details from item module
     * This method enriches menu items with details from the item module
     */
    public function getMenuItemWithDetails(int $menuItemId): ?MenuItemData
    {
        $menuItem = $this->itemRepository->find($menuItemId);
        
        if (!$menuItem) {
            return null;
        }
        
        // If item repository is available, enrich with item details
        if ($this->itemDetailsRepository) {
            $itemDetails = $this->itemDetailsRepository->getItemDetails($menuItem->itemId);
            
            if ($itemDetails) {
                // Return menu item with enriched details from repository
                $enrichedMenuItem = $this->itemRepository->find($menuItem->id);
                if ($enrichedMenuItem) {
                    return MenuItemData::fromModel($enrichedMenuItem, $itemDetails);
                }
            }
        }
        
        return $menuItem;
    }
    
    /**
     * Enrich sections with item details from the item module
     */
    private function enrichSectionsWithItemDetails(DataCollection $sections): DataCollection
    {
        // Collect all unique item IDs from all sections and their children
        $itemIds = $this->collectItemIdsFromSections($sections);
        
        if (empty($itemIds)) {
            return $sections;
        }
        
        // Batch fetch item details
        $itemDetailsMap = $this->itemDetailsRepository->getMultipleItemDetails($itemIds);
        
        if (empty($itemDetailsMap)) {
            return $sections;
        }
        
        // Enrich sections with item details - sections is already a DataCollection
        return $sections->map(function ($section) use ($itemDetailsMap) {
            return $this->enrichSectionWithItemDetails($section, $itemDetailsMap);
        });
    }
    
    /**
     * Collect all item IDs from sections and their children recursively
     */
    private function collectItemIdsFromSections(DataCollection $sections): array
    {
        $itemIds = [];
        
        foreach ($sections as $section) {
            // Resolve lazy-loaded items if needed
            $items = null;
            if (isset($section->items)) {
                $items = $section->items instanceof \Spatie\LaravelData\Lazy 
                    ? $section->items->resolve() 
                    : $section->items;
            }
            
            // Collect from section items
            if ($items) {
                foreach ($items as $item) {
                    $itemIds[] = $item->itemId;
                }
            }
            
            // Resolve lazy-loaded children if needed
            $children = null;
            if (isset($section->children)) {
                $children = $section->children instanceof \Spatie\LaravelData\Lazy 
                    ? $section->children->resolve() 
                    : $section->children;
            }
            
            // Collect from child sections recursively
            if ($children) {
                $childItemIds = $this->collectItemIdsFromSections($children);
                $itemIds = array_merge($itemIds, $childItemIds);
            }
        }
        
        return array_unique($itemIds);
    }
    
    /**
     * Enrich a single section with item details
     */
    private function enrichSectionWithItemDetails(object $section, array $itemDetailsMap): object
    {
        // Resolve and enrich items
        $enrichedItems = $section->items;
        if (isset($section->items)) {
            $items = $section->items instanceof \Spatie\LaravelData\Lazy 
                ? $section->items->resolve() 
                : $section->items;
                
            if ($items) {
                // items is already a DataCollection, just use map directly
                $enrichedItems = $items->map(function ($item) use ($itemDetailsMap) {
                    return $this->enrichMenuItemWithDetails($item, $itemDetailsMap);
                });
            }
        }
        
        // Resolve and enrich child sections recursively
        $enrichedChildren = $section->children;
        if (isset($section->children)) {
            $children = $section->children instanceof \Spatie\LaravelData\Lazy 
                ? $section->children->resolve() 
                : $section->children;
                
            if ($children) {
                // children is already a DataCollection, just use map directly
                $enrichedChildren = $children->map(function ($childSection) use ($itemDetailsMap) {
                    return $this->enrichSectionWithItemDetails($childSection, $itemDetailsMap);
                });
            }
        }
        
        // Return new section with enriched items and children
        return new \Colame\Menu\Data\MenuSectionWithItemsData(
            id: $section->id,
            menuId: $section->menuId,
            parentId: $section->parentId,
            name: $section->name,
            slug: $section->slug,
            description: $section->description,
            displayName: $section->displayName,
            isActive: $section->isActive,
            isFeatured: $section->isFeatured,
            sortOrder: $section->sortOrder,
            isAvailable: $section->isAvailable,
            items: $enrichedItems,
            children: $enrichedChildren,
            metadata: $section->metadata,
        );
    }
    
    /**
     * Enrich a single menu item with base item details
     */
    private function enrichMenuItemWithDetails(object $item, array $itemDetailsMap): MenuItemData
    {
        // If we have details for this item, create enriched version
        if (isset($itemDetailsMap[$item->itemId])) {
            $itemDetails = $itemDetailsMap[$item->itemId];
            
            // Handle both object and array formats
            if (is_array($itemDetails)) {
                $itemDetails = (object) $itemDetails;
            }
            
            return new MenuItemData(
                id: $item->id,
                menuId: $item->menuId,
                menuSectionId: $item->menuSectionId,
                itemId: $item->itemId,
                displayName: $item->displayName,
                displayDescription: $item->displayDescription,
                priceOverride: $item->priceOverride,
                isActive: $item->isActive,
                isFeatured: $item->isFeatured,
                isRecommended: $item->isRecommended,
                isNew: $item->isNew,
                isSeasonal: $item->isSeasonal,
                sortOrder: $item->sortOrder,
                preparationTimeOverride: $item->preparationTimeOverride,
                availableModifiers: $item->availableModifiers,
                dietaryLabels: $item->dietaryLabels,
                allergenInfo: $item->allergenInfo,
                calorieCount: $item->calorieCount,
                nutritionalInfo: $item->nutritionalInfo,
                imageUrl: $item->imageUrl,
                metadata: $item->metadata,
                createdAt: $item->createdAt,
                updatedAt: $item->updatedAt,
                baseItem: (object) [
                    'name' => $itemDetails->name ?? null,
                    'description' => $itemDetails->description ?? null,
                    'basePrice' => $itemDetails->basePrice ?? $itemDetails->base_price ?? null,
                    'preparationTime' => $itemDetails->preparationTime ?? $itemDetails->preparation_time ?? null,
                    'category' => $itemDetails->category ?? null,
                    'imageUrl' => $itemDetails->imageUrl ?? $itemDetails->image_url ?? null
                ],
            );
        }
        
        // Return original item if no details available
        return $item;
    }
}
