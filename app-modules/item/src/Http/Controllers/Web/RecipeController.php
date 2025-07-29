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
        
        return Inertia::render('item/recipes/index', [
            'recipes' => $this->recipeService->getRecipes($filters),
            'ingredients' => $this->recipeService->getAllIngredients(),
            'features' => [
                'preparation_cost' => $this->features->isEnabled('item.preparation_cost'),
                'recipe_scaling' => $this->features->isEnabled('item.recipe_scaling'),
            ],
        ]);
    }
    
    /**
     * Show the form for creating a new recipe
     */
    public function create(Request $request): Response
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        $itemId = $request->input('item_id');
        $item = $itemId ? $this->itemService->find($itemId) : null;
        
        return Inertia::render('item/recipes/create', [
            'item' => $item,
            'items' => $this->itemService->getItemsForSelect([
                'without_recipe' => true,
                'type' => ['product', 'combo'],
            ]),
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
     * Store a newly created recipe
     */
    public function store(Request $request)
    {
        if (!$this->features->isEnabled('item.recipes')) {
            abort(404);
        }
        
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'variant_id' => 'nullable|integer|exists:item_variants,id',
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