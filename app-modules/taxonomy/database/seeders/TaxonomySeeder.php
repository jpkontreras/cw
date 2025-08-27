<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Database\Seeders;

use Colame\Taxonomy\Enums\TaxonomyType;
use Colame\Taxonomy\Models\Taxonomy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        // Product Organization (Hierarchical)
        $this->createItemCategories();
        $this->createMenuSections();
        $this->createIngredientTypes();
        
        // Attributes
        $this->createDietaryLabels();
        $this->createAllergens();
        $this->createCuisineTypes();
        $this->createPreparationMethods();
        $this->createSpiceLevels();
        
        // Business
        $this->createCustomerSegments();
        $this->createPriceRanges();
        $this->createLocationZones();
        $this->createPromotionTypes();
        
        // Tags
        $this->createGeneralTags();
        $this->createSeasonalTags();
        $this->createFeatureTags();
    }
    
    private function createItemCategories(): void
    {
        $categories = [
            'Beverages' => [
                'metadata' => ['icon' => 'coffee', 'color' => '#8B4513', 'description' => 'All drink options'],
                'children' => [
                    'Hot Beverages' => [
                        'metadata' => ['icon' => 'mug-hot', 'color' => '#D2691E'],
                        'children' => [
                            'Coffee' => [
                                'metadata' => ['icon' => 'coffee', 'color' => '#6F4E37'],
                                'children' => ['Espresso', 'Americano', 'Cappuccino', 'Latte', 'Macchiato', 'Mocha', 'Flat White', 'Cortado', 'Ristretto']
                            ],
                            'Tea' => [
                                'metadata' => ['icon' => 'leaf', 'color' => '#228B22'],
                                'children' => ['Black Tea', 'Green Tea', 'White Tea', 'Oolong Tea', 'Herbal Tea', 'Chai', 'Matcha', 'Iced Tea']
                            ],
                            'Hot Chocolate' => ['Classic', 'White Chocolate', 'Dark Chocolate', 'Spiced'],
                            'Other Hot Drinks' => ['Hot Toddy', 'Mulled Wine', 'Hot Cider', 'Warm Milk']
                        ]
                    ],
                    'Cold Beverages' => [
                        'metadata' => ['icon' => 'glass', 'color' => '#00CED1'],
                        'children' => [
                            'Soft Drinks' => [
                                'children' => ['Cola', 'Lemon-Lime', 'Orange', 'Root Beer', 'Ginger Ale', 'Tonic Water', 'Club Soda', 'Energy Drinks']
                            ],
                            'Juices' => [
                                'children' => ['Orange Juice', 'Apple Juice', 'Cranberry Juice', 'Grape Juice', 'Pineapple Juice', 'Tomato Juice', 'Mixed Fruit', 'Fresh Squeezed']
                            ],
                            'Smoothies' => [
                                'children' => ['Fruit Smoothies', 'Green Smoothies', 'Protein Smoothies', 'Yogurt Smoothies']
                            ],
                            'Iced Coffee & Tea' => ['Iced Americano', 'Cold Brew', 'Iced Latte', 'Frappe', 'Iced Tea', 'Bubble Tea'],
                            'Water' => ['Still Water', 'Sparkling Water', 'Flavored Water', 'Coconut Water']
                        ]
                    ],
                    'Alcoholic Beverages' => [
                        'metadata' => ['icon' => 'wine-glass', 'color' => '#722F37'],
                        'children' => [
                            'Beer' => [
                                'children' => ['Draft Beer', 'Bottled Beer', 'Craft Beer', 'Imported Beer', 'Light Beer', 'IPA', 'Lager', 'Stout', 'Ale']
                            ],
                            'Wine' => [
                                'children' => ['Red Wine', 'White Wine', 'Rosé', 'Sparkling Wine', 'Champagne', 'Dessert Wine', 'House Wine']
                            ],
                            'Spirits' => [
                                'children' => ['Whiskey', 'Vodka', 'Rum', 'Gin', 'Tequila', 'Brandy', 'Liqueurs', 'Cognac']
                            ],
                            'Cocktails' => [
                                'children' => ['Classic Cocktails', 'Signature Cocktails', 'Frozen Cocktails', 'Mocktails', 'Shots']
                            ]
                        ]
                    ]
                ]
            ],
            'Food' => [
                'metadata' => ['icon' => 'utensils', 'color' => '#FF6B6B', 'description' => 'All food items'],
                'children' => [
                    'Appetizers' => [
                        'metadata' => ['icon' => 'plate', 'color' => '#FF8C00'],
                        'children' => [
                            'Cold Starters' => ['Bruschetta', 'Carpaccio', 'Ceviche', 'Tartare', 'Antipasto', 'Cheese Board'],
                            'Hot Starters' => ['Wings', 'Nachos', 'Mozzarella Sticks', 'Calamari', 'Spring Rolls', 'Quesadillas'],
                            'Soups' => [
                                'children' => ['Cream Soups', 'Broth Soups', 'Chowders', 'Bisques', 'Gazpacho', 'Minestrone', 'French Onion']
                            ],
                            'Salads' => [
                                'children' => ['Caesar Salad', 'Greek Salad', 'Garden Salad', 'Cobb Salad', 'Caprese', 'Waldorf', 'Coleslaw']
                            ],
                            'Bread & Dips' => ['Garlic Bread', 'Focaccia', 'Breadsticks', 'Hummus', 'Guacamole', 'Spinach Dip']
                        ]
                    ],
                    'Main Courses' => [
                        'metadata' => ['icon' => 'drumstick-bite', 'color' => '#8B4513'],
                        'children' => [
                            'Beef' => [
                                'children' => ['Steak', 'Ribeye', 'Sirloin', 'Filet Mignon', 'Beef Stew', 'Roast Beef', 'Beef Burger', 'Meatballs']
                            ],
                            'Poultry' => [
                                'children' => ['Grilled Chicken', 'Roasted Chicken', 'Chicken Wings', 'Chicken Curry', 'Duck', 'Turkey', 'Chicken Parmesan']
                            ],
                            'Pork' => [
                                'children' => ['Pork Chops', 'Pulled Pork', 'Ribs', 'Bacon Dishes', 'Ham', 'Pork Belly', 'Carnitas']
                            ],
                            'Seafood' => [
                                'children' => ['Grilled Fish', 'Salmon', 'Tuna', 'Shrimp', 'Lobster', 'Crab', 'Scallops', 'Fish & Chips', 'Sushi']
                            ],
                            'Lamb' => ['Lamb Chops', 'Rack of Lamb', 'Lamb Curry', 'Gyros', 'Lamb Shank'],
                            'Vegetarian Mains' => [
                                'children' => ['Veggie Burger', 'Stuffed Vegetables', 'Tofu Dishes', 'Tempeh', 'Vegetable Curry', 'Ratatouille']
                            ],
                            'Pasta' => [
                                'children' => ['Spaghetti', 'Fettuccine', 'Penne', 'Lasagna', 'Ravioli', 'Carbonara', 'Alfredo', 'Marinara']
                            ],
                            'Rice Dishes' => [
                                'children' => ['Fried Rice', 'Risotto', 'Paella', 'Biryani', 'Pilaf', 'Sushi Rolls', 'Rice Bowls']
                            ],
                            'Pizza' => [
                                'children' => ['Margherita', 'Pepperoni', 'Hawaiian', 'Vegetarian', 'Meat Lovers', 'BBQ Chicken', 'White Pizza']
                            ]
                        ]
                    ],
                    'Breakfast' => [
                        'metadata' => ['icon' => 'sun', 'color' => '#FFD700'],
                        'children' => [
                            'Eggs' => ['Scrambled', 'Fried', 'Poached', 'Boiled', 'Omelette', 'Frittata', 'Eggs Benedict', 'Shakshuka'],
                            'Pancakes & Waffles' => ['Buttermilk Pancakes', 'Blueberry Pancakes', 'Belgian Waffles', 'Crepes', 'French Toast'],
                            'Breakfast Meats' => ['Bacon', 'Sausage', 'Ham', 'Canadian Bacon', 'Chorizo'],
                            'Cereals & Oats' => ['Oatmeal', 'Granola', 'Muesli', 'Porridge', 'Cereal'],
                            'Bakery' => ['Croissant', 'Bagel', 'English Muffin', 'Biscuits', 'Danish', 'Muffin'],
                            'Healthy Options' => ['Acai Bowl', 'Yogurt Parfait', 'Fruit Salad', 'Avocado Toast', 'Smoothie Bowl']
                        ]
                    ],
                    'Desserts' => [
                        'metadata' => ['icon' => 'cake', 'color' => '#FF69B4'],
                        'children' => [
                            'Cakes' => ['Chocolate Cake', 'Cheesecake', 'Red Velvet', 'Carrot Cake', 'Tiramisu', 'Black Forest', 'Lemon Cake'],
                            'Ice Cream & Frozen' => ['Vanilla', 'Chocolate', 'Strawberry', 'Gelato', 'Sorbet', 'Frozen Yogurt', 'Sundae'],
                            'Pastries' => ['Apple Pie', 'Éclair', 'Cannoli', 'Baklava', 'Profiteroles', 'Tart', 'Strudel'],
                            'Cookies & Brownies' => ['Chocolate Chip', 'Brownies', 'Macarons', 'Biscotti', 'Sugar Cookies', 'Blondies'],
                            'Puddings & Custards' => ['Crème Brûlée', 'Flan', 'Panna Cotta', 'Rice Pudding', 'Bread Pudding', 'Mousse'],
                            'Fruit Desserts' => ['Fruit Tart', 'Fruit Salad', 'Poached Pears', 'Baked Apples', 'Berry Cobbler']
                        ]
                    ],
                    'Sides' => [
                        'metadata' => ['icon' => 'bowl', 'color' => '#90EE90'],
                        'children' => [
                            'Potatoes' => ['French Fries', 'Mashed Potatoes', 'Baked Potato', 'Sweet Potato Fries', 'Hash Browns', 'Potato Salad'],
                            'Vegetables' => ['Steamed Vegetables', 'Grilled Vegetables', 'Roasted Vegetables', 'Sautéed Greens', 'Corn on the Cob'],
                            'Grains' => ['Rice', 'Quinoa', 'Couscous', 'Barley', 'Bulgur', 'Polenta'],
                            'Legumes' => ['Black Beans', 'Pinto Beans', 'Chickpeas', 'Lentils', 'Edamame'],
                            'Breads' => ['Dinner Rolls', 'Naan', 'Pita', 'Tortillas', 'Cornbread']
                        ]
                    ]
                ]
            ],
            'Snacks & Light Bites' => [
                'metadata' => ['icon' => 'cookie-bite', 'color' => '#FFA500'],
                'children' => [
                    'Sweet Snacks' => [
                        'children' => ['Cookies', 'Candy', 'Chocolate Bars', 'Donuts', 'Cupcakes', 'Energy Bars', 'Trail Mix']
                    ],
                    'Savory Snacks' => [
                        'children' => ['Chips', 'Pretzels', 'Popcorn', 'Nuts', 'Crackers', 'Beef Jerky', 'Cheese Sticks']
                    ],
                    'Healthy Snacks' => [
                        'children' => ['Fresh Fruit', 'Vegetable Sticks', 'Yogurt', 'Granola Bars', 'Rice Cakes', 'Hummus & Veggies']
                    ]
                ]
            ],
            'Kids Menu' => [
                'metadata' => ['icon' => 'child', 'color' => '#87CEEB'],
                'children' => [
                    'Kids Mains' => ['Chicken Nuggets', 'Mini Burger', 'Mac & Cheese', 'Mini Pizza', 'Grilled Cheese', 'Hot Dog'],
                    'Kids Sides' => ['Fruit Cup', 'Apple Slices', 'Carrot Sticks', 'Mini Fries', 'Tater Tots'],
                    'Kids Desserts' => ['Ice Cream Cup', 'Cookie', 'Brownie', 'Fruit Popsicle'],
                    'Kids Drinks' => ['Juice Box', 'Milk', 'Chocolate Milk', 'Lemonade']
                ]
            ]
        ];
        
        $this->createHierarchy($categories, TaxonomyType::ITEM_CATEGORY);
    }
    
    private function createMenuSections(): void
    {
        $sections = [
            'Early Bird' => [
                'metadata' => ['icon' => 'sunrise', 'color' => '#FDB813', 'time_start' => '05:00', 'time_end' => '10:00']
            ],
            'Breakfast' => [
                'metadata' => ['icon' => 'sun', 'color' => '#FFD700', 'time_start' => '06:00', 'time_end' => '11:30']
            ],
            'Brunch' => [
                'metadata' => ['icon' => 'coffee', 'color' => '#DEB887', 'time_start' => '10:00', 'time_end' => '14:00']
            ],
            'Lunch' => [
                'metadata' => ['icon' => 'sun', 'color' => '#FFA500', 'time_start' => '11:30', 'time_end' => '15:00']
            ],
            'Afternoon Tea' => [
                'metadata' => ['icon' => 'mug-hot', 'color' => '#FFB6C1', 'time_start' => '14:00', 'time_end' => '17:00']
            ],
            'Happy Hour' => [
                'metadata' => ['icon' => 'glass-cheers', 'color' => '#FF69B4', 'time_start' => '16:00', 'time_end' => '19:00']
            ],
            'Dinner' => [
                'metadata' => ['icon' => 'moon', 'color' => '#4B0082', 'time_start' => '17:00', 'time_end' => '22:00']
            ],
            'Late Night' => [
                'metadata' => ['icon' => 'moon-stars', 'color' => '#191970', 'time_start' => '22:00', 'time_end' => '02:00']
            ],
            'All Day' => [
                'metadata' => ['icon' => 'clock', 'color' => '#228B22', 'time_start' => '00:00', 'time_end' => '23:59']
            ],
            'Weekend Specials' => [
                'metadata' => ['icon' => 'calendar-week', 'color' => '#FF1493', 'days' => ['saturday', 'sunday']]
            ],
            'Daily Specials' => [
                'metadata' => ['icon' => 'star', 'color' => '#FFD700', 'featured' => true]
            ],
            'Kids Menu' => [
                'metadata' => ['icon' => 'child', 'color' => '#87CEEB']
            ],
            'Seniors Menu' => [
                'metadata' => ['icon' => 'user-clock', 'color' => '#D3D3D3']
            ],
            'Catering Menu' => [
                'metadata' => ['icon' => 'truck', 'color' => '#708090']
            ],
            'Takeout Only' => [
                'metadata' => ['icon' => 'box', 'color' => '#CD853F']
            ],
            'Dine-In Only' => [
                'metadata' => ['icon' => 'utensils', 'color' => '#8B4513']
            ]
        ];
        
        $sortOrder = 0;
        foreach ($sections as $name => $data) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($name),
                    'type' => TaxonomyType::MENU_SECTION->value,
                ],
                [
                    'name' => $name,
                    'metadata' => $data['metadata'],
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function createIngredientTypes(): void
    {
        $ingredients = [
            'Proteins' => [
                'metadata' => ['icon' => 'drumstick', 'color' => '#8B4513'],
                'children' => [
                    'Meat' => [
                        'children' => [
                            'Beef' => ['Ground Beef', 'Steak Cuts', 'Roast', 'Short Ribs', 'Brisket'],
                            'Pork' => ['Ground Pork', 'Pork Chops', 'Bacon', 'Ham', 'Sausage', 'Pork Belly'],
                            'Poultry' => ['Chicken Breast', 'Chicken Thighs', 'Whole Chicken', 'Turkey', 'Duck', 'Quail'],
                            'Lamb' => ['Lamb Chops', 'Ground Lamb', 'Lamb Shoulder', 'Rack of Lamb'],
                            'Game' => ['Venison', 'Wild Boar', 'Rabbit', 'Pheasant']
                        ]
                    ],
                    'Seafood' => [
                        'children' => [
                            'Fish' => ['Salmon', 'Tuna', 'Cod', 'Halibut', 'Tilapia', 'Sea Bass', 'Trout', 'Mahi Mahi'],
                            'Shellfish' => ['Shrimp', 'Lobster', 'Crab', 'Scallops', 'Mussels', 'Clams', 'Oysters'],
                            'Other Seafood' => ['Squid', 'Octopus', 'Eel', 'Caviar']
                        ]
                    ],
                    'Plant Proteins' => [
                        'children' => ['Tofu', 'Tempeh', 'Seitan', 'TVP', 'Plant-Based Meat', 'Legumes']
                    ]
                ]
            ],
            'Dairy' => [
                'metadata' => ['icon' => 'cheese', 'color' => '#FFFACD'],
                'children' => [
                    'Milk Products' => ['Whole Milk', 'Skim Milk', 'Cream', 'Half & Half', 'Buttermilk', 'Evaporated Milk'],
                    'Cheese' => [
                        'children' => [
                            'Soft Cheese' => ['Mozzarella', 'Ricotta', 'Brie', 'Camembert', 'Feta', 'Goat Cheese'],
                            'Hard Cheese' => ['Cheddar', 'Parmesan', 'Swiss', 'Gruyere', 'Gouda', 'Manchego'],
                            'Blue Cheese' => ['Gorgonzola', 'Roquefort', 'Stilton']
                        ]
                    ],
                    'Yogurt & Cream' => ['Greek Yogurt', 'Regular Yogurt', 'Sour Cream', 'Crème Fraîche', 'Mascarpone'],
                    'Butter & Eggs' => ['Butter', 'Margarine', 'Eggs', 'Egg Whites', 'Egg Substitute']
                ]
            ],
            'Produce' => [
                'metadata' => ['icon' => 'carrot', 'color' => '#90EE90'],
                'children' => [
                    'Vegetables' => [
                        'children' => [
                            'Leafy Greens' => ['Lettuce', 'Spinach', 'Kale', 'Arugula', 'Romaine', 'Cabbage', 'Bok Choy'],
                            'Root Vegetables' => ['Potatoes', 'Carrots', 'Onions', 'Garlic', 'Beets', 'Turnips', 'Radishes'],
                            'Cruciferous' => ['Broccoli', 'Cauliflower', 'Brussels Sprouts', 'Cabbage'],
                            'Nightshades' => ['Tomatoes', 'Bell Peppers', 'Eggplant', 'Chili Peppers'],
                            'Squash' => ['Zucchini', 'Yellow Squash', 'Butternut', 'Pumpkin', 'Acorn Squash'],
                            'Other Vegetables' => ['Corn', 'Peas', 'Green Beans', 'Asparagus', 'Celery', 'Cucumber', 'Mushrooms']
                        ]
                    ],
                    'Fruits' => [
                        'children' => [
                            'Citrus' => ['Oranges', 'Lemons', 'Limes', 'Grapefruit', 'Tangerines'],
                            'Berries' => ['Strawberries', 'Blueberries', 'Raspberries', 'Blackberries', 'Cranberries'],
                            'Tree Fruits' => ['Apples', 'Pears', 'Peaches', 'Plums', 'Cherries', 'Apricots'],
                            'Tropical' => ['Bananas', 'Pineapple', 'Mango', 'Papaya', 'Coconut', 'Passion Fruit'],
                            'Melons' => ['Watermelon', 'Cantaloupe', 'Honeydew'],
                            'Dried Fruits' => ['Raisins', 'Dates', 'Figs', 'Prunes', 'Apricots']
                        ]
                    ],
                    'Fresh Herbs' => [
                        'children' => ['Basil', 'Cilantro', 'Parsley', 'Mint', 'Rosemary', 'Thyme', 'Oregano', 'Sage', 'Dill', 'Chives']
                    ]
                ]
            ],
            'Grains & Starches' => [
                'metadata' => ['icon' => 'wheat', 'color' => '#F5DEB3'],
                'children' => [
                    'Rice' => ['White Rice', 'Brown Rice', 'Jasmine', 'Basmati', 'Wild Rice', 'Arborio', 'Sushi Rice'],
                    'Pasta' => ['Spaghetti', 'Penne', 'Fusilli', 'Rigatoni', 'Lasagna Sheets', 'Macaroni', 'Orzo'],
                    'Bread & Flour' => ['All-Purpose Flour', 'Bread Flour', 'Whole Wheat', 'Cornmeal', 'Breadcrumbs', 'Yeast'],
                    'Other Grains' => ['Quinoa', 'Barley', 'Oats', 'Couscous', 'Bulgur', 'Millet', 'Farro']
                ]
            ],
            'Condiments & Sauces' => [
                'metadata' => ['icon' => 'bottle', 'color' => '#DC143C'],
                'children' => [
                    'Basic Condiments' => ['Ketchup', 'Mustard', 'Mayonnaise', 'Hot Sauce', 'BBQ Sauce', 'Worcestershire'],
                    'Asian Sauces' => ['Soy Sauce', 'Teriyaki', 'Hoisin', 'Sriracha', 'Fish Sauce', 'Oyster Sauce'],
                    'Vinegars' => ['Balsamic', 'Red Wine Vinegar', 'Apple Cider Vinegar', 'Rice Vinegar', 'White Vinegar'],
                    'Cooking Sauces' => ['Tomato Sauce', 'Alfredo', 'Pesto', 'Marinara', 'Hollandaise', 'Béarnaise']
                ]
            ],
            'Oils & Fats' => [
                'metadata' => ['icon' => 'droplet', 'color' => '#FFD700'],
                'children' => [
                    'Cooking Oils' => ['Olive Oil', 'Vegetable Oil', 'Canola Oil', 'Coconut Oil', 'Peanut Oil', 'Sesame Oil'],
                    'Animal Fats' => ['Butter', 'Lard', 'Tallow', 'Duck Fat', 'Bacon Fat']
                ]
            ],
            'Seasonings & Spices' => [
                'metadata' => ['icon' => 'pepper', 'color' => '#8B0000'],
                'children' => [
                    'Basic Seasonings' => ['Salt', 'Black Pepper', 'White Pepper', 'Garlic Powder', 'Onion Powder'],
                    'Herbs & Spices' => ['Paprika', 'Cumin', 'Coriander', 'Turmeric', 'Cinnamon', 'Nutmeg', 'Cloves', 'Bay Leaves'],
                    'Spice Blends' => ['Italian Seasoning', 'Cajun', 'Curry Powder', 'Chinese Five Spice', 'Garam Masala']
                ]
            ],
            'Baking Ingredients' => [
                'metadata' => ['icon' => 'cake', 'color' => '#DEB887'],
                'children' => [
                    'Sweeteners' => ['Sugar', 'Brown Sugar', 'Honey', 'Maple Syrup', 'Corn Syrup', 'Molasses'],
                    'Leavening' => ['Baking Powder', 'Baking Soda', 'Yeast', 'Cream of Tartar'],
                    'Chocolate' => ['Dark Chocolate', 'Milk Chocolate', 'White Chocolate', 'Cocoa Powder', 'Chocolate Chips'],
                    'Extracts' => ['Vanilla Extract', 'Almond Extract', 'Lemon Extract', 'Peppermint Extract']
                ]
            ],
            'Beverages' => [
                'metadata' => ['icon' => 'glass', 'color' => '#4682B4'],
                'children' => [
                    'Non-Alcoholic' => ['Coffee', 'Tea', 'Juices', 'Soda', 'Water', 'Sports Drinks'],
                    'Alcoholic' => ['Beer', 'Wine', 'Spirits', 'Liqueurs']
                ]
            ]
        ];
        
        $this->createHierarchy($ingredients, TaxonomyType::INGREDIENT_TYPE);
    }
    
    private function createDietaryLabels(): void
    {
        $labels = [
            ['name' => 'Vegan', 'metadata' => ['icon' => 'leaf', 'color' => '#228B22', 'description' => 'Contains no animal products']],
            ['name' => 'Vegetarian', 'metadata' => ['icon' => 'carrot', 'color' => '#32CD32', 'description' => 'Contains no meat or fish']],
            ['name' => 'Pescatarian', 'metadata' => ['icon' => 'fish', 'color' => '#4682B4', 'description' => 'Contains fish but no other meat']],
            ['name' => 'Gluten-Free', 'metadata' => ['icon' => 'wheat-slash', 'color' => '#D2691E', 'description' => 'Contains no gluten']],
            ['name' => 'Dairy-Free', 'metadata' => ['icon' => 'cow-slash', 'color' => '#F0E68C', 'description' => 'Contains no dairy products']],
            ['name' => 'Nut-Free', 'metadata' => ['icon' => 'tree-slash', 'color' => '#8B4513', 'description' => 'Contains no tree nuts or peanuts']],
            ['name' => 'Egg-Free', 'metadata' => ['icon' => 'egg-slash', 'color' => '#FFFACD', 'description' => 'Contains no eggs']],
            ['name' => 'Soy-Free', 'metadata' => ['icon' => 'seedling-slash', 'color' => '#8FBC8F', 'description' => 'Contains no soy products']],
            ['name' => 'Halal', 'metadata' => ['icon' => 'certificate', 'color' => '#006400', 'description' => 'Prepared according to Islamic law']],
            ['name' => 'Kosher', 'metadata' => ['icon' => 'star-david', 'color' => '#4169E1', 'description' => 'Prepared according to Jewish dietary laws']],
            ['name' => 'Organic', 'metadata' => ['icon' => 'seedling', 'color' => '#90EE90', 'description' => 'Made with certified organic ingredients']],
            ['name' => 'Non-GMO', 'metadata' => ['icon' => 'dna-slash', 'color' => '#98FB98', 'description' => 'Contains no genetically modified organisms']],
            ['name' => 'Low-Carb', 'metadata' => ['icon' => 'chart-line-down', 'color' => '#FF6347', 'description' => 'Low in carbohydrates']],
            ['name' => 'Keto-Friendly', 'metadata' => ['icon' => 'fire', 'color' => '#FF4500', 'description' => 'Ketogenic diet compatible']],
            ['name' => 'Paleo', 'metadata' => ['icon' => 'bone', 'color' => '#8B4513', 'description' => 'Paleolithic diet friendly']],
            ['name' => 'Whole30', 'metadata' => ['icon' => 'calendar-30', 'color' => '#FF8C00', 'description' => 'Whole30 program compliant']],
            ['name' => 'Mediterranean', 'metadata' => ['icon' => 'olive', 'color' => '#808000', 'description' => 'Mediterranean diet friendly']],
            ['name' => 'DASH', 'metadata' => ['icon' => 'heart', 'color' => '#FF69B4', 'description' => 'DASH diet compliant']],
            ['name' => 'Sugar-Free', 'metadata' => ['icon' => 'candy-cane-slash', 'color' => '#FF69B4', 'description' => 'Contains no added sugar']],
            ['name' => 'Low-Sodium', 'metadata' => ['icon' => 'salt-shaker-slash', 'color' => '#B0C4DE', 'description' => 'Low in sodium content']],
            ['name' => 'High-Protein', 'metadata' => ['icon' => 'dumbbell', 'color' => '#DC143C', 'description' => 'High in protein content']],
            ['name' => 'High-Fiber', 'metadata' => ['icon' => 'wheat', 'color' => '#DEB887', 'description' => 'High in dietary fiber']],
            ['name' => 'Low-Fat', 'metadata' => ['icon' => 'droplet-slash', 'color' => '#87CEEB', 'description' => 'Low in fat content']],
            ['name' => 'Heart-Healthy', 'metadata' => ['icon' => 'heart-pulse', 'color' => '#FF1493', 'description' => 'Good for cardiovascular health']],
            ['name' => 'Diabetic-Friendly', 'metadata' => ['icon' => 'glucose', 'color' => '#4B0082', 'description' => 'Suitable for diabetics']],
            ['name' => 'Raw', 'metadata' => ['icon' => 'leaf', 'color' => '#00FF00', 'description' => 'Uncooked or minimally processed']],
            ['name' => 'Locally-Sourced', 'metadata' => ['icon' => 'map-marker', 'color' => '#228B22', 'description' => 'Made with local ingredients']],
            ['name' => 'Farm-to-Table', 'metadata' => ['icon' => 'tractor', 'color' => '#8B4513', 'description' => 'Direct from farm sources']],
            ['name' => 'Sustainable', 'metadata' => ['icon' => 'recycle', 'color' => '#008000', 'description' => 'Environmentally sustainable']]
        ];
        
        foreach ($labels as $index => $label) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($label['name']),
                    'type' => TaxonomyType::DIETARY_LABEL->value,
                ],
                [
                    'name' => $label['name'],
                    'metadata' => $label['metadata'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function createAllergens(): void
    {
        $allergens = [
            ['name' => 'Contains Milk', 'metadata' => ['icon' => 'cow', 'color' => '#F5F5DC', 'severity' => 'high']],
            ['name' => 'Contains Eggs', 'metadata' => ['icon' => 'egg', 'color' => '#FFFACD', 'severity' => 'high']],
            ['name' => 'Contains Fish', 'metadata' => ['icon' => 'fish', 'color' => '#4682B4', 'severity' => 'high']],
            ['name' => 'Contains Shellfish', 'metadata' => ['icon' => 'shrimp', 'color' => '#FF7F50', 'severity' => 'high']],
            ['name' => 'Contains Crustaceans', 'metadata' => ['icon' => 'lobster', 'color' => '#FF6347', 'severity' => 'high']],
            ['name' => 'Contains Tree Nuts', 'metadata' => ['icon' => 'tree', 'color' => '#8B4513', 'severity' => 'high']],
            ['name' => 'Contains Peanuts', 'metadata' => ['icon' => 'peanut', 'color' => '#D2691E', 'severity' => 'high']],
            ['name' => 'Contains Wheat', 'metadata' => ['icon' => 'wheat', 'color' => '#F5DEB3', 'severity' => 'high']],
            ['name' => 'Contains Soy', 'metadata' => ['icon' => 'seedling', 'color' => '#8FBC8F', 'severity' => 'medium']],
            ['name' => 'Contains Sesame', 'metadata' => ['icon' => 'seed', 'color' => '#F4A460', 'severity' => 'medium']],
            ['name' => 'Contains Mustard', 'metadata' => ['icon' => 'jar', 'color' => '#FFDB58', 'severity' => 'medium']],
            ['name' => 'Contains Celery', 'metadata' => ['icon' => 'plant', 'color' => '#90EE90', 'severity' => 'low']],
            ['name' => 'Contains Lupin', 'metadata' => ['icon' => 'flower', 'color' => '#9370DB', 'severity' => 'medium']],
            ['name' => 'Contains Molluscs', 'metadata' => ['icon' => 'shell', 'color' => '#8B7D6B', 'severity' => 'high']],
            ['name' => 'Contains Sulphites', 'metadata' => ['icon' => 'flask', 'color' => '#FFD700', 'severity' => 'medium']],
            ['name' => 'Contains Gluten', 'metadata' => ['icon' => 'bread', 'color' => '#DEB887', 'severity' => 'high']],
            ['name' => 'Contains Lactose', 'metadata' => ['icon' => 'glass-milk', 'color' => '#FFFFF0', 'severity' => 'medium']],
            ['name' => 'May Contain Traces of Nuts', 'metadata' => ['icon' => 'exclamation-triangle', 'color' => '#FFA500', 'severity' => 'warning']],
            ['name' => 'May Contain Traces of Gluten', 'metadata' => ['icon' => 'exclamation-triangle', 'color' => '#F0E68C', 'severity' => 'warning']]
        ];
        
        foreach ($allergens as $index => $allergen) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($allergen['name']),
                    'type' => TaxonomyType::ALLERGEN->value,
                ],
                [
                    'name' => $allergen['name'],
                    'metadata' => $allergen['metadata'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function createCuisineTypes(): void
    {
        $cuisines = [
            // Americas
            ['name' => 'Chilean', 'metadata' => ['icon' => 'flag', 'color' => '#D52B1E', 'region' => 'South America']],
            ['name' => 'American', 'metadata' => ['icon' => 'flag-usa', 'color' => '#B22234', 'region' => 'North America']],
            ['name' => 'Mexican', 'metadata' => ['icon' => 'pepper-hot', 'color' => '#006341', 'region' => 'North America']],
            ['name' => 'Brazilian', 'metadata' => ['icon' => 'flag', 'color' => '#009C3B', 'region' => 'South America']],
            ['name' => 'Peruvian', 'metadata' => ['icon' => 'mountain', 'color' => '#D91023', 'region' => 'South America']],
            ['name' => 'Argentinian', 'metadata' => ['icon' => 'cow', 'color' => '#75AADB', 'region' => 'South America']],
            ['name' => 'Colombian', 'metadata' => ['icon' => 'coffee', 'color' => '#FCD116', 'region' => 'South America']],
            ['name' => 'Caribbean', 'metadata' => ['icon' => 'umbrella-beach', 'color' => '#00CED1', 'region' => 'Caribbean']],
            ['name' => 'Cuban', 'metadata' => ['icon' => 'cigar', 'color' => '#002590', 'region' => 'Caribbean']],
            ['name' => 'Canadian', 'metadata' => ['icon' => 'maple-leaf', 'color' => '#FF0000', 'region' => 'North America']],
            
            // Europe
            ['name' => 'Italian', 'metadata' => ['icon' => 'pizza-slice', 'color' => '#008C45', 'region' => 'Europe']],
            ['name' => 'French', 'metadata' => ['icon' => 'wine-glass', 'color' => '#0055A4', 'region' => 'Europe']],
            ['name' => 'Spanish', 'metadata' => ['icon' => 'guitar', 'color' => '#C60B1E', 'region' => 'Europe']],
            ['name' => 'Mediterranean', 'metadata' => ['icon' => 'olive', 'color' => '#0D5EAF', 'region' => 'Mediterranean']],
            ['name' => 'Greek', 'metadata' => ['icon' => 'columns', 'color' => '#0D5EAF', 'region' => 'Europe']],
            ['name' => 'German', 'metadata' => ['icon' => 'beer', 'color' => '#DD0000', 'region' => 'Europe']],
            ['name' => 'British', 'metadata' => ['icon' => 'crown', 'color' => '#012169', 'region' => 'Europe']],
            ['name' => 'Irish', 'metadata' => ['icon' => 'clover', 'color' => '#169B62', 'region' => 'Europe']],
            ['name' => 'Portuguese', 'metadata' => ['icon' => 'fish', 'color' => '#006600', 'region' => 'Europe']],
            ['name' => 'Russian', 'metadata' => ['icon' => 'snowflake', 'color' => '#0039A6', 'region' => 'Europe']],
            ['name' => 'Polish', 'metadata' => ['icon' => 'kielbasa', 'color' => '#DC143C', 'region' => 'Europe']],
            ['name' => 'Scandinavian', 'metadata' => ['icon' => 'fish', 'color' => '#006AA7', 'region' => 'Europe']],
            
            // Asia
            ['name' => 'Chinese', 'metadata' => ['icon' => 'dragon', 'color' => '#DE2910', 'region' => 'Asia']],
            ['name' => 'Japanese', 'metadata' => ['icon' => 'torii', 'color' => '#BC002D', 'region' => 'Asia']],
            ['name' => 'Thai', 'metadata' => ['icon' => 'pepper', 'color' => '#A51931', 'region' => 'Asia']],
            ['name' => 'Indian', 'metadata' => ['icon' => 'curry', 'color' => '#FF9933', 'region' => 'Asia']],
            ['name' => 'Korean', 'metadata' => ['icon' => 'kimchi', 'color' => '#003478', 'region' => 'Asia']],
            ['name' => 'Vietnamese', 'metadata' => ['icon' => 'bowl-rice', 'color' => '#DA251D', 'region' => 'Asia']],
            ['name' => 'Malaysian', 'metadata' => ['icon' => 'spice', 'color' => '#FFD700', 'region' => 'Asia']],
            ['name' => 'Indonesian', 'metadata' => ['icon' => 'island', 'color' => '#FF0000', 'region' => 'Asia']],
            ['name' => 'Filipino', 'metadata' => ['icon' => 'sun', 'color' => '#0038A8', 'region' => 'Asia']],
            ['name' => 'Singaporean', 'metadata' => ['icon' => 'city', 'color' => '#EE2536', 'region' => 'Asia']],
            
            // Middle East & Africa
            ['name' => 'Middle Eastern', 'metadata' => ['icon' => 'mosque', 'color' => '#006233', 'region' => 'Middle East']],
            ['name' => 'Turkish', 'metadata' => ['icon' => 'kebab', 'color' => '#E30A17', 'region' => 'Middle East']],
            ['name' => 'Lebanese', 'metadata' => ['icon' => 'tree', 'color' => '#ED1C24', 'region' => 'Middle East']],
            ['name' => 'Israeli', 'metadata' => ['icon' => 'star-david', 'color' => '#0038B8', 'region' => 'Middle East']],
            ['name' => 'Moroccan', 'metadata' => ['icon' => 'tagine', 'color' => '#C1272D', 'region' => 'Africa']],
            ['name' => 'Ethiopian', 'metadata' => ['icon' => 'coffee-bean', 'color' => '#FCDD09', 'region' => 'Africa']],
            ['name' => 'South African', 'metadata' => ['icon' => 'grill', 'color' => '#007A4D', 'region' => 'Africa']],
            
            // Fusion & Modern
            ['name' => 'Fusion', 'metadata' => ['icon' => 'blend', 'color' => '#FF6B6B', 'region' => 'International']],
            ['name' => 'Asian Fusion', 'metadata' => ['icon' => 'yin-yang', 'color' => '#FF6B35', 'region' => 'International']],
            ['name' => 'Tex-Mex', 'metadata' => ['icon' => 'taco', 'color' => '#BF0A30', 'region' => 'Fusion']],
            ['name' => 'Modern European', 'metadata' => ['icon' => 'star', 'color' => '#4B0082', 'region' => 'Modern']],
            ['name' => 'Contemporary', 'metadata' => ['icon' => 'sparkles', 'color' => '#FF1493', 'region' => 'Modern']],
            ['name' => 'Molecular', 'metadata' => ['icon' => 'flask', 'color' => '#00CED1', 'region' => 'Modern']]
        ];
        
        foreach ($cuisines as $index => $cuisine) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($cuisine['name']),
                    'type' => TaxonomyType::CUISINE_TYPE->value,
                ],
                [
                    'name' => $cuisine['name'],
                    'metadata' => $cuisine['metadata'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function createPreparationMethods(): void
    {
        $methods = [
            // Dry Heat Methods
            ['name' => 'Grilled', 'metadata' => ['icon' => 'grill', 'color' => '#8B4513', 'heat_type' => 'dry', 'temp' => 'high']],
            ['name' => 'Roasted', 'metadata' => ['icon' => 'oven', 'color' => '#A0522D', 'heat_type' => 'dry', 'temp' => 'medium-high']],
            ['name' => 'Baked', 'metadata' => ['icon' => 'bread', 'color' => '#D2691E', 'heat_type' => 'dry', 'temp' => 'medium']],
            ['name' => 'Broiled', 'metadata' => ['icon' => 'fire-flame', 'color' => '#FF4500', 'heat_type' => 'dry', 'temp' => 'very-high']],
            ['name' => 'Pan-Seared', 'metadata' => ['icon' => 'pan', 'color' => '#8B4513', 'heat_type' => 'dry', 'temp' => 'high']],
            ['name' => 'Sautéed', 'metadata' => ['icon' => 'pan', 'color' => '#DAA520', 'heat_type' => 'dry', 'temp' => 'medium-high']],
            ['name' => 'Stir-Fried', 'metadata' => ['icon' => 'wok', 'color' => '#FF8C00', 'heat_type' => 'dry', 'temp' => 'very-high']],
            ['name' => 'Pan-Fried', 'metadata' => ['icon' => 'pan', 'color' => '#FF6347', 'heat_type' => 'dry', 'temp' => 'medium']],
            ['name' => 'Deep-Fried', 'metadata' => ['icon' => 'oil', 'color' => '#FFD700', 'heat_type' => 'dry', 'temp' => 'high']],
            ['name' => 'Air-Fried', 'metadata' => ['icon' => 'wind', 'color' => '#87CEEB', 'heat_type' => 'dry', 'temp' => 'high']],
            ['name' => 'Smoked', 'metadata' => ['icon' => 'smoke', 'color' => '#708090', 'heat_type' => 'dry', 'temp' => 'low']],
            ['name' => 'Chargrilled', 'metadata' => ['icon' => 'fire', 'color' => '#2F4F4F', 'heat_type' => 'dry', 'temp' => 'very-high']],
            ['name' => 'Blackened', 'metadata' => ['icon' => 'fire-flame', 'color' => '#000000', 'heat_type' => 'dry', 'temp' => 'very-high']],
            
            // Moist Heat Methods
            ['name' => 'Boiled', 'metadata' => ['icon' => 'water', 'color' => '#4682B4', 'heat_type' => 'moist', 'temp' => 'high']],
            ['name' => 'Steamed', 'metadata' => ['icon' => 'cloud', 'color' => '#87CEEB', 'heat_type' => 'moist', 'temp' => 'medium']],
            ['name' => 'Poached', 'metadata' => ['icon' => 'droplet', 'color' => '#B0E0E6', 'heat_type' => 'moist', 'temp' => 'low']],
            ['name' => 'Simmered', 'metadata' => ['icon' => 'bubbles', 'color' => '#5F9EA0', 'heat_type' => 'moist', 'temp' => 'low']],
            ['name' => 'Blanched', 'metadata' => ['icon' => 'thermometer', 'color' => '#00CED1', 'heat_type' => 'moist', 'temp' => 'high']],
            ['name' => 'Braised', 'metadata' => ['icon' => 'pot', 'color' => '#8B7355', 'heat_type' => 'combination', 'temp' => 'low']],
            ['name' => 'Stewed', 'metadata' => ['icon' => 'cauldron', 'color' => '#8B4513', 'heat_type' => 'moist', 'temp' => 'low']],
            ['name' => 'Pressure-Cooked', 'metadata' => ['icon' => 'gauge', 'color' => '#4169E1', 'heat_type' => 'moist', 'temp' => 'high']],
            ['name' => 'Slow-Cooked', 'metadata' => ['icon' => 'clock', 'color' => '#CD853F', 'heat_type' => 'moist', 'temp' => 'low']],
            ['name' => 'Sous Vide', 'metadata' => ['icon' => 'vacuum', 'color' => '#4B0082', 'heat_type' => 'moist', 'temp' => 'precise']],
            
            // No Heat Methods
            ['name' => 'Raw', 'metadata' => ['icon' => 'leaf', 'color' => '#90EE90', 'heat_type' => 'none', 'temp' => 'none']],
            ['name' => 'Cured', 'metadata' => ['icon' => 'salt', 'color' => '#F5F5DC', 'heat_type' => 'none', 'temp' => 'none']],
            ['name' => 'Marinated', 'metadata' => ['icon' => 'jar', 'color' => '#FF69B4', 'heat_type' => 'none', 'temp' => 'none']],
            ['name' => 'Pickled', 'metadata' => ['icon' => 'jar', 'color' => '#9ACD32', 'heat_type' => 'none', 'temp' => 'none']],
            ['name' => 'Fermented', 'metadata' => ['icon' => 'bacteria', 'color' => '#8FBC8F', 'heat_type' => 'none', 'temp' => 'none']],
            
            // Specialty Methods
            ['name' => 'Flambéed', 'metadata' => ['icon' => 'fire', 'color' => '#FF4500', 'heat_type' => 'special', 'temp' => 'high']],
            ['name' => 'Torched', 'metadata' => ['icon' => 'flame', 'color' => '#FF8C00', 'heat_type' => 'special', 'temp' => 'very-high']],
            ['name' => 'Glazed', 'metadata' => ['icon' => 'brush', 'color' => '#DAA520', 'heat_type' => 'combination', 'temp' => 'medium']],
            ['name' => 'Caramelized', 'metadata' => ['icon' => 'candy', 'color' => '#D2691E', 'heat_type' => 'dry', 'temp' => 'medium']],
            ['name' => 'Dehydrated', 'metadata' => ['icon' => 'sun', 'color' => '#F4A460', 'heat_type' => 'dry', 'temp' => 'low']],
            ['name' => 'Freeze-Dried', 'metadata' => ['icon' => 'snowflake', 'color' => '#ADD8E6', 'heat_type' => 'special', 'temp' => 'none']],
            ['name' => 'Confit', 'metadata' => ['icon' => 'oil-can', 'color' => '#FFD700', 'heat_type' => 'moist', 'temp' => 'low']]
        ];
        
        foreach ($methods as $index => $method) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($method['name']),
                    'type' => TaxonomyType::PREPARATION_METHOD->value,
                ],
                [
                    'name' => $method['name'],
                    'metadata' => $method['metadata'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function createSpiceLevels(): void
    {
        $levels = [
            ['name' => 'Not Spicy', 'metadata' => ['icon' => 'leaf', 'color' => '#98FB98', 'level' => 0, 'scoville' => '0']],
            ['name' => 'Mild', 'metadata' => ['icon' => 'pepper-mild', 'color' => '#90EE90', 'level' => 1, 'scoville' => '0-1000']],
            ['name' => 'Medium-Mild', 'metadata' => ['icon' => 'pepper', 'color' => '#ADFF2F', 'level' => 2, 'scoville' => '1000-2500']],
            ['name' => 'Medium', 'metadata' => ['icon' => 'pepper-hot', 'color' => '#FFD700', 'level' => 3, 'scoville' => '2500-5000']],
            ['name' => 'Medium-Hot', 'metadata' => ['icon' => 'pepper-hot', 'color' => '#FFA500', 'level' => 4, 'scoville' => '5000-15000']],
            ['name' => 'Hot', 'metadata' => ['icon' => 'fire', 'color' => '#FF8C00', 'level' => 5, 'scoville' => '15000-30000']],
            ['name' => 'Very Hot', 'metadata' => ['icon' => 'fire', 'color' => '#FF6347', 'level' => 6, 'scoville' => '30000-50000']],
            ['name' => 'Extra Hot', 'metadata' => ['icon' => 'fire-flame', 'color' => '#FF4500', 'level' => 7, 'scoville' => '50000-100000']],
            ['name' => 'Extremely Hot', 'metadata' => ['icon' => 'fire-flame-curved', 'color' => '#DC143C', 'level' => 8, 'scoville' => '100000-350000']],
            ['name' => 'Insanely Hot', 'metadata' => ['icon' => 'explosion', 'color' => '#8B0000', 'level' => 9, 'scoville' => '350000-1000000']],
            ['name' => 'Nuclear', 'metadata' => ['icon' => 'radiation', 'color' => '#FF0000', 'level' => 10, 'scoville' => '1000000+', 'warning' => true]]
        ];
        
        foreach ($levels as $index => $level) {
            $taxonomy = Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($level['name']),
                    'type' => TaxonomyType::SPICE_LEVEL->value,
                ],
                [
                    'name' => $level['name'],
                    'metadata' => $level['metadata'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
            
            // Add spice level as attribute
            $taxonomy->setTaxonomyAttribute('level', $level['metadata']['level'], 'number');
            $taxonomy->setTaxonomyAttribute('scoville_range', $level['metadata']['scoville'], 'string');
        }
    }
    
    private function createPriceRanges(): void
    {
        $ranges = [
            ['name' => 'Free', 'metadata' => ['icon' => 'gift', 'color' => '#32CD32', 'symbol' => 'FREE', 'min' => 0, 'max' => 0]],
            ['name' => 'Budget', 'metadata' => ['icon' => 'dollar-sign', 'color' => '#90EE90', 'symbol' => '$', 'min' => 0.01, 'max' => 10]],
            ['name' => 'Economy', 'metadata' => ['icon' => 'dollar-sign', 'color' => '#9ACD32', 'symbol' => '$$', 'min' => 10, 'max' => 20]],
            ['name' => 'Standard', 'metadata' => ['icon' => 'dollar-sign', 'color' => '#FFD700', 'symbol' => '$$$', 'min' => 20, 'max' => 35]],
            ['name' => 'Premium', 'metadata' => ['icon' => 'dollar-sign', 'color' => '#FF8C00', 'symbol' => '$$$$', 'min' => 35, 'max' => 60]],
            ['name' => 'Luxury', 'metadata' => ['icon' => 'gem', 'color' => '#DC143C', 'symbol' => '$$$$$', 'min' => 60, 'max' => 100]],
            ['name' => 'Ultra-Luxury', 'metadata' => ['icon' => 'crown', 'color' => '#4B0082', 'symbol' => '$$$$$$', 'min' => 100, 'max' => null]],
            ['name' => 'Market Price', 'metadata' => ['icon' => 'chart-line', 'color' => '#4169E1', 'symbol' => 'MP', 'variable' => true]]
        ];
        
        foreach ($ranges as $index => $range) {
            $taxonomy = Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($range['name']),
                    'type' => TaxonomyType::PRICE_RANGE->value,
                ],
                [
                    'name' => $range['name'],
                    'metadata' => $range['metadata'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
            
            // Add price range attributes
            if (isset($range['metadata']['min'])) {
                $taxonomy->setTaxonomyAttribute('min_price', $range['metadata']['min'], 'number');
            }
            if (isset($range['metadata']['max'])) {
                $taxonomy->setTaxonomyAttribute('max_price', $range['metadata']['max'], 'number');
            }
        }
    }
    
    private function createLocationZones(): void
    {
        $zones = [
            'Chile' => [
                'metadata' => ['icon' => 'flag', 'color' => '#D52B1E', 'country_code' => 'CL'],
                'children' => [
                    'Santiago Metropolitan' => [
                        'metadata' => ['icon' => 'city', 'color' => '#0039A6'],
                        'children' => [
                            'Downtown Santiago' => ['Las Condes', 'Providencia', 'Santiago Centro', 'Vitacura', 'Lo Barnechea'],
                            'East Santiago' => ['Ñuñoa', 'La Reina', 'Peñalolén', 'La Florida', 'Puente Alto'],
                            'West Santiago' => ['Maipú', 'Pudahuel', 'Cerro Navia', 'Lo Prado', 'Quinta Normal'],
                            'North Santiago' => ['Recoleta', 'Independencia', 'Conchalí', 'Huechuraba', 'Quilicura'],
                            'South Santiago' => ['San Miguel', 'San Joaquín', 'La Cisterna', 'El Bosque', 'San Bernardo']
                        ]
                    ],
                    'Northern Chile' => [
                        'children' => [
                            'Arica y Parinacota' => ['Arica', 'Putre', 'Camarones'],
                            'Tarapacá' => ['Iquique', 'Alto Hospicio', 'Pozo Almonte'],
                            'Antofagasta' => ['Antofagasta', 'Calama', 'San Pedro de Atacama', 'Mejillones'],
                            'Atacama' => ['Copiapó', 'Caldera', 'Vallenar', 'Chañaral'],
                            'Coquimbo' => ['La Serena', 'Coquimbo', 'Ovalle', 'Vicuña']
                        ]
                    ],
                    'Central Chile' => [
                        'children' => [
                            'Valparaíso' => ['Valparaíso', 'Viña del Mar', 'Quilpué', 'Villa Alemana', 'San Antonio'],
                            'O\'Higgins' => ['Rancagua', 'San Fernando', 'Pichilemu', 'Santa Cruz'],
                            'Maule' => ['Talca', 'Curicó', 'Constitución', 'Linares'],
                            'Ñuble' => ['Chillán', 'San Carlos', 'Bulnes', 'Yungay'],
                            'Biobío' => ['Concepción', 'Talcahuano', 'Los Ángeles', 'Coronel', 'Lota']
                        ]
                    ],
                    'Southern Chile' => [
                        'children' => [
                            'La Araucanía' => ['Temuco', 'Padre Las Casas', 'Villarrica', 'Pucón', 'Angol'],
                            'Los Ríos' => ['Valdivia', 'La Unión', 'Río Bueno', 'Panguipulli'],
                            'Los Lagos' => ['Puerto Montt', 'Osorno', 'Castro', 'Ancud', 'Puerto Varas']
                        ]
                    ],
                    'Austral Chile' => [
                        'children' => [
                            'Aysén' => ['Coyhaique', 'Aysén', 'Chile Chico', 'Cochrane'],
                            'Magallanes' => ['Punta Arenas', 'Puerto Natales', 'Porvenir']
                        ]
                    ]
                ]
            ],
            'International' => [
                'metadata' => ['icon' => 'globe', 'color' => '#4169E1'],
                'children' => [
                    'North America' => [
                        'children' => ['United States', 'Canada', 'Mexico']
                    ],
                    'South America' => [
                        'children' => ['Argentina', 'Brazil', 'Peru', 'Colombia', 'Venezuela', 'Ecuador', 'Bolivia', 'Paraguay', 'Uruguay']
                    ],
                    'Europe' => [
                        'children' => ['Spain', 'France', 'Italy', 'Germany', 'United Kingdom', 'Portugal']
                    ],
                    'Asia' => [
                        'children' => ['China', 'Japan', 'South Korea', 'India', 'Thailand']
                    ],
                    'Oceania' => [
                        'children' => ['Australia', 'New Zealand']
                    ]
                ]
            ],
            'Delivery Zones' => [
                'metadata' => ['icon' => 'truck', 'color' => '#FF6347'],
                'children' => [
                    'Zone A - Central' => ['0-5 km radius', 'Free delivery', '15-30 min'],
                    'Zone B - Extended' => ['5-10 km radius', 'Small fee', '30-45 min'],
                    'Zone C - Suburban' => ['10-20 km radius', 'Standard fee', '45-60 min'],
                    'Zone D - Remote' => ['20+ km radius', 'Premium fee', '60+ min']
                ]
            ]
        ];
        
        $this->createHierarchy($zones, TaxonomyType::LOCATION_ZONE);
    }
    
    private function createPromotionTypes(): void
    {
        $promotions = [
            // Discount Types
            ['name' => 'Percentage Discount', 'metadata' => ['icon' => 'percent', 'color' => '#FF6347', 'type' => 'discount']],
            ['name' => 'Fixed Amount Off', 'metadata' => ['icon' => 'dollar-sign', 'color' => '#32CD32', 'type' => 'discount']],
            ['name' => 'Buy One Get One (BOGO)', 'metadata' => ['icon' => 'tags', 'color' => '#FFD700', 'type' => 'multi-buy']],
            ['name' => 'Buy X Get Y Free', 'metadata' => ['icon' => 'gift', 'color' => '#FF69B4', 'type' => 'multi-buy']],
            ['name' => 'Bundle Deal', 'metadata' => ['icon' => 'box', 'color' => '#9370DB', 'type' => 'bundle']],
            ['name' => 'Combo Offer', 'metadata' => ['icon' => 'utensils', 'color' => '#FF8C00', 'type' => 'bundle']],
            ['name' => 'Meal Deal', 'metadata' => ['icon' => 'burger', 'color' => '#DAA520', 'type' => 'bundle']],
            
            // Time-Based Promotions
            ['name' => 'Happy Hour', 'metadata' => ['icon' => 'clock', 'color' => '#FF1493', 'type' => 'time-based']],
            ['name' => 'Early Bird Special', 'metadata' => ['icon' => 'sun', 'color' => '#FFD700', 'type' => 'time-based']],
            ['name' => 'Late Night Deal', 'metadata' => ['icon' => 'moon', 'color' => '#191970', 'type' => 'time-based']],
            ['name' => 'Weekend Special', 'metadata' => ['icon' => 'calendar-week', 'color' => '#4169E1', 'type' => 'time-based']],
            ['name' => 'Weekday Offer', 'metadata' => ['icon' => 'briefcase', 'color' => '#708090', 'type' => 'time-based']],
            ['name' => 'Flash Sale', 'metadata' => ['icon' => 'bolt', 'color' => '#FFD700', 'type' => 'time-based']],
            ['name' => 'Limited Time Offer', 'metadata' => ['icon' => 'hourglass', 'color' => '#FF4500', 'type' => 'time-based']],
            
            // Customer-Based Promotions
            ['name' => 'Student Discount', 'metadata' => ['icon' => 'graduation-cap', 'color' => '#4682B4', 'type' => 'customer']],
            ['name' => 'Senior Discount', 'metadata' => ['icon' => 'user-clock', 'color' => '#D3D3D3', 'type' => 'customer']],
            ['name' => 'Military Discount', 'metadata' => ['icon' => 'shield', 'color' => '#556B2F', 'type' => 'customer']],
            ['name' => 'Employee Discount', 'metadata' => ['icon' => 'id-badge', 'color' => '#4B0082', 'type' => 'customer']],
            ['name' => 'Birthday Special', 'metadata' => ['icon' => 'cake', 'color' => '#FF69B4', 'type' => 'customer']],
            ['name' => 'Anniversary Offer', 'metadata' => ['icon' => 'heart', 'color' => '#DC143C', 'type' => 'customer']],
            ['name' => 'VIP Member Exclusive', 'metadata' => ['icon' => 'crown', 'color' => '#FFD700', 'type' => 'customer']],
            ['name' => 'New Customer Offer', 'metadata' => ['icon' => 'user-plus', 'color' => '#32CD32', 'type' => 'customer']],
            ['name' => 'Referral Bonus', 'metadata' => ['icon' => 'users', 'color' => '#00CED1', 'type' => 'customer']],
            
            // Loyalty Programs
            ['name' => 'Points Reward', 'metadata' => ['icon' => 'star', 'color' => '#FFD700', 'type' => 'loyalty']],
            ['name' => 'Cashback', 'metadata' => ['icon' => 'money-bill-wave', 'color' => '#228B22', 'type' => 'loyalty']],
            ['name' => 'Stamp Card', 'metadata' => ['icon' => 'stamp', 'color' => '#8B4513', 'type' => 'loyalty']],
            ['name' => 'Tier Upgrade', 'metadata' => ['icon' => 'level-up', 'color' => '#4169E1', 'type' => 'loyalty']],
            
            // Seasonal Promotions
            ['name' => 'Holiday Special', 'metadata' => ['icon' => 'tree', 'color' => '#228B22', 'type' => 'seasonal']],
            ['name' => 'Summer Sale', 'metadata' => ['icon' => 'sun', 'color' => '#FFD700', 'type' => 'seasonal']],
            ['name' => 'Winter Warmers', 'metadata' => ['icon' => 'snowflake', 'color' => '#87CEEB', 'type' => 'seasonal']],
            ['name' => 'Spring Special', 'metadata' => ['icon' => 'flower', 'color' => '#FFB6C1', 'type' => 'seasonal']],
            ['name' => 'Back to School', 'metadata' => ['icon' => 'backpack', 'color' => '#4682B4', 'type' => 'seasonal']],
            
            // Service Promotions
            ['name' => 'Free Delivery', 'metadata' => ['icon' => 'truck', 'color' => '#32CD32', 'type' => 'service']],
            ['name' => 'Free Upgrade', 'metadata' => ['icon' => 'arrow-up', 'color' => '#9370DB', 'type' => 'service']],
            ['name' => 'Priority Service', 'metadata' => ['icon' => 'rocket', 'color' => '#FF4500', 'type' => 'service']],
            ['name' => 'Extended Warranty', 'metadata' => ['icon' => 'shield-check', 'color' => '#2E8B57', 'type' => 'service']],
            
            // Cross-Selling
            ['name' => 'Add-On Deal', 'metadata' => ['icon' => 'plus', 'color' => '#FF8C00', 'type' => 'cross-sell']],
            ['name' => 'Upgrade Offer', 'metadata' => ['icon' => 'arrow-trend-up', 'color' => '#4169E1', 'type' => 'cross-sell']],
            ['name' => 'Side Item Special', 'metadata' => ['icon' => 'bowl', 'color' => '#DAA520', 'type' => 'cross-sell']]
        ];
        
        foreach ($promotions as $index => $promotion) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($promotion['name']),
                    'type' => TaxonomyType::PROMOTION_TYPE->value,
                ],
                [
                    'name' => $promotion['name'],
                    'metadata' => $promotion['metadata'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function createCustomerSegments(): void
    {
        $segments = [
            // Loyalty Tiers
            ['name' => 'Platinum VIP', 'metadata' => ['icon' => 'gem', 'color' => '#E5E4E2', 'tier' => 5, 'min_visits' => 100]],
            ['name' => 'Gold Member', 'metadata' => ['icon' => 'crown', 'color' => '#FFD700', 'tier' => 4, 'min_visits' => 50]],
            ['name' => 'Silver Member', 'metadata' => ['icon' => 'medal', 'color' => '#C0C0C0', 'tier' => 3, 'min_visits' => 25]],
            ['name' => 'Bronze Member', 'metadata' => ['icon' => 'award', 'color' => '#CD7F32', 'tier' => 2, 'min_visits' => 10]],
            ['name' => 'Regular Customer', 'metadata' => ['icon' => 'user', 'color' => '#4169E1', 'tier' => 1, 'min_visits' => 5]],
            ['name' => 'New Customer', 'metadata' => ['icon' => 'user-plus', 'color' => '#32CD32', 'tier' => 0, 'min_visits' => 0]],
            
            // Demographics
            ['name' => 'Student', 'metadata' => ['icon' => 'graduation-cap', 'color' => '#4682B4', 'demographic' => true]],
            ['name' => 'Senior Citizen', 'metadata' => ['icon' => 'user-clock', 'color' => '#D3D3D3', 'demographic' => true]],
            ['name' => 'Family', 'metadata' => ['icon' => 'users', 'color' => '#FF69B4', 'demographic' => true]],
            ['name' => 'Young Professional', 'metadata' => ['icon' => 'briefcase', 'color' => '#708090', 'demographic' => true]],
            ['name' => 'Tourist', 'metadata' => ['icon' => 'plane', 'color' => '#00CED1', 'demographic' => true]],
            ['name' => 'Local Resident', 'metadata' => ['icon' => 'home', 'color' => '#228B22', 'demographic' => true]],
            
            // Business Types
            ['name' => 'Corporate', 'metadata' => ['icon' => 'building', 'color' => '#708090', 'business' => true]],
            ['name' => 'Small Business', 'metadata' => ['icon' => 'store', 'color' => '#DAA520', 'business' => true]],
            ['name' => 'Government', 'metadata' => ['icon' => 'landmark', 'color' => '#4B0082', 'business' => true]],
            ['name' => 'Non-Profit', 'metadata' => ['icon' => 'hand-holding-heart', 'color' => '#FF1493', 'business' => true]],
            ['name' => 'Educational Institution', 'metadata' => ['icon' => 'school', 'color' => '#8B4513', 'business' => true]],
            
            // Behavior-Based
            ['name' => 'Frequent Diner', 'metadata' => ['icon' => 'utensils', 'color' => '#FF6347', 'behavior' => 'frequent']],
            ['name' => 'Weekend Warrior', 'metadata' => ['icon' => 'calendar-week', 'color' => '#4169E1', 'behavior' => 'weekend']],
            ['name' => 'Lunch Regular', 'metadata' => ['icon' => 'sun', 'color' => '#FFD700', 'behavior' => 'lunch']],
            ['name' => 'Early Bird', 'metadata' => ['icon' => 'sunrise', 'color' => '#FDB813', 'behavior' => 'early']],
            ['name' => 'Night Owl', 'metadata' => ['icon' => 'moon', 'color' => '#191970', 'behavior' => 'late']],
            ['name' => 'Takeout Preferred', 'metadata' => ['icon' => 'box', 'color' => '#CD853F', 'behavior' => 'takeout']],
            ['name' => 'Delivery Only', 'metadata' => ['icon' => 'truck', 'color' => '#32CD32', 'behavior' => 'delivery']],
            ['name' => 'Dine-In Preferred', 'metadata' => ['icon' => 'chair', 'color' => '#8B4513', 'behavior' => 'dine-in']],
            
            // Special Groups
            ['name' => 'Birthday Club', 'metadata' => ['icon' => 'cake', 'color' => '#FF69B4', 'special' => true]],
            ['name' => 'Wine Club', 'metadata' => ['icon' => 'wine-glass', 'color' => '#722F37', 'special' => true]],
            ['name' => 'Chef\'s Table', 'metadata' => ['icon' => 'chef-hat', 'color' => '#8B4513', 'special' => true]],
            ['name' => 'Influencer', 'metadata' => ['icon' => 'camera', 'color' => '#FF1493', 'special' => true]],
            ['name' => 'Food Critic', 'metadata' => ['icon' => 'pen', 'color' => '#4B0082', 'special' => true]],
            ['name' => 'Event Host', 'metadata' => ['icon' => 'calendar', 'color' => '#FFD700', 'special' => true]]
        ];
        
        foreach ($segments as $index => $segment) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($segment['name']),
                    'type' => TaxonomyType::CUSTOMER_SEGMENT->value,
                ],
                [
                    'name' => $segment['name'],
                    'metadata' => $segment['metadata'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function createGeneralTags(): void
    {
        $tags = [
            // Feature Tags
            ['name' => 'Featured', 'metadata' => ['icon' => 'star', 'color' => '#FFD700', 'priority' => 10]],
            ['name' => 'Editor\'s Choice', 'metadata' => ['icon' => 'award', 'color' => '#4169E1', 'priority' => 9]],
            ['name' => 'Staff Pick', 'metadata' => ['icon' => 'thumbs-up', 'color' => '#32CD32', 'priority' => 8]],
            ['name' => 'Customer Favorite', 'metadata' => ['icon' => 'heart', 'color' => '#FF1493', 'priority' => 8]],
            ['name' => 'Best Seller', 'metadata' => ['icon' => 'trophy', 'color' => '#FFD700', 'priority' => 9]],
            ['name' => 'Most Popular', 'metadata' => ['icon' => 'fire', 'color' => '#FF6347', 'priority' => 8]],
            ['name' => 'Trending', 'metadata' => ['icon' => 'chart-line', 'color' => '#FF4500', 'priority' => 7]],
            
            // Status Tags
            ['name' => 'New', 'metadata' => ['icon' => 'sparkles', 'color' => '#32CD32', 'priority' => 9]],
            ['name' => 'Coming Soon', 'metadata' => ['icon' => 'clock', 'color' => '#4169E1', 'priority' => 6]],
            ['name' => 'Back in Stock', 'metadata' => ['icon' => 'box', 'color' => '#90EE90', 'priority' => 7]],
            ['name' => 'Limited Quantity', 'metadata' => ['icon' => 'exclamation', 'color' => '#FFA500', 'priority' => 8]],
            ['name' => 'Last Chance', 'metadata' => ['icon' => 'hourglass-end', 'color' => '#FF4500', 'priority' => 9]],
            ['name' => 'Sold Out', 'metadata' => ['icon' => 'times-circle', 'color' => '#DC143C', 'priority' => 5]],
            ['name' => 'Pre-Order', 'metadata' => ['icon' => 'calendar-plus', 'color' => '#9370DB', 'priority' => 6]],
            
            // Quality Tags
            ['name' => 'Premium', 'metadata' => ['icon' => 'gem', 'color' => '#4B0082', 'priority' => 8]],
            ['name' => 'Artisan', 'metadata' => ['icon' => 'hands', 'color' => '#8B4513', 'priority' => 7]],
            ['name' => 'Handmade', 'metadata' => ['icon' => 'hand-paper', 'color' => '#D2691E', 'priority' => 7]],
            ['name' => 'Award Winning', 'metadata' => ['icon' => 'medal', 'color' => '#FFD700', 'priority' => 9]],
            ['name' => 'Signature', 'metadata' => ['icon' => 'signature', 'color' => '#4B0082', 'priority' => 8]],
            ['name' => 'House Special', 'metadata' => ['icon' => 'home', 'color' => '#228B22', 'priority' => 7]],
            ['name' => 'Chef\'s Special', 'metadata' => ['icon' => 'chef-hat', 'color' => '#8B4513', 'priority' => 8]],
            ['name' => 'Gourmet', 'metadata' => ['icon' => 'utensils', 'color' => '#DAA520', 'priority' => 7]],
            
            // Promotional Tags
            ['name' => 'On Sale', 'metadata' => ['icon' => 'tag', 'color' => '#DC143C', 'priority' => 9]],
            ['name' => 'Clearance', 'metadata' => ['icon' => 'percentage', 'color' => '#FF4500', 'priority' => 8]],
            ['name' => 'Deal of the Day', 'metadata' => ['icon' => 'calendar-day', 'color' => '#FFD700', 'priority' => 10]],
            ['name' => 'Bundle Deal', 'metadata' => ['icon' => 'boxes', 'color' => '#9370DB', 'priority' => 7]],
            ['name' => 'Value Pack', 'metadata' => ['icon' => 'box-open', 'color' => '#32CD32', 'priority' => 6]],
            ['name' => 'Buy 2 Get 1', 'metadata' => ['icon' => 'tags', 'color' => '#FF69B4', 'priority' => 8]],
            
            // Dietary/Health Tags
            ['name' => 'Healthy Choice', 'metadata' => ['icon' => 'apple', 'color' => '#90EE90', 'priority' => 7]],
            ['name' => 'Low Calorie', 'metadata' => ['icon' => 'weight', 'color' => '#87CEEB', 'priority' => 6]],
            ['name' => 'Superfood', 'metadata' => ['icon' => 'bolt', 'color' => '#32CD32', 'priority' => 7]],
            ['name' => 'Comfort Food', 'metadata' => ['icon' => 'couch', 'color' => '#DEB887', 'priority' => 6]],
            ['name' => 'Kids Friendly', 'metadata' => ['icon' => 'child', 'color' => '#87CEEB', 'priority' => 7]],
            
            // Source/Origin Tags
            ['name' => 'Local', 'metadata' => ['icon' => 'map-pin', 'color' => '#228B22', 'priority' => 7]],
            ['name' => 'Imported', 'metadata' => ['icon' => 'globe', 'color' => '#4169E1', 'priority' => 6]],
            ['name' => 'Farm Fresh', 'metadata' => ['icon' => 'tractor', 'color' => '#8B4513', 'priority' => 7]],
            ['name' => 'Sustainable', 'metadata' => ['icon' => 'leaf', 'color' => '#00FF00', 'priority' => 7]],
            ['name' => 'Fair Trade', 'metadata' => ['icon' => 'handshake', 'color' => '#4B7C2C', 'priority' => 6]],
            
            // Experience Tags
            ['name' => 'Must Try', 'metadata' => ['icon' => 'exclamation-circle', 'color' => '#FF6347', 'priority' => 9]],
            ['name' => 'Instagram Worthy', 'metadata' => ['icon' => 'camera', 'color' => '#E1306C', 'priority' => 7]],
            ['name' => 'Shareable', 'metadata' => ['icon' => 'share', 'color' => '#00CED1', 'priority' => 6]],
            ['name' => 'Date Night', 'metadata' => ['icon' => 'heart', 'color' => '#FF69B4', 'priority' => 6]],
            ['name' => 'Group Friendly', 'metadata' => ['icon' => 'users', 'color' => '#4169E1', 'priority' => 6]]
        ];
        
        foreach ($tags as $index => $tag) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($tag['name']),
                    'type' => TaxonomyType::GENERAL_TAG->value,
                ],
                [
                    'name' => $tag['name'],
                    'metadata' => $tag['metadata'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function createSeasonalTags(): void
    {
        $tags = [
            // Seasons
            ['name' => 'Spring Menu', 'metadata' => ['icon' => 'flower', 'color' => '#FFB6C1', 'season' => 'spring']],
            ['name' => 'Summer Special', 'metadata' => ['icon' => 'sun', 'color' => '#FFD700', 'season' => 'summer']],
            ['name' => 'Fall Flavors', 'metadata' => ['icon' => 'leaf', 'color' => '#FF8C00', 'season' => 'fall']],
            ['name' => 'Winter Warmers', 'metadata' => ['icon' => 'snowflake', 'color' => '#87CEEB', 'season' => 'winter']],
            
            // Holidays - International
            ['name' => 'New Year\'s Eve', 'metadata' => ['icon' => 'champagne-glasses', 'color' => '#FFD700', 'month' => 12]],
            ['name' => 'Valentine\'s Day', 'metadata' => ['icon' => 'heart', 'color' => '#FF1493', 'month' => 2]],
            ['name' => 'Easter', 'metadata' => ['icon' => 'egg', 'color' => '#FFB6C1', 'month' => 4]],
            ['name' => 'Mother\'s Day', 'metadata' => ['icon' => 'flower-tulip', 'color' => '#FF69B4', 'month' => 5]],
            ['name' => 'Father\'s Day', 'metadata' => ['icon' => 'tie', 'color' => '#4169E1', 'month' => 6]],
            ['name' => 'Halloween', 'metadata' => ['icon' => 'ghost', 'color' => '#FF8C00', 'month' => 10]],
            ['name' => 'Thanksgiving', 'metadata' => ['icon' => 'turkey', 'color' => '#8B4513', 'month' => 11]],
            ['name' => 'Christmas', 'metadata' => ['icon' => 'tree-christmas', 'color' => '#228B22', 'month' => 12]],
            
            // Chilean Holidays
            ['name' => 'Fiestas Patrias', 'metadata' => ['icon' => 'flag', 'color' => '#D52B1E', 'month' => 9]],
            ['name' => 'Día del Trabajador', 'metadata' => ['icon' => 'hammer', 'color' => '#FF0000', 'month' => 5]],
            ['name' => 'Día de la Madre', 'metadata' => ['icon' => 'heart', 'color' => '#FF69B4', 'month' => 5]],
            ['name' => 'Día del Padre', 'metadata' => ['icon' => 'user-tie', 'color' => '#4169E1', 'month' => 6]],
            ['name' => 'Navidad', 'metadata' => ['icon' => 'star', 'color' => '#FFD700', 'month' => 12]],
            ['name' => 'Año Nuevo', 'metadata' => ['icon' => 'fireworks', 'color' => '#FF4500', 'month' => 1]],
            
            // Events & Occasions
            ['name' => 'World Cup', 'metadata' => ['icon' => 'futbol', 'color' => '#228B22', 'event' => 'sports']],
            ['name' => 'Olympics', 'metadata' => ['icon' => 'medal', 'color' => '#FFD700', 'event' => 'sports']],
            ['name' => 'Back to School', 'metadata' => ['icon' => 'backpack', 'color' => '#4682B4', 'month' => 3]],
            ['name' => 'Graduation Season', 'metadata' => ['icon' => 'graduation-cap', 'color' => '#000000', 'month' => 12]],
            ['name' => 'Wedding Season', 'metadata' => ['icon' => 'rings', 'color' => '#FFB6C1', 'season' => 'summer']],
            ['name' => 'Festival Season', 'metadata' => ['icon' => 'music', 'color' => '#9370DB', 'season' => 'summer']],
            
            // Monthly Themes
            ['name' => 'January Detox', 'metadata' => ['icon' => 'leaf', 'color' => '#90EE90', 'month' => 1]],
            ['name' => 'February Love', 'metadata' => ['icon' => 'heart', 'color' => '#FF1493', 'month' => 2]],
            ['name' => 'March Madness', 'metadata' => ['icon' => 'basketball', 'color' => '#FF8C00', 'month' => 3]],
            ['name' => 'April Showers', 'metadata' => ['icon' => 'cloud-rain', 'color' => '#87CEEB', 'month' => 4]],
            ['name' => 'May Flowers', 'metadata' => ['icon' => 'flower', 'color' => '#FFB6C1', 'month' => 5]],
            ['name' => 'June Sunshine', 'metadata' => ['icon' => 'sun', 'color' => '#FFD700', 'month' => 6]],
            ['name' => 'July BBQ', 'metadata' => ['icon' => 'grill', 'color' => '#8B4513', 'month' => 7]],
            ['name' => 'August Heat', 'metadata' => ['icon' => 'temperature-high', 'color' => '#FF4500', 'month' => 8]],
            ['name' => 'September Harvest', 'metadata' => ['icon' => 'wheat', 'color' => '#DAA520', 'month' => 9]],
            ['name' => 'October Fest', 'metadata' => ['icon' => 'beer', 'color' => '#D2691E', 'month' => 10]],
            ['name' => 'November Thanks', 'metadata' => ['icon' => 'pray', 'color' => '#8B4513', 'month' => 11]],
            ['name' => 'December Holidays', 'metadata' => ['icon' => 'gifts', 'color' => '#DC143C', 'month' => 12]],
            
            // Weather-Based
            ['name' => 'Rainy Day', 'metadata' => ['icon' => 'umbrella', 'color' => '#4682B4', 'weather' => 'rain']],
            ['name' => 'Sunny Day', 'metadata' => ['icon' => 'sun', 'color' => '#FFD700', 'weather' => 'sun']],
            ['name' => 'Cold Weather', 'metadata' => ['icon' => 'thermometer-empty', 'color' => '#87CEEB', 'weather' => 'cold']],
            ['name' => 'Hot Weather', 'metadata' => ['icon' => 'thermometer-full', 'color' => '#FF4500', 'weather' => 'hot']]
        ];
        
        foreach ($tags as $index => $tag) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($tag['name']),
                    'type' => TaxonomyType::SEASONAL_TAG->value,
                ],
                [
                    'name' => $tag['name'],
                    'metadata' => $tag['metadata'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function createFeatureTags(): void
    {
        $tags = [
            // Display Features
            ['name' => 'Homepage Banner', 'metadata' => ['icon' => 'image', 'color' => '#FFD700', 'placement' => 'homepage', 'priority' => 10]],
            ['name' => 'Carousel Feature', 'metadata' => ['icon' => 'images', 'color' => '#4169E1', 'placement' => 'carousel', 'priority' => 9]],
            ['name' => 'Sidebar Highlight', 'metadata' => ['icon' => 'sidebar', 'color' => '#32CD32', 'placement' => 'sidebar', 'priority' => 7]],
            ['name' => 'Top of Category', 'metadata' => ['icon' => 'arrow-up', 'color' => '#FF6347', 'placement' => 'category_top', 'priority' => 8]],
            ['name' => 'Search Priority', 'metadata' => ['icon' => 'search', 'color' => '#9370DB', 'placement' => 'search', 'priority' => 8]],
            
            // Marketing Features
            ['name' => 'Email Newsletter', 'metadata' => ['icon' => 'envelope', 'color' => '#4169E1', 'channel' => 'email', 'priority' => 7]],
            ['name' => 'Social Media', 'metadata' => ['icon' => 'share-alt', 'color' => '#1DA1F2', 'channel' => 'social', 'priority' => 8]],
            ['name' => 'Push Notification', 'metadata' => ['icon' => 'bell', 'color' => '#FF4500', 'channel' => 'push', 'priority' => 9]],
            ['name' => 'SMS Campaign', 'metadata' => ['icon' => 'mobile', 'color' => '#32CD32', 'channel' => 'sms', 'priority' => 7]],
            ['name' => 'App Feature', 'metadata' => ['icon' => 'mobile-alt', 'color' => '#4169E1', 'channel' => 'app', 'priority' => 9]],
            
            // Recommendation Features
            ['name' => 'AI Recommended', 'metadata' => ['icon' => 'robot', 'color' => '#00CED1', 'algorithm' => 'ai', 'priority' => 8]],
            ['name' => 'Personalized', 'metadata' => ['icon' => 'user-cog', 'color' => '#FF69B4', 'algorithm' => 'personalized', 'priority' => 9]],
            ['name' => 'Similar Items', 'metadata' => ['icon' => 'clone', 'color' => '#4169E1', 'algorithm' => 'similarity', 'priority' => 6]],
            ['name' => 'Frequently Bought Together', 'metadata' => ['icon' => 'shopping-cart', 'color' => '#32CD32', 'algorithm' => 'association', 'priority' => 7]],
            ['name' => 'You May Also Like', 'metadata' => ['icon' => 'thumbs-up', 'color' => '#FFD700', 'algorithm' => 'collaborative', 'priority' => 6]],
            
            // Urgency Features
            ['name' => 'Flash Feature', 'metadata' => ['icon' => 'bolt', 'color' => '#FFD700', 'duration' => '1_hour', 'priority' => 10]],
            ['name' => 'Today Only', 'metadata' => ['icon' => 'calendar-day', 'color' => '#FF4500', 'duration' => '24_hours', 'priority' => 9]],
            ['name' => 'Weekend Feature', 'metadata' => ['icon' => 'calendar-week', 'color' => '#4169E1', 'duration' => 'weekend', 'priority' => 8]],
            ['name' => 'Week Long', 'metadata' => ['icon' => 'calendar-alt', 'color' => '#32CD32', 'duration' => 'week', 'priority' => 7]],
            ['name' => 'Monthly Feature', 'metadata' => ['icon' => 'calendar', 'color' => '#9370DB', 'duration' => 'month', 'priority' => 6]],
            
            // Review Features
            ['name' => '5 Star Featured', 'metadata' => ['icon' => 'star', 'color' => '#FFD700', 'rating' => 5, 'priority' => 9]],
            ['name' => 'Critics Choice', 'metadata' => ['icon' => 'award', 'color' => '#4B0082', 'source' => 'critics', 'priority' => 9]],
            ['name' => 'User Choice', 'metadata' => ['icon' => 'users', 'color' => '#32CD32', 'source' => 'users', 'priority' => 8]],
            ['name' => 'Media Feature', 'metadata' => ['icon' => 'newspaper', 'color' => '#708090', 'source' => 'media', 'priority' => 8]],
            ['name' => 'Influencer Pick', 'metadata' => ['icon' => 'star', 'color' => '#FF1493', 'source' => 'influencer', 'priority' => 7]],
            
            // Collection Features
            ['name' => 'Summer Collection', 'metadata' => ['icon' => 'sun', 'color' => '#FFD700', 'collection' => 'summer', 'priority' => 8]],
            ['name' => 'Winter Collection', 'metadata' => ['icon' => 'snowflake', 'color' => '#87CEEB', 'collection' => 'winter', 'priority' => 8]],
            ['name' => 'Holiday Collection', 'metadata' => ['icon' => 'gift', 'color' => '#DC143C', 'collection' => 'holiday', 'priority' => 9]],
            ['name' => 'Limited Edition', 'metadata' => ['icon' => 'gem', 'color' => '#4B0082', 'collection' => 'limited', 'priority' => 9]],
            ['name' => 'Exclusive Collection', 'metadata' => ['icon' => 'lock', 'color' => '#FFD700', 'collection' => 'exclusive', 'priority' => 10]],
            
            // Special Features
            ['name' => 'Live Demo', 'metadata' => ['icon' => 'video', 'color' => '#FF0000', 'special' => 'demo', 'priority' => 8]],
            ['name' => 'Virtual Tour', 'metadata' => ['icon' => 'vr-cardboard', 'color' => '#4169E1', 'special' => 'vr', 'priority' => 7]],
            ['name' => '360 View', 'metadata' => ['icon' => 'panorama', 'color' => '#00CED1', 'special' => '360', 'priority' => 7]],
            ['name' => 'AR Experience', 'metadata' => ['icon' => 'cube', 'color' => '#9370DB', 'special' => 'ar', 'priority' => 8]],
            ['name' => 'Interactive', 'metadata' => ['icon' => 'hand-pointer', 'color' => '#32CD32', 'special' => 'interactive', 'priority' => 7]]
        ];
        
        foreach ($tags as $index => $tag) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($tag['name']),
                    'type' => TaxonomyType::FEATURE_TAG->value,
                ],
                [
                    'name' => $tag['name'],
                    'metadata' => $tag['metadata'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function createHierarchy(array $items, TaxonomyType $type, ?int $parentId = null, int $sortOrder = 0): void
    {
        foreach ($items as $name => $data) {
            if (is_string($data)) {
                // Simple string item
                $slug = Str::slug($data);
                Taxonomy::firstOrCreate(
                    [
                        'slug' => $slug,
                        'type' => $type->value,
                    ],
                    [
                        'name' => $data,
                        'parent_id' => $parentId,
                        'sort_order' => $sortOrder++,
                        'is_active' => true,
                    ]
                );
            } else {
                // Complex item with metadata and/or children
                $itemName = is_numeric($name) ? $data['name'] ?? $data : $name;
                $slug = Str::slug($itemName);
                
                $taxonomy = Taxonomy::firstOrCreate(
                    [
                        'slug' => $slug,
                        'type' => $type->value,
                    ],
                    [
                        'name' => $itemName,
                        'parent_id' => $parentId,
                        'metadata' => $data['metadata'] ?? null,
                        'sort_order' => $sortOrder++,
                        'is_active' => true,
                    ]
                );
                
                if (isset($data['children'])) {
                    $this->createHierarchy($data['children'], $type, $taxonomy->id);
                }
            }
        }
    }
}