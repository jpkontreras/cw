<?php

namespace Colame\Item\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Item\Services\RecipeService;
use Colame\Item\Contracts\ItemServiceInterface;
use App\Core\Contracts\FeatureFlagInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function __construct(
        private readonly RecipeService $recipeService,
        private readonly ItemServiceInterface $itemService,
        private readonly FeatureFlagInterface $features,
    ) {}
    
    /**
     * Display a listing of recipes
     */
    public function index(Request $request): Response
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        $filters = $request->only(['search', 'category_id', 'has_unavailable_ingredients']);
        $recipes = $this->recipeService->getRecipes($filters);
        
        // Calculate stats
        $stats = [
            'total_recipes' => $recipes->count(),
            'active_recipes' => $recipes->filter(fn($r) => $r->isActive ?? true)->count(),
            'avg_profit_margin' => $this->calculateAverageMargin($recipes),
            'highest_margin_recipe' => $this->getHighestMarginRecipe($recipes),
        ];
        
        // Get low margin and high cost recipes
        $low_margin_recipes = $recipes->filter(function ($recipe) {
            if (!isset($recipe->cost) || !$recipe->cost || !isset($recipe->sellingPrice)) {
                return false;
            }
            $margin = (($recipe->sellingPrice - $recipe->cost) / $recipe->sellingPrice) * 100;
            return $margin < 30;
        })->values();
        
        $high_cost_recipes = $recipes->filter(function ($recipe) {
            return isset($recipe->cost) && $recipe->cost > 100; // Adjust threshold as needed
        })->values();
        
        // Get items for dropdown
        $items = $this->itemService->getAllItems()->map(fn($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'base_price' => $item->basePrice ?? 0,
            'unit' => $item->unit ?? null,
        ]);
        
        // Define units and difficulty levels
        $units = [
            ['value' => 'portion', 'label' => 'Portion'],
            ['value' => 'serving', 'label' => 'Serving'],
            ['value' => 'batch', 'label' => 'Batch'],
            ['value' => 'kg', 'label' => 'Kilogram'],
            ['value' => 'g', 'label' => 'Gram'],
            ['value' => 'l', 'label' => 'Liter'],
            ['value' => 'ml', 'label' => 'Milliliter'],
        ];
        
        $difficulty_levels = [
            ['value' => 'easy', 'label' => 'Easy'],
            ['value' => 'medium', 'label' => 'Medium'],
            ['value' => 'hard', 'label' => 'Hard'],
        ];
        
        // Pagination stub (adjust based on your needs)
        $pagination = [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => $recipes->count(),
            'total' => $recipes->count(),
        ];
        
        // Metadata stub
        $metadata = [
            'columns' => [],
            'filters' => [],
        ];
        
        return Inertia::render('item/recipes/index', [
            'recipes' => $recipes,
            'pagination' => $pagination,
            'metadata' => $metadata,
            'items' => $items,
            'units' => $units,
            'difficulty_levels' => $difficulty_levels,
            'ingredients' => $this->recipeService->getAllIngredients(),
            'stats' => $stats,
            'high_cost_recipes' => $high_cost_recipes,
            'low_margin_recipes' => $low_margin_recipes,
            'features' => [
                'recipe_scaling' => $this->features->isEnabled('item.recipe_scaling'),
                'nutrition_tracking' => $this->features->isEnabled('item.nutrition_tracking'),
                'allergen_tracking' => $this->features->isEnabled('item.allergen_tracking'),
                'recipe_versioning' => $this->features->isEnabled('item.recipe_versioning'),
            ],
        ]);
    }
    
    /**
     * Calculate average profit margin for recipes
     */
    private function calculateAverageMargin($recipes): float
    {
        if ($recipes->isEmpty()) {
            return 0.0;
        }
        
        $margins = $recipes->map(function ($recipe) {
            if (!isset($recipe->cost) || !$recipe->cost || !isset($recipe->sellingPrice)) {
                return 0;
            }
            return (($recipe->sellingPrice - $recipe->cost) / $recipe->sellingPrice) * 100;
        })->filter(fn($m) => $m > 0);
        
        return $margins->isEmpty() ? 0.0 : $margins->average();
    }
    
    /**
     * Get the name of the recipe with highest margin
     */
    private function getHighestMarginRecipe($recipes): ?string
    {
        if ($recipes->isEmpty()) {
            return null;
        }
        
        $bestRecipe = null;
        $bestMargin = 0;
        
        foreach ($recipes as $recipe) {
            if (!isset($recipe->cost) || !$recipe->cost || !isset($recipe->sellingPrice)) {
                continue;
            }
            
            $margin = (($recipe->sellingPrice - $recipe->cost) / $recipe->sellingPrice) * 100;
            if ($margin > $bestMargin) {
                $bestMargin = $margin;
                $bestRecipe = $recipe->name ?? 'Unknown';
            }
        }
        
        return $bestRecipe;
    }
    
    /**
     * Show the form for creating a new recipe
     */
    public function create(Request $request): Response
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        // Get items for dropdown
        $items = $this->itemService->getAllItems()->map(fn($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'unit' => $item->unit ?? null,
        ]);
        
        // Define units and difficulty levels
        $units = [
            ['value' => 'portion', 'label' => 'Portion'],
            ['value' => 'serving', 'label' => 'Serving'],
            ['value' => 'batch', 'label' => 'Batch'],
            ['value' => 'kg', 'label' => 'Kilogram'],
            ['value' => 'g', 'label' => 'Gram'],
            ['value' => 'l', 'label' => 'Liter'],
            ['value' => 'ml', 'label' => 'Milliliter'],
        ];
        
        $difficulty_levels = [
            ['value' => 'easy', 'label' => 'Easy'],
            ['value' => 'medium', 'label' => 'Medium'],
            ['value' => 'hard', 'label' => 'Hard'],
        ];
        
        return Inertia::render('item/recipes/create', [
            'items' => $items,
            'units' => $units,
            'difficulty_levels' => $difficulty_levels,
        ]);
    }
    
    /**
     * Store a newly created recipe
     */
    public function store(Request $request)
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'item_id' => 'required|integer',
            'description' => 'nullable|string',
            'yield_quantity' => 'required|numeric|min:1',
            'yield_unit' => 'required|string',
            'prep_time_minutes' => 'nullable|integer|min:0',
            'cook_time_minutes' => 'nullable|integer|min:0',
            'difficulty' => 'required|string|in:easy,medium,hard',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.item_id' => 'required|integer',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'ingredients.*.unit' => 'required|string',
            'ingredients.*.notes' => 'nullable|string',
            'instructions' => 'nullable|array',
            'instructions.*.instruction' => 'required|string',
            'instructions.*.duration_minutes' => 'nullable|integer|min:0',
        ]);
        
        $recipe = $this->recipeService->createRecipe($validated);
        
        return redirect()->route('recipe.show', $recipe->id)
            ->with('success', 'Recipe created successfully');
    }
    
    /**
     * Display the specified recipe
     */
    public function show(int $id): Response
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        $recipe = $this->recipeService->findWithIngredients($id);
        
        if (!$recipe) {
            abort(404);
        }
        
        $locationId = request()->input('location_id');
        $cost = $this->recipeService->calculateRecipeCost($id, $locationId);
        
        return Inertia::render('item/recipes/show', [
            'recipe' => $recipe,
            'item' => $this->itemService->find($recipe->itemId),
            'cost_calculation' => $cost,
            'recipes_using_as_ingredient' => $this->recipeService->getRecipesUsingIngredient($recipe->itemId),
        ]);
    }
    
    /**
     * Show the form for editing the specified recipe
     */
    public function edit(int $id): Response
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        $recipe = $this->recipeService->findWithIngredients($id);
        
        if (!$recipe) {
            abort(404);
        }
        
        return Inertia::render('item/recipes/edit', [
            'recipe' => $recipe,
            'item' => $this->itemService->find($recipe->itemId),
            'ingredients' => $this->itemService->getItemsForSelect([
                'as_ingredient' => true,
            ]),
            'units' => [
                ['value' => 'unit', 'label' => 'Unit'],
                ['value' => 'kg', 'label' => 'Kilogram'],
                ['value' => 'g', 'label' => 'Gram'],
                ['value' => 'l', 'label' => 'Liter'],
                ['value' => 'ml', 'label' => 'Milliliter'],
                ['value' => 'cup', 'label' => 'Cup'],
                ['value' => 'tbsp', 'label' => 'Tablespoon'],
                ['value' => 'tsp', 'label' => 'Teaspoon'],
            ],
        ]);
    }
    
    /**
     * Update the specified recipe
     */
    public function update(Request $request, int $id)
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'yield_quantity' => 'required|numeric|min:0.01',
            'yield_unit' => 'required|string',
            'preparation_time' => 'nullable|integer|min:0',
            'cooking_time' => 'nullable|integer|min:0',
            'instructions' => 'nullable|string',
            'notes' => 'nullable|string',
            'labor_cost_per_hour' => 'nullable|numeric|min:0',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.ingredient_item_id' => 'required|integer|exists:items,id',
            'ingredients.*.ingredient_variant_id' => 'nullable|integer|exists:item_variants,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.001',
            'ingredients.*.unit' => 'required|string',
        ]);
        
        $recipe = $this->recipeService->updateRecipe($id, $validated);
        
        return redirect()->route('recipe.show', $recipe->id)
            ->with('success', 'Recipe updated successfully');
    }
    
    /**
     * Remove the specified recipe
     */
    public function destroy(int $id)
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        $this->recipeService->deleteRecipe($id);
        
        return redirect()->route('recipe.index')
            ->with('success', 'Recipe deleted successfully');
    }
    
    /**
     * Production planning view
     */
    public function production(): Response
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        $locationId = request()->input('location_id');
        
        return Inertia::render('item/recipes/production', [
            'recipes' => $this->recipeService->getRecipesForProduction($locationId),
            'locations' => [], // Will be fetched from location module
        ]);
    }
    
    /**
     * Check ingredient availability for production
     */
    public function checkAvailability(Request $request)
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'recipe_id' => 'required|integer|exists:recipes,id',
            'portions' => 'required|numeric|min:0.01',
            'location_id' => 'nullable|integer',
        ]);
        
        $availability = $this->recipeService->checkIngredientAvailability(
            $validated['recipe_id'],
            $validated['portions'],
            $validated['location_id']
        );
        
        return response()->json(['availability' => $availability]);
    }
    
    /**
     * Produce recipe (consume ingredients)
     */
    public function produce(Request $request)
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'recipe_id' => 'required|integer|exists:recipes,id',
            'portions' => 'required|numeric|min:0.01',
            'location_id' => 'nullable|integer',
        ]);
        
        $this->recipeService->consumeIngredients(
            $validated['recipe_id'],
            $validated['portions'],
            $validated['location_id']
        );
        
        return redirect()->route('recipe.production')
            ->with('success', 'Recipe produced successfully. Ingredients consumed.');
    }
    
    /**
     * Recipe cost analysis
     */
    public function costAnalysis(Request $request): Response
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        $locationId = $request->input('location_id');
        
        $recipes = $this->recipeService->getRecipesWithCosts($locationId);
        $analysis = $this->recipeService->analyzeCosts($recipes);
        
        return Inertia::render('item/recipes/cost-analysis', [
            'recipes' => $recipes,
            'analysis' => $analysis,
            'locations' => [], // Will be fetched from location module
        ]);
    }
}