<?php

namespace Colame\AiDiscovery\Contracts;

use Colame\AiDiscovery\Data\ExtractedVariantData;
use Colame\AiDiscovery\Data\ExtractedModifierData;
use Spatie\LaravelData\DataCollection;

interface FoodIntelligenceInterface
{
    /**
     * Analyze item structure to identify potential variants and modifiers
     */
    public function analyzeItemStructure(
        string $itemName,
        ?string $description = null,
        ?string $category = null
    ): array;

    /**
     * Extract variants from conversation data
     */
    public function extractVariants(array $conversationData): DataCollection;

    /**
     * Extract modifiers from conversation data
     */
    public function extractModifiers(array $conversationData): DataCollection;

    /**
     * Detect cultural context and regional variations
     */
    public function detectCulturalContext(
        array $itemData,
        string $location,
        ?string $cuisineType = null
    ): array;

    /**
     * Suggest pricing strategy based on item and market context
     */
    public function suggestPricingStrategy(
        array $itemData,
        array $marketContext,
        ?array $competitorData = null
    ): array;

    /**
     * Identify allergens and dietary restrictions
     */
    public function identifyAllergens(
        string $itemName,
        ?string $description = null,
        ?array $ingredients = null
    ): array;

    /**
     * Generate nutritional estimates
     */
    public function estimateNutritionalInfo(
        string $itemName,
        ?string $category = null,
        ?array $ingredients = null
    ): array;

    /**
     * Suggest complementary items or upsells
     */
    public function suggestComplementaryItems(
        string $itemName,
        string $category,
        ?array $existingMenu = null
    ): array;

    /**
     * Validate extracted data for completeness and accuracy
     */
    public function validateExtractedData(array $extractedData): array;

    /**
     * Learn from user corrections and feedback
     */
    public function learnFromFeedback(
        string $itemName,
        array $originalData,
        array $correctedData
    ): void;

    /**
     * Get common patterns for a food category
     */
    public function getCategoryPatterns(string $category): array;

    /**
     * Detect item type (e.g., main dish, appetizer, beverage)
     */
    public function detectItemType(
        string $itemName,
        ?string $description = null
    ): string;
}