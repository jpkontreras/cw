<?php

declare(strict_types=1);

namespace Colame\Menu\Services;

use Colame\Menu\Contracts\MenuVersioningInterface;
use Colame\Menu\Data\MenuVersionData;
use Colame\Menu\Models\Menu;
use Colame\Menu\Models\MenuVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;

class MenuVersioningService implements MenuVersioningInterface
{
    public function createVersion(int $menuId, string $changeType, ?string $description = null): MenuVersionData
    {
        $menu = Menu::with([
            'sections.items.modifiers',
            'availabilityRules',
            'locations'
        ])->findOrFail($menuId);
        
        // Create snapshot of current menu structure
        $snapshot = $this->createSnapshot($menu);
        
        $version = MenuVersion::create([
            'menu_id' => $menuId,
            'change_type' => $changeType,
            'change_description' => $description,
            'snapshot' => $snapshot,
            'created_by' => Auth::id(),
        ]);
        
        return MenuVersionData::fromModel($version);
    }
    
    public function getVersions(int $menuId): DataCollection
    {
        $versions = MenuVersion::where('menu_id', $menuId)
            ->orderBy('version_number', 'desc')
            ->get();
        
        return MenuVersionData::collect($versions, DataCollection::class);
    }
    
    public function getVersion(int $versionId): ?MenuVersionData
    {
        $version = MenuVersion::find($versionId);
        return $version ? MenuVersionData::fromModel($version) : null;
    }
    
    public function getLatestVersion(int $menuId): ?MenuVersionData
    {
        $version = MenuVersion::where('menu_id', $menuId)
            ->orderBy('version_number', 'desc')
            ->first();
        
        return $version ? MenuVersionData::fromModel($version) : null;
    }
    
    public function getPublishedVersion(int $menuId): ?MenuVersionData
    {
        $version = MenuVersion::where('menu_id', $menuId)
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc')
            ->first();
        
        return $version ? MenuVersionData::fromModel($version) : null;
    }
    
    public function publishVersion(int $versionId): bool
    {
        $version = MenuVersion::findOrFail($versionId);
        
        // Unpublish any currently published version
        MenuVersion::where('menu_id', $version->menu_id)
            ->whereNotNull('published_at')
            ->update(['published_at' => null]);
        
        $version->publish();
        
        return true;
    }
    
    public function archiveVersion(int $versionId): bool
    {
        $version = MenuVersion::findOrFail($versionId);
        $version->archive();
        
        return true;
    }
    
    public function restoreFromVersion(int $versionId): bool
    {
        return DB::transaction(function () use ($versionId) {
            $version = MenuVersion::findOrFail($versionId);
            $snapshot = $version->snapshot;
            
            $menu = Menu::findOrFail($version->menu_id);
            
            // Delete current menu structure
            $menu->sections()->delete();
            $menu->availabilityRules()->delete();
            
            // Restore menu properties
            $menu->update([
                'name' => $snapshot['name'] ?? $menu->name,
                'description' => $snapshot['description'] ?? $menu->description,
                'type' => $snapshot['type'] ?? $menu->type,
                'is_active' => $snapshot['is_active'] ?? $menu->is_active,
                'metadata' => $snapshot['metadata'] ?? $menu->metadata,
            ]);
            
            // Restore sections and items
            if (isset($snapshot['sections'])) {
                foreach ($snapshot['sections'] as $sectionData) {
                    $section = $menu->sections()->create([
                        'name' => $sectionData['name'],
                        'slug' => $sectionData['slug'],
                        'description' => $sectionData['description'] ?? null,
                        'is_active' => $sectionData['is_active'] ?? true,
                        'sort_order' => $sectionData['sort_order'] ?? 0,
                    ]);
                    
                    // Restore items in section
                    if (isset($sectionData['items'])) {
                        foreach ($sectionData['items'] as $itemData) {
                            $item = $section->items()->create([
                                'menu_id' => $menu->id,
                                'item_id' => $itemData['item_id'],
                                'display_name' => $itemData['display_name'] ?? null,
                                'display_description' => $itemData['display_description'] ?? null,
                                'price_override' => $itemData['price_override'] ?? null,
                                'is_active' => $itemData['is_active'] ?? true,
                                'is_featured' => $itemData['is_featured'] ?? false,
                                'sort_order' => $itemData['sort_order'] ?? 0,
                            ]);
                            
                            // Restore modifiers
                            if (isset($itemData['modifiers'])) {
                                foreach ($itemData['modifiers'] as $modifierData) {
                                    $item->modifiers()->create($modifierData);
                                }
                            }
                        }
                    }
                }
            }
            
            // Restore availability rules
            if (isset($snapshot['availability_rules'])) {
                foreach ($snapshot['availability_rules'] as $ruleData) {
                    $menu->availabilityRules()->create($ruleData);
                }
            }
            
            // Create a new version to track the restoration
            $this->createVersion(
                $menu->id,
                'updated',
                "Restored from version {$version->version_number}"
            );
            
            return true;
        });
    }
    
    public function compareVersions(int $versionId1, int $versionId2): array
    {
        $version1 = MenuVersion::findOrFail($versionId1);
        $version2 = MenuVersion::findOrFail($versionId2);
        
        if ($version1->menu_id !== $version2->menu_id) {
            throw new \InvalidArgumentException("Versions must be from the same menu");
        }
        
        $snapshot1 = $version1->snapshot;
        $snapshot2 = $version2->snapshot;
        
        $differences = [
            'version1' => [
                'id' => $version1->id,
                'number' => $version1->version_number,
                'created_at' => $version1->created_at,
            ],
            'version2' => [
                'id' => $version2->id,
                'number' => $version2->version_number,
                'created_at' => $version2->created_at,
            ],
            'changes' => [],
        ];
        
        // Compare basic properties
        $properties = ['name', 'description', 'type', 'is_active'];
        foreach ($properties as $prop) {
            if (($snapshot1[$prop] ?? null) !== ($snapshot2[$prop] ?? null)) {
                $differences['changes'][$prop] = [
                    'old' => $snapshot1[$prop] ?? null,
                    'new' => $snapshot2[$prop] ?? null,
                ];
            }
        }
        
        // Compare sections count
        $sections1Count = count($snapshot1['sections'] ?? []);
        $sections2Count = count($snapshot2['sections'] ?? []);
        
        if ($sections1Count !== $sections2Count) {
            $differences['changes']['sections_count'] = [
                'old' => $sections1Count,
                'new' => $sections2Count,
            ];
        }
        
        // Compare items count
        $items1Count = $this->countItemsInSnapshot($snapshot1);
        $items2Count = $this->countItemsInSnapshot($snapshot2);
        
        if ($items1Count !== $items2Count) {
            $differences['changes']['items_count'] = [
                'old' => $items1Count,
                'new' => $items2Count,
            ];
        }
        
        return $differences;
    }
    
    public function pruneOldVersions(int $menuId, int $keepCount = 10): int
    {
        // Get versions to delete (keep the most recent ones)
        $versionsToDelete = MenuVersion::where('menu_id', $menuId)
            ->orderBy('version_number', 'desc')
            ->skip($keepCount)
            ->pluck('id');
        
        // Don't delete published versions
        $versionsToDelete = MenuVersion::whereIn('id', $versionsToDelete)
            ->whereNull('published_at')
            ->pluck('id');
        
        $deletedCount = MenuVersion::whereIn('id', $versionsToDelete)->delete();
        
        return $deletedCount;
    }
    
    private function createSnapshot(Menu $menu): array
    {
        $snapshot = [
            'name' => $menu->name,
            'slug' => $menu->slug,
            'description' => $menu->description,
            'type' => $menu->type,
            'is_active' => $menu->is_active,
            'is_default' => $menu->is_default,
            'metadata' => $menu->metadata,
            'sections' => [],
            'availability_rules' => [],
        ];
        
        // Add sections with items
        foreach ($menu->sections as $section) {
            $sectionData = [
                'name' => $section->name,
                'slug' => $section->slug,
                'description' => $section->description,
                'parent_id' => $section->parent_id,
                'is_active' => $section->is_active,
                'is_featured' => $section->is_featured,
                'sort_order' => $section->sort_order,
                'items' => [],
            ];
            
            // Add items
            foreach ($section->items as $item) {
                $itemData = [
                    'item_id' => $item->item_id,
                    'display_name' => $item->display_name,
                    'display_description' => $item->display_description,
                    'price_override' => $item->price_override,
                    'is_active' => $item->is_active,
                    'is_featured' => $item->is_featured,
                    'is_recommended' => $item->is_recommended,
                    'is_new' => $item->is_new,
                    'is_seasonal' => $item->is_seasonal,
                    'sort_order' => $item->sort_order,
                    'modifiers' => [],
                ];
                
                // Add modifiers
                foreach ($item->modifiers as $modifier) {
                    $itemData['modifiers'][] = [
                        'modifier_group_id' => $modifier->modifier_group_id,
                        'modifier_id' => $modifier->modifier_id,
                        'is_required' => $modifier->is_required,
                        'is_available' => $modifier->is_available,
                        'price_override' => $modifier->price_override,
                    ];
                }
                
                $sectionData['items'][] = $itemData;
            }
            
            $snapshot['sections'][] = $sectionData;
        }
        
        // Add availability rules
        foreach ($menu->availabilityRules as $rule) {
            $snapshot['availability_rules'][] = [
                'rule_type' => $rule->rule_type,
                'days_of_week' => $rule->days_of_week,
                'start_time' => $rule->start_time,
                'end_time' => $rule->end_time,
                'start_date' => $rule->start_date,
                'end_date' => $rule->end_date,
                'priority' => $rule->priority,
            ];
        }
        
        return $snapshot;
    }
    
    private function countItemsInSnapshot(array $snapshot): int
    {
        $count = 0;
        
        foreach ($snapshot['sections'] ?? [] as $section) {
            $count += count($section['items'] ?? []);
        }
        
        return $count;
    }
}