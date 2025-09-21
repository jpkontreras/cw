<?php

namespace Colame\Item\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ModifierSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Define comprehensive modifier groups with their options
        $modifierGroups = [
            // SIZE MODIFIERS
            [
                'name' => 'Size',
                'description' => 'Choose your preferred size',
                'selection_type' => 'single',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'modifiers' => [
                    ['name' => 'Small', 'price_adjustment' => -200],  // -$2.00
                    ['name' => 'Medium', 'price_adjustment' => 0, 'is_default' => true],
                    ['name' => 'Large', 'price_adjustment' => 200],   // +$2.00
                    ['name' => 'Extra Large', 'price_adjustment' => 400], // +$4.00
                ],
            ],

            // DRINK SIZES
            [
                'name' => 'Drink Size',
                'description' => 'Select your drink size',
                'selection_type' => 'single',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'modifiers' => [
                    ['name' => 'Small (12 oz)', 'price_adjustment' => -100],
                    ['name' => 'Medium (16 oz)', 'price_adjustment' => 0, 'is_default' => true],
                    ['name' => 'Large (20 oz)', 'price_adjustment' => 100],
                    ['name' => 'Extra Large (24 oz)', 'price_adjustment' => 200],
                ],
            ],

            // TEMPERATURE
            [
                'name' => 'Temperature',
                'description' => 'How would you like it served?',
                'selection_type' => 'single',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'modifiers' => [
                    ['name' => 'Hot', 'price_adjustment' => 0, 'is_default' => true],
                    ['name' => 'Iced', 'price_adjustment' => 0],
                    ['name' => 'Extra Hot', 'price_adjustment' => 0],
                    ['name' => 'Lukewarm', 'price_adjustment' => 0],
                ],
            ],

            // SPICE LEVEL
            [
                'name' => 'Spice Level',
                'description' => 'Choose your spice preference',
                'selection_type' => 'single',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'modifiers' => [
                    ['name' => 'No Spice', 'price_adjustment' => 0],
                    ['name' => 'Mild', 'price_adjustment' => 0, 'is_default' => true],
                    ['name' => 'Medium', 'price_adjustment' => 0],
                    ['name' => 'Hot', 'price_adjustment' => 0],
                    ['name' => 'Extra Hot', 'price_adjustment' => 0],
                    ['name' => 'Thai Hot', 'price_adjustment' => 0],
                ],
            ],

            // COOKING PREFERENCE
            [
                'name' => 'Cooking Preference',
                'description' => 'How would you like it cooked?',
                'selection_type' => 'single',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'modifiers' => [
                    ['name' => 'Rare', 'price_adjustment' => 0],
                    ['name' => 'Medium Rare', 'price_adjustment' => 0],
                    ['name' => 'Medium', 'price_adjustment' => 0, 'is_default' => true],
                    ['name' => 'Medium Well', 'price_adjustment' => 0],
                    ['name' => 'Well Done', 'price_adjustment' => 0],
                ],
            ],

            // BREAD TYPE
            [
                'name' => 'Bread Type',
                'description' => 'Choose your bread',
                'selection_type' => 'single',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'modifiers' => [
                    ['name' => 'White', 'price_adjustment' => 0, 'is_default' => true],
                    ['name' => 'Whole Wheat', 'price_adjustment' => 0],
                    ['name' => 'Sourdough', 'price_adjustment' => 100],
                    ['name' => 'Rye', 'price_adjustment' => 100],
                    ['name' => 'Multigrain', 'price_adjustment' => 100],
                    ['name' => 'Gluten Free', 'price_adjustment' => 200],
                    ['name' => 'Ciabatta', 'price_adjustment' => 150],
                    ['name' => 'Brioche', 'price_adjustment' => 200],
                ],
            ],

            // CRUST TYPE (Pizza)
            [
                'name' => 'Crust Type',
                'description' => 'Select your crust preference',
                'selection_type' => 'single',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'modifiers' => [
                    ['name' => 'Regular', 'price_adjustment' => 0, 'is_default' => true],
                    ['name' => 'Thin & Crispy', 'price_adjustment' => 0],
                    ['name' => 'Thick', 'price_adjustment' => 200],
                    ['name' => 'Stuffed Crust', 'price_adjustment' => 400],
                    ['name' => 'Gluten Free', 'price_adjustment' => 300],
                    ['name' => 'Cauliflower', 'price_adjustment' => 400],
                ],
            ],

            // MILK OPTIONS
            [
                'name' => 'Milk Options',
                'description' => 'Choose your milk preference',
                'selection_type' => 'single',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'modifiers' => [
                    ['name' => 'Whole Milk', 'price_adjustment' => 0, 'is_default' => true],
                    ['name' => '2% Milk', 'price_adjustment' => 0],
                    ['name' => 'Skim Milk', 'price_adjustment' => 0],
                    ['name' => 'Soy Milk', 'price_adjustment' => 60],
                    ['name' => 'Almond Milk', 'price_adjustment' => 60],
                    ['name' => 'Oat Milk', 'price_adjustment' => 80],
                    ['name' => 'Coconut Milk', 'price_adjustment' => 80],
                    ['name' => 'Half & Half', 'price_adjustment' => 50],
                ],
            ],

            // COFFEE EXTRAS
            [
                'name' => 'Coffee Additions',
                'description' => 'Customize your coffee',
                'selection_type' => 'multiple',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 10,
                'modifiers' => [
                    ['name' => 'Extra Shot', 'price_adjustment' => 75, 'max_quantity' => 4],
                    ['name' => 'Decaf', 'price_adjustment' => 0],
                    ['name' => 'Half Caff', 'price_adjustment' => 0],
                    ['name' => 'Vanilla Syrup', 'price_adjustment' => 60, 'max_quantity' => 3],
                    ['name' => 'Caramel Syrup', 'price_adjustment' => 60, 'max_quantity' => 3],
                    ['name' => 'Hazelnut Syrup', 'price_adjustment' => 60, 'max_quantity' => 3],
                    ['name' => 'Sugar Free Vanilla', 'price_adjustment' => 60, 'max_quantity' => 3],
                    ['name' => 'Whipped Cream', 'price_adjustment' => 50],
                    ['name' => 'Extra Foam', 'price_adjustment' => 0],
                    ['name' => 'No Foam', 'price_adjustment' => 0],
                ],
            ],

            // BURGER TOPPINGS
            [
                'name' => 'Burger Toppings',
                'description' => 'Customize your burger',
                'selection_type' => 'multiple',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 15,
                'modifiers' => [
                    ['name' => 'Lettuce', 'price_adjustment' => 0],
                    ['name' => 'Tomato', 'price_adjustment' => 0],
                    ['name' => 'Onion', 'price_adjustment' => 0],
                    ['name' => 'Pickles', 'price_adjustment' => 0],
                    ['name' => 'Bacon', 'price_adjustment' => 200],
                    ['name' => 'Extra Cheese', 'price_adjustment' => 100],
                    ['name' => 'Mushrooms', 'price_adjustment' => 100],
                    ['name' => 'Jalapeños', 'price_adjustment' => 50],
                    ['name' => 'Avocado', 'price_adjustment' => 250],
                    ['name' => 'Fried Egg', 'price_adjustment' => 150],
                    ['name' => 'Caramelized Onions', 'price_adjustment' => 100],
                    ['name' => 'BBQ Sauce', 'price_adjustment' => 0],
                    ['name' => 'Mayo', 'price_adjustment' => 0],
                    ['name' => 'Ketchup', 'price_adjustment' => 0],
                    ['name' => 'Mustard', 'price_adjustment' => 0],
                ],
            ],

            // PIZZA TOPPINGS
            [
                'name' => 'Pizza Toppings',
                'description' => 'Add extra toppings',
                'selection_type' => 'multiple',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 10,
                'modifiers' => [
                    ['name' => 'Pepperoni', 'price_adjustment' => 200],
                    ['name' => 'Italian Sausage', 'price_adjustment' => 200],
                    ['name' => 'Ham', 'price_adjustment' => 200],
                    ['name' => 'Bacon', 'price_adjustment' => 250],
                    ['name' => 'Ground Beef', 'price_adjustment' => 200],
                    ['name' => 'Chicken', 'price_adjustment' => 250],
                    ['name' => 'Anchovies', 'price_adjustment' => 200],
                    ['name' => 'Extra Cheese', 'price_adjustment' => 150],
                    ['name' => 'Mushrooms', 'price_adjustment' => 150],
                    ['name' => 'Onions', 'price_adjustment' => 100],
                    ['name' => 'Green Peppers', 'price_adjustment' => 100],
                    ['name' => 'Black Olives', 'price_adjustment' => 150],
                    ['name' => 'Pineapple', 'price_adjustment' => 150],
                    ['name' => 'Jalapeños', 'price_adjustment' => 100],
                    ['name' => 'Tomatoes', 'price_adjustment' => 100],
                ],
            ],

            // SALAD DRESSING
            [
                'name' => 'Salad Dressing',
                'description' => 'Choose your dressing',
                'selection_type' => 'single',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'modifiers' => [
                    ['name' => 'Ranch', 'price_adjustment' => 0],
                    ['name' => 'Caesar', 'price_adjustment' => 0],
                    ['name' => 'Italian', 'price_adjustment' => 0],
                    ['name' => 'Balsamic Vinaigrette', 'price_adjustment' => 0],
                    ['name' => 'Honey Mustard', 'price_adjustment' => 0],
                    ['name' => 'Blue Cheese', 'price_adjustment' => 50],
                    ['name' => 'Thousand Island', 'price_adjustment' => 0],
                    ['name' => 'Oil & Vinegar', 'price_adjustment' => 0],
                    ['name' => 'No Dressing', 'price_adjustment' => 0],
                    ['name' => 'Dressing on Side', 'price_adjustment' => 0],
                ],
            ],

            // SALAD ADDITIONS
            [
                'name' => 'Salad Add-ons',
                'description' => 'Enhance your salad',
                'selection_type' => 'multiple',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 10,
                'modifiers' => [
                    ['name' => 'Grilled Chicken', 'price_adjustment' => 400],
                    ['name' => 'Grilled Shrimp', 'price_adjustment' => 500],
                    ['name' => 'Grilled Salmon', 'price_adjustment' => 600],
                    ['name' => 'Hard Boiled Egg', 'price_adjustment' => 150],
                    ['name' => 'Bacon Bits', 'price_adjustment' => 200],
                    ['name' => 'Avocado', 'price_adjustment' => 250],
                    ['name' => 'Extra Cheese', 'price_adjustment' => 100],
                    ['name' => 'Croutons', 'price_adjustment' => 0],
                    ['name' => 'Nuts', 'price_adjustment' => 150],
                    ['name' => 'Dried Cranberries', 'price_adjustment' => 100],
                ],
            ],

            // PASTA ADDITIONS
            [
                'name' => 'Pasta Options',
                'description' => 'Customize your pasta',
                'selection_type' => 'multiple',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 5,
                'modifiers' => [
                    ['name' => 'Extra Sauce', 'price_adjustment' => 150],
                    ['name' => 'Extra Cheese', 'price_adjustment' => 200],
                    ['name' => 'Meatballs', 'price_adjustment' => 400, 'max_quantity' => 3],
                    ['name' => 'Italian Sausage', 'price_adjustment' => 350],
                    ['name' => 'Grilled Chicken', 'price_adjustment' => 400],
                    ['name' => 'Shrimp', 'price_adjustment' => 500],
                    ['name' => 'Gluten Free Pasta', 'price_adjustment' => 300],
                    ['name' => 'Whole Wheat Pasta', 'price_adjustment' => 200],
                ],
            ],

            // SIDES
            [
                'name' => 'Side Options',
                'description' => 'Choose your side',
                'selection_type' => 'single',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'modifiers' => [
                    ['name' => 'French Fries', 'price_adjustment' => 0, 'is_default' => true],
                    ['name' => 'Sweet Potato Fries', 'price_adjustment' => 150],
                    ['name' => 'Onion Rings', 'price_adjustment' => 200],
                    ['name' => 'Side Salad', 'price_adjustment' => 100],
                    ['name' => 'Coleslaw', 'price_adjustment' => 0],
                    ['name' => 'Mashed Potatoes', 'price_adjustment' => 100],
                    ['name' => 'Rice', 'price_adjustment' => 0],
                    ['name' => 'Vegetables', 'price_adjustment' => 100],
                    ['name' => 'Soup of the Day', 'price_adjustment' => 200],
                ],
            ],

            // SANDWICH OPTIONS
            [
                'name' => 'Sandwich Options',
                'description' => 'Customize your sandwich',
                'selection_type' => 'multiple',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 10,
                'modifiers' => [
                    ['name' => 'Toasted', 'price_adjustment' => 0],
                    ['name' => 'Extra Meat', 'price_adjustment' => 300],
                    ['name' => 'Extra Cheese', 'price_adjustment' => 100],
                    ['name' => 'No Mayo', 'price_adjustment' => 0],
                    ['name' => 'No Onions', 'price_adjustment' => 0],
                    ['name' => 'Add Bacon', 'price_adjustment' => 200],
                    ['name' => 'Add Avocado', 'price_adjustment' => 250],
                    ['name' => 'Make it a Combo', 'price_adjustment' => 400],
                ],
            ],

            // ICE CREAM OPTIONS
            [
                'name' => 'Ice Cream Options',
                'description' => 'Customize your ice cream',
                'selection_type' => 'multiple',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 5,
                'modifiers' => [
                    ['name' => 'Extra Scoop', 'price_adjustment' => 250],
                    ['name' => 'Hot Fudge', 'price_adjustment' => 150],
                    ['name' => 'Caramel Sauce', 'price_adjustment' => 150],
                    ['name' => 'Chocolate Sauce', 'price_adjustment' => 150],
                    ['name' => 'Whipped Cream', 'price_adjustment' => 75],
                    ['name' => 'Sprinkles', 'price_adjustment' => 50],
                    ['name' => 'Crushed Oreos', 'price_adjustment' => 100],
                    ['name' => 'Nuts', 'price_adjustment' => 100],
                    ['name' => 'Cherry on Top', 'price_adjustment' => 25],
                    ['name' => 'Waffle Cone', 'price_adjustment' => 100],
                ],
            ],

            // PREPARATION INSTRUCTIONS
            [
                'name' => 'Special Instructions',
                'description' => 'Special preparation requests',
                'selection_type' => 'multiple',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 10,
                'modifiers' => [
                    ['name' => 'No Salt', 'price_adjustment' => 0],
                    ['name' => 'Low Salt', 'price_adjustment' => 0],
                    ['name' => 'No Oil', 'price_adjustment' => 0],
                    ['name' => 'Extra Crispy', 'price_adjustment' => 0],
                    ['name' => 'Light Ice', 'price_adjustment' => 0],
                    ['name' => 'Extra Ice', 'price_adjustment' => 0],
                    ['name' => 'No Ice', 'price_adjustment' => 0],
                    ['name' => 'Cut in Half', 'price_adjustment' => 0],
                    ['name' => 'Separate Packaging', 'price_adjustment' => 50],
                    ['name' => 'Extra Napkins', 'price_adjustment' => 0],
                    ['name' => 'Extra Utensils', 'price_adjustment' => 0],
                ],
            ],

            // DIETARY OPTIONS
            [
                'name' => 'Dietary Modifications',
                'description' => 'Dietary preferences and restrictions',
                'selection_type' => 'multiple',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 5,
                'modifiers' => [
                    ['name' => 'Make it Vegan', 'price_adjustment' => 200],
                    ['name' => 'Make it Vegetarian', 'price_adjustment' => 0],
                    ['name' => 'Gluten Free Option', 'price_adjustment' => 300],
                    ['name' => 'Dairy Free', 'price_adjustment' => 150],
                    ['name' => 'Nut Free', 'price_adjustment' => 0],
                    ['name' => 'Low Carb', 'price_adjustment' => 200],
                    ['name' => 'Keto Friendly', 'price_adjustment' => 300],
                ],
            ],

            // PACKAGING OPTIONS
            [
                'name' => 'Packaging',
                'description' => 'Packaging preferences',
                'selection_type' => 'single',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'modifiers' => [
                    ['name' => 'For Here', 'price_adjustment' => 0, 'is_default' => true],
                    ['name' => 'To Go', 'price_adjustment' => 0],
                    ['name' => 'Eco-Friendly Packaging', 'price_adjustment' => 50],
                ],
            ],
        ];

        // Insert modifier groups and their modifiers
        foreach ($modifierGroups as $groupData) {
            $modifiers = $groupData['modifiers'];
            unset($groupData['modifiers']);

            // Insert modifier group
            $groupData['created_at'] = $now;
            $groupData['updated_at'] = $now;
            $groupId = DB::table('modifier_groups')->insertGetId($groupData);

            // Insert modifiers for this group
            foreach ($modifiers as $index => $modifier) {
                $modifier['modifier_group_id'] = $groupId;
                $modifier['sort_order'] = $index;
                $modifier['is_active'] = true;
                $modifier['max_quantity'] = $modifier['max_quantity'] ?? 1;
                $modifier['is_default'] = $modifier['is_default'] ?? false;
                $modifier['created_at'] = $now;
                $modifier['updated_at'] = $now;

                DB::table('item_modifiers')->insert($modifier);
            }
        }

        $this->command->info('Comprehensive modifiers seeded successfully!');
        $this->command->info('Created ' . count($modifierGroups) . ' modifier groups with their options.');
    }
}