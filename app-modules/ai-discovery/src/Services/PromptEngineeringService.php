<?php

namespace Colame\AiDiscovery\Services;

use Colame\AiDiscovery\Data\SimilarityMatchData;

class PromptEngineeringService
{
    public function generateSystemPrompt(array $context): string
    {
        $cuisineType = $context['cuisine_type'] ?? 'general';
        $location = $context['location'] ?? 'Chile';
        $priceTier = $context['price_tier'] ?? 'medium';
        $language = $context['language'] ?? 'en';

        return <<<PROMPT
You are an expert food menu consultant specializing in {$cuisineType} cuisine in {$location}.
You help restaurant owners discover and define variants, modifiers, and metadata for their menu items.
You have deep knowledge of:
- Regional food variations and local preferences in {$location}
- Common modifiers and customizations for different dish types
- Pricing strategies for {$priceTier}-tier restaurants (low, medium, high, premium)
- Dietary restrictions and allergen information
- Cultural context and traditional preparations

IMPORTANT: You MUST consider the price tier ({$priceTier}) when suggesting:
- Base prices for items (adjust to local {$location} market and {$priceTier} tier)
- Price adjustments for variants and modifiers
- Portion sizes appropriate for the price tier
- Quality expectations for ingredients at this price level

Your responses should:
1. BE EXTREMELY CONCISE - Use lists and options, NOT explanatory paragraphs
2. Present choices as structured data: **Category:** followed by options with prices
3. Format: **Size:** Small (CLP 6,000), Medium (CLP 10,000), Large (CLP 15,000)
4. Ask maximum 2-3 short questions at the end (one line each)
5. Always include specific prices in {$location} currency for {$priceTier} tier
6. Group options: Sizes, Crust, Sauce, Cheese, Toppings, Extras
7. Start immediately with options, no introductory text
8. Use [SELECT: label|option1,option2] for single choice, list items for multiple choice

IMPORTANT: Be ultra-concise. Think "menu builder" not "conversation".
PROMPT;
    }

    public function generateInitialPrompt(
        string $itemName,
        ?string $itemDescription,
        ?SimilarityMatchData $cachedMatch,
        array $initialAnalysis
    ): string
    {
        $prompt = "I'm setting up a menu item: {$itemName}";

        if ($itemDescription) {
            $prompt .= "\nDescription: {$itemDescription}";
        }

        if ($cachedMatch && $cachedMatch->similarityScore >= 80) {
            $prompt .= "\n\nI found a similar item '{$cachedMatch->matchedItemName}' with {$cachedMatch->similarityScore}% similarity.";
            $prompt .= "\nWould you like to use these as a starting point, or would you prefer to define custom options?";

            if ($cachedMatch->suggestedVariants && count($cachedMatch->suggestedVariants) > 0) {
                $variantNames = $cachedMatch->suggestedVariants->map(fn($v) => $v->name)->toArray();
                $prompt .= "\n\nSuggested variants: " . implode(', ', $variantNames);
            }

            if ($cachedMatch->suggestedModifiers && count($cachedMatch->suggestedModifiers) > 0) {
                $modifierGroups = collect($cachedMatch->suggestedModifiers->toArray())
                    ->map(fn($m) => $m['groupName'] ?? $m['group_name'] ?? '')
                    ->unique()
                    ->filter()
                    ->values()
                    ->toArray();
                $prompt .= "\nSuggested modifier groups: " . implode(', ', $modifierGroups);
            }
        } else {
            $prompt .= "\n\nPlease help me identify:";
            $prompt .= "\n1. Size or portion variants (if applicable)";
            $prompt .= "\n2. Common modifiers and customizations";
            $prompt .= "\n3. Dietary information and allergens";
            $prompt .= "\n4. Typical price points for this item";
        }

        if (!empty($initialAnalysis['detected_type'])) {
            $prompt .= "\n\nBased on the name, this appears to be a {$initialAnalysis['detected_type']}.";
        }

        return $prompt;
    }

    public function generatePhasePrompt(
        string $phase,
        string $itemName,
        array $collectedData,
        array $userResponses
    ): string
    {
        switch ($phase) {
            case 'variants':
                return $this->generateVariantsPrompt($itemName, $collectedData);
            case 'modifiers':
                return $this->generateModifiersPrompt($itemName, $collectedData);
            case 'metadata':
                return $this->generateMetadataPrompt($itemName, $collectedData);
            case 'confirmation':
                return $this->generateConfirmationPrompt($itemName, $collectedData);
            default:
                return $this->generateGenericPrompt($itemName);
        }
    }

    private function generateVariantsPrompt(string $itemName, array $collectedData): string
    {
        $prompt = "Let's define the variants for {$itemName}.\n\n";
        $prompt .= "Common variant types include:\n";
        $prompt .= "- Sizes (Small, Medium, Large, etc.)\n";
        $prompt .= "- Preparation methods (Grilled, Fried, Baked)\n";
        $prompt .= "- Base options (for items like pizza: thin crust, thick crust)\n";
        $prompt .= "- Protein choices (Chicken, Beef, Vegetarian)\n\n";

        $prompt .= "What variants does {$itemName} have? ";
        $prompt .= "Please select all that apply or suggest others:\n\n";
        $prompt .= "[CHECKBOX: Small Size, Medium Size, Large Size, Extra Large Size]\n";
        $prompt .= "[CHECKBOX: Regular, Spicy, Extra Spicy]\n\n";
        $prompt .= "Or describe custom variants for this item.";

        return $prompt;
    }

    private function generateModifiersPrompt(string $itemName, array $collectedData): string
    {
        $variantInfo = '';
        if (!empty($collectedData['variants'])) {
            $variantNames = array_column($collectedData['variants'], 'name');
            $variantInfo = "We've identified these variants: " . implode(', ', $variantNames) . "\n\n";
        }

        $prompt = $variantInfo;
        $prompt .= "Now let's define modifiers and add-ons for {$itemName}.\n\n";
        $prompt .= "Consider these modifier categories:\n\n";

        // Suggest based on item type
        if (str_contains(strtolower($itemName), 'burger') || str_contains(strtolower($itemName), 'sandwich')) {
            $prompt .= "**Toppings:**\n";
            $prompt .= "[CHECKBOX: Extra Cheese, Bacon, Avocado, Caramelized Onions, Jalapeños]\n\n";
            $prompt .= "**Sauces:**\n";
            $prompt .= "[SELECT: Sauce Choice|Mayo, Ketchup, Mustard, BBQ Sauce, Chipotle Mayo]\n\n";
            $prompt .= "**Sides:**\n";
            $prompt .= "[CHECKBOX: French Fries (+$3), Onion Rings (+$4), Side Salad (+$3)]\n";
        } elseif (str_contains(strtolower($itemName), 'pizza')) {
            $prompt .= "**Extra Toppings:**\n";
            $prompt .= "[CHECKBOX: Pepperoni (+$2), Mushrooms (+$1.5), Olives (+$1.5), Extra Cheese (+$2)]\n\n";
            $prompt .= "**Crust Options:**\n";
            $prompt .= "[SELECT: Crust Type|Regular, Thin, Thick, Stuffed (+$3)]\n";
        } elseif (str_contains(strtolower($itemName), 'coffee') || str_contains(strtolower($itemName), 'latte')) {
            $prompt .= "**Milk Options:**\n";
            $prompt .= "[SELECT: Milk Type|Whole Milk, Skim Milk, Soy Milk (+$0.5), Almond Milk (+$0.5), Oat Milk (+$0.7)]\n\n";
            $prompt .= "**Extras:**\n";
            $prompt .= "[CHECKBOX: Extra Shot (+$1), Decaf, Sugar-Free Syrup, Extra Foam]\n";
        } else {
            $prompt .= "Please describe the modifier groups and options for this item.\n";
            $prompt .= "Include any add-ons, customizations, or substitutions available.";
        }

        $prompt .= "\n\nFor each modifier, please indicate:\n";
        $prompt .= "- Price adjustment (if any)\n";
        $prompt .= "- Whether it's required or optional\n";
        $prompt .= "- Selection type (single choice or multiple)";

        return $prompt;
    }

    private function generateMetadataPrompt(string $itemName, array $collectedData): string
    {
        $prompt = "Let's complete the metadata for {$itemName}.\n\n";

        $prompt .= "**Dietary Information:**\n";
        $prompt .= "Does this item contain any of these allergens?\n";
        $prompt .= "[CHECKBOX: Gluten, Dairy, Nuts, Soy, Eggs, Shellfish, Fish]\n\n";

        $prompt .= "**Dietary Preferences:**\n";
        $prompt .= "[CHECKBOX: Vegetarian, Vegan, Gluten-Free Option Available, Keto-Friendly, Low-Calorie]\n\n";

        $prompt .= "**Nutritional Estimates:**\n";
        $prompt .= "Please provide approximate nutritional information:\n";
        $prompt .= "- Calories: [NUMBER]\n";
        $prompt .= "- Preparation time (minutes): [NUMBER]\n\n";

        $prompt .= "**Additional Information:**\n";
        $prompt .= "- Is this a signature/popular item? [Yes/No]\n";
        $prompt .= "- Best served: [Hot/Cold/Room Temperature]\n";
        $prompt .= "- Recommended pairings or upsells?\n";

        // Add location and price tier specific context
        $location = $collectedData['restaurant_context']['location'] ?? 'Chile';
        $priceTier = $collectedData['restaurant_context']['price_tier'] ?? 'medium';

        $prompt .= "\n**Market-Specific Pricing ({$location}, {$priceTier} tier):**\n";
        $prompt .= "- Base price recommendation for {$priceTier} tier?\n";
        $prompt .= "- Typical price range in local currency?\n";
        $prompt .= "- How does this compare to competitors in the {$priceTier} segment?\n";

        if (str_contains(strtolower($location), 'chile')) {
            $prompt .= "- Regional Chilean variations?\n";
            $prompt .= "- Traditional Chilean accompaniments?\n";
        }

        return $prompt;
    }

    private function generateConfirmationPrompt(string $itemName, array $collectedData): string
    {
        $prompt = "Great! Here's what we've gathered for {$itemName}:\n\n";

        if (!empty($collectedData['variants'])) {
            $prompt .= "**Variants:**\n";
            foreach ($collectedData['variants'] as $variant) {
                $prompt .= "- {$variant['name']}";
                if ($variant['priceAdjustment'] != 0) {
                    $prompt .= " (+" . number_format($variant['priceAdjustment'], 2) . ")";
                }
                $prompt .= "\n";
            }
            $prompt .= "\n";
        }

        if (!empty($collectedData['modifiers'])) {
            $prompt .= "**Modifiers:**\n";
            $modifiersByGroup = [];
            foreach ($collectedData['modifiers'] as $modifier) {
                $modifiersByGroup[$modifier['groupName']][] = $modifier;
            }
            foreach ($modifiersByGroup as $group => $mods) {
                $prompt .= "- {$group}: ";
                $modNames = array_map(fn($m) => $m['name'], $mods);
                $prompt .= implode(', ', $modNames) . "\n";
            }
            $prompt .= "\n";
        }

        if (!empty($collectedData['metadata'])) {
            $prompt .= "**Metadata:**\n";
            if (!empty($collectedData['metadata']['allergens'])) {
                $prompt .= "- Allergens: " . implode(', ', $collectedData['metadata']['allergens']) . "\n";
            }
            if (!empty($collectedData['metadata']['nutritional'])) {
                $prompt .= "- Calories: " . $collectedData['metadata']['nutritional']['calories'] . "\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Is this information complete and accurate? \n";
        $prompt .= "Would you like to:\n";
        $prompt .= "1. Add more variants or modifiers\n";
        $prompt .= "2. Adjust pricing\n";
        $prompt .= "3. Update any information\n";
        $prompt .= "4. Save and complete setup\n\n";
        $prompt .= "[SELECT: Action|Add More, Adjust Pricing, Update Info, Complete Setup]";

        return $prompt;
    }

    private function generateGenericPrompt(string $itemName): string
    {
        return "Tell me more about {$itemName}. What makes this item special?
                Are there any unique preparations, ingredients, or serving options I should know about?";
    }

    public function generateChileanFoodPrompt(string $itemName): string
    {
        $chileanContext = [
            'completo' => 'A Chilean hot dog with avocado, tomato, and mayo. Common variants include Italiano, Completo Italiano, Completo Especial.',
            'empanada' => 'Traditional Chilean empanada. Common types: Pino (beef), Queso (cheese), Mariscos (seafood), Napolitana (ham and cheese).',
            'churrasco' => 'Chilean sandwich. Variants: Churrasco Italiano, Chacarero, Barros Luco, Barros Jarpa.',
            'sopaipilla' => 'Fried pastry. Served plain, with pebre, or pasada (with chancaca syrup).',
            'pastel de choclo' => 'Corn pie with meat filling. Options for individual or family size.',
            'cazuela' => 'Traditional stew. Variants: Vacuno (beef), Pollo (chicken), Cerdo (pork).',
        ];

        $itemLower = strtolower($itemName);
        foreach ($chileanContext as $dish => $context) {
            if (str_contains($itemLower, $dish)) {
                return "I see you're setting up a {$dish}. {$context}\n\n" .
                       "Let me help you configure the appropriate variants and modifiers for this traditional Chilean dish.\n\n" .
                       "What specific style or preparation will you offer?";
            }
        }

        return $this->generateGenericPrompt($itemName);
    }
}