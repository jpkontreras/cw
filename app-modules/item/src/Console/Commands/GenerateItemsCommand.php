<?php

declare(strict_types=1);

namespace Colame\Item\Console\Commands;

use Colame\Item\Models\Item;
use Colame\Item\Models\ItemImage;
use Colame\Item\Models\ItemVariant;
use Colame\Item\Models\ItemLocationPrice;
use Colame\Item\Models\ItemCategory;
use Colame\Item\Models\ModifierGroup;
use Colame\Item\Models\ItemModifier;
use Colame\Item\Models\ItemModifierGroup;
use Colame\Item\Models\CompoundItem;
use Colame\Location\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class GenerateItemsCommand extends Command
{
    protected $signature = 'item:generate {count=10 : Number of items to generate}
                            {--type=mixed : Type of items (product|service|combo|mixed)}
                            {--category= : Specific category ID}
                            {--with-variants : Add size variants}
                            {--with-modifiers : Add modifier groups}
                            {--price-range=1000-25000 : Price range in CLP}
                            {--use-default-images : Use default media images}
                            {--locations= : Comma-separated location IDs or "all"}
                            {--location-price-variance=10 : Percentage variance for location pricing}';

    protected $description = 'Generate sample items with realistic Chilean restaurant data';

    // PRODUCTS - Physical items with inventory
    private array $productItems = [
        'fastfood' => [
            'Empanada de Pino' => 'Tradicional empanada chilena con carne, cebolla, huevo y aceitunas',
            'Empanada de Queso' => 'Empanada horneada rellena de queso derretido',
            'Completo Italiano' => 'Hot dog con palta, tomate y mayonesa casera',
            'Chacarero' => 'Sandwich con churrasco, porotos verdes, tomate y ají verde',
            'Barros Luco' => 'Sandwich de carne con queso derretido',
            'Churrasco Solo' => 'Sandwich de carne a la plancha',
            'Sopaipilla' => 'Masa frita de zapallo, perfecta con pebre',
            'Sopaipilla Pasada' => 'Sopaipilla bañada en chancaca',
        ],
        'main-dishes' => [
            'Pastel de Choclo' => 'Pastel de maíz con pino de carne y pollo',
            'Cazuela de Vacuno' => 'Sopa tradicional con carne, papas y verduras',
            'Porotos Granados' => 'Guiso de porotos con maíz, zapallo y albahaca',
            'Caldillo de Congrio' => 'Sopa de pescado congrio con verduras',
            'Charquicán' => 'Guiso de papas, zapallo, carne y verduras',
            'Pastel de Papas' => 'Capas de puré con pino de carne',
            'Lomo a lo Pobre' => 'Filete con huevos fritos, papas fritas y cebolla',
            'Reineta Frita' => 'Pescado reineta apanado con ensalada chilena',
        ],
        'beverages' => [
            'Pisco Sour' => 'Cóctel tradicional con pisco, limón y azúcar',
            'Terremoto' => 'Vino pipeño con helado de piña',
            'Mote con Huesillo' => 'Bebida refrescante con durazno deshidratado y trigo',
            'Cola de Mono' => 'Bebida navideña con pisco, leche y café',
            'Bebida Bilz' => 'Refresco tradicional chileno',
            'Bebida Pap' => 'Refresco de papaya',
            'Jugo Natural' => 'Jugo fresco del día',
            'Café Cortado' => 'Espresso con un toque de leche',
            'Coca-Cola 350ml' => 'Bebida gaseosa sabor cola',
            'Sprite 350ml' => 'Bebida gaseosa lima-limón',
            'Fanta 350ml' => 'Bebida gaseosa sabor naranja',
            'Agua Mineral 500ml' => 'Agua mineral sin gas',
            'Agua con Gas 500ml' => 'Agua mineral con gas',
        ],
        'desserts' => [
            'Leche Asada' => 'Postre de leche horneado con caramelo',
            'Tres Leches' => 'Bizcocho bañado en tres tipos de leche',
            'Pie de Limón' => 'Tarta de limón con merengue',
            'Kuchen de Frambuesa' => 'Tarta alemana con frambuesas',
            'Alfajor' => 'Galletas con manjar y coco rallado',
            'Brazo de Reina' => 'Rollo de bizcocho con manjar',
            'Calzones Rotos' => 'Masa frita espolvoreada con azúcar flor',
            'Chilenitos' => 'Canutos rellenos con manjar',
        ],
        'bakery' => [
            'Marraqueta' => 'Pan francés crujiente tradicional',
            'Hallulla' => 'Pan redondo suave perfecto para sandwiches',
            'Dobladitas' => 'Pan de masa doblada con manteca',
            'Pan Amasado' => 'Pan casero tradicional del campo',
            'Coliza' => 'Pan largo similar a la baguette',
            'Pan Integral' => 'Pan saludable con harina integral',
        ],
    ];

    // SERVICES - Non-physical services
    private array $serviceItems = [
        'Delivery Express' => 'Servicio de entrega a domicilio en 30 minutos',
        'Delivery Estándar' => 'Servicio de entrega a domicilio en 60 minutos',
        'Catering Pequeño' => 'Servicio de catering para 10-20 personas',
        'Catering Mediano' => 'Servicio de catering para 20-50 personas',
        'Catering Grande' => 'Servicio de catering para más de 50 personas',
        'Clase de Cocina Chilena' => 'Aprende a cocinar platos tradicionales (2 horas)',
        'Chef a Domicilio' => 'Chef privado para eventos especiales',
        'Decoración de Mesa' => 'Servicio de decoración para eventos',
        'Mesero Adicional' => 'Personal extra para eventos',
        'Arriendo de Salón' => 'Espacio privado para eventos (4 horas)',
    ];

    // COMBOS - Combinations of products
    private array $comboTemplates = [
        'Combo Completo' => ['description' => 'Completo + Papas Fritas + Bebida', 'items' => 3],
        'Combo Empanadas' => ['description' => '2 Empanadas + Bebida', 'items' => 2],
        'Menú del Día' => ['description' => 'Entrada + Plato Principal + Postre + Bebida', 'items' => 4],
        'Combo Familiar' => ['description' => '4 Platos principales + 4 Bebidas + 2 Postres', 'items' => 6],
        'Desayuno Completo' => ['description' => 'Café + Jugo + Pan con agregados', 'items' => 3],
        'Once Chilena' => ['description' => 'Té o Café + Pan + Agregados dulces y salados', 'items' => 4],
        'Combo Parrillada' => ['description' => 'Carne + Ensaladas + Pan + Bebida', 'items' => 4],
        'Promo Almuerzo' => ['description' => 'Plato del día + Ensalada + Bebida', 'items' => 3],
    ];

    private array $imagePaths = [];
    private array $categories = [];
    private array $locations = [];
    private $existingProducts;

    public function handle(): int
    {
        $this->info('🍽️ Generating Chilean restaurant items...');
        
        DB::beginTransaction();
        try {
            $this->loadImagePaths();
            $this->loadCategories();
            $this->loadLocations();
            $this->loadExistingProducts();
            
            $count = (int) $this->argument('count');
            $type = $this->option('type');
            
            $bar = $this->output->createProgressBar($count);
            $bar->start();
            
            for ($i = 0; $i < $count; $i++) {
                $this->generateItem($type);
                $bar->advance();
            }
            
            $bar->finish();
            DB::commit();
            
            $this->newLine();
            $this->info("✅ Successfully generated {$count} items!");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to generate items: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function loadImagePaths(): void
    {
        if (!$this->option('use-default-images')) {
            return;
        }

        $categories = ['fastfood', 'main-dishes', 'beverages', 'desserts', 'bakery'];
        
        foreach ($categories as $category) {
            $path = "public/foods/categorized/{$category}/medium";
            if (Storage::exists($path)) {
                $files = Storage::files($path);
                $this->imagePaths[$category] = array_map(function ($file) {
                    return str_replace('public/', '', $file);
                }, $files);
            }
        }
    }

    private function loadCategories(): void
    {
        // For now, we'll use simple category keys without database
        // When taxonomy module is ready, we can integrate properly
        $this->categories = [
            'main-dishes' => 1,
            'fastfood' => 2,
            'beverages' => 3,
            'desserts' => 4,
            'bakery' => 5,
            'services' => 6,
            'combos' => 7,
        ];
    }

    private function loadLocations(): void
    {
        $locationOption = $this->option('locations');
        
        if (!$locationOption) {
            return;
        }
        
        if ($locationOption === 'all') {
            $this->locations = Location::where('is_active', true)->pluck('id')->toArray();
        } else {
            $this->locations = array_map('intval', explode(',', $locationOption));
        }
    }

    private function loadExistingProducts(): void
    {
        // Load existing products for combo creation
        $this->existingProducts = Item::where('type', 'product')
            ->where('is_active', true)
            ->get();
        
        if ($this->existingProducts->isEmpty()) {
            $this->existingProducts = collect();
        }
    }

    private function generateItem(string $type): void
    {
        $itemType = $this->determineItemType($type);
        
        switch ($itemType) {
            case 'product':
                $this->generateProduct();
                break;
            case 'service':
                $this->generateService();
                break;
            case 'combo':
                $this->generateCombo();
                break;
        }
    }

    private function determineItemType(string $type): string
    {
        if ($type === 'mixed') {
            $types = ['product', 'service', 'combo'];
            // Weight towards products (60% products, 20% services, 20% combos)
            $weights = [60, 20, 20];
            $rand = rand(1, 100);
            
            if ($rand <= 60) return 'product';
            if ($rand <= 80) return 'service';
            return 'combo';
        }
        return $type;
    }

    private function generateProduct(): void
    {
        $categoryKey = $this->selectProductCategory();
        $items = $this->productItems[$categoryKey] ?? $this->productItems['beverages'];
        
        $name = array_rand($items);
        $description = $items[$name];
        $uniqueName = $name . ' #' . rand(1000, 9999);

        $item = Item::create([
            'name' => $uniqueName,
            'slug' => Str::slug($uniqueName),
            'description' => $description,
            'sku' => 'PRD-' . strtoupper(Str::random(8)),
            'barcode' => fake()->ean13(),
            'type' => 'product',
            'base_price' => $this->generatePrice($categoryKey),
            'base_cost' => $this->generateCost($this->generatePrice($categoryKey)),
            'preparation_time' => $this->getPreparationTime($categoryKey),
            'stock_quantity' => rand(20, 100),
            'track_inventory' => true,
            'low_stock_threshold' => rand(5, 20),
            'is_active' => true,
            'is_available' => true,
            'is_featured' => rand(0, 100) < 20,
            'sort_order' => rand(1, 100),
            'nutritional_info' => $this->generateNutritionalInfo($categoryKey),
            'allergens' => $this->generateAllergens($categoryKey),
        ]);

        $this->addImageToItem($item, $categoryKey);
        $this->addVariantsIfNeeded($item, $categoryKey);
        $this->addModifiersIfNeeded($item, $categoryKey);
        $this->addLocationPricing($item);
    }

    private function generateService(): void
    {
        $services = $this->serviceItems;
        $name = array_rand($services);
        $description = $services[$name];
        $uniqueName = $name . ' #' . rand(1000, 9999);

        $item = Item::create([
            'name' => $uniqueName,
            'slug' => Str::slug($uniqueName),
            'description' => $description,
            'sku' => 'SRV-' . strtoupper(Str::random(8)),
            'type' => 'service',
            'base_price' => $this->generateServicePrice($name),
            'base_cost' => 0, // Services typically don't have material cost
            'preparation_time' => $this->getServiceDuration($name),
            'stock_quantity' => null,
            'track_inventory' => false,
            'is_active' => true,
            'is_available' => true,
            'is_featured' => rand(0, 100) < 10,
            'sort_order' => rand(1, 100),
        ]);

        $this->addLocationPricing($item);
    }

    private function generateCombo(): void
    {
        // Need at least 2 products to create a combo
        if ($this->existingProducts->count() < 2) {
            $this->generateProduct(); // Fall back to creating a product
            return;
        }

        $templates = $this->comboTemplates;
        $templateName = array_rand($templates);
        $template = $templates[$templateName];
        $uniqueName = $templateName . ' #' . rand(1000, 9999);

        // Select random products for the combo
        $comboItems = $this->existingProducts->random(min($template['items'], $this->existingProducts->count()));
        
        // Calculate combo price (10-20% discount from sum)
        $totalPrice = $comboItems->sum('base_price');
        $discount = rand(10, 20) / 100;
        $comboPrice = round($totalPrice * (1 - $discount));

        $combo = Item::create([
            'name' => $uniqueName,
            'slug' => Str::slug($uniqueName),
            'description' => $template['description'],
            'sku' => 'CMB-' . strtoupper(Str::random(8)),
            'type' => 'combo',
            'base_price' => $comboPrice,
            'base_cost' => $comboItems->sum('base_cost'),
            'preparation_time' => $comboItems->max('preparation_time'),
            'stock_quantity' => null,
            'track_inventory' => false,
            'is_active' => true,
            'is_available' => true,
            'is_featured' => rand(0, 100) < 30, // Combos are often featured
            'sort_order' => rand(1, 50), // Combos usually appear first
        ]);

        // Link the combo items using CompoundItem
        foreach ($comboItems as $index => $childItem) {
            CompoundItem::create([
                'parent_item_id' => $combo->id,
                'child_item_id' => $childItem->id,
                'quantity' => 1,
                'is_required' => true,
                'allow_substitution' => $template['items'] > 2, // Allow substitution for larger combos
                'sort_order' => $index + 1,
            ]);
        }

        // Add a combo image if available
        if ($this->option('use-default-images') && isset($this->imagePaths['fastfood'])) {
            $imagePath = $this->imagePaths['fastfood'][array_rand($this->imagePaths['fastfood'])];
            ItemImage::create([
                'item_id' => $combo->id,
                'url' => Storage::url($imagePath),
                'path' => $imagePath,
                'is_primary' => true,
                'sort_order' => 1,
            ]);
        }

        $this->addLocationPricing($combo);
    }

    private function selectProductCategory(): string
    {
        if ($categoryId = $this->option('category')) {
            return array_search($categoryId, $this->categories) ?: 'beverages';
        }

        $categories = array_keys($this->productItems);
        return $categories[array_rand($categories)];
    }

    private function generatePrice(string $category): float
    {
        $ranges = [
            'beverages' => [1000, 5000],
            'bakery' => [500, 3000],
            'fastfood' => [2000, 8000],
            'main-dishes' => [5000, 15000],
            'desserts' => [2000, 6000],
        ];

        $range = $ranges[$category] ?? [1000, 10000];
        return round(rand($range[0], $range[1]) / 100) * 100;
    }

    private function generateServicePrice(string $serviceName): float
    {
        if (str_contains($serviceName, 'Delivery')) {
            return rand(2000, 5000);
        }
        if (str_contains($serviceName, 'Catering')) {
            return rand(50000, 500000);
        }
        if (str_contains($serviceName, 'Chef') || str_contains($serviceName, 'Clase')) {
            return rand(30000, 100000);
        }
        return rand(5000, 50000);
    }

    private function generateCost(float $price): float
    {
        $margin = rand(30, 60) / 100;
        return round($price * (1 - $margin));
    }

    private function getPreparationTime(string $category): int
    {
        $times = [
            'beverages' => rand(1, 5),
            'bakery' => 0, // Pre-made
            'fastfood' => rand(5, 15),
            'main-dishes' => rand(15, 45),
            'desserts' => rand(5, 10),
        ];

        return $times[$category] ?? 10;
    }

    private function getServiceDuration(string $serviceName): int
    {
        if (str_contains($serviceName, 'Delivery')) {
            return str_contains($serviceName, 'Express') ? 30 : 60;
        }
        if (str_contains($serviceName, 'Clase')) {
            return 120; // 2 hours
        }
        if (str_contains($serviceName, 'Arriendo')) {
            return 240; // 4 hours
        }
        return 0;
    }

    private function generateNutritionalInfo(string $category): array
    {
        $baseCalories = match($category) {
            'main-dishes' => rand(400, 800),
            'fastfood' => rand(300, 600),
            'desserts' => rand(200, 500),
            'beverages' => rand(50, 250),
            'bakery' => rand(150, 350),
            default => rand(100, 400),
        };

        return [
            'calories' => $baseCalories,
            'protein' => rand(5, 30) . 'g',
            'carbs' => rand(20, 60) . 'g',
            'fat' => rand(5, 25) . 'g',
            'sodium' => rand(200, 800) . 'mg',
        ];
    }

    private function generateAllergens(string $category): array
    {
        $allAllergens = ['gluten', 'lacteos', 'huevos', 'frutos_secos', 'soya', 'mariscos', 'pescado'];
        
        $categoryAllergens = match($category) {
            'bakery' => ['gluten', 'huevos', 'lacteos'],
            'desserts' => ['gluten', 'lacteos', 'huevos'],
            'main-dishes' => ['gluten'],
            default => [],
        };

        $count = rand(0, 2);
        for ($i = 0; $i < $count; $i++) {
            $categoryAllergens[] = $allAllergens[array_rand($allAllergens)];
        }

        return array_unique($categoryAllergens);
    }

    private function addImageToItem(Item $item, string $categoryKey): void
    {
        if ($this->option('use-default-images') && isset($this->imagePaths[$categoryKey])) {
            $imagePath = $this->imagePaths[$categoryKey][array_rand($this->imagePaths[$categoryKey])];
            ItemImage::create([
                'item_id' => $item->id,
                'url' => Storage::url($imagePath),
                'path' => $imagePath,
                'is_primary' => true,
                'sort_order' => 1,
            ]);
        }
    }

    private function addVariantsIfNeeded(Item $item, string $categoryKey): void
    {
        if (!$this->option('with-variants')) {
            return;
        }

        // Only add variants to certain categories
        if (!in_array($categoryKey, ['beverages', 'fastfood', 'main-dishes'])) {
            return;
        }

        $sizes = [
            ['name' => 'Pequeño', 'multiplier' => 0.8],
            ['name' => 'Mediano', 'multiplier' => 1.0],
            ['name' => 'Grande', 'multiplier' => 1.3],
        ];

        foreach ($sizes as $index => $size) {
            ItemVariant::create([
                'item_id' => $item->id,
                'name' => $size['name'],
                'sku' => $item->sku . '-' . strtoupper(substr($size['name'], 0, 1)),
                'price_adjustment' => round($item->base_price * ($size['multiplier'] - 1)),
                'is_available' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function addModifiersIfNeeded(Item $item, string $categoryKey): void
    {
        if (!$this->option('with-modifiers')) {
            return;
        }

        // Only add modifiers to food items
        if (!in_array($categoryKey, ['fastfood', 'main-dishes'])) {
            return;
        }

        $group = ModifierGroup::create([
            'name' => 'Extras y Personalizaciones',
            'description' => 'Personaliza tu pedido',
            'selection_type' => 'multiple',
            'min_selections' => 0,
            'max_selections' => 5,
            'is_required' => false,
            'is_active' => true,
        ]);

        $modifiers = [
            ['name' => 'Extra Palta', 'price' => 1500],
            ['name' => 'Extra Queso', 'price' => 1000],
            ['name' => 'Sin Cebolla', 'price' => 0],
            ['name' => 'Sin Tomate', 'price' => 0],
            ['name' => 'Extra Ají Verde', 'price' => 500],
            ['name' => 'Con Pebre', 'price' => 800],
        ];

        foreach ($modifiers as $index => $modifier) {
            ItemModifier::create([
                'modifier_group_id' => $group->id,
                'name' => $modifier['name'],
                'price_impact' => $modifier['price'],
                'is_default' => false,
                'is_available' => true,
                'max_quantity' => $modifier['price'] > 0 ? 3 : 1,
                'sort_order' => $index + 1,
            ]);
        }

        ItemModifierGroup::create([
            'item_id' => $item->id,
            'modifier_group_id' => $group->id,
            'sort_order' => 1,
        ]);
    }

    private function addLocationPricing(Item $item): void
    {
        if (empty($this->locations)) {
            return;
        }

        $variance = (int) $this->option('location-price-variance');
        
        foreach ($this->locations as $locationId) {
            $priceAdjustment = 1 + (rand(-$variance, $variance) / 100);
            $locationPrice = round($item->base_price * $priceAdjustment);
            
            ItemLocationPrice::create([
                'item_id' => $item->id,
                'location_id' => $locationId,
                'price' => $locationPrice,
                'cost' => $item->base_cost ? round($item->base_cost * $priceAdjustment) : 0,
                'is_active' => true,
            ]);
        }
    }
}