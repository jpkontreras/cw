<?php

namespace Colame\Item\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Item\Services\InventoryService;
use Colame\Item\Contracts\ItemServiceInterface;
use App\Core\Contracts\FeatureFlagInterface;
use App\Core\Traits\HandlesPaginationBounds;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    use HandlesPaginationBounds;
    
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly ItemServiceInterface $itemService,
        private readonly FeatureFlagInterface $features,
    ) {}
    
    /**
     * Display inventory overview
     */
    public function index(Request $request): Response
    {
        $locationId = $request->input('location_id');
        $filters = $request->only(['search', 'category_id', 'low_stock', 'out_of_stock', 'sort', 'page']);
        $perPage = (int) $request->input('per_page', 20);
        
        $paginatedData = $this->inventoryService->getPaginatedInventory($filters, $perPage, $locationId);
        $responseData = $paginatedData->toArray();
        
        // Handle out-of-bounds pagination
        if ($redirect = $this->handleOutOfBoundsPagination($responseData['pagination'], $request, 'inventory.index')) {
            return $redirect;
        }
        
        return Inertia::render('item/inventory/index', [
            'inventory' => $responseData['data'],
            'pagination' => $responseData['pagination'],
            'metadata' => $responseData['metadata'],
            'low_stock_items' => $this->inventoryService->getLowStockItems($locationId),
            'inventory_value' => $this->inventoryService->calculateInventoryValue($locationId),
            'features' => [
                'stock_transfers' => $this->features->isEnabled('item.stock_transfers'),
                'stock_reservation' => $this->features->isEnabled('item.stock_reservation'),
                'auto_reorder' => $this->features->isEnabled('item.auto_reorder'),
                'batch_tracking' => $this->features->isEnabled('item.batch_tracking'),
            ],
        ]);
    }
    
    /**
     * Show inventory adjustments form
     */
    public function adjustments(): Response
    {
        return Inertia::render('item/inventory/adjustments', [
            'adjustment_types' => [
                ['value' => 'recount', 'label' => 'Physical Recount'],
                ['value' => 'damaged', 'label' => 'Damaged Goods'],
                ['value' => 'expired', 'label' => 'Expired'],
                ['value' => 'theft', 'label' => 'Theft/Loss'],
                ['value' => 'return', 'label' => 'Return to Supplier'],
                ['value' => 'other', 'label' => 'Other'],
            ],
            'recent_adjustments' => $this->inventoryService->getRecentAdjustments(10),
        ]);
    }
    
    /**
     * Process inventory adjustment
     */
    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'location_id' => 'nullable|integer',
            'quantity_change' => 'required|numeric|not_in:0',
            'adjustment_type' => 'required|string',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);
        
        $adjustment = $this->inventoryService->adjustInventory($validated);
        
        return redirect()->route('inventory.index')
            ->with('success', 'Inventory adjusted successfully');
    }
    
    /**
     * Show stock transfer form
     */
    public function transfers(): Response
    {
        if (!$this->features->isEnabled('item.stock_transfers')) {
            abort(404);
        }
        
        return Inertia::render('item/inventory/transfers', [
            'locations' => [], // Will be fetched from location module
            'pending_transfers' => $this->inventoryService->getPendingTransfers(),
            'recent_transfers' => $this->inventoryService->getRecentTransfers(10),
        ]);
    }
    
    /**
     * Process stock transfer
     */
    public function transfer(Request $request)
    {
        if (!$this->features->isEnabled('item.stock_transfers')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'from_location_id' => 'required|integer|different:to_location_id',
            'to_location_id' => 'required|integer|different:from_location_id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);
        
        $transfer = $this->inventoryService->transferStock($validated);
        
        return redirect()->route('inventory.transfers')
            ->with('success', 'Stock transfer initiated successfully');
    }
    
    /**
     * Show stock take (inventory count) form
     */
    public function stockTake(): Response
    {
        $locationId = request()->input('location_id');
        
        return Inertia::render('item/inventory/stock-take', [
            'items' => $this->itemService->getItemsForStockTake($locationId),
            'last_stock_take' => $this->inventoryService->getLastStockTake($locationId),
        ]);
    }
    
    /**
     * Process stock take
     */
    public function processStockTake(Request $request)
    {
        $validated = $request->validate([
            'counts' => 'required|array',
            'counts.*.item_id' => 'required|integer|exists:items,id',
            'counts.*.variant_id' => 'nullable|integer|exists:item_variants,id',
            'counts.*.location_id' => 'nullable|integer',
            'counts.*.counted_quantity' => 'required|numeric|min:0',
            'counts.*.notes' => 'nullable|string',
        ]);
        
        $results = $this->inventoryService->performStockTake($validated['counts']);
        
        return redirect()->route('inventory.index')
            ->with('success', "Stock take completed. {$results['adjusted']} items adjusted.");
    }
    
    /**
     * Show reorder settings
     */
    public function reorderSettings(): Response
    {
        if (!$this->features->isEnabled('item.auto_reorder')) {
            abort(404);
        }
        
        return Inertia::render('item/inventory/reorder-settings', [
            'items_to_reorder' => $this->inventoryService->getItemsToReorder(),
            'reorder_rules' => $this->inventoryService->getReorderRules(),
        ]);
    }
    
    /**
     * Update reorder levels
     */
    public function updateReorderLevels(Request $request)
    {
        if (!$this->features->isEnabled('item.auto_reorder')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'location_id' => 'nullable|integer',
            'min_quantity' => 'required|numeric|min:0',
            'reorder_quantity' => 'required|numeric|min:0.01',
            'max_quantity' => 'nullable|numeric|min:0',
        ]);
        
        $this->inventoryService->updateReorderLevels(
            $validated['item_id'],
            $validated['variant_id'],
            $validated['location_id'],
            $validated['min_quantity'],
            $validated['reorder_quantity'],
            $validated['max_quantity']
        );
        
        return redirect()->route('inventory.reorder-settings')
            ->with('success', 'Reorder levels updated successfully');
    }
    
    /**
     * Show inventory movement history
     */
    public function history(Request $request): Response
    {
        $itemId = $request->input('item_id');
        $variantId = $request->input('variant_id');
        $locationId = $request->input('location_id');
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;
        
        $history = $this->inventoryService->getMovementHistory(
            $itemId,
            $variantId,
            $locationId,
            $startDate,
            $endDate
        );
        
        return Inertia::render('item/inventory/history', [
            'history' => $history,
            'item' => $itemId ? $this->itemService->find($itemId) : null,
            'filters' => $request->only(['item_id', 'variant_id', 'location_id', 'start_date', 'end_date']),
        ]);
    }
    
    /**
     * Export inventory data
     */
    public function export(Request $request)
    {
        $locationId = $request->input('location_id');
        $format = $request->input('format', 'csv');
        
        $exportData = $this->inventoryService->exportInventory($locationId, $format);
        
        return response()->download($exportData['path'], $exportData['filename']);
    }
}