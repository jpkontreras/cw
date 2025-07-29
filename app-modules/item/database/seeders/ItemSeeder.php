<?php

namespace Colame\Item\Database\Seeders;

use Illuminate\Database\Seeder;
use Colame\Item\Models\Item;
use Colame\Item\Models\ItemVariant;
use Colame\Item\Models\ModifierGroup;
use Colame\Item\Models\ItemModifier;
use Colame\Item\Models\ItemImage;
use Colame\Item\Models\Ingredient;
use Colame\Item\Models\Recipe;
use Colame\Item\Models\RecipeIngredient;
use Colame\Item\Models\ItemInventory;
use Colame\Item\Models\ItemLocationPrice;
use Illuminate\Support\Facades\DB;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedFoodItems();
            $this->seedBeverageItems();
            $this->seedModifiers();
            $this->seedRecipes();
            $this->seedInventory();
            $this->seedLocationPricing();
        });
    }
    
    /**
     * Seed food items
     */
    private function seedFoodItems(): void
    {
        // Main dishes
        $empanada = Item::create([
            'name' => 'Empanada',
            'description' => 'Traditional Chilean empanada',
            'type' => 'product',
            'category_id' => 1, // Food category
            'base_price' => 2500,
            'cost' => 800,
            'sku' => 'EMP-001',
            'track_stock' => true,
            'is_available' => true,
            'allow_modifiers' => true,
            'preparation_time' => 15,
            'sort_order' => 1,
        ]);
        
        // Create variants for empanada
        ItemVariant::create([
            'item_id' => $empanada->id,
            'name' => 'Empanada de Pino',
            'sku' => 'EMP-001-PINO',
            'price' => 2500,
            'cost' => 800,
            'track_stock' => true,
            'is_available' => true,
            'sort_order' => 1,
        ]);
        
        ItemVariant::create([
            'item_id' => $empanada->id,
            'name' => 'Empanada de Queso',
            'sku' => 'EMP-001-QUESO',
            'price' => 2200,
            'cost' => 700,
            'track_stock' => true,
            'is_available' => true,
            'sort_order' => 2,
        ]);
        
        ItemVariant::create([
            'item_id' => $empanada->id,
            'name' => 'Empanada de Mariscos',
            'sku' => 'EMP-001-MAR',
            'price' => 3500,
            'cost' => 1200,
            'track_stock' => true,
            'is_available' => true,
            'sort_order' => 3,
        ]);
        
        // Completos
        $completo = Item::create([
            'name' => 'Completo',
            'description' => 'Chilean hot dog with avocado, tomato, and mayo',
            'type' => 'product',
            'category_id' => 1,
            'base_price' => 3000,
            'cost' => 1000,
            'sku' => 'COMP-001',
            'track_stock' => true,
            'is_available' => true,
            'allow_modifiers' => true,
            'preparation_time' => 10,
            'sort_order' => 2,
        ]);
        
        ItemVariant::create([
            'item_id' => $completo->id,
            'name' => 'Completo Italiano',
            'sku' => 'COMP-001-IT',
            'price' => 3200,
            'cost' => 1100,
            'track_stock' => true,
            'is_available' => true,
            'sort_order' => 1,
        ]);
        
        // Churrascos
        $churrasco = Item::create([
            'name' => 'Churrasco',
            'description' => 'Grilled beef sandwich',
            'type' => 'product',
            'category_id' => 1,
            'base_price' => 4500,
            'cost' => 1500,
            'sku' => 'CHUR-001',
            'track_stock' => true,
            'is_available' => true,
            'allow_modifiers' => true,
            'preparation_time' => 12,
            'sort_order' => 3,
        ]);
        
        // Sides
        $papas = Item::create([
            'name' => 'Papas Fritas',
            'description' => 'French fries',
            'type' => 'product',
            'category_id' => 2, // Sides category
            'base_price' => 1500,
            'cost' => 400,
            'sku' => 'PAP-001',
            'track_stock' => true,
            'is_available' => true,
            'allow_modifiers' => false,
            'preparation_time' => 8,
            'sort_order' => 1,
        ]);
    }
    
    /**
     * Seed beverage items
     */
    private function seedBeverageItems(): void
    {
        // Soft drinks
        $bebida = Item::create([
            'name' => 'Bebida',
            'description' => 'Soft drinks',
            'type' => 'product',
            'category_id' => 3, // Beverages category
            'base_price' => 1500,
            'cost' => 600,
            'sku' => 'BEB-001',
            'track_stock' => true,
            'is_available' => true,
            'allow_modifiers' => false,
            'preparation_time' => 0,
            'sort_order' => 1,
        ]);
        
        ItemVariant::create([
            'item_id' => $bebida->id,
            'name' => 'Coca-Cola 350ml',
            'sku' => 'BEB-001-CC350',
            'price' => 1500,
            'cost' => 600,
            'track_stock' => true,
            'is_available' => true,
            'sort_order' => 1,
        ]);
        
        ItemVariant::create([
            'item_id' => $bebida->id,
            'name' => 'Fanta 350ml',
            'sku' => 'BEB-001-FT350',
            'price' => 1500,
            'cost' => 600,
            'track_stock' => true,
            'is_available' => true,
            'sort_order' => 2,
        ]);
        
        ItemVariant::create([
            'item_id' => $bebida->id,
            'name' => 'Sprite 350ml',
            'sku' => 'BEB-001-SP350',
            'price' => 1500,
            'cost' => 600,
            'track_stock' => true,
            'is_available' => true,
            'sort_order' => 3,
        ]);
        
        // Natural juices
        $jugo = Item::create([
            'name' => 'Jugo Natural',
            'description' => 'Fresh natural juice',
            'type' => 'product',
            'category_id' => 3,
            'base_price' => 2500,
            'cost' => 800,
            'sku' => 'JUG-001',
            'track_stock' => false,
            'is_available' => true,
            'allow_modifiers' => true,
            'preparation_time' => 5,
            'sort_order' => 2,
        ]);
    }
    
    /**
     * Seed modifiers
     */
    private function seedModifiers(): void
    {
        // Extras group
        $extras = ModifierGroup::create([
            'name' => 'Extras',
            'description' => 'Additional toppings and extras',
            'min_selections' => 0,
            'max_selections' => 5,
            'is_required' => false,
            'allow_multiple' => true,
            'display_order' => 1,
        ]);
        
        ItemModifier::create([
            'modifier_group_id' => $extras->id,
            'name' => 'Extra Palta',
            'sku' => 'MOD-PALTA',
            'price_adjustment' => 500,
            'is_available' => true,
            'display_order' => 1,
        ]);
        
        ItemModifier::create([
            'modifier_group_id' => $extras->id,
            'name' => 'Extra Queso',
            'sku' => 'MOD-QUESO',
            'price_adjustment' => 300,
            'is_available' => true,
            'display_order' => 2,
        ]);
        
        ItemModifier::create([
            'modifier_group_id' => $extras->id,
            'name' => 'Extra Tomate',
            'sku' => 'MOD-TOMATE',
            'price_adjustment' => 200,
            'is_available' => true,
            'display_order' => 3,
        ]);
        
        ItemModifier::create([
            'modifier_group_id' => $extras->id,
            'name' => 'Doble Carne',
            'sku' => 'MOD-DOBLE',
            'price_adjustment' => 1500,
            'is_available' => true,
            'display_order' => 4,
        ]);
        
        // Size group for beverages
        $sizes = ModifierGroup::create([
            'name' => 'Tamaño',
            'description' => 'Choose size',
            'min_selections' => 1,
            'max_selections' => 1,
            'is_required' => true,
            'allow_multiple' => false,
            'display_order' => 2,
        ]);
        
        ItemModifier::create([
            'modifier_group_id' => $sizes->id,
            'name' => 'Pequeño',
            'sku' => 'MOD-SIZE-S',
            'price_adjustment' => 0,
            'is_available' => true,
            'display_order' => 1,
        ]);
        
        ItemModifier::create([
            'modifier_group_id' => $sizes->id,
            'name' => 'Mediano',
            'sku' => 'MOD-SIZE-M',
            'price_adjustment' => 300,
            'is_available' => true,
            'display_order' => 2,
        ]);
        
        ItemModifier::create([
            'modifier_group_id' => $sizes->id,
            'name' => 'Grande',
            'sku' => 'MOD-SIZE-L',
            'price_adjustment' => 500,
            'is_available' => true,
            'display_order' => 3,
        ]);
        
        // Attach modifier groups to items
        DB::table('item_modifier_groups')->insert([
            ['item_id' => 1, 'modifier_group_id' => $extras->id], // Empanada extras
            ['item_id' => 2, 'modifier_group_id' => $extras->id], // Completo extras
            ['item_id' => 3, 'modifier_group_id' => $extras->id], // Churrasco extras
            ['item_id' => 7, 'modifier_group_id' => $sizes->id], // Juice sizes
        ]);
    }
    
    /**
     * Seed recipes
     */
    private function seedRecipes(): void
    {
        // Create ingredients
        $carne = Ingredient::create([
            'name' => 'Carne Molida',
            'description' => 'Ground beef',
            'unit' => 'kg',
            'cost_per_unit' => 8000,
            'supplier_code' => 'ING-CARNE-001',
        ]);
        
        $cebolla = Ingredient::create([
            'name' => 'Cebolla',
            'description' => 'Onion',
            'unit' => 'kg',
            'cost_per_unit' => 1200,
            'supplier_code' => 'ING-CEB-001',
        ]);
        
        $huevo = Ingredient::create([
            'name' => 'Huevo',
            'description' => 'Egg',
            'unit' => 'unit',
            'cost_per_unit' => 200,
            'supplier_code' => 'ING-HUEVO-001',
        ]);
        
        $masa = Ingredient::create([
            'name' => 'Masa Empanada',
            'description' => 'Empanada dough',
            'unit' => 'unit',
            'cost_per_unit' => 150,
            'supplier_code' => 'ING-MASA-001',
        ]);
        
        // Recipe for Empanada de Pino
        $recipe = Recipe::create([
            'item_id' => 1,
            'variant_id' => 1, // Empanada de Pino variant
            'name' => 'Recipe: Empanada de Pino',
            'description' => 'Traditional Chilean meat empanada recipe',
            'yield_quantity' => 10,
            'yield_unit' => 'unit',
            'preparation_time' => 30,
            'cooking_time' => 25,
            'total_cost' => 0, // Will be calculated
            'instructions' => '1. Prepare the filling\n2. Fill the dough\n3. Seal edges\n4. Bake at 200°C for 25 minutes',
            'notes' => 'Can be frozen for up to 3 months',
        ]);
        
        // Recipe ingredients
        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'ingredient_item_id' => Item::where('name', 'Carne Molida')->first()->id ?? 1,
            'quantity' => 0.5,
            'unit' => 'kg',
        ]);
        
        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'ingredient_item_id' => Item::where('name', 'Cebolla')->first()->id ?? 2,
            'quantity' => 0.3,
            'unit' => 'kg',
        ]);
        
        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'ingredient_item_id' => Item::where('name', 'Huevo')->first()->id ?? 3,
            'quantity' => 5,
            'unit' => 'unit',
        ]);
        
        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'ingredient_item_id' => Item::where('name', 'Masa Empanada')->first()->id ?? 4,
            'quantity' => 10,
            'unit' => 'unit',
        ]);
    }
    
    /**
     * Seed inventory
     */
    private function seedInventory(): void
    {
        $items = Item::where('track_stock', true)->get();
        
        foreach ($items as $item) {
            ItemInventory::create([
                'item_id' => $item->id,
                'variant_id' => null,
                'location_id' => 1, // Main location
                'quantity_on_hand' => rand(50, 200),
                'quantity_reserved' => 0,
                'min_quantity' => 20,
                'reorder_quantity' => 50,
                'max_quantity' => 300,
                'unit_cost' => $item->cost,
                'last_counted_at' => now(),
                'last_restocked_at' => now()->subDays(rand(1, 7)),
            ]);
            
            // Add inventory for variants
            foreach ($item->variants as $variant) {
                if ($variant->track_stock) {
                    ItemInventory::create([
                        'item_id' => $item->id,
                        'variant_id' => $variant->id,
                        'location_id' => 1,
                        'quantity_on_hand' => rand(20, 100),
                        'quantity_reserved' => 0,
                        'min_quantity' => 10,
                        'reorder_quantity' => 30,
                        'max_quantity' => 150,
                        'unit_cost' => $variant->cost,
                        'last_counted_at' => now(),
                        'last_restocked_at' => now()->subDays(rand(1, 7)),
                    ]);
                }
            }
        }
    }
    
    /**
     * Seed location-specific pricing
     */
    private function seedLocationPricing(): void
    {
        // Happy hour pricing (20% discount on beverages)
        ItemLocationPrice::create([
            'item_id' => 5, // Bebida
            'variant_id' => null,
            'location_id' => 1,
            'name' => 'Happy Hour - Beverages',
            'price_type' => 'percentage',
            'price_value' => -20, // 20% discount
            'starts_at' => now()->setTime(17, 0),
            'ends_at' => now()->setTime(19, 0),
            'days_of_week' => json_encode([1, 2, 3, 4, 5]), // Monday to Friday
            'is_active' => true,
            'priority' => 10,
        ]);
        
        // Weekend premium pricing (10% increase)
        ItemLocationPrice::create([
            'item_id' => 1, // Empanada
            'variant_id' => null,
            'location_id' => 1,
            'name' => 'Weekend Premium',
            'price_type' => 'percentage',
            'price_value' => 10, // 10% increase
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->addYear(),
            'days_of_week' => json_encode([0, 6]), // Saturday and Sunday
            'is_active' => true,
            'priority' => 5,
        ]);
        
        // Location 2 fixed pricing
        ItemLocationPrice::create([
            'item_id' => 3, // Churrasco
            'variant_id' => null,
            'location_id' => 2,
            'name' => 'Downtown Location Price',
            'price_type' => 'fixed',
            'price_value' => 5000, // Fixed price
            'starts_at' => now()->startOfDay(),
            'ends_at' => null,
            'days_of_week' => null,
            'is_active' => true,
            'priority' => 1,
        ]);
    }
}