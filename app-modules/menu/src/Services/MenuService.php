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
                    ->with(['children' => function ($q) {
                        $q->where('is_active', true)->orderBy('sort_order');
                    }, 'activeItems']);
            }
        ])->findOrFail($id);
        
        $sections = new DataCollection(MenuSectionWithItemsData::class, []);
        
        foreach ($menu->sections as $section) {
            $sections->push(MenuSectionWithItemsData::fromModel($section));
        }
        
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
        }
        
        // Check for duplicate items
        $itemIds = [];
        foreach ($menu->sections as $section) {
            foreach ($section->items as $item) {
                if (in_array($item->item_id, $itemIds)) {
                    $warnings[] = "Item ID {$item->item_id} appears in multiple sections";
                }
                $itemIds[] = $item->item_id;
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