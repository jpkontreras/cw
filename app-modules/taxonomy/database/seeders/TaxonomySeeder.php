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
        // Item Categories (Hierarchical)
        $this->createItemCategories();
        
        // Menu Sections
        $this->createMenuSections();
        
        // Dietary Labels
        $this->createDietaryLabels();
        
        // Allergens
        $this->createAllergens();
        
        // Cuisine Types
        $this->createCuisineTypes();
        
        // Preparation Methods
        $this->createPreparationMethods();
        
        // Spice Levels
        $this->createSpiceLevels();
        
        // Price Ranges
        $this->createPriceRanges();
        
        // Customer Segments
        $this->createCustomerSegments();
        
        // General Tags
        $this->createGeneralTags();
    }
    
    private function createItemCategories(): void
    {
        $categories = [
            'Beverages' => [
                'metadata' => ['icon' => 'coffee', 'color' => '#8B4513'],
                'children' => [
                    'Hot Drinks' => [
                        'children' => ['Coffee', 'Tea', 'Hot Chocolate']
                    ],
                    'Cold Drinks' => [
                        'children' => ['Soft Drinks', 'Juices', 'Smoothies', 'Iced Coffee', 'Iced Tea']
                    ],
                    'Alcoholic' => [
                        'children' => ['Beer', 'Wine', 'Cocktails', 'Spirits']
                    ],
                ],
            ],
            'Food' => [
                'metadata' => ['icon' => 'utensils', 'color' => '#FF6B6B'],
                'children' => [
                    'Appetizers' => [
                        'children' => ['Soups', 'Salads', 'Starters', 'Bread']
                    ],
                    'Main Courses' => [
                        'children' => ['Meat', 'Poultry', 'Seafood', 'Vegetarian Mains', 'Pasta', 'Rice Dishes']
                    ],
                    'Desserts' => [
                        'children' => ['Cakes', 'Ice Cream', 'Pastries', 'Fruit']
                    ],
                    'Sides' => [
                        'children' => ['Fries', 'Vegetables', 'Rice', 'Beans']
                    ],
                ],
            ],
            'Snacks' => [
                'metadata' => ['icon' => 'cookie', 'color' => '#FFA500'],
                'children' => [
                    'Sweet' => ['Cookies', 'Candy', 'Chocolate'],
                    'Savory' => ['Chips', 'Nuts', 'Popcorn'],
                ],
            ],
        ];
        
        $this->createHierarchy($categories, TaxonomyType::ITEM_CATEGORY);
    }
    
    private function createMenuSections(): void
    {
        $sections = [
            ['name' => 'Breakfast', 'metadata' => ['icon' => 'sun', 'color' => '#FFD700']],
            ['name' => 'Lunch', 'metadata' => ['icon' => 'sun', 'color' => '#FFA500']],
            ['name' => 'Dinner', 'metadata' => ['icon' => 'moon', 'color' => '#4B0082']],
            ['name' => 'All Day', 'metadata' => ['icon' => 'clock', 'color' => '#228B22']],
            ['name' => 'Specials', 'metadata' => ['icon' => 'star', 'color' => '#FF1493', 'featured' => true]],
            ['name' => 'Kids Menu', 'metadata' => ['icon' => 'child', 'color' => '#87CEEB']],
            ['name' => 'Happy Hour', 'metadata' => ['icon' => 'glass-cheers', 'color' => '#FF69B4']],
        ];
        
        foreach ($sections as $index => $section) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($section['name']),
                    'type' => TaxonomyType::MENU_SECTION->value,
                ],
                [
                    'name' => $section['name'],
                    'metadata' => $section['metadata'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function createDietaryLabels(): void
    {
        $labels = [
            ['name' => 'Vegan', 'metadata' => ['icon' => 'leaf', 'color' => '#228B22', 'description' => 'Contains no animal products']],
            ['name' => 'Vegetarian', 'metadata' => ['icon' => 'carrot', 'color' => '#32CD32', 'description' => 'Contains no meat or fish']],
            ['name' => 'Gluten Free', 'metadata' => ['icon' => 'wheat', 'color' => '#D2691E', 'description' => 'Contains no gluten']],
            ['name' => 'Dairy Free', 'metadata' => ['icon' => 'milk', 'color' => '#F0E68C', 'description' => 'Contains no dairy products']],
            ['name' => 'Nut Free', 'metadata' => ['icon' => 'tree', 'color' => '#8B4513', 'description' => 'Contains no nuts']],
            ['name' => 'Halal', 'metadata' => ['icon' => 'certificate', 'color' => '#006400', 'description' => 'Prepared according to Islamic law']],
            ['name' => 'Kosher', 'metadata' => ['icon' => 'star-david', 'color' => '#4169E1', 'description' => 'Prepared according to Jewish dietary laws']],
            ['name' => 'Organic', 'metadata' => ['icon' => 'seedling', 'color' => '#90EE90', 'description' => 'Made with organic ingredients']],
            ['name' => 'Low Carb', 'metadata' => ['icon' => 'chart-line', 'color' => '#FF6347', 'description' => 'Low in carbohydrates']],
            ['name' => 'Keto', 'metadata' => ['icon' => 'fire', 'color' => '#FF4500', 'description' => 'Ketogenic diet friendly']],
            ['name' => 'Paleo', 'metadata' => ['icon' => 'bone', 'color' => '#8B4513', 'description' => 'Paleolithic diet friendly']],
            ['name' => 'Sugar Free', 'metadata' => ['icon' => 'candy-cane', 'color' => '#FF69B4', 'description' => 'Contains no added sugar']],
            ['name' => 'Low Sodium', 'metadata' => ['icon' => 'salt', 'color' => '#B0C4DE', 'description' => 'Low in sodium']],
            ['name' => 'High Protein', 'metadata' => ['icon' => 'dumbbell', 'color' => '#DC143C', 'description' => 'High in protein']],
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
            ['name' => 'Contains Milk', 'metadata' => ['icon' => 'cow', 'color' => '#F5F5DC']],
            ['name' => 'Contains Eggs', 'metadata' => ['icon' => 'egg', 'color' => '#FFFACD']],
            ['name' => 'Contains Fish', 'metadata' => ['icon' => 'fish', 'color' => '#4682B4']],
            ['name' => 'Contains Shellfish', 'metadata' => ['icon' => 'shrimp', 'color' => '#FF7F50']],
            ['name' => 'Contains Tree Nuts', 'metadata' => ['icon' => 'tree', 'color' => '#8B4513']],
            ['name' => 'Contains Peanuts', 'metadata' => ['icon' => 'nut', 'color' => '#D2691E']],
            ['name' => 'Contains Wheat', 'metadata' => ['icon' => 'wheat', 'color' => '#F5DEB3']],
            ['name' => 'Contains Soy', 'metadata' => ['icon' => 'seedling', 'color' => '#8FBC8F']],
            ['name' => 'Contains Sesame', 'metadata' => ['icon' => 'seed', 'color' => '#F4A460']],
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
            'Chilean', 'American', 'Italian', 'Mexican', 'Chinese', 
            'Japanese', 'Thai', 'Indian', 'Mediterranean', 'French',
            'Spanish', 'Greek', 'Vietnamese', 'Korean', 'Fusion',
        ];
        
        foreach ($cuisines as $index => $cuisine) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($cuisine),
                    'type' => TaxonomyType::CUISINE_TYPE->value,
                ],
                [
                    'name' => $cuisine,
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
    
    private function createPreparationMethods(): void
    {
        $methods = [
            ['name' => 'Grilled', 'metadata' => ['icon' => 'grill', 'color' => '#8B4513']],
            ['name' => 'Fried', 'metadata' => ['icon' => 'fire', 'color' => '#FF6347']],
            ['name' => 'Baked', 'metadata' => ['icon' => 'oven', 'color' => '#D2691E']],
            ['name' => 'Steamed', 'metadata' => ['icon' => 'cloud', 'color' => '#87CEEB']],
            ['name' => 'Raw', 'metadata' => ['icon' => 'leaf', 'color' => '#90EE90']],
            ['name' => 'Roasted', 'metadata' => ['icon' => 'fire-alt', 'color' => '#A0522D']],
            ['name' => 'Boiled', 'metadata' => ['icon' => 'water', 'color' => '#4682B4']],
            ['name' => 'Sautéed', 'metadata' => ['icon' => 'pan', 'color' => '#DAA520']],
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
            ['name' => 'Mild', 'metadata' => ['icon' => 'pepper-hot', 'color' => '#90EE90', 'level' => 1]],
            ['name' => 'Medium', 'metadata' => ['icon' => 'pepper-hot', 'color' => '#FFD700', 'level' => 2]],
            ['name' => 'Hot', 'metadata' => ['icon' => 'pepper-hot', 'color' => '#FF8C00', 'level' => 3]],
            ['name' => 'Extra Hot', 'metadata' => ['icon' => 'fire', 'color' => '#FF4500', 'level' => 4]],
            ['name' => 'Extreme', 'metadata' => ['icon' => 'fire-flame', 'color' => '#DC143C', 'level' => 5]],
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
        }
    }
    
    private function createPriceRanges(): void
    {
        $ranges = [
            ['name' => 'Budget', 'metadata' => ['icon' => 'dollar-sign', 'color' => '#90EE90', 'min' => 0, 'max' => 10]],
            ['name' => 'Standard', 'metadata' => ['icon' => 'dollar-sign', 'color' => '#FFD700', 'min' => 10, 'max' => 25]],
            ['name' => 'Premium', 'metadata' => ['icon' => 'dollar-sign', 'color' => '#FF8C00', 'min' => 25, 'max' => 50]],
            ['name' => 'Luxury', 'metadata' => ['icon' => 'dollar-sign', 'color' => '#DC143C', 'min' => 50, 'max' => null]],
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
            $taxonomy->setTaxonomyAttribute('min_price', $range['metadata']['min'], 'number');
            if ($range['metadata']['max']) {
                $taxonomy->setTaxonomyAttribute('max_price', $range['metadata']['max'], 'number');
            }
        }
    }
    
    private function createCustomerSegments(): void
    {
        $segments = [
            ['name' => 'VIP', 'metadata' => ['icon' => 'crown', 'color' => '#FFD700']],
            ['name' => 'Regular', 'metadata' => ['icon' => 'user', 'color' => '#4169E1']],
            ['name' => 'New', 'metadata' => ['icon' => 'user-plus', 'color' => '#32CD32']],
            ['name' => 'Corporate', 'metadata' => ['icon' => 'building', 'color' => '#708090']],
            ['name' => 'Family', 'metadata' => ['icon' => 'users', 'color' => '#FF69B4']],
            ['name' => 'Student', 'metadata' => ['icon' => 'graduation-cap', 'color' => '#4682B4']],
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
            // Feature tags
            ['name' => 'Featured', 'metadata' => ['icon' => 'star', 'color' => '#FFD700']],
            ['name' => 'New', 'metadata' => ['icon' => 'sparkles', 'color' => '#32CD32']],
            ['name' => 'Popular', 'metadata' => ['icon' => 'fire', 'color' => '#FF6347']],
            ['name' => 'Best Seller', 'metadata' => ['icon' => 'trophy', 'color' => '#FFD700']],
            ['name' => 'Limited Time', 'metadata' => ['icon' => 'clock', 'color' => '#FF4500']],
            ['name' => 'Seasonal', 'metadata' => ['icon' => 'calendar', 'color' => '#FFA500']],
            ['name' => 'Chef Special', 'metadata' => ['icon' => 'chef-hat', 'color' => '#8B4513']],
            ['name' => 'Recommended', 'metadata' => ['icon' => 'thumbs-up', 'color' => '#4169E1']],
            ['name' => 'On Sale', 'metadata' => ['icon' => 'tag', 'color' => '#DC143C']],
            ['name' => 'Combo Deal', 'metadata' => ['icon' => 'box', 'color' => '#FF69B4']],
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