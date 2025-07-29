<?php

namespace Colame\Item\Repositories;

use App\Core\Traits\ValidatesPagination;
use Colame\Item\Contracts\RecipeRepositoryInterface;
use Colame\Item\Data\RecipeData;
use Colame\Item\Data\IngredientData;
use Colame\Item\Data\RecipeIngredientData;
use Colame\Item\Models\Recipe;
use Colame\Item\Models\Ingredient;
use Colame\Item\Models\RecipeIngredient;
use Colame\Item\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecipeRepository implements RecipeRepositoryInterface
{
    use ValidatesPagination;
    
    /**
     * Find a recipe by ID
     */
    public function find(int $id): ?RecipeData
    {
        $recipe = Recipe::find($id);
        
        if (!$recipe) {
            return null;
        }
        
        return $this->loadRecipeData($recipe);
    }
    
    /**
     * Find a recipe by item ID
     */
    public function findByItem(int $itemId, ?int $variantId = null): ?RecipeData
    {
        $query = Recipe::where('item_id', $itemId);
        
        if ($variantId !== null) {
            $query->where('item_variant_id', $variantId);
        } else {
            $query->whereNull('item_variant_id');
        }
        
        $recipe = $query->first();
        
        if (!$recipe) {
            return null;
        }
        
        return $this->loadRecipeData($recipe);
    }
    
    /**
     * Get all ingredients
     */
    public function getAllIngredients(): Collection
    {
        return Ingredient::active()
            ->orderBy('name')
            ->get()
            ->map(fn($ingredient) => IngredientData::from($ingredient));
    }
    
    /**
     * Find an ingredient by ID
     */
    public function findIngredient(int $id): ?IngredientData
    {
        $ingredient = Ingredient::find($id);
        
        return $ingredient ? IngredientData::from($ingredient) : null;
    }
    
    /**
     * Create a new recipe
     */
    public function create(array $data): RecipeData
    {
        $recipe = Recipe::create($data);
        
        return RecipeData::from($recipe);
    }
    
    /**
     * Update a recipe
     */
    public function update(int $id, array $data): RecipeData
    {
        $recipe = Recipe::findOrFail($id);
        $recipe->update($data);
        
        return $this->loadRecipeData($recipe->fresh());
    }
    
    /**
     * Create a new ingredient
     */
    public function createIngredient(array $data): IngredientData
    {
        $ingredient = Ingredient::create($data);
        
        return IngredientData::from($ingredient);
    }
    
    /**
     * Update an ingredient
     */
    public function updateIngredient(int $id, array $data): IngredientData
    {
        $ingredient = Ingredient::findOrFail($id);
        $ingredient->update($data);
        
        return IngredientData::from($ingredient->fresh());
    }
    
    /**
     * Add ingredients to a recipe
     */
    public function addIngredientsToRecipe(int $recipeId, array $ingredients): void
    {
        foreach ($ingredients as $ingredientData) {
            RecipeIngredient::create([
                'recipe_id' => $recipeId,
                'ingredient_id' => $ingredientData['ingredient_id'],
                'quantity' => $ingredientData['quantity'],
                'unit' => $ingredientData['unit'],
                'is_optional' => $ingredientData['is_optional'] ?? false,
            ]);
        }
    }
    
    /**
     * Update recipe ingredients
     */
    public function updateRecipeIngredients(int $recipeId, array $ingredients): void
    {
        // Delete existing ingredients
        RecipeIngredient::where('recipe_id', $recipeId)->delete();
        
        // Add new ingredients
        $this->addIngredientsToRecipe($recipeId, $ingredients);
    }
    
    /**
     * Remove ingredient from recipe
     */
    public function removeIngredientFromRecipe(int $recipeId, int $ingredientId): bool
    {
        return RecipeIngredient::where('recipe_id', $recipeId)
            ->where('ingredient_id', $ingredientId)
            ->delete() > 0;
    }
    
    /**
     * Calculate recipe cost
     */
    public function calculateRecipeCost(int $recipeId): float
    {
        $recipeIngredients = RecipeIngredient::where('recipe_id', $recipeId)
            ->join('ingredients', 'ingredients.id', '=', 'recipe_ingredients.ingredient_id')
            ->select('recipe_ingredients.*', 'ingredients.cost_per_unit', 'ingredients.unit as ingredient_unit')
            ->get();
        
        $totalCost = 0;
        
        foreach ($recipeIngredients as $recipeIngredient) {
            // Convert quantity to ingredient's base unit if needed
            $quantity = $this->convertToBaseUnit(
                $recipeIngredient->quantity,
                $recipeIngredient->unit,
                $recipeIngredient->ingredient_unit
            );
            
            $totalCost += $quantity * $recipeIngredient->cost_per_unit;
        }
        
        return $totalCost;
    }
    
    /**
     * Calculate item cost including all components
     */
    public function calculateItemCost(int $itemId, ?int $variantId = null): float
    {
        $recipe = $this->findByItem($itemId, $variantId);
        
        if (!$recipe) {
            // Return base cost if no recipe
            $item = Item::find($itemId);
            return $item ? $item->base_cost : 0;
        }
        
        $recipeCost = $this->calculateRecipeCost($recipe->id);
        
        // Adjust for yield
        if ($recipe->yieldQuantity > 0) {
            return $recipeCost / $recipe->yieldQuantity;
        }
        
        return $recipeCost;
    }
    
    /**
     * Update item costs based on recipe
     */
    public function updateItemCosts(): int
    {
        $count = 0;
        
        $recipes = Recipe::all();
        
        foreach ($recipes as $recipe) {
            $cost = $this->calculateRecipeCost($recipe->id);
            
            // Adjust for yield
            if ($recipe->yield_quantity > 0) {
                $cost = $cost / $recipe->yield_quantity;
            }
            
            $item = Item::find($recipe->item_id);
            if ($item) {
                $item->base_cost = round($cost, 2);
                $item->save();
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Check ingredient availability for a recipe
     */
    public function checkIngredientAvailability(int $recipeId, int $quantity = 1): array
    {
        $recipe = Recipe::findOrFail($recipeId);
        $recipeIngredients = RecipeIngredient::where('recipe_id', $recipeId)
            ->join('ingredients', 'ingredients.id', '=', 'recipe_ingredients.ingredient_id')
            ->select('recipe_ingredients.*', 'ingredients.name', 'ingredients.current_stock', 'ingredients.unit as ingredient_unit')
            ->get();
        
        $availability = [
            'available' => true,
            'ingredients' => [],
        ];
        
        foreach ($recipeIngredients as $recipeIngredient) {
            if ($recipeIngredient->is_optional) {
                continue;
            }
            
            // Convert required quantity to ingredient's base unit
            $requiredQuantity = $this->convertToBaseUnit(
                $recipeIngredient->quantity * $quantity,
                $recipeIngredient->unit,
                $recipeIngredient->ingredient_unit
            );
            
            $isAvailable = $recipeIngredient->current_stock >= $requiredQuantity;
            
            $availability['ingredients'][] = [
                'ingredient_id' => $recipeIngredient->ingredient_id,
                'name' => $recipeIngredient->name,
                'required_quantity' => $requiredQuantity,
                'current_stock' => $recipeIngredient->current_stock,
                'unit' => $recipeIngredient->ingredient_unit,
                'is_available' => $isAvailable,
                'shortage' => $isAvailable ? 0 : $requiredQuantity - $recipeIngredient->current_stock,
            ];
            
            if (!$isAvailable) {
                $availability['available'] = false;
            }
        }
        
        return $availability;
    }
    
    /**
     * Get recipes using an ingredient
     */
    public function getRecipesUsingIngredient(int $ingredientId): Collection
    {
        $recipeIds = RecipeIngredient::where('ingredient_id', $ingredientId)
            ->pluck('recipe_id')
            ->unique();
        
        return Recipe::whereIn('id', $recipeIds)
            ->get()
            ->map(fn($recipe) => $this->loadRecipeData($recipe));
    }
    
    /**
     * Update ingredient prices
     */
    public function updateIngredientPrices(array $priceUpdates): int
    {
        $count = 0;
        
        DB::transaction(function () use ($priceUpdates, &$count) {
            foreach ($priceUpdates as $update) {
                $updated = Ingredient::where('id', $update['ingredient_id'])
                    ->update(['cost_per_unit' => $update['price']]);
                
                if ($updated) {
                    $count++;
                }
            }
        });
        
        return $count;
    }
    
    /**
     * Get ingredients below reorder level
     */
    public function getIngredientsBelowReorderLevel(): Collection
    {
        return Ingredient::needsReorder()
            ->get()
            ->map(fn($ingredient) => IngredientData::from($ingredient));
    }
    
    /**
     * Calculate ingredient requirements for production
     */
    public function calculateIngredientRequirements(array $itemQuantities): Collection
    {
        $requirements = collect();
        
        foreach ($itemQuantities as $data) {
            $itemId = $data['item_id'];
            $variantId = $data['variant_id'] ?? null;
            $quantity = $data['quantity'];
            
            $recipe = $this->findByItem($itemId, $variantId);
            
            if (!$recipe) {
                continue;
            }
            
            $recipeIngredients = RecipeIngredient::where('recipe_id', $recipe->id)
                ->join('ingredients', 'ingredients.id', '=', 'recipe_ingredients.ingredient_id')
                ->select('recipe_ingredients.*', 'ingredients.name', 'ingredients.unit as ingredient_unit')
                ->get();
            
            foreach ($recipeIngredients as $recipeIngredient) {
                $requiredQuantity = $this->convertToBaseUnit(
                    $recipeIngredient->quantity * $quantity,
                    $recipeIngredient->unit,
                    $recipeIngredient->ingredient_unit
                );
                
                $key = $recipeIngredient->ingredient_id;
                
                if ($requirements->has($key)) {
                    $existing = $requirements->get($key);
                    $existing['quantity'] += $requiredQuantity;
                    $requirements->put($key, $existing);
                } else {
                    $requirements->put($key, [
                        'ingredient_id' => $recipeIngredient->ingredient_id,
                        'name' => $recipeIngredient->name,
                        'quantity' => $requiredQuantity,
                        'unit' => $recipeIngredient->ingredient_unit,
                    ]);
                }
            }
        }
        
        return $requirements->values();
    }
    
    /**
     * Delete a recipe
     */
    public function delete(int $id): bool
    {
        return Recipe::where('id', $id)->delete() > 0;
    }
    
    /**
     * Delete an ingredient
     */
    public function deleteIngredient(int $id): bool
    {
        $ingredient = Ingredient::find($id);
        
        return $ingredient ? $ingredient->delete() : false;
    }
    
    /**
     * Get recipe yield adjustments
     */
    public function calculateYieldAdjustment(int $recipeId, float $desiredYield): array
    {
        $recipe = Recipe::findOrFail($recipeId);
        $scalingFactor = $desiredYield / $recipe->yield_quantity;
        
        $recipeIngredients = RecipeIngredient::where('recipe_id', $recipeId)
            ->join('ingredients', 'ingredients.id', '=', 'recipe_ingredients.ingredient_id')
            ->select('recipe_ingredients.*', 'ingredients.name')
            ->get();
        
        $adjustedIngredients = [];
        
        foreach ($recipeIngredients as $ingredient) {
            $adjustedIngredients[] = [
                'ingredient_id' => $ingredient->ingredient_id,
                'name' => $ingredient->name,
                'original_quantity' => $ingredient->quantity,
                'adjusted_quantity' => round($ingredient->quantity * $scalingFactor, 2),
                'unit' => $ingredient->unit,
            ];
        }
        
        return [
            'original_yield' => $recipe->yield_quantity,
            'desired_yield' => $desiredYield,
            'scaling_factor' => round($scalingFactor, 2),
            'yield_unit' => $recipe->yield_unit,
            'ingredients' => $adjustedIngredients,
        ];
    }
    
    /**
     * Load recipe data with ingredients
     */
    private function loadRecipeData(Recipe $recipe): RecipeData
    {
        $ingredients = RecipeIngredient::where('recipe_id', $recipe->id)
            ->join('ingredients', 'ingredients.id', '=', 'recipe_ingredients.ingredient_id')
            ->select('recipe_ingredients.*', 'ingredients.*', 'recipe_ingredients.id as recipe_ingredient_id')
            ->get()
            ->map(function ($row) {
                $ingredient = IngredientData::from([
                    'id' => $row->ingredient_id,
                    'name' => $row->name,
                    'unit' => $row->unit,
                    'cost_per_unit' => $row->cost_per_unit,
                    'supplier_id' => $row->supplier_id,
                    'storage_requirements' => $row->storage_requirements,
                    'shelf_life_days' => $row->shelf_life_days,
                    'current_stock' => $row->current_stock,
                    'reorder_level' => $row->reorder_level,
                    'reorder_quantity' => $row->reorder_quantity,
                    'is_active' => $row->is_active,
                ]);
                
                return RecipeIngredientData::from([
                    'id' => $row->recipe_ingredient_id,
                    'recipe_id' => $row->recipe_id,
                    'ingredient_id' => $row->ingredient_id,
                    'quantity' => $row->quantity,
                    'unit' => $row->unit,
                    'is_optional' => $row->is_optional,
                    'ingredient' => $ingredient,
                ]);
            })
            ->all();
        
        $totalCost = $this->calculateRecipeCost($recipe->id);
        $costPerUnit = $recipe->yield_quantity > 0 ? $totalCost / $recipe->yield_quantity : $totalCost;
        
        return RecipeData::from([
            'id' => $recipe->id,
            'item_id' => $recipe->item_id,
            'item_variant_id' => $recipe->item_variant_id,
            'instructions' => $recipe->instructions,
            'prep_time_minutes' => $recipe->prep_time_minutes,
            'cook_time_minutes' => $recipe->cook_time_minutes,
            'yield_quantity' => $recipe->yield_quantity,
            'yield_unit' => $recipe->yield_unit,
            'notes' => $recipe->notes,
            'ingredients' => $ingredients,
            'total_cost' => $totalCost,
            'cost_per_unit' => $costPerUnit,
            'created_at' => $recipe->created_at,
            'updated_at' => $recipe->updated_at,
        ]);
    }
    
    /**
     * Convert quantity to base unit
     */
    private function convertToBaseUnit(float $quantity, string $fromUnit, string $toUnit): float
    {
        // If units match, no conversion needed
        if (strtolower($fromUnit) === strtolower($toUnit)) {
            return $quantity;
        }
        
        // Simple conversion logic
        $conversions = [
            'kg' => ['g' => 1000],
            'l' => ['ml' => 1000],
            'dozen' => ['unit' => 12],
            'case' => ['unit' => 24],
        ];
        
        $from = strtolower($fromUnit);
        $to = strtolower($toUnit);
        
        if (isset($conversions[$from][$to])) {
            return $quantity * $conversions[$from][$to];
        }
        
        if (isset($conversions[$to][$from])) {
            return $quantity / $conversions[$to][$from];
        }
        
        // If no conversion found, return as is
        return $quantity;
    }
    
    /**
     * Paginate ingredients with filters
     */
    public function paginateWithFilters(
        array $filters = [],
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        $perPage = $this->validatePerPage($perPage);
        
        $query = Ingredient::query();
        
        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        if (!empty($filters['needs_reorder'])) {
            $query->needsReorder();
        }
        
        if (!empty($filters['out_of_stock'])) {
            $query->outOfStock();
        }
        
        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }
        
        // Sort
        if (!empty($filters['sort'])) {
            $sortField = ltrim($filters['sort'], '-');
            $sortDirection = str_starts_with($filters['sort'], '-') ? 'desc' : 'asc';
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('name');
        }
        
        return $query->paginate($perPage, $columns, $pageName, $page);
    }
}