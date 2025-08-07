<?php

declare(strict_types=1);

namespace Colame\Menu\Repositories;

use Colame\Menu\Contracts\MenuItemRepositoryInterface;
use Colame\Menu\Data\MenuItemData;
use Colame\Menu\Data\MenuItemWithModifiersData;
use Colame\Menu\Models\MenuItem;
use Spatie\LaravelData\DataCollection;

class MenuItemRepository implements MenuItemRepositoryInterface
{
    public function find(int $id): ?MenuItemData
    {
        $item = MenuItem::find($id);
        return $item ? MenuItemData::fromModel($item) : null;
    }
    
    public function findWithModifiers(int $id): ?MenuItemWithModifiersData
    {
        $item = MenuItem::with('modifiers')->find($id);
        return $item ? MenuItemWithModifiersData::fromModel($item) : null;
    }
    
    public function getByMenu(int $menuId): DataCollection
    {
        $items = MenuItem::where('menu_id', $menuId)
            ->orderBy('sort_order')
            ->get();
        return MenuItemData::collect($items, DataCollection::class);
    }
    
    public function getBySection(int $sectionId): DataCollection
    {
        $items = MenuItem::where('menu_section_id', $sectionId)
            ->orderBy('sort_order')
            ->get();
        return MenuItemData::collect($items, DataCollection::class);
    }
    
    public function getActiveBySection(int $sectionId): DataCollection
    {
        $items = MenuItem::where('menu_section_id', $sectionId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return MenuItemData::collect($items, DataCollection::class);
    }
    
    public function getFeaturedByMenu(int $menuId): DataCollection
    {
        $items = MenuItem::where('menu_id', $menuId)
            ->where('is_featured', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return MenuItemData::collect($items, DataCollection::class);
    }
    
    public function findByMenuAndItem(int $menuId, int $itemId): ?MenuItemData
    {
        $item = MenuItem::where('menu_id', $menuId)
            ->where('item_id', $itemId)
            ->first();
        
        return $item ? MenuItemData::fromModel($item) : null;
    }
    
    public function addToSection(int $sectionId, int $itemId, array $data = []): MenuItemData
    {
        $section = \Colame\Menu\Models\MenuSection::findOrFail($sectionId);
        
        $itemData = array_merge([
            'menu_id' => $section->menu_id,
            'menu_section_id' => $sectionId,
            'item_id' => $itemId,
        ], $data);
        
        $item = MenuItem::create($itemData);
        return MenuItemData::fromModel($item);
    }
    
    public function update(int $id, array $data): MenuItemData
    {
        $item = MenuItem::findOrFail($id);
        $item->update($data);
        return MenuItemData::fromModel($item);
    }
    
    public function removeFromMenu(int $menuId, int $itemId): bool
    {
        return MenuItem::where('menu_id', $menuId)
            ->where('item_id', $itemId)
            ->delete() > 0;
    }
    
    public function moveToSection(int $id, int $sectionId): bool
    {
        $item = MenuItem::findOrFail($id);
        $section = \Colame\Menu\Models\MenuSection::findOrFail($sectionId);
        
        // Ensure the section belongs to the same menu
        if ($item->menu_id !== $section->menu_id) {
            return false;
        }
        
        $item->menu_section_id = $sectionId;
        return $item->save();
    }
    
    public function updateOrder(int $id, int $order): bool
    {
        return MenuItem::where('id', $id)
            ->update(['sort_order' => $order]) > 0;
    }
    
    public function bulkUpdateOrder(array $orders): bool
    {
        foreach ($orders as $id => $order) {
            MenuItem::where('id', $id)->update(['sort_order' => $order]);
        }
        
        return true;
    }
    
    public function setFeatured(int $id, bool $featured = true): bool
    {
        return MenuItem::where('id', $id)
            ->update(['is_featured' => $featured]) > 0;
    }
    
    public function setAvailability(int $id, bool $available): bool
    {
        return MenuItem::where('id', $id)
            ->update(['is_active' => $available]) > 0;
    }
}