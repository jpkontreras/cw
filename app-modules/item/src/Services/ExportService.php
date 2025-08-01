<?php

namespace Colame\Item\Services;

use App\Core\Services\BaseService;
use App\Core\Contracts\FeatureFlagInterface;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Contracts\ModifierRepositoryInterface;
use Colame\Item\Contracts\InventoryRepositoryInterface;
use Colame\Item\Contracts\RecipeRepositoryInterface;
use Colame\Item\Contracts\PricingRepositoryInterface;
use League\Csv\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class ExportService extends BaseService
{
    public function __construct(
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly ModifierRepositoryInterface $modifierRepository,
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly RecipeRepositoryInterface $recipeRepository,
        private readonly PricingRepositoryInterface $pricingRepository,
        FeatureFlagInterface $features,
    ) {
        parent::__construct($features);
    }
    
    /**
     * Export items to CSV
     */
    public function exportItemsToCsv(array $filters = [], array $options = []): string
    {
        $this->authorize('item.export');
        
        $items = $this->itemRepository->getItemsForExport($filters);
        $includeVariants = $options['include_variants'] ?? true;
        $includeInventory = $options['include_inventory'] ?? false;
        
        $csv = Writer::createFromString();
        
        // Add headers
        $headers = $this->getItemHeaders($options);
        $csv->insertOne($headers);
        
        // Add data rows
        foreach ($items as $item) {
            $row = $this->formatItemRow($item, $options);
            $csv->insertOne($row);
            
            // Add variants as separate rows if requested
            if ($includeVariants && $item->variants->isNotEmpty()) {
                foreach ($item->variants as $variant) {
                    $variantRow = $this->formatVariantRow($item, $variant, $options);
                    $csv->insertOne($variantRow);
                }
            }
        }
        
        $filename = $this->generateFilename('items', 'csv');
        $path = $this->saveExportFile($csv->toString(), $filename);
        
        Log::info('Items exported to CSV', [
            'count' => $items->count(),
            'file' => $filename,
            'filters' => $filters,
        ]);
        
        return $path;
    }
    
    /**
     * Export items to Excel
     */
    public function exportItemsToExcel(array $filters = [], array $options = []): string
    {
        $this->authorize('item.export');
        
        $spreadsheet = new Spreadsheet();
        
        // Items sheet
        $itemsSheet = $spreadsheet->getActiveSheet();
        $itemsSheet->setTitle('Items');
        $this->fillItemsSheet($itemsSheet, $filters, $options);
        
        // Variants sheet if enabled
        if ($options['include_variants'] ?? true) {
            $variantsSheet = $spreadsheet->createSheet();
            $variantsSheet->setTitle('Variants');
            $this->fillVariantsSheet($variantsSheet, $filters);
        }
        
        // Modifiers sheet if enabled
        if (($options['include_modifiers'] ?? false) && $this->features->isEnabled('item.modifiers')) {
            $modifiersSheet = $spreadsheet->createSheet();
            $modifiersSheet->setTitle('Modifiers');
            $this->fillModifiersSheet($modifiersSheet);
        }
        
        // Inventory sheet if enabled
        if (($options['include_inventory'] ?? false) && $this->features->isEnabled('item.inventory_tracking')) {
            $inventorySheet = $spreadsheet->createSheet();
            $inventorySheet->setTitle('Inventory');
            $this->fillInventorySheet($inventorySheet, $filters);
        }
        
        // Recipes sheet if enabled
        if (($options['include_recipes'] ?? false) && $this->features->isEnabled('item.recipes')) {
            $recipesSheet = $spreadsheet->createSheet();
            $recipesSheet->setTitle('Recipes');
            $this->fillRecipesSheet($recipesSheet, $filters);
        }
        
        $writer = new Xlsx($spreadsheet);
        $filename = $this->generateFilename('items', 'xlsx');
        $tempPath = storage_path('app/temp/' . $filename);
        
        // Ensure temp directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        
        $writer->save($tempPath);
        
        $path = $this->saveExportFile(file_get_contents($tempPath), $filename);
        unlink($tempPath); // Clean up temp file
        
        return $path;
    }
    
    /**
     * Export items to JSON
     */
    public function exportItemsToJson(array $filters = [], array $options = []): string
    {
        $this->authorize('item.export');
        
        $items = $this->itemRepository->getItemsForExport($filters);
        $data = ['items' => []];
        
        foreach ($items as $item) {
            $itemData = $item->toArray();
            
            // Include related data based on options
            if ($options['include_variants'] ?? true) {
                $itemData['variants'] = $item->variants->toArray();
            }
            
            if (($options['include_modifiers'] ?? false) && $this->features->isEnabled('item.modifiers')) {
                $itemData['modifier_groups'] = $this->modifierRepository->getItemModifierGroups($item->id)->toArray();
            }
            
            if (($options['include_inventory'] ?? false) && $this->features->isEnabled('item.inventory_tracking')) {
                $inventory = $this->inventoryRepository->getInventoryLevel($item->id);
                $itemData['inventory'] = $inventory ? $inventory->toArray() : null;
            }
            
            if (($options['include_recipes'] ?? false) && $this->features->isEnabled('item.recipes')) {
                $recipe = $this->recipeRepository->findByItem($item->id);
                $itemData['recipe'] = $recipe ? $recipe->toArray() : null;
            }
            
            $data['items'][] = $itemData;
        }
        
        // Add metadata
        $data['metadata'] = [
            'exported_at' => Carbon::now()->toIso8601String(),
            'count' => count($data['items']),
            'filters' => $filters,
            'version' => '1.0',
        ];
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = $this->generateFilename('items', 'json');
        $path = $this->saveExportFile($json, $filename);
        
        return $path;
    }
    
    /**
     * Export inventory levels
     */
    public function exportInventory(array $filters = [], string $format = 'csv'): string
    {
        $this->authorize('item.export');
        
        if (!$this->features->isEnabled('item.inventory_tracking')) {
            throw new \Exception('Inventory tracking is not enabled');
        }
        
        $inventory = $this->inventoryRepository->getInventoryForExport($filters);
        
        switch ($format) {
            case 'csv':
                return $this->exportInventoryToCsv($inventory);
            case 'excel':
                return $this->exportInventoryToExcel($inventory);
            case 'json':
                return $this->exportInventoryToJson($inventory);
            default:
                throw new \Exception('Unsupported export format: ' . $format);
        }
    }
    
    /**
     * Export price list
     */
    public function exportPriceList(array $filters = [], string $format = 'csv'): string
    {
        $this->authorize('item.export');
        
        $items = $this->itemRepository->getItemsForExport($filters);
        $locationId = $filters['location_id'] ?? null;
        
        $priceData = [];
        
        foreach ($items as $item) {
            $baseData = [
                'sku' => $item->sku,
                'name' => $item->name,
                'category' => $item->category_name ?? 'Uncategorized',
                'base_price' => $item->base_price,
            ];
            
            if ($this->features->isEnabled('item.dynamic_pricing') && $locationId) {
                $locationPrice = $this->pricingRepository->getLocationPrice($item->id, null, $locationId);
                if ($locationPrice) {
                    $baseData['location_price'] = $locationPrice->price_value;
                    $baseData['price_type'] = $locationPrice->price_type;
                }
            }
            
            $priceData[] = $baseData;
            
            // Add variants
            foreach ($item->variants as $variant) {
                $variantData = $baseData;
                $variantData['sku'] = $variant->sku;
                $variantData['name'] = $item->name . ' - ' . $variant->name;
                $variantData['base_price'] = $variant->price;
                
                if ($this->features->isEnabled('item.dynamic_pricing') && $locationId) {
                    $variantLocationPrice = $this->pricingRepository->getLocationPrice($item->id, $variant->id, $locationId);
                    if ($variantLocationPrice) {
                        $variantData['location_price'] = $variantLocationPrice->price_value;
                        $variantData['price_type'] = $variantLocationPrice->price_type;
                    }
                }
                
                $priceData[] = $variantData;
            }
        }
        
        switch ($format) {
            case 'csv':
                return $this->exportPriceDataToCsv($priceData);
            case 'excel':
                return $this->exportPriceDataToExcel($priceData);
            case 'pdf':
                return $this->exportPriceDataToPdf($priceData, $filters);
            default:
                throw new \Exception('Unsupported export format: ' . $format);
        }
    }
    
    /**
     * Export templates for import
     */
    public function exportImportTemplate(string $type): string
    {
        $headers = [];
        $sampleData = [];
        
        switch ($type) {
            case 'items':
                $headers = [
                    'name', 'description', 'type', 'category_id', 'base_price', 
                    'cost', 'sku', 'barcode', 'track_stock', 'is_available', 
                    'allow_modifiers', 'preparation_time'
                ];
                $sampleData = [
                    [
                        'Empanada de Pino', 'Traditional meat empanada', 'product', 
                        '1', '2500', '800', 'EMP-001', '1234567890', 'true', 
                        'true', 'true', '15'
                    ],
                ];
                break;
                
            case 'modifiers':
                $headers = [
                    'group_name', 'name', 'sku', 'price_adjustment', 
                    'is_available', 'display_order'
                ];
                $sampleData = [
                    [
                        'Extras', 'Extra Cheese', 'MOD-CHEESE', '300', 
                        'true', '1'
                    ],
                ];
                break;
                
            case 'inventory':
                $headers = [
                    'sku', 'location_id', 'quantity', 'min_quantity', 
                    'reorder_quantity', 'max_quantity'
                ];
                $sampleData = [
                    [
                        'EMP-001', '1', '100', '20', '50', '200'
                    ],
                ];
                break;
                
            case 'recipes':
                $headers = [
                    'item_id', 'name', 'yield_quantity', 'yield_unit', 
                    'preparation_time', 'cooking_time', 'instructions'
                ];
                $sampleData = [
                    [
                        '1', 'Empanada Recipe', '10', 'unit', 
                        '30', '25', 'Mix ingredients and bake'
                    ],
                ];
                break;
        }
        
        $csv = Writer::createFromString();
        $csv->insertOne($headers);
        
        foreach ($sampleData as $row) {
            $csv->insertOne($row);
        }
        
        $filename = "import_template_{$type}.csv";
        $path = $this->saveExportFile($csv->toString(), $filename);
        
        return $path;
    }
    
    /**
     * Get item headers for export
     */
    private function getItemHeaders(array $options): array
    {
        $headers = [
            'ID', 'SKU', 'Name', 'Description', 'Type', 'Category', 
            'Base Price', 'Cost', 'Track Stock', 'Available', 
            'Allow Modifiers', 'Prep Time', 'Created At'
        ];
        
        if ($options['include_inventory'] ?? false) {
            $headers = array_merge($headers, ['Stock On Hand', 'Reserved', 'Available Stock']);
        }
        
        if ($options['include_sales'] ?? false) {
            $headers = array_merge($headers, ['Units Sold', 'Revenue']);
        }
        
        return $headers;
    }
    
    /**
     * Format item row for export
     */
    private function formatItemRow($item, array $options): array
    {
        $row = [
            $item->id,
            $item->sku,
            $item->name,
            $item->description,
            $item->type,
            $item->category_name ?? 'Uncategorized',
            $item->base_price,
            $item->cost,
            $item->track_stock ? 'Yes' : 'No',
            $item->is_available ? 'Yes' : 'No',
            $item->allow_modifiers ? 'Yes' : 'No',
            $item->preparation_time,
            $item->created_at->format('Y-m-d H:i:s'),
        ];
        
        if ($options['include_inventory'] ?? false) {
            $inventory = $this->inventoryRepository->getInventoryLevel($item->id);
            if ($inventory) {
                $row[] = $inventory->quantity_on_hand;
                $row[] = $inventory->quantity_reserved;
                $row[] = $inventory->quantity_on_hand - $inventory->quantity_reserved;
            } else {
                $row[] = '';
                $row[] = '';
                $row[] = '';
            }
        }
        
        return $row;
    }
    
    /**
     * Format variant row for export
     */
    private function formatVariantRow($item, $variant, array $options): array
    {
        $row = [
            $variant->id,
            $variant->sku,
            $item->name . ' - ' . $variant->name,
            $variant->description ?? $item->description,
            $item->type,
            $item->category_name ?? 'Uncategorized',
            $variant->price,
            $variant->cost,
            $variant->track_stock ? 'Yes' : 'No',
            $variant->is_available ? 'Yes' : 'No',
            $item->allow_modifiers ? 'Yes' : 'No',
            $item->preparation_time,
            $variant->created_at->format('Y-m-d H:i:s'),
        ];
        
        if ($options['include_inventory'] ?? false) {
            $inventory = $this->inventoryRepository->getInventoryLevel($item->id, $variant->id);
            if ($inventory) {
                $row[] = $inventory->quantity_on_hand;
                $row[] = $inventory->quantity_reserved;
                $row[] = $inventory->quantity_on_hand - $inventory->quantity_reserved;
            } else {
                $row[] = '';
                $row[] = '';
                $row[] = '';
            }
        }
        
        return $row;
    }
    
    /**
     * Generate export filename
     */
    private function generateFilename(string $type, string $extension): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_His');
        return "{$type}_export_{$timestamp}.{$extension}";
    }
    
    /**
     * Save export file
     */
    private function saveExportFile(string $content, string $filename): string
    {
        $path = 'exports/' . $filename;
        Storage::put($path, $content);
        
        return Storage::path($path);
    }
    
    /**
     * Fill items sheet for Excel export
     */
    private function fillItemsSheet($sheet, array $filters, array $options): void
    {
        $items = $this->itemRepository->getItemsForExport($filters);
        
        // Headers
        $headers = $this->getItemHeaders($options);
        $sheet->fromArray([$headers], null, 'A1');
        
        // Style headers
        $sheet->getStyle('A1:' . chr(64 + count($headers)) . '1')
            ->getFont()->setBold(true);
        
        // Data
        $row = 2;
        foreach ($items as $item) {
            $data = $this->formatItemRow($item, $options);
            $sheet->fromArray([$data], null, 'A' . $row);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', chr(64 + count($headers))) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    /**
     * Export inventory to CSV
     */
    private function exportInventoryToCsv(Collection $inventory): string
    {
        $csv = Writer::createFromString();
        
        $headers = [
            'Item ID', 'SKU', 'Item Name', 'Variant', 'Location',
            'On Hand', 'Reserved', 'Available', 'Min Level', 
            'Reorder Level', 'Max Level', 'Unit Cost', 'Total Value'
        ];
        
        $csv->insertOne($headers);
        
        foreach ($inventory as $item) {
            $row = [
                $item->item_id,
                $item->sku,
                $item->item_name,
                $item->variant_name ?? '',
                $item->location_name ?? 'Default',
                $item->quantity_on_hand,
                $item->quantity_reserved,
                $item->quantity_on_hand - $item->quantity_reserved,
                $item->min_quantity,
                $item->reorder_quantity,
                $item->max_quantity ?? '',
                $item->unit_cost,
                $item->quantity_on_hand * $item->unit_cost,
            ];
            $csv->insertOne($row);
        }
        
        $filename = $this->generateFilename('inventory', 'csv');
        return $this->saveExportFile($csv->toString(), $filename);
    }
}