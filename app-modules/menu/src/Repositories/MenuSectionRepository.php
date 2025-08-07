<?php

declare(strict_types=1);

namespace Colame\Menu\Repositories;

use Colame\Menu\Contracts\MenuSectionRepositoryInterface;
use Colame\Menu\Data\MenuSectionData;
use Colame\Menu\Data\MenuSectionWithItemsData;
use Colame\Menu\Models\MenuSection;
use Spatie\LaravelData\DataCollection;

class MenuSectionRepository implements MenuSectionRepositoryInterface
{
    public function find(int $id): ?MenuSectionData
    {
        $section = MenuSection::find($id);
        return $section ? MenuSectionData::fromModel($section) : null;
    }
    
    public function findWithItems(int $id): ?MenuSectionWithItemsData
    {
        $section = MenuSection::with(['items', 'children.items'])
            ->find($id);
        
        return $section ? MenuSectionWithItemsData::fromModel($section) : null;
    }
    
    public function getByMenu(int $menuId): DataCollection
    {
        $sections = MenuSection::where('menu_id', $menuId)
            ->orderBy('sort_order')
            ->get();
        return MenuSectionData::collect($sections, DataCollection::class);
    }
    
    public function getRootSectionsByMenu(int $menuId): DataCollection
    {
        $sections = MenuSection::where('menu_id', $menuId)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();
        return MenuSectionData::collect($sections, DataCollection::class);
    }
    
    public function getChildren(int $parentId): DataCollection
    {
        $sections = MenuSection::where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->get();
        return MenuSectionData::collect($sections, DataCollection::class);
    }
    
    public function getActiveSectionsByMenu(int $menuId): DataCollection
    {
        $sections = MenuSection::where('menu_id', $menuId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return MenuSectionData::collect($sections, DataCollection::class);
    }
    
    public function create(array $data): MenuSectionData
    {
        $section = MenuSection::create($data);
        return MenuSectionData::fromModel($section);
    }
    
    public function update(int $id, array $data): MenuSectionData
    {
        $section = MenuSection::findOrFail($id);
        $section->update($data);
        return MenuSectionData::fromModel($section);
    }
    
    public function delete(int $id): bool
    {
        $section = MenuSection::findOrFail($id);
        
        // Delete all child sections first
        MenuSection::where('parent_id', $id)->delete();
        
        return $section->delete();
    }
    
    public function moveToParent(int $id, ?int $parentId): bool
    {
        $section = MenuSection::findOrFail($id);
        
        // Prevent moving to self or descendant
        if ($parentId) {
            if ($id === $parentId || $this->isDescendant($id, $parentId)) {
                return false;
            }
        }
        
        $section->parent_id = $parentId;
        return $section->save();
    }
    
    public function updateOrder(int $id, int $order): bool
    {
        return MenuSection::where('id', $id)
            ->update(['sort_order' => $order]) > 0;
    }
    
    public function bulkUpdateOrder(array $orders): bool
    {
        foreach ($orders as $id => $order) {
            MenuSection::where('id', $id)->update(['sort_order' => $order]);
        }
        
        return true;
    }
    
    private function isDescendant(int $parentId, int $possibleDescendantId): bool
    {
        $section = MenuSection::find($possibleDescendantId);
        
        while ($section && $section->parent_id) {
            if ($section->parent_id === $parentId) {
                return true;
            }
            $section = MenuSection::find($section->parent_id);
        }
        
        return false;
    }
}