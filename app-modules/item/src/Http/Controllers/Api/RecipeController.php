<?php

namespace Colame\Item\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Item\Services\RecipeService;
use App\Core\Contracts\FeatureFlagInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RecipeController extends Controller
{
    public function __construct(
        private readonly RecipeService $recipeService,
        private readonly FeatureFlagInterface $features,
    ) {}
    
    /**
     * Get all recipes
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.recipes')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Recipes are not enabled',
            ], 404);
        }
        
        $filters = $request->only(['search', 'category_id', 'has_unavailable_ingredients']);
        $recipes = $this->recipeService->getRecipes($filters);
        
        return response()->json([
            'data' => $recipes,
            'meta' => [
                'count' => $recipes->count(),
                'features' => [
                    'preparation_cost' => $this->features->isEnabled('item.preparation_cost'),
                    'recipe_scaling' => $this->features->isEnabled('item.recipe_scaling'),
                ],
            ],
        ]);
    }
    
    /**
     * Get recipe by item
     */
    public function getByItem(int $itemId, Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.recipes')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Recipes are not enabled',
            ], 404);
        }
        
        $variantId = $request->input('variant_id');
        
        try {
            $recipe = $this->recipeService->getRecipeByItem($itemId, $variantId);
            
            if (!$recipe) {
                return response()->json([
                    'error' => 'Recipe not found',
                    'message' => 'No recipe found for the specified item',
                ], 404);
            }
            
            return response()->json([
                'data' => $recipe,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Item not found',
                'message' => $e->getMessage(),
            ], 404);
        }
    }
    
    /**
     * Get recipe details
     */
    public function show(int $id): JsonResponse
    {
        if (!$this->features->isEnabled('item.recipes')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Recipes are not enabled',
            ], 404);
        }
        
        $recipe = $this->recipeService->findWithIngredients($id);
        
        if (!$recipe) {
            return response()->json([
                'error' => 'Recipe not found',
                'message' => 'The requested recipe does not exist',
            ], 404);
        }
        
        return response()->json([
            'data' => $recipe,
        ]);
    }
    
    /**
     * Create recipe
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.recipes')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Recipes are not enabled',
            ], 404);
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
        
        try {
            $recipe = $this->recipeService->createRecipe($validated);
            
            return response()->json([
                'data' => $recipe,
                'message' => 'Recipe created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Creation failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Update recipe
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->features->isEnabled('item.recipes')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Recipes are not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'yield_quantity' => 'sometimes|required|numeric|min:0.01',
            'yield_unit' => 'sometimes|required|string',
            'preparation_time' => 'nullable|integer|min:0',
            'cooking_time' => 'nullable|integer|min:0',
            'instructions' => 'nullable|string',
            'notes' => 'nullable|string',
            'labor_cost_per_hour' => 'nullable|numeric|min:0',
            'ingredients' => 'sometimes|required|array|min:1',
            'ingredients.*.ingredient_item_id' => 'required|integer|exists:items,id',
            'ingredients.*.ingredient_variant_id' => 'nullable|integer|exists:item_variants,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.001',
            'ingredients.*.unit' => 'required|string',
        ]);
        
        try {
            $recipe = $this->recipeService->updateRecipe($id, $validated);
            
            return response()->json([
                'data' => $recipe,
                'message' => 'Recipe updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Update failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Delete recipe
     */
    public function destroy(int $id): JsonResponse
    {
        if (!$this->features->isEnabled('item.recipes')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Recipes are not enabled',
            ], 404);
        }
        
        try {
            $this->recipeService->deleteRecipe($id);
            
            return response()->json([
                'message' => 'Recipe deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Deletion failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Calculate recipe cost
     */
    public function calculateCost(int $id, Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.recipes')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Recipes are not enabled',
            ], 404);
        }
        
        $locationId = $request->input('location_id');
        
        try {
            $cost = $this->recipeService->calculateRecipeCost($id, $locationId);
            
            return response()->json([
                'data' => $cost,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Calculation failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Check ingredient availability
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.recipes')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Recipes are not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'recipe_id' => 'required|integer|exists:recipes,id',
            'portions' => 'required|numeric|min:0.01',
            'location_id' => 'nullable|integer',
        ]);
        
        try {
            $availability = $this->recipeService->checkIngredientAvailability(
                $validated['recipe_id'],
                $validated['portions'],
                $validated['location_id']
            );
            
            return response()->json([
                'data' => $availability,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Check failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Produce recipe (consume ingredients)
     */
    public function produce(Request $request): JsonResponse
    {
        if (!$this->features->isEnabled('item.recipes')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Recipes are not enabled',
            ], 404);
        }
        
        $validated = $request->validate([
            'recipe_id' => 'required|integer|exists:recipes,id',
            'portions' => 'required|numeric|min:0.01',
            'location_id' => 'nullable|integer',
        ]);
        
        try {
            $this->recipeService->consumeIngredients(
                $validated['recipe_id'],
                $validated['portions'],
                $validated['location_id']
            );
            
            return response()->json([
                'message' => 'Recipe produced successfully. Ingredients consumed.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Production failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get all ingredients
     */
    public function ingredients(): JsonResponse
    {
        if (!$this->features->isEnabled('item.recipes')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Recipes are not enabled',
            ], 404);
        }
        
        $ingredients = $this->recipeService->getAllIngredients();
        
        return response()->json([
            'data' => $ingredients,
            'meta' => [
                'count' => $ingredients->count(),
            ],
        ]);
    }
    
    /**
     * Get recipes using an ingredient
     */
    public function recipesUsingIngredient(int $ingredientItemId): JsonResponse
    {
        if (!$this->features->isEnabled('item.recipes')) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => 'Recipes are not enabled',
            ], 404);
        }
        
        $recipes = $this->recipeService->getRecipesUsingIngredient($ingredientItemId);
        
        return response()->json([
            'data' => $recipes,
            'meta' => [
                'count' => $recipes->count(),
                'ingredient_item_id' => $ingredientItemId,
            ],
        ]);
    }
}