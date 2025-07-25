<?php

namespace Colame\Item\Database\Seeders;

use Illuminate\Database\Seeder;
use Colame\Item\Models\Item;
use Colame\Item\Models\ItemVariant;
use Colame\Item\Models\ItemModifier;
use Colame\Item\Models\ItemModifierGroup;
use Colame\Item\Models\ItemPricing;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        // Create some basic items without category dependencies
        $items = [
            // Sandwiches
            [
                'name' => 'Churrasco Italiano',
                'description' => 'Traditional Chilean sandwich with beef, tomato, avocado, and green beans',
                'sku' => 'SAND-001',
                'base_price' => 4500,
                'category_id' => 1,
                'type' => 'simple',
                'status' => 'active',
                'is_available' => true,
                'track_inventory' => true,
                'current_stock' => 100,
                'low_stock_threshold' => 20,
            ],
            [
                'name' => 'Completo',
                'description' => 'Hot dog with tomato, avocado, and mayonnaise',
                'sku' => 'SAND-002',
                'base_price' => 3500,
                'category_id' => 1,
                'type' => 'simple',
                'status' => 'active',
                'is_available' => true,
                'track_inventory' => true,
                'current_stock' => 100,
                'low_stock_threshold' => 20,
            ],
            [
                'name' => 'Barros Luco',
                'description' => 'Sandwich with beef and melted cheese',
                'sku' => 'SAND-003',
                'base_price' => 4000,
                'category_id' => 1,
                'type' => 'simple',
                'status' => 'active',
                'is_available' => true,
                'track_inventory' => true,
                'current_stock' => 100,
                'low_stock_threshold' => 20,
            ],
            
            // Beverages
            [
                'name' => 'Bebida 350ml',
                'description' => 'Soft drink can',
                'sku' => 'BEV-001',
                'base_price' => 1500,
                'category_id' => 2,
                'type' => 'variant',
                'status' => 'active',
                'is_available' => true,
                'track_inventory' => true,
                'current_stock' => 200,
                'low_stock_threshold' => 50,
            ],
            [
                'name' => 'Jugo Natural',
                'description' => 'Fresh natural juice',
                'sku' => 'BEV-002',
                'base_price' => 2000,
                'category_id' => 2,
                'type' => 'simple',
                'status' => 'active',
                'is_available' => true,
                'track_inventory' => true,
                'current_stock' => 50,
                'low_stock_threshold' => 10,
            ],
            
            // Sides
            [
                'name' => 'Papas Fritas',
                'description' => 'French fries portion',
                'sku' => 'SIDE-001',
                'base_price' => 2000,
                'category_id' => 3,
                'type' => 'simple',
                'status' => 'active',
                'is_available' => true,
                'track_inventory' => true,
                'current_stock' => 150,
                'low_stock_threshold' => 30,
            ],
            [
                'name' => 'Empanada de Pino',
                'description' => 'Traditional Chilean empanada with meat filling',
                'sku' => 'SIDE-002',
                'base_price' => 2500,
                'category_id' => 3,
                'type' => 'simple',
                'status' => 'active',
                'is_available' => true,
                'track_inventory' => true,
                'current_stock' => 80,
                'low_stock_threshold' => 20,
            ],
        ];

        foreach ($items as $itemData) {
            // Skip if item already exists
            if (Item::where('sku', $itemData['sku'])->exists()) {
                continue;
            }

            $item = Item::create($itemData);

            // Create variants for beverages
            if ($item->sku === 'BEV-001') {
                ItemVariant::create([
                    'item_id' => $item->id,
                    'name' => 'Coca-Cola',
                    'sku' => 'BEV-001-CC',
                    'attribute_type' => 'flavor',
                    'attribute_value' => 'cola',
                    'price_adjustment' => 0,
                    'is_available' => true,
                    'is_default' => true,
                    'current_stock' => 100,
                ]);
                ItemVariant::create([
                    'item_id' => $item->id,
                    'name' => 'Sprite',
                    'sku' => 'BEV-001-SP',
                    'attribute_type' => 'flavor',
                    'attribute_value' => 'lemon-lime',
                    'price_adjustment' => 0,
                    'is_available' => true,
                    'current_stock' => 50,
                ]);
                ItemVariant::create([
                    'item_id' => $item->id,
                    'name' => 'Fanta',
                    'sku' => 'BEV-001-FA',
                    'attribute_type' => 'flavor',
                    'attribute_value' => 'orange',
                    'price_adjustment' => 0,
                    'is_available' => true,
                    'current_stock' => 50,
                ]);
            }

            // Create modifiers for sandwiches
            if (in_array($item->sku, ['SAND-001', 'SAND-002', 'SAND-003'])) {
                // Create extra ingredients modifier group
                $extraGroup = ItemModifierGroup::create([
                    'name' => 'Ingredientes Extra',
                    'description' => 'Add extra ingredients',
                    'type' => 'multiple',
                    'min_selections' => 0,
                    'max_selections' => 5,
                    'sort_order' => 1,
                ]);

                // Attach group to item via pivot table
                \DB::table('item_modifier_group_items')->insert([
                    'item_id' => $item->id,
                    'modifier_group_id' => $extraGroup->id,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Create modifiers
                $extras = [
                    ['name' => 'Palta Extra', 'price' => 500],
                    ['name' => 'Queso Extra', 'price' => 400],
                    ['name' => 'Tomate Extra', 'price' => 300],
                    ['name' => 'Mayonesa Casera', 'price' => 300],
                    ['name' => 'Ají Verde', 'price' => 200],
                ];

                foreach ($extras as $index => $extra) {
                    ItemModifier::create([
                        'group_id' => $extraGroup->id,
                        'name' => $extra['name'],
                        'price' => $extra['price'],
                        'sort_order' => $index + 1,
                        'is_available' => true,
                    ]);
                }

                // Create bread type modifier group (required choice)
                $breadGroup = ItemModifierGroup::create([
                    'name' => 'Tipo de Pan',
                    'description' => 'Choose your bread type',
                    'type' => 'single',
                    'min_selections' => 1,
                    'max_selections' => 1,
                    'sort_order' => 0,
                ]);

                \DB::table('item_modifier_group_items')->insert([
                    'item_id' => $item->id,
                    'modifier_group_id' => $breadGroup->id,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $breads = [
                    ['name' => 'Marraqueta', 'price' => 0, 'is_default' => true],
                    ['name' => 'Hallulla', 'price' => 0, 'is_default' => false],
                    ['name' => 'Pan Integral', 'price' => 200, 'is_default' => false],
                ];

                foreach ($breads as $index => $bread) {
                    ItemModifier::create([
                        'group_id' => $breadGroup->id,
                        'name' => $bread['name'],
                        'price' => $bread['price'],
                        'is_default' => $bread['is_default'],
                        'sort_order' => $index + 1,
                        'is_available' => true,
                    ]);
                }
            }
        }

        // Create a compound item (combo meal)
        if (!Item::where('sku', 'COMBO-001')->exists()) {
            $combo = Item::create([
                'name' => 'Combo Completo',
                'description' => 'Completo + Papas Fritas + Bebida',
                'sku' => 'COMBO-001',
                'base_price' => 6500,
                'category_id' => 4,
                'type' => 'compound',
                'status' => 'active',
                'is_available' => true,
                'track_inventory' => false,
            ]);

            // Add ingredients (items that make up the combo)
            $completo = Item::where('sku', 'SAND-002')->first();
            $papas = Item::where('sku', 'SIDE-001')->first();
            $bebida = Item::where('sku', 'BEV-001')->first();

            if ($completo && $papas && $bebida) {
                \DB::table('item_ingredients')->insert([
                    ['item_id' => $combo->id, 'ingredient_id' => $completo->id, 'quantity' => 1, 'unit' => 'piece', 'created_at' => now(), 'updated_at' => now()],
                    ['item_id' => $combo->id, 'ingredient_id' => $papas->id, 'quantity' => 1, 'unit' => 'piece', 'created_at' => now(), 'updated_at' => now()],
                    ['item_id' => $combo->id, 'ingredient_id' => $bebida->id, 'quantity' => 1, 'unit' => 'piece', 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
        }

        $this->command->info('Item seeder completed successfully!');
    }
}