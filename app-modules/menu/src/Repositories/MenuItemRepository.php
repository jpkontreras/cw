<?php

declare(strict_types=1);

namespace Colame\Menu\Repositories;

use Colame\Menu\Contracts\MenuItemRepositoryInterface;
use Colame\Menu\Data\MenuItemData;
use Colame\Menu\Data\MenuItemWithModifiersData;
use Colame\Menu\Data\CreateMenuItemData;
use Colame\Menu\Data\UpdateMenuItemData;
use Colame\Menu\Models\MenuItem;
use Spatie\LaravelData\DataCollection;

class MenuItemRepository implements MenuItemRepositoryInterface
{
    public function find(int $id): ?MenuItemData
    {
        $item = MenuItem::find($id);
        return $item ? MenuItemData::fromModel($item, null) : null;
    }
    
    public function findWithModifiers(int $id): ?MenuItemWithModifiersData
    {
        $item = MenuItem::with('modifiers')->find($id);
        return $item ? MenuItemWithModifiersData::fromModel($item) : null;
    }
    
    public function getByMenu(int $menuId): DataCollection
    {
        $items = MenuItem::whereHas('section', function ($q) use ($menuId) {
            $q->where('menu_id', $menuId);
        })
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
        $items = MenuItem::whereHas('section', function ($q) use ($menuId) {
            $q->where('menu_id', $menuId);
        })
            ->where('is_featured', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return MenuItemData::collect($items, DataCollection::class);
    }
    
    public function findByMenuAndItem(int $menuId, int $itemId): ?MenuItemData
    {
        $item = MenuItem::whereHas('section', function ($q) use ($menuId) {
            $q->where('menu_id', $menuId);
        })
            ->where('item_id', $itemId)
            ->first();
        
        return $item ? MenuItemData::fromModel($item, null) : null;
    }
    
    public function addToSection(int $sectionId, int $itemId, ?CreateMenuItemData $data = null): MenuItemData
    {
        $itemData = [
            'menu_section_id' => $sectionId,
            'item_id' => $itemId,
        ];
        
        if ($data !== null) {
            $itemData = array_merge($itemData, $data->toArray());
        }
        
        $item = MenuItem::create($itemData);
        return MenuItemData::fromModel($item, null);
    }
    
    public function update(int $id, UpdateMenuItemData $data): MenuItemData
    {
        $item = MenuItem::findOrFail($id);
        $item->update($data->toArray());
        return MenuItemData::fromModel($item, null);
    }
    
    public function removeFromMenu(int $menuId, int $itemId): bool
    {
        return MenuItem::whereHas('section', function ($q) use ($menuId) {
            $q->where('menu_id', $menuId);
        })
            ->where('item_id', $itemId)
            ->delete() > 0;
    }
    
    public function moveToSection(int $id, int $sectionId): bool
    {
        $item = MenuItem::with('section')->findOrFail($id);
        $newSection = \Colame\Menu\Models\MenuSection::findOrFail($sectionId);
        
        // Ensure the section belongs to the same menu
        if ($item->section->menu_id !== $newSection->menu_id) {
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
    
    public function findByIdInMenuAndSection(int $id, int $menuId, int $sectionId): ?MenuItemData
    {
        $item = MenuItem::where('id', $id)
            ->where('menu_section_id', $sectionId)
            ->whereHas('section', function ($q) use ($menuId) {
                $q->where('menu_id', $menuId);
            })
            ->first();
        return $item ? MenuItemData::fromModel($item, null) : null;
    }
    
    public function findByItemIdInMenuAndSection(int $itemId, int $menuId, int $sectionId): ?MenuItemData
    {
        $item = MenuItem::where('item_id', $itemId)
            ->where('menu_section_id', $sectionId)
            ->whereHas('section', function ($q) use ($menuId) {
                $q->where('menu_id', $menuId);
            })
            ->first();
        return $item ? MenuItemData::fromModel($item, null) : null;
    }
    
    public function create(CreateMenuItemData $data): MenuItemData
    {
        $item = MenuItem::create($data->toArray());
        return MenuItemData::fromModel($item, null);
    }
    
    public function delete(int $id): bool
    {
        $item = MenuItem::findOrFail($id);
        return $item->delete();
    }
    
    public function updateOrCreate(array $attributes, array $values): MenuItemData
    {
        $item = MenuItem::updateOrCreate($attributes, $values);
        return MenuItemData::fromModel($item, null);
    }
    
    public function deleteExcept(int $menuId, array $exceptIds): int
    {
        $query = MenuItem::whereHas('section', function ($q) use ($menuId) {
            $q->where('menu_id', $menuId);
        });
        
        if (!empty($exceptIds)) {
            $query->whereNotIn('id', $exceptIds);
        }
        
        return $query->delete();
    }
    
    public function createInSection(int $sectionId, CreateMenuItemData $data): MenuItemData
    {
        $dataArray = $data->toArray();
        $dataArray['menu_section_id'] = $sectionId;
        
        $item = MenuItem::create($dataArray);
        return MenuItemData::fromModel($item, null);
    }
    
    public function deleteFromSection(int $sectionId, int $itemId): bool
    {
        return MenuItem::where('menu_section_id', $sectionId)
            ->where('item_id', $itemId)
            ->delete() > 0;
    }
    
    public function findBySection(int $sectionId): DataCollection
    {
        $items = MenuItem::where('menu_section_id', $sectionId)
            ->orderBy('sort_order')
            ->get();
        
        return MenuItemData::collect($items, DataCollection::class);
    }
    
    public function getByMenuWithRelations(int $menuId, array $relations = []): DataCollection
    {
        $query = MenuItem::whereHas('section', function ($q) use ($menuId) {
            $q->where('menu_id', $menuId);
        })
            ->orderBy('sort_order');
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $items = $query->get();
        return MenuItemData::collect($items, DataCollection::class);
    }
    
    public function getFeaturedAvailableByMenu(int $menuId): DataCollection
    {
        $items = MenuItem::whereHas('section', function ($q) use ($menuId) {
            $q->where('menu_id', $menuId);
        })
            ->where('is_featured', true)
            ->where('is_active', true)
            ->with(['section', 'modifiers'])
            ->orderBy('sort_order')
            ->get();
        
        return MenuItemData::collect($items, DataCollection::class);
    }
    
    public function sectionExistsInMenu(int $sectionId, int $menuId): bool
    {
        return \Colame\Menu\Models\MenuSection::where('id', $sectionId)
            ->where('menu_id', $menuId)
            ->exists();
    }
    
    public function findByIdInMenu(int $id, int $menuId): ?MenuItemData
    {
        $item = MenuItem::where('id', $id)
            ->whereHas('section', function ($q) use ($menuId) {
                $q->where('menu_id', $menuId);
            })
            ->first();
        
        return $item ? MenuItemData::fromModel($item, null) : null;
    }
    
    public function deleteByIdInMenu(int $id, int $menuId): bool
    {
        $item = MenuItem::where('id', $id)
            ->whereHas('section', function ($q) use ($menuId) {
                $q->where('menu_id', $menuId);
            })
            ->first();
        
        return $item ? $item->delete() : false;
    }
    
    public function getBySectionWithRelations(int $sectionId, int $menuId, array $relations = []): DataCollection
    {
        $query = MenuItem::where('menu_section_id', $sectionId)
            ->whereHas('section', function ($q) use ($menuId) {
                $q->where('menu_id', $menuId);
            })
            ->orderBy('sort_order');
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $items = $query->get();
        return MenuItemData::collect($items, DataCollection::class);
    }
    
    public function findWithRelations(int $id, int $menuId, int $sectionId): ?MenuItemData
    {
        $item = MenuItem::where('id', $id)
            ->where('menu_section_id', $sectionId)
            ->whereHas('section', function ($q) use ($menuId) {
                $q->where('menu_id', $menuId);
            })
            ->with(['modifiers'])
            ->first();
        
        return $item ? MenuItemData::fromModel($item, null) : null;
    }
    
    public function toggleAvailability(int $id, int $menuId, int $sectionId): ?MenuItemData
    {
        $item = MenuItem::where('id', $id)
            ->where('menu_section_id', $sectionId)
            ->whereHas('section', function ($q) use ($menuId) {
                $q->where('menu_id', $menuId);
            })
            ->first();
        
        if (!$item) {
            return null;
        }
        
        $item->is_active = !$item->is_active;
        $item->save();
        
        return MenuItemData::fromModel($item, null);
    }
    
    public function bulkUpdateAvailability(array $items, int $menuId): int
    {
        $updatedCount = 0;
        
        foreach ($items as $itemData) {
            if (!isset($itemData['id']) || !isset($itemData['isAvailable'])) {
                continue;
            }
            
            $updated = MenuItem::where('id', $itemData['id'])
                ->whereHas('section', function ($q) use ($menuId) {
                    $q->where('menu_id', $menuId);
                })
                ->update(['is_active' => $itemData['isAvailable']]);
            
            if ($updated > 0) {
                $updatedCount++;
            }
        }
        
        return $updatedCount;
    }
    
    public function getPopularByMenu(int $menuId, int $limit = 10): DataCollection
    {
        $items = MenuItem::whereHas('section', function ($q) use ($menuId) {
            $q->where('menu_id', $menuId);
        })
            ->where('is_active', true)
            ->where('is_featured', true)
            ->with(['section', 'modifiers'])
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
        
        return MenuItemData::collect($items, DataCollection::class);
    }
    
    public function searchInMenu(int $menuId, string $query, ?int $sectionId = null): DataCollection
    {
        $items = MenuItem::whereHas('section', function ($q) use ($menuId) {
            $q->where('menu_id', $menuId);
        })
            ->when($sectionId, function ($q) use ($sectionId) {
                $q->where('menu_section_id', $sectionId);
            })
            ->where(function ($q) use ($query) {
                $q->where('display_name', 'like', "%{$query}%")
                    ->orWhere('display_description', 'like', "%{$query}%");
            })
            ->with(['section', 'modifiers'])
            ->orderBy('sort_order')
            ->get();
        
        return MenuItemData::collect($items, DataCollection::class);
    }
    
    public function isActive(int $id): bool
    {
        $item = MenuItem::find($id);
        return $item ? $item->is_active : false;
    }
}