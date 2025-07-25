<?php

declare(strict_types=1);

namespace Colame\Item\Services;

use App\Core\Contracts\FeatureFlagInterface;
use App\Core\Services\BaseService;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Contracts\ItemServiceInterface;
use Colame\Item\Contracts\ItemVariantRepositoryInterface;
use Colame\Item\Contracts\ItemModifierRepositoryInterface;
use Colame\Item\Contracts\ItemPricingRepositoryInterface;
use Colame\Item\Data\CreateItemData;
use Colame\Item\Data\ItemData;
use Colame\Item\Data\ItemWithRelationsData;
use Colame\Item\Data\UpdateItemData;
use Colame\Item\Exceptions\ItemNotFoundException;
use Colame\Item\Exceptions\ItemNotAvailableException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Item service implementation
 */
class ItemService extends BaseService implements ItemServiceInterface
{
    public function __construct(
        FeatureFlagInterface $features,
        private ItemRepositoryInterface $itemRepository,
        private ItemVariantRepositoryInterface $variantRepository,
        private ItemModifierRepositoryInterface $modifierRepository,
        private ItemPricingRepositoryInterface $pricingRepository,
    ) {
        parent::__construct($features);
    }

    /**
     * Create a new item
     */
    public function createItem(CreateItemData $data): ItemData
    {
        $this->logAction('Creating item', ['name' => $data->name, 'sku' => $data->sku]);

        return DB::transaction(function () use ($data) {
            // Create the base item
            $itemData = $this->itemRepository->create($data->toArray());

            // Create location-specific pricing if provided
            if ($data->locationPricing && $this->isFeatureEnabled('item.location_pricing')) {
                foreach ($data->locationPricing as $pricing) {
                    $this->pricingRepository->upsert(
                        $itemData->id,
                        $pricing['location_id'],
                        $pricing['price'],
                        $pricing['metadata'] ?? []
                    );
                }
            }

            // Create variants if provided
            if ($data->variants && $data->type === 'variant' && $this->isFeatureEnabled('item.variants')) {
                foreach ($data->variants as $variant) {
                    $this->variantRepository->create(array_merge($variant, [
                        'item_id' => $itemData->id,
                    ]));
                }
            }

            // Create modifier groups if provided
            if ($data->modifierGroups && $this->isFeatureEnabled('item.modifiers')) {
                foreach ($data->modifierGroups as $groupData) {
                    $group = $this->modifierRepository->createGroup([
                        'name' => $groupData['name'],
                        'description' => $groupData['description'] ?? null,
                        'type' => $groupData['type'],
                        'is_required' => $groupData['required'] ?? false,
                        'min_selections' => $groupData['min_selections'] ?? null,
                        'max_selections' => $groupData['max_selections'] ?? null,
                    ]);

                    // Attach group to item
                    DB::table('item_modifier_group_items')->insert([
                        'item_id' => $itemData->id,
                        'modifier_group_id' => $group->id,
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Create modifiers in the group
                    if (isset($groupData['modifiers'])) {
                        foreach ($groupData['modifiers'] as $modifierData) {
                            $this->modifierRepository->create(array_merge($modifierData, [
                                'group_id' => $group->id,
                            ]));
                        }
                    }
                }
            }

            $this->logAction('Item created successfully', ['itemId' => $itemData->id]);

            return $itemData;
        });
    }

    /**
     * Update an existing item
     */
    public function updateItem(int $id, UpdateItemData $data): ItemData
    {
        $this->logAction('Updating item', ['itemId' => $id]);

        $item = $this->itemRepository->find($id);
        if (!$item) {
            throw new ItemNotFoundException("Item with ID {$id} not found");
        }

        $updatedItem = $this->itemRepository->update($id, $data->toArray());
        
        if (!$updatedItem) {
            throw new ItemNotFoundException("Failed to update item with ID {$id}");
        }

        $this->logAction('Item updated successfully', ['itemId' => $id]);

        return $updatedItem;
    }

    /**
     * Delete an item
     */
    public function deleteItem(int $id): bool
    {
        $this->logAction('Deleting item', ['itemId' => $id]);

        $item = $this->itemRepository->find($id);
        if (!$item) {
            throw new ItemNotFoundException("Item with ID {$id} not found");
        }

        $result = $this->itemRepository->delete($id);

        $this->logAction('Item deleted successfully', ['itemId' => $id]);

        return $result;
    }

    /**
     * Get item by ID
     */
    public function getItem(int $id): ItemData
    {
        $item = $this->itemRepository->find($id);
        
        if (!$item) {
            throw new ItemNotFoundException("Item with ID {$id} not found");
        }

        return $item;
    }

    /**
     * Get item with all relations
     */
    public function getItemWithRelations(int $id): ItemWithRelationsData
    {
        $item = $this->itemRepository->findWithRelations($id);
        
        if (!$item) {
            throw new ItemNotFoundException("Item with ID {$id} not found");
        }

        return $item;
    }

    /**
     * Get all items
     */
    public function getItems(array $filters = []): Collection
    {
        if (isset($filters['active']) && $filters['active']) {
            return $this->itemRepository->getActive();
        }

        if (isset($filters['category_id'])) {
            return $this->itemRepository->getByCategory($filters['category_id']);
        }

        if (isset($filters['location_id'])) {
            return $this->itemRepository->getByLocation($filters['location_id']);
        }

        return $this->itemRepository->all();
    }

    /**
     * Search items
     */
    public function searchItems(string $query): Collection
    {
        if (!$this->isFeatureEnabled('item.advanced_search')) {
            // Basic search
            return $this->itemRepository->search($query);
        }

        // Advanced search would include more filters and options
        return $this->itemRepository->search($query);
    }

    /**
     * Check item availability
     */
    public function checkAvailability(int $id, int $quantity, ?int $locationId = null): bool
    {
        $available = $this->itemRepository->checkAvailability($id, $quantity);

        if (!$available) {
            return false;
        }

        // Additional location-based availability checks could go here
        if ($locationId && $this->isFeatureEnabled('item.location_pricing')) {
            // Check if item is available at specific location
            $pricing = $this->pricingRepository->findByItemAndLocation($id, $locationId);
            if (!$pricing) {
                return false; // No pricing means not available at this location
            }
        }

        return true;
    }

    /**
     * Get item price
     */
    public function getPrice(int $id, ?int $locationId = null): float
    {
        return $this->itemRepository->getCurrentPrice($id, $locationId);
    }

    /**
     * Import items from CSV/Excel
     */
    public function importItems(string $filePath, array $options = []): array
    {
        if (!$this->isFeatureEnabled('item.import_export')) {
            throw new \RuntimeException('Import/export feature is not enabled');
        }

        $this->logAction('Importing items', ['file' => $filePath]);

        // TODO: Implement import logic
        return [
            'total' => 0,
            'imported' => 0,
            'errors' => [],
        ];
    }

    /**
     * Export items to CSV/Excel
     */
    public function exportItems(array $filters = [], string $format = 'csv'): string
    {
        if (!$this->isFeatureEnabled('item.import_export')) {
            throw new \RuntimeException('Import/export feature is not enabled');
        }

        $this->logAction('Exporting items', ['format' => $format, 'filters' => $filters]);

        // TODO: Implement export logic
        return '/tmp/items_export.' . $format;
    }
}