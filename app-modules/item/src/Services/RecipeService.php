<?php

namespace Colame\Item\Services;

use App\Core\Services\BaseService;
use App\Core\Contracts\FeatureFlagInterface;
use Colame\Item\Contracts\RecipeRepositoryInterface;
use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Contracts\InventoryRepositoryInterface;
use Colame\Item\Data\RecipeData;
use Colame\Item\Data\RecipeIngredientData;
use Colame\Item\Data\RecipeCostData;
use Colame\Item\Data\IngredientData;
use Colame\Item\Exceptions\ItemNotFoundException;
use Colame\Item\Exceptions\RecipeNotFoundException;
use Colame\Item\Exceptions\InvalidRecipeException;
use Colame\Item\Exceptions\InsufficientIngredientsException;
use Colame\Item\Events\RecipeCreated;
use Colame\Item\Events\RecipeUpdated;
use Colame\Item\Events\RecipeCostUpdated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RecipeService extends BaseService
{
    private const COST_CACHE_TTL = 86400; // 24 hours
    
    public function __construct(
        private readonly RecipeRepositoryInterface $recipeRepository,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly FeatureFlagInterface $features,
    ) {}
    
    /**
     * Create a new recipe
     */
    public function createRecipe(array $data): RecipeData
    {
        $this->authorize('item.recipes.create');
        
        // Validate item exists
        $item = $this->itemRepository->find($data['item_id']);
        if (!$item) {
            throw new ItemNotFoundException($data['item_id']);
        }
        
        // Validate variant if specified
        if (!empty($data['variant_id'])) {
            $variant = $this->itemRepository->findVariant($data['variant_id']);
            if (!$variant || $variant->itemId != $data['item_id']) {
                throw new InvalidRecipeException('Invalid variant for item');
            }
        }
        
        DB::beginTransaction();
        try {
            // Create recipe
            $recipe = $this->recipeRepository->createRecipe($data);
            
            // Add ingredients if provided
            if (!empty($data['ingredients'])) {
                foreach ($data['ingredients'] as $ingredient) {
                    $this->addIngredientToRecipe($recipe->id, $ingredient);
                }
            }
            
            // Calculate initial cost
            $this->calculateRecipeCost($recipe->id);
            
            DB::commit();
            
            event(new RecipeCreated($recipe));
            
            return $this->recipeRepository->findWithIngredients($recipe->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create recipe', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update a recipe
     */
    public function updateRecipe(int $recipeId, array $data): RecipeData
    {
        $this->authorize('item.recipes.update');
        
        $recipe = $this->recipeRepository->find($recipeId);
        if (!$recipe) {
            throw new RecipeNotFoundException($recipeId);
        }
        
        DB::beginTransaction();
        try {
            $updatedRecipe = $this->recipeRepository->updateRecipe($recipeId, $data);
            
            // Update ingredients if provided
            if (isset($data['ingredients'])) {
                $this->syncRecipeIngredients($recipeId, $data['ingredients']);
            }
            
            // Recalculate cost
            $this->calculateRecipeCost($recipeId);
            
            DB::commit();
            
            event(new RecipeUpdated($updatedRecipe));
            
            return $this->recipeRepository->findWithIngredients($recipeId);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update recipe', [
                'recipe_id' => $recipeId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Delete a recipe
     */
    public function deleteRecipe(int $recipeId): bool
    {
        $this->authorize('item.recipes.delete');
        
        $recipe = $this->recipeRepository->find($recipeId);
        if (!$recipe) {
            throw new RecipeNotFoundException($recipeId);
        }
        
        DB::beginTransaction();
        try {
            $deleted = $this->recipeRepository->deleteRecipe($recipeId);
            
            // Clear cost cache
            $this->clearRecipeCostCache($recipe->itemId, $recipe->variantId);
            
            DB::commit();
            
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete recipe', [
                'recipe_id' => $recipeId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Add ingredient to recipe
     */
    public function addIngredientToRecipe(int $recipeId, array $ingredientData): RecipeIngredientData
    {
        $recipe = $this->recipeRepository->find($recipeId);
        if (!$recipe) {
            throw new RecipeNotFoundException($recipeId);
        }
        
        // Validate ingredient item exists
        $ingredientItem = $this->itemRepository->find($ingredientData['ingredient_item_id']);
        if (!$ingredientItem) {
            throw new ItemNotFoundException($ingredientData['ingredient_item_id']);
        }
        
        // Check for circular dependency
        if ($this->hasCircularDependency($recipe->itemId, $ingredientData['ingredient_item_id'])) {
            throw new InvalidRecipeException('Circular dependency detected');
        }
        
        return $this->recipeRepository->addIngredient($recipeId, $ingredientData);
    }
    
    /**
     * Remove ingredient from recipe
     */
    public function removeIngredientFromRecipe(int $recipeId, int $ingredientId): bool
    {
        $recipe = $this->recipeRepository->find($recipeId);
        if (!$recipe) {
            throw new RecipeNotFoundException($recipeId);
        }
        
        return $this->recipeRepository->removeIngredient($recipeId, $ingredientId);
    }
    
    /**
     * Calculate recipe cost
     */
    public function calculateRecipeCost(int $recipeId, ?int $locationId = null): RecipeCostData
    {
        $recipe = $this->recipeRepository->findWithIngredients($recipeId);
        if (!$recipe) {
            throw new RecipeNotFoundException($recipeId);
        }
        
        $totalCost = 0;
        $ingredientCosts = [];
        $unavailableIngredients = [];
        
        foreach ($recipe->ingredients as $ingredient) {
            // Get ingredient cost
            $cost = $this->getIngredientCost($ingredient, $locationId);
            
            if ($cost === null) {
                $unavailableIngredients[] = $ingredient->ingredientItemId;
                continue;
            }
            
            $ingredientTotal = $cost * $ingredient->quantity;
            $totalCost += $ingredientTotal;
            
            $ingredientCosts[] = [
                'ingredient_id' => $ingredient->ingredientItemId,
                'quantity' => $ingredient->quantity,
                'unit_cost' => $cost,
                'total_cost' => $ingredientTotal,
                'unit' => $ingredient->unit
            ];
        }
        
        // Add preparation cost if enabled
        if ($this->features->isEnabled('item.preparation_cost')) {
            $prepCost = $this->calculatePreparationCost($recipe);
            $totalCost += $prepCost;
        }
        
        $costData = new RecipeCostData(
            recipeId: $recipeId,
            itemId: $recipe->itemId,
            variantId: $recipe->variantId,
            totalCost: $totalCost,
            ingredientCosts: $ingredientCosts,
            preparationCost: $prepCost ?? 0,
            portionCost: $recipe->yieldQuantity > 0 ? $totalCost / $recipe->yieldQuantity : 0,
            lastCalculated: now(),
            hasUnavailableIngredients: !empty($unavailableIngredients)
        );
        
        // Update recipe cost in database
        $this->recipeRepository->updateRecipeCost($recipeId, $totalCost);
        
        // Cache cost
        $this->cacheRecipeCost($recipe->itemId, $recipe->variantId, $costData);
        
        event(new RecipeCostUpdated($costData));
        
        return $costData;
    }
    
    /**
     * Get recipe by item
     */
    public function getRecipeByItem(int $itemId, ?int $variantId = null): ?RecipeData
    {
        $item = $this->itemRepository->find($itemId);
        if (!$item) {
            throw new ItemNotFoundException($itemId);
        }
        
        return $this->recipeRepository->findByItem($itemId, $variantId);
    }
    
    /**
     * Get recipes using ingredient
     */
    public function getRecipesUsingIngredient(int $ingredientItemId): Collection
    {
        return $this->recipeRepository->getRecipesUsingIngredient($ingredientItemId);
    }
    
    /**
     * Check ingredient availability for recipe
     */
    public function checkIngredientAvailability(
        int $recipeId,
        float $portions,
        ?int $locationId = null
    ): array {
        $recipe = $this->recipeRepository->findWithIngredients($recipeId);
        if (!$recipe) {
            throw new RecipeNotFoundException($recipeId);
        }
        
        $available = true;
        $shortages = [];
        
        foreach ($recipe->ingredients as $ingredient) {
            $requiredQuantity = $ingredient->quantity * $portions;
            
            // Check inventory
            $hasStock = $this->inventoryRepository->checkAvailability(
                $ingredient->ingredientItemId,
                $requiredQuantity,
                $ingredient->ingredientVariantId,
                $locationId
            );
            
            if (!$hasStock) {
                $available = false;
                $inventory = $this->inventoryRepository->getInventoryLevel(
                    $ingredient->ingredientItemId,
                    $ingredient->ingredientVariantId,
                    $locationId
                );
                
                $shortages[] = [
                    'ingredient_id' => $ingredient->ingredientItemId,
                    'required' => $requiredQuantity,
                    'available' => $inventory ? $inventory->quantityOnHand : 0,
                    'shortage' => $requiredQuantity - ($inventory ? $inventory->quantityOnHand : 0)
                ];
            }
        }
        
        return [
            'available' => $available,
            'shortages' => $shortages
        ];
    }
    
    /**
     * Consume ingredients for recipe production
     */
    public function consumeIngredients(
        int $recipeId,
        float $portions,
        ?int $locationId = null
    ): bool {
        $this->authorize('item.inventory.adjust');
        
        $recipe = $this->recipeRepository->findWithIngredients($recipeId);
        if (!$recipe) {
            throw new RecipeNotFoundException($recipeId);
        }
        
        // First check availability
        $availability = $this->checkIngredientAvailability($recipeId, $portions, $locationId);
        if (!$availability['available']) {
            throw new InsufficientIngredientsException($availability['shortages']);
        }
        
        DB::beginTransaction();
        try {
            foreach ($recipe->ingredients as $ingredient) {
                $consumeQuantity = -($ingredient->quantity * $portions);
                
                $this->inventoryRepository->adjustInventory([
                    'item_id' => $ingredient->ingredientItemId,
                    'variant_id' => $ingredient->ingredientVariantId,
                    'location_id' => $locationId,
                    'quantity_change' => $consumeQuantity,
                    'adjustment_type' => 'recipe_production',
                    'reason' => "Recipe production for {$recipe->name}",
                    'reference_type' => 'recipe',
                    'reference_id' => $recipeId
                ]);
            }
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to consume ingredients', [
                'recipe_id' => $recipeId,
                'portions' => $portions,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get all ingredients
     */
    public function getAllIngredients(): Collection
    {
        return $this->recipeRepository->getAllIngredients();
    }
    
    /**
     * Sync recipe ingredients
     */
    private function syncRecipeIngredients(int $recipeId, array $ingredients): void
    {
        // Remove existing ingredients
        $this->recipeRepository->removeAllIngredients($recipeId);
        
        // Add new ingredients
        foreach ($ingredients as $ingredient) {
            $this->addIngredientToRecipe($recipeId, $ingredient);
        }
    }
    
    /**
     * Check for circular dependency
     */
    private function hasCircularDependency(int $itemId, int $ingredientItemId): bool
    {
        if ($itemId == $ingredientItemId) {
            return true;
        }
        
        // Check if ingredient has a recipe that uses the original item
        $ingredientRecipe = $this->recipeRepository->findByItem($ingredientItemId);
        if (!$ingredientRecipe) {
            return false;
        }
        
        foreach ($ingredientRecipe->ingredients as $subIngredient) {
            if ($this->hasCircularDependency($itemId, $subIngredient->ingredientItemId)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get ingredient cost
     */
    private function getIngredientCost(RecipeIngredientData $ingredient, ?int $locationId): ?float
    {
        // Get inventory record for cost
        $inventory = $this->inventoryRepository->getInventoryLevel(
            $ingredient->ingredientItemId,
            $ingredient->ingredientVariantId,
            $locationId
        );
        
        if (!$inventory || $inventory->unitCost === null) {
            // Try to get from item base cost
            $item = $this->itemRepository->find($ingredient->ingredientItemId);
            return $item ? $item->cost : null;
        }
        
        return $inventory->unitCost;
    }
    
    /**
     * Calculate preparation cost
     */
    private function calculatePreparationCost(RecipeData $recipe): float
    {
        if (!$recipe->preparationTime || !$recipe->laborCostPerHour) {
            return 0;
        }
        
        $hours = $recipe->preparationTime / 60;
        return $hours * $recipe->laborCostPerHour;
    }
    
    /**
     * Cache recipe cost
     */
    private function cacheRecipeCost(int $itemId, ?int $variantId, RecipeCostData $cost): void
    {
        $cacheKey = $this->buildCostCacheKey($itemId, $variantId);
        Cache::put($cacheKey, $cost, self::COST_CACHE_TTL);
    }
    
    /**
     * Clear recipe cost cache
     */
    private function clearRecipeCostCache(int $itemId, ?int $variantId): void
    {
        $cacheKey = $this->buildCostCacheKey($itemId, $variantId);
        Cache::forget($cacheKey);
    }
    
    /**
     * Build cost cache key
     */
    private function buildCostCacheKey(int $itemId, ?int $variantId): string
    {
        return sprintf('recipe_cost:%d:%s', $itemId, $variantId ?? 'base');
    }
}