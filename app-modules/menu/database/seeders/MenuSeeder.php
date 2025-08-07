<?php

declare(strict_types=1);

namespace Colame\Menu\Database\Seeders;

use Illuminate\Database\Seeder;
use Colame\Menu\Models\Menu;
use Colame\Menu\Models\MenuSection;
use Colame\Menu\Models\MenuItem;
use Colame\Menu\Models\MenuAvailabilityRule;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main restaurant menu
        $mainMenu = Menu::create([
            'name' => 'Main Menu',
            'slug' => 'main-menu',
            'description' => 'Our complete restaurant menu',
            'type' => 'regular',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ]);
        
        // Create breakfast menu
        $breakfastMenu = Menu::create([
            'name' => 'Breakfast Menu',
            'slug' => 'breakfast-menu',
            'description' => 'Start your day with our delicious breakfast options',
            'type' => 'breakfast',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        
        // Create lunch menu
        $lunchMenu = Menu::create([
            'name' => 'Lunch Menu',
            'slug' => 'lunch-menu',
            'description' => 'Midday specials and lunch combinations',
            'type' => 'lunch',
            'is_active' => true,
            'sort_order' => 3,
        ]);
        
        // Add sections to main menu
        $appetizers = MenuSection::create([
            'menu_id' => $mainMenu->id,
            'name' => 'Appetizers',
            'slug' => 'appetizers',
            'description' => 'Start your meal with our delicious appetizers',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        $mainCourses = MenuSection::create([
            'menu_id' => $mainMenu->id,
            'name' => 'Main Courses',
            'slug' => 'main-courses',
            'description' => 'Our signature main dishes',
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 2,
        ]);
        
        $desserts = MenuSection::create([
            'menu_id' => $mainMenu->id,
            'name' => 'Desserts',
            'slug' => 'desserts',
            'description' => 'Sweet endings to your meal',
            'is_active' => true,
            'sort_order' => 3,
        ]);
        
        $beverages = MenuSection::create([
            'menu_id' => $mainMenu->id,
            'name' => 'Beverages',
            'slug' => 'beverages',
            'description' => 'Refreshing drinks',
            'is_active' => true,
            'sort_order' => 4,
        ]);
        
        // Add sub-sections to beverages
        $hotBeverages = MenuSection::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => $beverages->id,
            'name' => 'Hot Beverages',
            'slug' => 'hot-beverages',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        $coldBeverages = MenuSection::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => $beverages->id,
            'name' => 'Cold Beverages',
            'slug' => 'cold-beverages',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        
        // Add sample menu items (assuming items exist in item module)
        // In a real scenario, these would reference actual items
        for ($i = 1; $i <= 3; $i++) {
            MenuItem::create([
                'menu_id' => $mainMenu->id,
                'menu_section_id' => $appetizers->id,
                'item_id' => $i,
                'display_name' => "Appetizer {$i}",
                'display_description' => "Delicious appetizer option {$i}",
                'is_active' => true,
                'is_featured' => $i === 1,
                'sort_order' => $i,
                'dietary_labels' => $i === 2 ? ['vegetarian', 'gluten_free'] : null,
                'calorie_count' => rand(200, 500),
            ]);
        }
        
        for ($i = 4; $i <= 8; $i++) {
            MenuItem::create([
                'menu_id' => $mainMenu->id,
                'menu_section_id' => $mainCourses->id,
                'item_id' => $i,
                'display_name' => "Main Course " . ($i - 3),
                'display_description' => "Signature main dish " . ($i - 3),
                'is_active' => true,
                'is_recommended' => $i === 5,
                'is_new' => $i === 7,
                'sort_order' => $i - 3,
                'preparation_time_override' => rand(15, 30),
                'dietary_labels' => $i === 6 ? ['vegan', 'organic'] : null,
                'calorie_count' => rand(500, 1200),
            ]);
        }
        
        // Add availability rules for breakfast menu
        MenuAvailabilityRule::create([
            'menu_id' => $breakfastMenu->id,
            'rule_type' => 'time_based',
            'start_time' => '06:00',
            'end_time' => '11:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'priority' => 1,
        ]);
        
        // Add availability rules for lunch menu
        MenuAvailabilityRule::create([
            'menu_id' => $lunchMenu->id,
            'rule_type' => 'time_based',
            'start_time' => '11:00',
            'end_time' => '15:00',
            'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'priority' => 1,
        ]);
        
        // Weekend lunch hours
        MenuAvailabilityRule::create([
            'menu_id' => $lunchMenu->id,
            'rule_type' => 'time_based',
            'start_time' => '12:00',
            'end_time' => '16:00',
            'days_of_week' => ['saturday', 'sunday'],
            'priority' => 1,
        ]);
        
        $this->command->info('Menu module seeded successfully!');
    }
}