<?php

namespace Colame\Item\Contracts;

use App\Core\Contracts\BaseRepositoryInterface;
use Colame\Item\Data\RecipeData;
use Colame\Item\Data\IngredientData;
use Colame\Item\Data\RecipeIngredientData;
use Illuminate\Support\Collection;

interface RecipeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a recipe by ID
     */
    public function find(int $id): ?RecipeData;
    
    /**
     * Find a recipe by item ID
     */
    public function findByItem(int $itemId, ?int $variantId = null): ?RecipeData;
    
    /**
     * Get all ingredients
     */
    public function getAllIngredients(): Collection;
    
    /**
     * Find an ingredient by ID
     */
    public function findIngredient(int $id): ?IngredientData;
    
    /**
     * Create a new recipe
     */
    public function create(array $data): RecipeData;
    
    /**
     * Update a recipe
     */
    public function update(int $id, array $data): bool;
    
    /**
     * Update a recipe and return the updated data
     */
    public function updateAndReturn(int $id, array $data): RecipeData;
    
    /**
     * Create a new ingredient
     */
    public function createIngredient(array $data): IngredientData;
    
    /**
     * Update an ingredient
     */
    public function updateIngredient(int $id, array $data): bool;
    
    /**
     * Update an ingredient and return the updated data
     */
    public function updateIngredientAndReturn(int $id, array $data): IngredientData;
    
    /**
     * Add ingredients to a recipe
     */
    public function addIngredientsToRecipe(int $recipeId, array $ingredients): void;
    
    /**
     * Update recipe ingredients
     */
    public function updateRecipeIngredients(int $recipeId, array $ingredients): void;
    
    /**
     * Remove ingredient from recipe
     */
    public function removeIngredientFromRecipe(int $recipeId, int $ingredientId): bool;
    
    /**
     * Calculate recipe cost
     */
    public function calculateRecipeCost(int $recipeId): float;
    
    /**
     * Calculate item cost including all components
     */
    public function calculateItemCost(int $itemId, ?int $variantId = null): float;
    
    /**
     * Update item costs based on recipe
     */
    public function updateItemCosts(): int;
    
    /**
     * Check ingredient availability for a recipe
     */
    public function checkIngredientAvailability(int $recipeId, int $quantity = 1): array;
    
    /**
     * Get recipes using an ingredient
     */
    public function getRecipesUsingIngredient(int $ingredientId): Collection;
    
    /**
     * Update ingredient prices
     */
    public function updateIngredientPrices(array $priceUpdates): int;
    
    /**
     * Get ingredients below reorder level
     */
    public function getIngredientsBelowReorderLevel(): Collection;
    
    /**
     * Calculate ingredient requirements for production
     */
    public function calculateIngredientRequirements(array $itemQuantities): Collection;
    
    /**
     * Delete a recipe
     */
    public function delete(int $id): bool;
    
    /**
     * Delete an ingredient
     */
    public function deleteIngredient(int $id): bool;
    
    /**
     * Get recipe yield adjustments
     */
    public function calculateYieldAdjustment(int $recipeId, float $desiredYield): array;
}