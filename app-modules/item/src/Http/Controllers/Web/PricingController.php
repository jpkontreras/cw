<?php

namespace Colame\Item\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Item\Services\PricingService;
use Colame\Item\Contracts\ItemServiceInterface;
use App\Core\Contracts\FeatureFlagInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class PricingController extends Controller
{
    public function __construct(
        private readonly PricingService $pricingService,
        private readonly ItemServiceInterface $itemService,
        private readonly FeatureFlagInterface $features,
    ) {}
    
    /**
     * Display pricing rules overview
     */
    public function index(Request $request): Response
    {
        if (!$this->features->isEnabled('item.dynamic_pricing')) {
            abort(404);
        }
        
        $locationId = $request->input('location_id');
        $activeRules = $this->pricingService->getActiveRulesForLocation($locationId ?? 0);
        
        // Mock data for now - in a real implementation, these would come from the service
        $mockPriceRules = [];
        $mockPagination = [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 20,
            'total' => 0,
            'from' => 0,
            'to' => 0,
        ];
        
        return Inertia::render('item/pricing/index', [
            'price_rules' => $mockPriceRules,
            'pagination' => $mockPagination,
            'metadata' => [],
            'rule_types' => [
                ['value' => 'percentage_discount', 'label' => 'Percentage Discount'],
                ['value' => 'fixed_discount', 'label' => 'Fixed Discount'],
                ['value' => 'override', 'label' => 'Price Override'],
                ['value' => 'multiplier', 'label' => 'Price Multiplier'],
            ],
            'active_rules_count' => $activeRules->count(),
            'total_discount_given' => 0,
            'avg_discount_percentage' => 0, // This was missing and causing the error
            'upcoming_rules' => [],
            'expiring_rules' => [],
            'features' => [
                'time_based_pricing' => $this->features->isEnabled('item.time_based_pricing'),
                'location_pricing' => $this->features->isEnabled('item.location_pricing'),
                'customer_group_pricing' => false,
                'quantity_pricing' => false,
            ],
            'items' => $this->itemService->getItemsForSelect(),
            'categories' => [], // Will be fetched from taxonomy module
            'locations' => [], // Will be fetched from location module
            'customer_groups' => [], // Will be fetched from customer module
        ]);
    }
    
    /**
     * Show form to create pricing rule
     */
    public function create(): Response
    {
        if (!$this->features->isEnabled('item.dynamic_pricing')) {
            abort(404);
        }
        
        return Inertia::render('item/pricing/create', [
            'items' => $this->itemService->getItemsForSelect(),
            'locations' => [], // Will be fetched from location module
            'price_types' => [
                ['value' => 'fixed', 'label' => 'Fixed Price'],
                ['value' => 'percentage', 'label' => 'Percentage Adjustment'],
            ],
            'days_of_week' => [
                ['value' => 0, 'label' => 'Sunday'],
                ['value' => 1, 'label' => 'Monday'],
                ['value' => 2, 'label' => 'Tuesday'],
                ['value' => 3, 'label' => 'Wednesday'],
                ['value' => 4, 'label' => 'Thursday'],
                ['value' => 5, 'label' => 'Friday'],
                ['value' => 6, 'label' => 'Saturday'],
            ],
        ]);
    }
    
    /**
     * Store a new pricing rule
     */
    public function store(Request $request)
    {
        if (!$this->features->isEnabled('item.dynamic_pricing')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'location_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'price_type' => 'required|string|in:fixed,percentage',
            'price_value' => 'required|numeric',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|between:0,6',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:0',
        ]);
        
        $locationPrice = $this->pricingService->setLocationPrice($validated);
        
        return redirect()->route('pricing.index')
            ->with('success', 'Pricing rule created successfully');
    }
    
    /**
     * Show pricing rule details
     */
    public function show(int $id): Response
    {
        if (!$this->features->isEnabled('item.dynamic_pricing')) {
            abort(404);
        }
        
        $rule = $this->pricingService->findLocationPrice($id);
        
        if (!$rule) {
            abort(404);
        }
        
        return Inertia::render('item/pricing/show', [
            'pricing_rule' => $rule,
            'item' => $this->itemService->find($rule->itemId),
            'price_history' => $this->pricingService->getPriceHistory(
                $rule->itemId,
                $rule->locationId,
                Carbon::now()->subDays(30)
            ),
        ]);
    }
    
    /**
     * Show form to edit pricing rule
     */
    public function edit(int $id): Response
    {
        if (!$this->features->isEnabled('item.dynamic_pricing')) {
            abort(404);
        }
        
        $rule = $this->pricingService->findLocationPrice($id);
        
        if (!$rule) {
            abort(404);
        }
        
        return Inertia::render('item/pricing/edit', [
            'pricing_rule' => $rule,
            'items' => $this->itemService->getItemsForSelect(),
            'locations' => [], // Will be fetched from location module
            'price_types' => [
                ['value' => 'fixed', 'label' => 'Fixed Price'],
                ['value' => 'percentage', 'label' => 'Percentage Adjustment'],
            ],
            'days_of_week' => [
                ['value' => 0, 'label' => 'Sunday'],
                ['value' => 1, 'label' => 'Monday'],
                ['value' => 2, 'label' => 'Tuesday'],
                ['value' => 3, 'label' => 'Wednesday'],
                ['value' => 4, 'label' => 'Thursday'],
                ['value' => 5, 'label' => 'Friday'],
                ['value' => 6, 'label' => 'Saturday'],
            ],
        ]);
    }
    
    /**
     * Update pricing rule
     */
    public function update(Request $request, int $id)
    {
        if (!$this->features->isEnabled('item.dynamic_pricing')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price_type' => 'required|string|in:fixed,percentage',
            'price_value' => 'required|numeric',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|between:0,6',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:0',
        ]);
        
        $rule = $this->pricingService->updateLocationPrice($id, $validated);
        
        return redirect()->route('pricing.show', $rule->id)
            ->with('success', 'Pricing rule updated successfully');
    }
    
    /**
     * Delete pricing rule
     */
    public function destroy(int $id)
    {
        if (!$this->features->isEnabled('item.dynamic_pricing')) {
            abort(404);
        }
        
        $this->pricingService->deleteLocationPrice($id);
        
        return redirect()->route('pricing.index')
            ->with('success', 'Pricing rule deleted successfully');
    }
    
    /**
     * Price calculator tool
     */
    public function calculator(): Response
    {
        if (!$this->features->isEnabled('item.dynamic_pricing')) {
            abort(404);
        }
        
        return Inertia::render('item/pricing/calculator', [
            'items' => $this->itemService->getItemsForSelect(['with_variants' => true]),
            'locations' => [], // Will be fetched from location module
        ]);
    }
    
    /**
     * Calculate price for given parameters
     */
    public function calculate(Request $request)
    {
        if (!$this->features->isEnabled('item.dynamic_pricing')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
            'location_id' => 'nullable|integer',
            'modifier_ids' => 'nullable|array',
            'modifier_ids.*' => 'integer|exists:item_modifiers,id',
            'datetime' => 'nullable|date',
        ]);
        
        $calculation = $this->pricingService->calculatePrice(
            $validated['item_id'],
            $validated['variant_id'] ?? null,
            $validated['location_id'] ?? null,
            $validated['modifier_ids'] ?? null,
            $validated['datetime'] ? Carbon::parse($validated['datetime']) : null
        );
        
        return response()->json(['calculation' => $calculation]);
    }
    
    /**
     * Bulk price update form
     */
    public function bulkUpdate(): Response
    {
        if (!$this->features->isEnabled('item.dynamic_pricing')) {
            abort(404);
        }
        
        return Inertia::render('item/pricing/bulk-update', [
            'categories' => [], // Will be fetched from taxonomy module
            'locations' => [], // Will be fetched from location module
            'update_types' => [
                ['value' => 'percentage_increase', 'label' => 'Percentage Increase'],
                ['value' => 'percentage_decrease', 'label' => 'Percentage Decrease'],
                ['value' => 'fixed_amount', 'label' => 'Fixed Amount'],
                ['value' => 'round_to_nearest', 'label' => 'Round to Nearest'],
            ],
        ]);
    }
    
    /**
     * Process bulk price update
     */
    public function processBulkUpdate(Request $request)
    {
        if (!$this->features->isEnabled('item.dynamic_pricing')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'filters' => 'required|array',
            'filters.category_id' => 'nullable|integer',
            'filters.location_id' => 'nullable|integer',
            'filters.item_ids' => 'nullable|array',
            'update_type' => 'required|string',
            'update_value' => 'required|numeric',
            'effective_date' => 'nullable|date',
        ]);
        
        $updated = $this->pricingService->bulkUpdatePrices($validated);
        
        return redirect()->route('pricing.index')
            ->with('success', "{$updated} prices updated successfully");
    }
}