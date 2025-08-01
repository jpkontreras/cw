<?php

namespace Colame\Item\Services;

use App\Core\Services\BaseService;
use App\Core\Contracts\FeatureFlagInterface;
use Colame\Item\Contracts\ItemServiceInterface;
use Colame\Item\Services\ModifierService;
use Colame\Item\Services\PricingService;
use Colame\Item\Services\InventoryService;
use Colame\Item\Services\RecipeService;
use Colame\Item\Exceptions\ImportException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use League\Csv\Reader;
use League\Csv\Statement;

class ImportService extends BaseService
{
    private array $importResults = [
        'success' => 0,
        'failed' => 0,
        'errors' => [],
        'warnings' => [],
    ];
    
    public function __construct(
        private readonly ItemServiceInterface $itemService,
        private readonly ModifierService $modifierService,
        private readonly PricingService $pricingService,
        private readonly InventoryService $inventoryService,
        private readonly RecipeService $recipeService,
        FeatureFlagInterface $features,
    ) {
        parent::__construct($features);
    }
    
    /**
     * Import items from CSV file
     */
    public function importItemsFromCsv(string $filePath, array $options = []): array
    {
        $this->authorize('item.import');
        $this->resetResults();
        
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $records = Statement::create()->process($csv);
            
            DB::beginTransaction();
            
            foreach ($records as $offset => $record) {
                $this->processItemRecord($record, $offset + 2, $options);
            }
            
            if ($this->importResults['failed'] > 0 && !($options['partial'] ?? false)) {
                DB::rollBack();
                throw new ImportException('Import failed with errors. Use partial import to skip failed records.');
            }
            
            DB::commit();
            
            return $this->importResults;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CSV import failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new ImportException('Failed to import CSV: ' . $e->getMessage());
        }
    }
    
    /**
     * Import items from JSON
     */
    public function importItemsFromJson(string $jsonData, array $options = []): array
    {
        $this->authorize('item.import');
        $this->resetResults();
        
        try {
            $data = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ImportException('Invalid JSON data: ' . json_last_error_msg());
            }
            
            $items = $data['items'] ?? $data;
            if (!is_array($items)) {
                throw new ImportException('Invalid data format. Expected array of items.');
            }
            
            DB::beginTransaction();
            
            foreach ($items as $index => $item) {
                $this->processItemRecord($item, $index + 1, $options);
            }
            
            if ($this->importResults['failed'] > 0 && !($options['partial'] ?? false)) {
                DB::rollBack();
                throw new ImportException('Import failed with errors. Use partial import to skip failed records.');
            }
            
            DB::commit();
            
            return $this->importResults;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('JSON import failed', [
                'error' => $e->getMessage()
            ]);
            throw new ImportException('Failed to import JSON: ' . $e->getMessage());
        }
    }
    
    /**
     * Import modifiers from CSV
     */
    public function importModifiersFromCsv(string $filePath, array $options = []): array
    {
        $this->authorize('item.import');
        $this->resetResults();
        
        if (!$this->features->isEnabled('item.modifiers')) {
            throw new ImportException('Modifiers feature is not enabled');
        }
        
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $records = Statement::create()->process($csv);
            
            DB::beginTransaction();
            
            foreach ($records as $offset => $record) {
                $this->processModifierRecord($record, $offset + 2, $options);
            }
            
            if ($this->importResults['failed'] > 0 && !($options['partial'] ?? false)) {
                DB::rollBack();
                throw new ImportException('Import failed with errors.');
            }
            
            DB::commit();
            
            return $this->importResults;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Modifier CSV import failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Import inventory levels from CSV
     */
    public function importInventoryFromCsv(string $filePath, array $options = []): array
    {
        $this->authorize('item.import');
        $this->resetResults();
        
        if (!$this->features->isEnabled('item.inventory_tracking')) {
            throw new ImportException('Inventory tracking is not enabled');
        }
        
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $records = Statement::create()->process($csv);
            
            DB::beginTransaction();
            
            foreach ($records as $offset => $record) {
                $this->processInventoryRecord($record, $offset + 2, $options);
            }
            
            if ($this->importResults['failed'] > 0 && !($options['partial'] ?? false)) {
                DB::rollBack();
                throw new ImportException('Import failed with errors.');
            }
            
            DB::commit();
            
            return $this->importResults;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Inventory CSV import failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Import recipes from structured data
     */
    public function importRecipes(array $recipes, array $options = []): array
    {
        $this->authorize('item.import');
        $this->resetResults();
        
        if (!$this->features->isEnabled('item.recipes')) {
            throw new ImportException('Recipes feature is not enabled');
        }
        
        DB::beginTransaction();
        
        try {
            foreach ($recipes as $index => $recipe) {
                $this->processRecipeRecord($recipe, $index + 1, $options);
            }
            
            if ($this->importResults['failed'] > 0 && !($options['partial'] ?? false)) {
                DB::rollBack();
                throw new ImportException('Import failed with errors.');
            }
            
            DB::commit();
            
            return $this->importResults;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Recipe import failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Validate import file
     */
    public function validateImportFile(string $filePath, string $type): array
    {
        $errors = [];
        $warnings = [];
        
        if (!file_exists($filePath)) {
            $errors[] = 'File not found';
            return ['valid' => false, 'errors' => $errors];
        }
        
        if (!is_readable($filePath)) {
            $errors[] = 'File is not readable';
            return ['valid' => false, 'errors' => $errors];
        }
        
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        if ($extension === 'csv') {
            try {
                $csv = Reader::createFromPath($filePath, 'r');
                $csv->setHeaderOffset(0);
                $headers = $csv->getHeader();
                
                $requiredHeaders = $this->getRequiredHeaders($type);
                $missingHeaders = array_diff($requiredHeaders, $headers);
                
                if (!empty($missingHeaders)) {
                    $errors[] = 'Missing required columns: ' . implode(', ', $missingHeaders);
                }
                
                // Check for recommended headers
                $recommendedHeaders = $this->getRecommendedHeaders($type);
                $missingRecommended = array_diff($recommendedHeaders, $headers);
                
                if (!empty($missingRecommended)) {
                    $warnings[] = 'Missing recommended columns: ' . implode(', ', $missingRecommended);
                }
                
            } catch (\Exception $e) {
                $errors[] = 'Invalid CSV file: ' . $e->getMessage();
            }
        } elseif ($extension === 'json') {
            $content = file_get_contents($filePath);
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Invalid JSON: ' . json_last_error_msg();
            }
        } else {
            $errors[] = 'Unsupported file format. Use CSV or JSON.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
    
    /**
     * Process item record
     */
    private function processItemRecord(array $record, int $line, array $options): void
    {
        try {
            // Map CSV columns to item data
            $itemData = $this->mapItemData($record, $options);
            
            // Validate item data
            $validator = Validator::make($itemData, [
                'name' => 'required|string|max:255',
                'type' => 'required|in:product,service,combo',
                'base_price' => 'required|numeric|min:0',
                'sku' => 'nullable|string|unique:items,sku',
            ]);
            
            if ($validator->fails()) {
                throw new ImportException('Validation failed: ' . implode(', ', $validator->errors()->all()));
            }
            
            // Check if updating existing item
            if (isset($itemData['sku']) && $options['update_existing'] ?? false) {
                $existingItem = $this->itemService->findBySku($itemData['sku']);
                if ($existingItem) {
                    $this->itemService->updateItem($existingItem->id, $itemData);
                    $this->importResults['success']++;
                    return;
                }
            }
            
            // Create new item
            $item = $this->itemService->createItem($itemData);
            
            // Process variants if present
            if (isset($record['variants']) && is_array($record['variants'])) {
                foreach ($record['variants'] as $variant) {
                    $this->itemService->createVariant($item->id, $variant);
                }
            }
            
            $this->importResults['success']++;
            
        } catch (\Exception $e) {
            $this->importResults['failed']++;
            $this->importResults['errors'][] = "Line {$line}: " . $e->getMessage();
            
            if (!($options['partial'] ?? false)) {
                throw $e;
            }
        }
    }
    
    /**
     * Process modifier record
     */
    private function processModifierRecord(array $record, int $line, array $options): void
    {
        try {
            // First ensure modifier group exists
            $groupName = $record['group_name'] ?? $record['modifier_group'] ?? null;
            if (!$groupName) {
                throw new ImportException('Modifier group name is required');
            }
            
            $group = $this->modifierService->findGroupByName($groupName);
            if (!$group) {
                // Create group if it doesn't exist
                $group = $this->modifierService->createModifierGroup([
                    'name' => $groupName,
                    'min_selections' => 0,
                    'max_selections' => 10,
                    'is_required' => false,
                    'allow_multiple' => true,
                ]);
            }
            
            // Create modifier
            $modifierData = [
                'modifier_group_id' => $group->id,
                'name' => $record['name'],
                'sku' => $record['sku'] ?? null,
                'price_adjustment' => $record['price_adjustment'] ?? 0,
                'is_available' => $record['is_available'] ?? true,
            ];
            
            $this->modifierService->createModifier($modifierData);
            $this->importResults['success']++;
            
        } catch (\Exception $e) {
            $this->importResults['failed']++;
            $this->importResults['errors'][] = "Line {$line}: " . $e->getMessage();
            
            if (!($options['partial'] ?? false)) {
                throw $e;
            }
        }
    }
    
    /**
     * Process inventory record
     */
    private function processInventoryRecord(array $record, int $line, array $options): void
    {
        try {
            $sku = $record['sku'] ?? null;
            $itemId = $record['item_id'] ?? null;
            
            if (!$sku && !$itemId) {
                throw new ImportException('Either SKU or item_id is required');
            }
            
            // Find item
            if ($sku) {
                $item = $this->itemService->findBySku($sku);
                if (!$item) {
                    throw new ImportException("Item with SKU '{$sku}' not found");
                }
                $itemId = $item->id;
            }
            
            // Update inventory
            $this->inventoryService->adjustInventory([
                'item_id' => $itemId,
                'variant_id' => $record['variant_id'] ?? null,
                'location_id' => $record['location_id'] ?? null,
                'quantity_change' => $record['quantity'] ?? 0,
                'adjustment_type' => 'import',
                'reason' => 'Bulk import',
                'notes' => 'Imported from file',
            ]);
            
            // Update reorder levels if provided
            if (isset($record['min_quantity']) || isset($record['reorder_quantity'])) {
                $this->inventoryService->updateReorderLevels(
                    $itemId,
                    $record['variant_id'] ?? null,
                    $record['location_id'] ?? null,
                    $record['min_quantity'] ?? 0,
                    $record['reorder_quantity'] ?? 0,
                    $record['max_quantity'] ?? null
                );
            }
            
            $this->importResults['success']++;
            
        } catch (\Exception $e) {
            $this->importResults['failed']++;
            $this->importResults['errors'][] = "Line {$line}: " . $e->getMessage();
            
            if (!($options['partial'] ?? false)) {
                throw $e;
            }
        }
    }
    
    /**
     * Process recipe record
     */
    private function processRecipeRecord(array $record, int $line, array $options): void
    {
        try {
            // Validate recipe has ingredients
            if (empty($record['ingredients'])) {
                throw new ImportException('Recipe must have at least one ingredient');
            }
            
            $recipe = $this->recipeService->createRecipe($record);
            $this->importResults['success']++;
            
        } catch (\Exception $e) {
            $this->importResults['failed']++;
            $this->importResults['errors'][] = "Line {$line}: " . $e->getMessage();
            
            if (!($options['partial'] ?? false)) {
                throw $e;
            }
        }
    }
    
    /**
     * Map CSV data to item structure
     */
    private function mapItemData(array $record, array $options): array
    {
        $mapping = $options['column_mapping'] ?? [];
        
        $data = [
            'name' => $record[$mapping['name'] ?? 'name'] ?? null,
            'description' => $record[$mapping['description'] ?? 'description'] ?? null,
            'type' => $record[$mapping['type'] ?? 'type'] ?? 'product',
            'category_id' => $record[$mapping['category_id'] ?? 'category_id'] ?? null,
            'base_price' => $record[$mapping['base_price'] ?? 'base_price'] ?? 0,
            'cost' => $record[$mapping['cost'] ?? 'cost'] ?? null,
            'sku' => $record[$mapping['sku'] ?? 'sku'] ?? null,
            'barcode' => $record[$mapping['barcode'] ?? 'barcode'] ?? null,
            'track_stock' => $this->parseBooleanValue($record[$mapping['track_stock'] ?? 'track_stock'] ?? false),
            'is_available' => $this->parseBooleanValue($record[$mapping['is_available'] ?? 'is_available'] ?? true),
            'allow_modifiers' => $this->parseBooleanValue($record[$mapping['allow_modifiers'] ?? 'allow_modifiers'] ?? false),
            'preparation_time' => $record[$mapping['preparation_time'] ?? 'preparation_time'] ?? null,
        ];
        
        return array_filter($data, fn($value) => $value !== null);
    }
    
    /**
     * Get required headers for import type
     */
    private function getRequiredHeaders(string $type): array
    {
        return match ($type) {
            'items' => ['name', 'type', 'base_price'],
            'modifiers' => ['group_name', 'name', 'price_adjustment'],
            'inventory' => ['sku', 'quantity'],
            'recipes' => ['item_id', 'name', 'yield_quantity'],
            default => [],
        };
    }
    
    /**
     * Get recommended headers for import type
     */
    private function getRecommendedHeaders(string $type): array
    {
        return match ($type) {
            'items' => ['sku', 'description', 'cost', 'category_id'],
            'modifiers' => ['sku', 'is_available'],
            'inventory' => ['location_id', 'min_quantity', 'reorder_quantity'],
            'recipes' => ['description', 'preparation_time', 'instructions'],
            default => [],
        };
    }
    
    /**
     * Parse boolean value from various formats
     */
    private function parseBooleanValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return (bool) $value;
        }
        
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['1', 'true', 'yes', 'y', 'on']);
        }
        
        return false;
    }
    
    /**
     * Reset import results
     */
    private function resetResults(): void
    {
        $this->importResults = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'warnings' => [],
        ];
    }
}