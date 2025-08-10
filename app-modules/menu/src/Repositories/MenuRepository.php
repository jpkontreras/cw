<?php

declare(strict_types=1);

namespace Colame\Menu\Repositories;

use App\Core\Data\PaginatedResourceData;
use App\Core\Traits\ValidatesPagination;
use Colame\Menu\Contracts\MenuRepositoryInterface;
use Colame\Menu\Data\MenuData;
use Colame\Menu\Data\MenuWithRelationsData;
use Colame\Menu\Data\CreateMenuData;
use Colame\Menu\Data\UpdateMenuData;
use Colame\Menu\Models\Menu;
use Spatie\LaravelData\DataCollection;

class MenuRepository implements MenuRepositoryInterface
{
    use ValidatesPagination;
    
    public function find(int $id): ?MenuData
    {
        $menu = Menu::find($id);
        return $menu ? MenuData::fromModel($menu) : null;
    }
    
    public function findWithRelations(int $id): ?MenuWithRelationsData
    {
        $menu = Menu::with([
            'sections' => function ($query) {
                $query->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->with(['activeItems.item']);
            },
            'availabilityRules', 
            'locations'
        ])->find($id);
        
        return $menu ? MenuWithRelationsData::fromModel($menu) : null;
    }
    
    public function findBySlug(string $slug): ?MenuData
    {
        $menu = Menu::where('slug', $slug)->first();
        return $menu ? MenuData::fromModel($menu) : null;
    }
    
    public function all(): DataCollection
    {
        $menus = Menu::orderBy('sort_order')->get();
        return MenuData::collect($menus, DataCollection::class);
    }
    
    public function getActive(): DataCollection
    {
        $menus = Menu::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return MenuData::collect($menus, DataCollection::class);
    }
    
    public function getByType(string $type): DataCollection
    {
        $menus = Menu::where('type', $type)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return MenuData::collect($menus, DataCollection::class);
    }
    
    public function getByLocation(int $locationId): DataCollection
    {
        $menus = Menu::whereHas('locations', function ($query) use ($locationId) {
            $query->where('location_id', $locationId)
                ->where('is_active', true);
        })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return MenuData::collect($menus, DataCollection::class);
    }
    
    public function getDefault(): ?MenuData
    {
        $menu = Menu::where('is_default', true)->first();
        return $menu ? MenuData::fromModel($menu) : null;
    }
    
    public function getCurrentlyAvailable(): DataCollection
    {
        $menus = Menu::where('is_active', true)
            ->get()
            ->filter(fn($menu) => $menu->isAvailable());
        
        return MenuData::collect($menus, DataCollection::class);
    }
    
    public function create(CreateMenuData $data): MenuData
    {
        $menu = Menu::create($data->toArray());
        return MenuData::fromModel($menu);
    }
    
    public function update(int $id, UpdateMenuData $data): MenuData
    {
        $menu = Menu::findOrFail($id);
        $menu->update($data->toArray());
        return MenuData::fromModel($menu);
    }
    
    public function delete(int $id): bool
    {
        $menu = Menu::findOrFail($id);
        return $menu->delete();
    }
    
    public function activate(int $id): bool
    {
        return Menu::where('id', $id)->update(['is_active' => true]) > 0;
    }
    
    public function deactivate(int $id): bool
    {
        return Menu::where('id', $id)->update(['is_active' => false]) > 0;
    }
    
    public function setAsDefault(int $id): bool
    {
        // First, unset any existing default
        Menu::where('is_default', true)->update(['is_default' => false]);
        
        // Then set the new default
        return Menu::where('id', $id)->update(['is_default' => true]) > 0;
    }
    
    public function clone(int $id, string $newName): MenuData
    {
        $originalMenu = Menu::with(['sections.items', 'availabilityRules'])
            ->findOrFail($id);
        
        // Clone the menu
        $newMenu = $originalMenu->replicate();
        $newMenu->name = $newName;
        $newMenu->slug = null; // Will be auto-generated
        $newMenu->is_default = false;
        $newMenu->save();
        
        // Clone sections
        foreach ($originalMenu->sections as $section) {
            $newSection = $section->replicate();
            $newSection->menu_id = $newMenu->id;
            $newSection->save();
            
            // Clone items in section
            foreach ($section->items as $item) {
                $newItem = $item->replicate();
                $newItem->menu_id = $newMenu->id;
                $newItem->menu_section_id = $newSection->id;
                $newItem->save();
            }
        }
        
        // Clone availability rules
        foreach ($originalMenu->availabilityRules as $rule) {
            $newRule = $rule->replicate();
            $newRule->menu_id = $newMenu->id;
            $newRule->save();
        }
        
        return MenuData::fromModel($newMenu);
    }
    
    public function findWithSectionsForStructure(int $id): ?MenuWithRelationsData
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
                              ->with('activeItems');
                        }, 
                        'activeItems'
                    ]);
            }
        ])->find($id);
        
        return $menu ? MenuWithRelationsData::fromModel($menu) : null;
    }
    
    public function findOrFail(int $id): MenuData
    {
        $menu = Menu::findOrFail($id);
        return MenuData::fromModel($menu);
    }
    
    public function findWithAvailabilityRules(int $id): ?MenuData
    {
        $menu = Menu::with('availabilityRules')->find($id);
        return $menu ? MenuData::fromModel($menu) : null;
    }
    
    public function getActiveByLocation(int $locationId): DataCollection
    {
        $menus = Menu::whereHas('locations', function ($query) use ($locationId) {
            $query->where('location_id', $locationId)
                ->where('is_active', true);
        })
        ->where('is_active', true)
        ->get();
        
        return MenuData::collect($menus, DataCollection::class);
    }
    
    public function isMenuActiveAtLocation(int $menuId, int $locationId): bool
    {
        return Menu::where('id', $menuId)
            ->whereHas('locations', function ($query) use ($locationId) {
                $query->where('location_id', $locationId)
                    ->where('is_active', true);
            })
            ->exists();
    }
    
    public function paginate(int $perPage = 15): PaginatedResourceData
    {
        $perPage = $this->validatePerPage($perPage);
        $paginator = Menu::orderBy('sort_order')->paginate($perPage);
        return PaginatedResourceData::fromPaginator($paginator, MenuData::class);
    }
    
    public function paginateActive(int $perPage = 15): PaginatedResourceData
    {
        $perPage = $this->validatePerPage($perPage);
        $paginator = Menu::where('is_active', true)
            ->orderBy('sort_order')
            ->paginate($perPage);
        return PaginatedResourceData::fromPaginator($paginator, MenuData::class);
    }
    
    public function paginateByLocation(int $locationId, int $perPage = 15): PaginatedResourceData
    {
        $perPage = $this->validatePerPage($perPage);
        $paginator = Menu::whereHas('locations', function ($query) use ($locationId) {
            $query->where('location_id', $locationId)
                ->where('is_active', true);
        })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->paginate($perPage);
        return PaginatedResourceData::fromPaginator($paginator, MenuData::class);
    }
}