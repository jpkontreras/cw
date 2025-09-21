<?php

namespace Colame\AiDiscovery\Services;

use Colame\AiDiscovery\Contracts\FoodIntelligenceInterface;
use Colame\AiDiscovery\Data\ExtractedVariantData;
use Colame\AiDiscovery\Data\ExtractedModifierData;
use OpenAI\Laravel\Facades\OpenAI;
use Spatie\LaravelData\DataCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FoodIntelligenceService implements FoodIntelligenceInterface
{
    private array $foodCategories = [
        'main_dish' => ['burger', 'pizza', 'pasta', 'steak', 'chicken', 'fish', 'rice', 'noodles'],
        'appetizer' => ['soup', 'salad', 'bread', 'wings', 'nachos', 'dip', 'bruschetta'],
        'beverage' => ['coffee', 'tea', 'juice', 'smoothie', 'soda', 'beer', 'wine', 'cocktail'],
        'dessert' => ['cake', 'ice cream', 'pie', 'cookie', 'brownie', 'pudding', 'flan'],
        'side' => ['fries', 'rice', 'beans', 'coleslaw', 'mashed', 'vegetables'],
        'chilean' => ['empanada', 'completo', 'churrasco', 'sopaipilla', 'cazuela', 'pastel de choclo'],
    ];

    private array $commonAllergens = [
        'gluten' => ['wheat', 'bread', 'pasta', 'flour', 'crust', 'dough', 'breaded'],
        'dairy' => ['milk', 'cheese', 'cream', 'butter', 'yogurt', 'ice cream'],
        'nuts' => ['peanut', 'almond', 'walnut', 'cashew', 'pecan', 'hazelnut'],
        'soy' => ['soy', 'tofu', 'edamame', 'tempeh', 'miso'],
        'eggs' => ['egg', 'mayo', 'mayonnaise', 'meringue', 'custard'],
        'shellfish' => ['shrimp', 'lobster', 'crab', 'oyster', 'mussel', 'scallop'],
        'fish' => ['salmon', 'tuna', 'cod', 'tilapia', 'trout', 'anchovy'],
    ];

    public function analyzeItemStructure(
        string $itemName,
        ?string $description = null,
        ?string $category = null
    ): array {
        $analysis = [
            'detected_type' => $this->detectItemType($itemName, $description),
            'probable_variants' => [],
            'probable_modifiers' => [],
            'cultural_markers' => [],
            'price_indicators' => [],
        ];

        // Check for Chilean food patterns
        if ($this->isChileanDish($itemName)) {
            $analysis['cultural_markers'][] = 'chilean';
            $analysis['probable_variants'] = $this->getChileanVariants($itemName);
            $analysis['probable_modifiers'] = $this->getChileanModifiers($itemName);
        }

        // Analyze for size indicators
        if ($this->hasSizeIndicators($itemName, $description)) {
            $analysis['probable_variants'][] = [
                'type' => 'size',
                'options' => ['Small', 'Medium', 'Large', 'Extra Large'],
            ];
        }

        // Analyze for protein variations
        if ($this->hasProteinVariations($itemName, $description)) {
            $analysis['probable_variants'][] = [
                'type' => 'protein',
                'options' => ['Chicken', 'Beef', 'Pork', 'Fish', 'Vegetarian', 'Vegan'],
            ];
        }

        // Check learned patterns
        $learnedPatterns = $this->getLearnedPatterns($itemName, $category);
        if (!empty($learnedPatterns)) {
            $analysis['learned_patterns'] = $learnedPatterns;
        }

        return $analysis;
    }

    public function extractVariants(array $conversationData): DataCollection
    {
        $variants = new DataCollection(ExtractedVariantData::class, []);

        // Extract from structured conversation
        if (isset($conversationData['conversation'])) {
            foreach ($conversationData['conversation'] as $message) {
                if (!isset($message->content)) continue;

                $content = is_object($message) ? $message->content : $message['content'];

                // Look for size variants
                if (preg_match_all('/\b(small|medium|large|extra large|XL|XXL)\b/i', $content, $matches)) {
                    foreach ($matches[1] as $size) {
                        $variants->push(new ExtractedVariantData(
                            name: ucfirst(strtolower($size)),
                            displayName: $this->formatSizeName($size),
                            type: 'size',
                            priceAdjustment: $this->calculateSizePriceAdjustment($size),
                            confidence: 0.9,
                            reasoning: 'Extracted from conversation'
                        ));
                    }
                }

                // Look for preparation variants
                if (preg_match_all('/\b(grilled|fried|baked|roasted|steamed|raw)\b/i', $content, $matches)) {
                    foreach ($matches[1] as $prep) {
                        $variants->push(new ExtractedVariantData(
                            name: strtolower($prep),
                            displayName: ucfirst(strtolower($prep)),
                            type: 'preparation',
                            priceAdjustment: 0,
                            confidence: 0.85,
                            reasoning: 'Common preparation method'
                        ));
                    }
                }

                // Look for flavor variants
                if (preg_match_all('/\b(mild|regular|spicy|extra spicy|hot)\b/i', $content, $matches)) {
                    foreach ($matches[1] as $flavor) {
                        $variants->push(new ExtractedVariantData(
                            name: strtolower($flavor),
                            displayName: ucfirst(strtolower($flavor)),
                            type: 'flavor',
                            priceAdjustment: 0,
                            confidence: 0.8,
                            reasoning: 'Flavor variation'
                        ));
                    }
                }
            }
        }

        // Extract from pre-analyzed variants
        if (isset($conversationData['variants']) && is_array($conversationData['variants'])) {
            foreach ($conversationData['variants'] as $variant) {
                $variants->push(ExtractedVariantData::from($variant));
            }
        }

        // Remove duplicates by name
        $uniqueVariants = collect($variants->toArray())
            ->unique('name')
            ->values()
            ->toArray();

        return new DataCollection(ExtractedVariantData::class, $uniqueVariants);
    }

    public function extractModifiers(array $conversationData): DataCollection
    {
        $modifiers = new DataCollection(ExtractedModifierData::class, []);

        // Extract from conversation
        if (isset($conversationData['conversation'])) {
            foreach ($conversationData['conversation'] as $message) {
                if (!isset($message->content)) continue;

                $content = is_object($message) ? $message->content : $message['content'];

                // Look for topping modifiers
                if (stripos($content, 'topping') !== false || stripos($content, 'add-on') !== false) {
                    $toppings = $this->extractToppings($content);
                    foreach ($toppings as $topping) {
                        $modifiers->push(new ExtractedModifierData(
                            groupName: 'Toppings',
                            name: $topping['name'],
                            displayName: $topping['display'],
                            selectionType: 'multiple',
                            priceAdjustment: $topping['price'],
                            isRequired: false,
                            confidence: 0.85,
                            reasoning: 'Common topping option'
                        ));
                    }
                }

                // Look for sauce modifiers
                if (stripos($content, 'sauce') !== false || stripos($content, 'dressing') !== false) {
                    $sauces = $this->extractSauces($content);
                    foreach ($sauces as $sauce) {
                        $modifiers->push(new ExtractedModifierData(
                            groupName: 'Sauces',
                            name: $sauce['name'],
                            displayName: $sauce['display'],
                            selectionType: 'single',
                            priceAdjustment: $sauce['price'],
                            isRequired: false,
                            confidence: 0.8,
                            reasoning: 'Sauce option'
                        ));
                    }
                }

                // Look for side modifiers
                if (stripos($content, 'side') !== false || stripos($content, 'comes with') !== false) {
                    $sides = $this->extractSides($content);
                    foreach ($sides as $side) {
                        $modifiers->push(new ExtractedModifierData(
                            groupName: 'Sides',
                            name: $side['name'],
                            displayName: $side['display'],
                            selectionType: 'single',
                            priceAdjustment: $side['price'],
                            isRequired: false,
                            minSelections: 0,
                            maxSelections: 1,
                            confidence: 0.85,
                            reasoning: 'Side option'
                        ));
                    }
                }
            }
        }

        // Extract from pre-analyzed modifiers
        if (isset($conversationData['modifiers']) && is_array($conversationData['modifiers'])) {
            foreach ($conversationData['modifiers'] as $modifier) {
                $modifiers->push(ExtractedModifierData::from($modifier));
            }
        }

        // Remove duplicates by composite key (groupName:name)
        $uniqueModifiers = collect($modifiers->toArray())
            ->unique(function ($m) {
                $groupName = $m['groupName'] ?? $m['group_name'] ?? '';
                $name = $m['name'] ?? '';
                return $groupName . ':' . $name;
            })
            ->values()
            ->toArray();

        return new DataCollection(ExtractedModifierData::class, $uniqueModifiers);
    }

    public function detectCulturalContext(
        array $itemData,
        string $location,
        ?string $cuisineType = null
    ): array {
        $context = [
            'region' => $location,
            'cuisine_type' => $cuisineType,
            'cultural_markers' => [],
            'traditional_preparations' => [],
            'local_preferences' => [],
        ];

        $itemName = $itemData['name'] ?? '';

        // Chilean context
        if (stripos($location, 'chile') !== false || $this->isChileanDish($itemName)) {
            $context['cultural_markers'][] = 'chilean';

            if (stripos($itemName, 'completo') !== false) {
                $context['traditional_preparations'][] = 'Italiano (tomato, avocado, mayo)';
                $context['traditional_preparations'][] = 'Especial (with green beans)';
                $context['local_preferences'][] = 'Extra avocado is popular';
            }

            if (stripos($itemName, 'empanada') !== false) {
                $context['traditional_preparations'][] = 'Pino (beef with onions, egg, olive, raisin)';
                $context['traditional_preparations'][] = 'Queso (cheese)';
                $context['traditional_preparations'][] = 'Mariscos (seafood)';
                $context['local_preferences'][] = 'Baked preferred over fried';
            }

            $context['price_currency'] = 'CLP';
            $context['typical_price_range'] = $this->getChileanPriceRange($itemName);
        }

        // Mexican context
        if (stripos($location, 'mexico') !== false || stripos($cuisineType, 'mexican') !== false) {
            $context['cultural_markers'][] = 'mexican';
            $context['local_preferences'][] = 'Spicy options preferred';
            $context['local_preferences'][] = 'Lime and cilantro common garnishes';
        }

        // Asian contexts
        if ($this->isAsianCuisine($cuisineType)) {
            $context['cultural_markers'][] = 'asian';
            $context['local_preferences'][] = 'Rice as default side';
            $context['local_preferences'][] = 'Spice level customization important';
        }

        return $context;
    }

    public function suggestPricingStrategy(
        array $itemData,
        array $marketContext,
        ?array $competitorData = null
    ): array {
        $strategy = [
            'base_price_suggestion' => 0,
            'variant_pricing' => [],
            'modifier_pricing' => [],
            'psychological_pricing' => true,
            'reasoning' => [],
        ];

        $itemType = $this->detectItemType($itemData['name']);
        $priceTier = $marketContext['price_tier'] ?? 'medium';
        $location = $marketContext['location'] ?? 'Chile';

        // Base price suggestions
        if (stripos($location, 'chile') !== false) {
            $strategy['base_price_suggestion'] = $this->getChileanBasePrice($itemType, $priceTier);
            $strategy['currency'] = 'CLP';
        } else {
            $strategy['base_price_suggestion'] = $this->getGenericBasePrice($itemType, $priceTier);
            $strategy['currency'] = 'USD';
        }

        // Variant pricing strategy
        $strategy['variant_pricing'] = [
            'size_multipliers' => [
                'small' => 0.8,
                'medium' => 1.0,
                'large' => 1.3,
                'extra_large' => 1.6,
            ],
            'preparation_adjustments' => [
                'grilled' => 0,
                'fried' => 0,
                'premium' => 0.15, // 15% premium
            ],
        ];

        // Modifier pricing suggestions
        $strategy['modifier_pricing'] = [
            'toppings' => [
                'basic' => 500, // CLP
                'premium' => 1500, // CLP
            ],
            'sides' => [
                'standard' => 2000,
                'premium' => 3500,
            ],
        ];

        // Add reasoning
        $strategy['reasoning'][] = "Based on {$priceTier} tier pricing in {$location}";
        if ($competitorData) {
            $avgCompetitorPrice = array_sum($competitorData) / count($competitorData);
            $strategy['reasoning'][] = "Competitor average: " . number_format($avgCompetitorPrice);
        }

        return $strategy;
    }

    public function identifyAllergens(
        string $itemName,
        ?string $description = null,
        ?array $ingredients = null
    ): array {
        $allergens = [];
        $searchText = strtolower($itemName . ' ' . $description);

        foreach ($this->commonAllergens as $allergen => $indicators) {
            foreach ($indicators as $indicator) {
                if (stripos($searchText, $indicator) !== false) {
                    $allergens[] = $allergen;
                    break;
                }
            }
        }

        // Check ingredients list
        if ($ingredients) {
            foreach ($ingredients as $ingredient) {
                foreach ($this->commonAllergens as $allergen => $indicators) {
                    if (in_array(strtolower($ingredient), $indicators)) {
                        $allergens[] = $allergen;
                    }
                }
            }
        }

        return array_unique($allergens);
    }

    public function estimateNutritionalInfo(
        string $itemName,
        ?string $category = null,
        ?array $ingredients = null
    ): array {
        $nutritional = [
            'calories' => 0,
            'protein_g' => 0,
            'carbs_g' => 0,
            'fat_g' => 0,
            'fiber_g' => 0,
            'sodium_mg' => 0,
        ];

        // Basic estimates by category
        $categoryEstimates = [
            'burger' => ['calories' => 600, 'protein_g' => 30, 'carbs_g' => 40, 'fat_g' => 35],
            'pizza' => ['calories' => 285, 'protein_g' => 12, 'carbs_g' => 36, 'fat_g' => 10], // per slice
            'salad' => ['calories' => 250, 'protein_g' => 15, 'carbs_g' => 20, 'fat_g' => 15],
            'pasta' => ['calories' => 400, 'protein_g' => 15, 'carbs_g' => 60, 'fat_g' => 10],
            'sandwich' => ['calories' => 450, 'protein_g' => 25, 'carbs_g' => 40, 'fat_g' => 20],
        ];

        foreach ($categoryEstimates as $cat => $estimates) {
            if (stripos($itemName, $cat) !== false) {
                $nutritional = array_merge($nutritional, $estimates);
                break;
            }
        }

        // Adjust based on descriptors
        if (stripos($itemName, 'double') !== false) {
            $nutritional['calories'] *= 1.5;
            $nutritional['protein_g'] *= 1.5;
        }

        if (stripos($itemName, 'lite') !== false || stripos($itemName, 'light') !== false) {
            $nutritional['calories'] *= 0.7;
            $nutritional['fat_g'] *= 0.6;
        }

        return $nutritional;
    }

    public function suggestComplementaryItems(
        string $itemName,
        string $category,
        ?array $existingMenu = null
    ): array {
        $suggestions = [];

        $complements = [
            'burger' => ['fries', 'onion rings', 'soft drink', 'milkshake'],
            'pizza' => ['garlic bread', 'salad', 'wings', 'soft drink'],
            'pasta' => ['garlic bread', 'salad', 'wine', 'dessert'],
            'salad' => ['soup', 'bread', 'juice', 'smoothie'],
            'coffee' => ['pastry', 'muffin', 'sandwich', 'cookie'],
        ];

        foreach ($complements as $mainItem => $items) {
            if (stripos($itemName, $mainItem) !== false) {
                $suggestions = $items;
                break;
            }
        }

        // Filter out items already on menu
        if ($existingMenu) {
            $suggestions = array_filter($suggestions, function($item) use ($existingMenu) {
                foreach ($existingMenu as $menuItem) {
                    if (stripos($menuItem, $item) !== false) {
                        return false;
                    }
                }
                return true;
            });
        }

        return $suggestions;
    }

    public function validateExtractedData(array $extractedData): array
    {
        $validation = [
            'is_valid' => true,
            'issues' => [],
            'completeness_score' => 0,
        ];

        $requiredFields = ['variants', 'modifiers', 'metadata'];
        $presentFields = 0;

        foreach ($requiredFields as $field) {
            if (!empty($extractedData[$field])) {
                $presentFields++;
            } else {
                $validation['issues'][] = "Missing {$field}";
            }
        }

        $validation['completeness_score'] = ($presentFields / count($requiredFields)) * 100;

        // Validate variant structure
        if (isset($extractedData['variants'])) {
            foreach ($extractedData['variants'] as $variant) {
                if (!isset($variant['name']) || !isset($variant['type'])) {
                    $validation['issues'][] = 'Variant missing required fields';
                    $validation['is_valid'] = false;
                }
            }
        }

        // Validate modifier structure
        if (isset($extractedData['modifiers'])) {
            foreach ($extractedData['modifiers'] as $modifier) {
                if (!isset($modifier['name']) || !isset($modifier['groupName'])) {
                    $validation['issues'][] = 'Modifier missing required fields';
                    $validation['is_valid'] = false;
                }
            }
        }

        return $validation;
    }

    public function learnFromFeedback(
        string $itemName,
        array $originalData,
        array $correctedData
    ): void {
        // Store pattern in Redis
        $patternKey = 'ai_discovery:pattern:correction:' . md5($itemName);

        $existingPattern = Cache::get($patternKey, [
            'usage_count' => 0,
            'corrections' => []
        ]);

        $existingPattern['usage_count']++;
        $existingPattern['corrections'][] = [
            'original' => $originalData,
            'corrected' => $correctedData,
            'differences' => $this->calculateDifferences($originalData, $correctedData),
            'timestamp' => now()->toIso8601String(),
        ];
        $existingPattern['success_rate'] = $this->calculateSuccessRate($originalData, $correctedData);
        $existingPattern['normalized_name'] = $this->normalizeItemName($itemName);

        Cache::put($patternKey, $existingPattern, 86400 * 30); // 30 days

        // Clear related cache
        Cache::forget('food_patterns:' . $this->detectItemType($itemName));
    }

    public function getCategoryPatterns(string $category): array
    {
        return Cache::tags(['food_patterns', $category])->remember(
            "category_patterns_{$category}",
            3600,
            function() use ($category) {
                // For now, return empty array as patterns will be built over time
                // In production, this would aggregate patterns from multiple sources
                return [];
            }
        );
    }

    public function detectItemType(string $itemName, ?string $description = null): string
    {
        $itemLower = strtolower($itemName . ' ' . $description);

        foreach ($this->foodCategories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($itemLower, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'general';
    }

    // Private helper methods

    private function isChileanDish(string $itemName): bool
    {
        $chileanDishes = ['empanada', 'completo', 'churrasco', 'sopaipilla', 'cazuela',
                         'pastel de choclo', 'charquican', 'porotos', 'humitas'];

        foreach ($chileanDishes as $dish) {
            if (stripos($itemName, $dish) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getChileanVariants(string $itemName): array
    {
        $variants = [];

        if (stripos($itemName, 'empanada') !== false) {
            $variants = [
                ['type' => 'filling', 'options' => ['Pino', 'Queso', 'Mariscos', 'Napolitana']],
                ['type' => 'size', 'options' => ['Individual', 'Mini', 'Familiar']],
            ];
        } elseif (stripos($itemName, 'completo') !== false) {
            $variants = [
                ['type' => 'style', 'options' => ['Normal', 'Italiano', 'Especial', 'Dinámico']],
            ];
        }

        return $variants;
    }

    private function getChileanModifiers(string $itemName): array
    {
        $modifiers = [];

        if (stripos($itemName, 'completo') !== false) {
            $modifiers = [
                ['group' => 'Extras', 'options' => ['Extra Palta', 'Extra Tomate', 'Extra Mayo', 'Chucrut']],
                ['group' => 'Salsas', 'options' => ['Ají', 'Salsa Verde', 'Americana']],
            ];
        } elseif (stripos($itemName, 'empanada') !== false) {
            $modifiers = [
                ['group' => 'Acompañamiento', 'options' => ['Pebre', 'Chancho en Piedra', 'Ají Verde']],
            ];
        }

        return $modifiers;
    }

    private function getChileanPriceRange(string $itemName): array
    {
        $ranges = [
            'empanada' => ['min' => 2000, 'max' => 4500],
            'completo' => ['min' => 2500, 'max' => 5000],
            'churrasco' => ['min' => 4000, 'max' => 8000],
            'sopaipilla' => ['min' => 300, 'max' => 800],
        ];

        foreach ($ranges as $dish => $range) {
            if (stripos($itemName, $dish) !== false) {
                return $range;
            }
        }

        return ['min' => 3000, 'max' => 8000];
    }

    private function getChileanBasePrice(string $itemType, string $priceTier): float
    {
        $prices = [
            'low' => ['main_dish' => 4000, 'appetizer' => 2500, 'beverage' => 1500, 'dessert' => 2000],
            'medium' => ['main_dish' => 7000, 'appetizer' => 4000, 'beverage' => 2500, 'dessert' => 3500],
            'high' => ['main_dish' => 12000, 'appetizer' => 6000, 'beverage' => 4000, 'dessert' => 5000],
        ];

        return $prices[$priceTier][$itemType] ?? 5000;
    }

    private function getGenericBasePrice(string $itemType, string $priceTier): float
    {
        $prices = [
            'low' => ['main_dish' => 8, 'appetizer' => 5, 'beverage' => 3, 'dessert' => 4],
            'medium' => ['main_dish' => 15, 'appetizer' => 8, 'beverage' => 5, 'dessert' => 7],
            'high' => ['main_dish' => 25, 'appetizer' => 12, 'beverage' => 8, 'dessert' => 10],
        ];

        return $prices[$priceTier][$itemType] ?? 10;
    }

    private function hasSizeIndicators(string $itemName, ?string $description): bool
    {
        $sizeWords = ['small', 'medium', 'large', 'mini', 'jumbo', 'individual', 'family', 'personal'];
        $text = strtolower($itemName . ' ' . $description);

        foreach ($sizeWords as $word) {
            if (stripos($text, $word) !== false) {
                return true;
            }
        }
        return false;
    }

    private function hasProteinVariations(string $itemName, ?string $description): bool
    {
        $proteinWords = ['chicken', 'beef', 'pork', 'fish', 'shrimp', 'tofu', 'vegetarian'];
        $text = strtolower($itemName . ' ' . $description);

        $count = 0;
        foreach ($proteinWords as $word) {
            if (stripos($text, $word) !== false) {
                $count++;
            }
        }
        return $count >= 2;
    }

    private function isAsianCuisine(?string $cuisineType): bool
    {
        if (!$cuisineType) return false;

        $asianTypes = ['chinese', 'japanese', 'thai', 'vietnamese', 'korean', 'indian'];
        foreach ($asianTypes as $type) {
            if (stripos($cuisineType, $type) !== false) {
                return true;
            }
        }
        return false;
    }

    private function formatSizeName(string $size): string
    {
        $sizeMap = [
            'small' => 'Small',
            'medium' => 'Medium',
            'large' => 'Large',
            'extra large' => 'Extra Large',
            'xl' => 'Extra Large',
            'xxl' => 'XXL',
        ];

        return $sizeMap[strtolower($size)] ?? ucfirst($size);
    }

    private function calculateSizePriceAdjustment(string $size): float
    {
        $adjustments = [
            'small' => -2.00,
            'medium' => 0.00,
            'large' => 3.00,
            'extra large' => 5.00,
            'xl' => 5.00,
            'xxl' => 7.00,
        ];

        return $adjustments[strtolower($size)] ?? 0.00;
    }

    private function extractToppings(string $content): array
    {
        $toppings = [];
        $commonToppings = [
            'cheese' => ['name' => 'cheese', 'display' => 'Extra Cheese', 'price' => 2.00],
            'bacon' => ['name' => 'bacon', 'display' => 'Bacon', 'price' => 3.00],
            'avocado' => ['name' => 'avocado', 'display' => 'Avocado', 'price' => 2.50],
            'mushroom' => ['name' => 'mushrooms', 'display' => 'Mushrooms', 'price' => 1.50],
            'onion' => ['name' => 'onions', 'display' => 'Onions', 'price' => 1.00],
            'jalapeño' => ['name' => 'jalapenos', 'display' => 'Jalapeños', 'price' => 1.00],
        ];

        foreach ($commonToppings as $keyword => $topping) {
            if (stripos($content, $keyword) !== false) {
                $toppings[] = $topping;
            }
        }

        return $toppings;
    }

    private function extractSauces(string $content): array
    {
        $sauces = [];
        $commonSauces = [
            'mayo' => ['name' => 'mayo', 'display' => 'Mayonnaise', 'price' => 0],
            'ketchup' => ['name' => 'ketchup', 'display' => 'Ketchup', 'price' => 0],
            'mustard' => ['name' => 'mustard', 'display' => 'Mustard', 'price' => 0],
            'bbq' => ['name' => 'bbq', 'display' => 'BBQ Sauce', 'price' => 0],
            'ranch' => ['name' => 'ranch', 'display' => 'Ranch', 'price' => 0],
            'chipotle' => ['name' => 'chipotle', 'display' => 'Chipotle Mayo', 'price' => 0.50],
        ];

        foreach ($commonSauces as $keyword => $sauce) {
            if (stripos($content, $keyword) !== false) {
                $sauces[] = $sauce;
            }
        }

        return $sauces;
    }

    private function extractSides(string $content): array
    {
        $sides = [];
        $commonSides = [
            'fries' => ['name' => 'fries', 'display' => 'French Fries', 'price' => 3.00],
            'onion rings' => ['name' => 'onion_rings', 'display' => 'Onion Rings', 'price' => 4.00],
            'salad' => ['name' => 'salad', 'display' => 'Side Salad', 'price' => 3.50],
            'rice' => ['name' => 'rice', 'display' => 'Rice', 'price' => 2.50],
            'beans' => ['name' => 'beans', 'display' => 'Beans', 'price' => 2.50],
        ];

        foreach ($commonSides as $keyword => $side) {
            if (stripos($content, $keyword) !== false) {
                $sides[] = $side;
            }
        }

        return $sides;
    }

    private function getLearnedPatterns(string $itemName, ?string $category): array
    {
        $normalizedName = $this->normalizeItemName($itemName);
        $patternKey = 'ai_discovery:pattern:learned:' . md5($normalizedName . ':' . $category);

        // Get cached patterns
        $patterns = Cache::get($patternKey, []);

        // If no patterns cached, return empty array
        // Patterns will be built up over time as items are discovered
        return is_array($patterns) ? $patterns : [];
    }

    private function normalizeItemName(string $itemName): string
    {
        // Remove accents, lowercase, remove special characters
        $normalized = Str::ascii($itemName);
        $normalized = strtolower($normalized);
        $normalized = preg_replace('/[^a-z0-9 ]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }

    private function calculateDifferences(array $original, array $corrected): array
    {
        $differences = [];

        foreach ($corrected as $key => $value) {
            if (!isset($original[$key]) || $original[$key] !== $value) {
                $differences[$key] = [
                    'original' => $original[$key] ?? null,
                    'corrected' => $value,
                ];
            }
        }

        return $differences;
    }

    private function calculateSuccessRate(array $original, array $corrected): float
    {
        if (empty($original)) return 0.0;

        $correctCount = 0;
        $totalCount = count($original);

        foreach ($original as $key => $value) {
            if (isset($corrected[$key]) && $corrected[$key] === $value) {
                $correctCount++;
            }
        }

        return round(($correctCount / $totalCount) * 100, 2);
    }
}