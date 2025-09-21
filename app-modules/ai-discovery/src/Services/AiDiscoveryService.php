<?php

namespace Colame\AiDiscovery\Services;

use Colame\AiDiscovery\Contracts\AiDiscoveryInterface;
use Colame\AiDiscovery\Contracts\FoodIntelligenceInterface;
use Colame\AiDiscovery\Contracts\SimilarityCacheInterface;
use Colame\AiDiscovery\Data\ConversationContextData;
use Colame\AiDiscovery\Data\DiscoverySessionData;
use Colame\AiDiscovery\Data\ExtractedDataData;
use Colame\AiDiscovery\Data\RestaurantContextData;
use Colame\AiDiscovery\Data\SimilarityMatchData;
use Colame\AiDiscovery\Data\AiMessageData;
use Colame\AiDiscovery\Models\DiscoverySession;
use Colame\AiDiscovery\Services\AiResponseCacheService;
use OpenAI\Laravel\Facades\OpenAI;
use Spatie\LaravelData\DataCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiDiscoveryService implements AiDiscoveryInterface
{
    public function __construct(
        private FoodIntelligenceInterface $foodIntelligence,
        private SimilarityCacheInterface $similarityCache,
        private PromptEngineeringService $promptService,
        private AiResponseCacheService $cacheService
    ) {}

    public function startDiscovery(
        string $itemName,
        array $context,
        ?string $itemDescription = null
    ): DiscoverySessionData
    {
        $sessionUuid = Str::uuid()->toString();

        // Step 1: Check Redis cache for exact match
        $cacheParams = [
            'item_name' => $itemName,
            'description' => $itemDescription,
            'cuisine_type' => $context['cuisine_type'] ?? 'general',
            'location' => $context['location'] ?? 'Chile',
            'price_tier' => $context['price_tier'] ?? 'medium',
            'language' => $context['language'] ?? 'en',
        ];

        $cacheKey = $this->cacheService->generateCacheKey($cacheParams);
        $cachedData = $this->cacheService->getCachedInitialResponse($cacheKey);

        if ($cachedData && $this->cacheService->shouldUseCache($cacheParams)) {
            Log::info('Using cached AI response', [
                'item' => $itemName,
                'cache_key' => $cacheKey,
                'cached_at' => $cachedData['timestamp'] ?? null
            ]);

            // Create session from cached data
            $session = DiscoverySession::create([
                'user_id' => auth()->id() ?? 1,
                'session_uuid' => $sessionUuid,
                'restaurant_context' => $context,
                'conversation_history' => $cachedData['response']['conversation'] ?? [],
                'extracted_data' => [
                    'variants' => [],
                    'modifiers' => [],
                    'metadata' => [],
                ],
                'confidence_scores' => [],
                'status' => 'active',
                'messages_count' => 3,
                'tokens_used' => 0, // No tokens used for cached response
            ]);

            // Store session in Redis for quick access
            $this->cacheService->storeSession($sessionUuid, $session->toArray());

            return DiscoverySessionData::from($session);
        }

        // Step 2: No cache hit, proceed with API call
        Log::info('Cache miss, calling AI provider', [
            'item' => $itemName,
            'cache_key' => $cacheKey
        ]);

        // Check for similar items (fallback to old similarity logic)
        $cachedMatch = $this->suggestFromCache($itemName);

        // Create database session
        $session = DiscoverySession::create([
            'user_id' => auth()->id() ?? 1,
            'session_uuid' => $sessionUuid,
            'restaurant_context' => $context,
            'conversation_history' => [],
            'extracted_data' => [
                'variants' => [],
                'modifiers' => [],
                'metadata' => [],
            ],
            'confidence_scores' => [],
            'status' => 'active',
        ]);

        // Generate initial analysis
        $initialAnalysis = $this->foodIntelligence->analyzeItemStructure(
            $itemName,
            $itemDescription,
            $context['category'] ?? null
        );

        // Create initial conversation
        $systemPrompt = $this->promptService->generateSystemPrompt($context);
        $initialUserMessage = $this->promptService->generateInitialPrompt(
            $itemName,
            $itemDescription,
            $cachedMatch,
            $initialAnalysis
        );

        $conversation = [
            new AiMessageData('system', $systemPrompt),
            new AiMessageData('user', $initialUserMessage),
        ];

        // Get initial AI response
        $response = OpenAI::chat()->create([
            'model' => config('ai-discovery.ai.model', 'gemini-2.0-flash-exp'),
            'messages' => array_map(fn($msg) => $msg->toArray(), $conversation),
            'temperature' => config('ai-discovery.ai.temperature', 0.7),
            'max_tokens' => config('ai-discovery.ai.max_tokens', 2000),
        ]);

        $assistantMessage = new AiMessageData(
            'assistant',
            $response->choices[0]->message->content,
            $this->extractInteractiveElements($response->choices[0]->message->content)
        );

        $conversation[] = $assistantMessage;

        // Step 3: Cache the response
        $ttl = $this->cacheService->getTtlForItem($itemName, $context['price_tier'] ?? 'medium');
        $this->cacheService->cacheInitialResponse($cacheKey, [
            'conversation' => array_map(fn($msg) => $msg->toArray(), $conversation),
            'ai_response' => $response->choices[0]->message->content,
            'usage' => [
                'total_tokens' => $response->usage->totalTokens ?? 0,
                'prompt_tokens' => $response->usage->promptTokens ?? 0,
                'completion_tokens' => $response->usage->completionTokens ?? 0,
            ],
            'model' => config('ai-discovery.ai.model', 'gemini-2.0-flash-exp'),
        ], $ttl);

        // Update session with initial conversation
        $session->update([
            'conversation_history' => array_map(fn($msg) => $msg->toArray(), $conversation),
            'messages_count' => 3,
            'tokens_used' => $response->usage->totalTokens ?? 0,
        ]);

        // Store session in Redis for quick access
        $this->cacheService->storeSession($sessionUuid, $session->toArray());

        return DiscoverySessionData::from($session);
    }

    public function processUserResponse(
        string $sessionId,
        string $response,
        ?array $selections = null
    ): ConversationContextData
    {
        $session = DiscoverySession::where('session_uuid', $sessionId)->firstOrFail();

        $conversation = $this->hydrateConversation($session->conversation_history);
        $userMessage = new AiMessageData('user', $response, null, $selections);
        $conversation[] = $userMessage;

        // Determine current phase
        $currentPhase = $this->determinePhase($conversation, $session->extracted_data);

        // Generate appropriate prompt for current phase
        $nextPrompt = $this->generatePhaseSpecificPrompt(
            $currentPhase,
            $session->extracted_data,
            $conversation
        );

        // Get AI response
        $aiResponse = OpenAI::chat()->create([
            'model' => config('ai-discovery.ai.model', 'gemini-2.0-flash-exp'),
            'messages' => array_map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ], $conversation),
            'temperature' => config('ai-discovery.ai.temperature', 0.7),
            'max_tokens' => config('ai-discovery.ai.max_tokens', 2000),
        ]);

        $assistantMessage = new AiMessageData(
            'assistant',
            $aiResponse->choices[0]->message->content,
            $this->extractInteractiveElements($aiResponse->choices[0]->message->content)
        );
        $conversation[] = $assistantMessage;

        // Extract data based on current phase
        $extractedData = $this->extractPhaseData($currentPhase, $conversation, $session->extracted_data);

        // Update session
        $session->update([
            'conversation_history' => array_map(fn($msg) => $msg->toArray(), $conversation),
            'extracted_data' => $extractedData,
            'messages_count' => $session->messages_count + 2,
            'tokens_used' => $session->tokens_used + $aiResponse->usage->totalTokens,
        ]);

        return new ConversationContextData(
            sessionId: $sessionId,
            currentPhase: $currentPhase,
            itemName: $this->extractItemName($conversation),
            itemDescription: $this->extractItemDescription($conversation),
            itemCategory: $session->restaurant_context['category'] ?? null,
            collectedData: $extractedData,
            userResponses: $this->extractUserResponses($conversation),
            interactionCount: $session->messages_count,
            lastUserInput: $response,
            nextPrompt: $assistantMessage->content,
        );
    }

    public function suggestFromCache(string $itemName, float $threshold = 80.0): ?SimilarityMatchData
    {
        $matches = $this->similarityCache->findSimilar($itemName, $threshold);

        if ($matches->count() === 0) {
            return null;
        }

        return $matches->first();
    }

    public function completeDiscovery(string $sessionId): DiscoverySessionData
    {
        $session = DiscoverySession::where('session_uuid', $sessionId)->firstOrFail();

        // Extract final consolidated data
        $conversation = $this->hydrateConversation($session->conversation_history);

        $finalVariants = $this->foodIntelligence->extractVariants(
            $session->extracted_data
        );

        $finalModifiers = $this->foodIntelligence->extractModifiers(
            $session->extracted_data
        );

        // Calculate confidence scores
        $confidenceScores = $this->calculateConfidenceScores(
            $finalVariants,
            $finalModifiers,
            $session->extracted_data
        );

        // Store in cache for future use
        $this->similarityCache->storeExtraction(
            [
                'name' => $this->extractItemName($conversation),
                'category' => $session->restaurant_context['category'] ?? null,
            ],
            [
                'variants' => $finalVariants->toArray(),
                'modifiers' => $finalModifiers->toArray(),
                'metadata' => $session->extracted_data['metadata'] ?? [],
            ]
        );

        // Update session status
        $session->update([
            'status' => 'completed',
            'extracted_data' => [
                'variants' => $finalVariants->toArray(),
                'modifiers' => $finalModifiers->toArray(),
                'metadata' => $session->extracted_data['metadata'] ?? [],
            ],
            'confidence_scores' => $confidenceScores,
        ]);

        return DiscoverySessionData::from($session);
    }

    public function getConversationContext(string $sessionId): ConversationContextData
    {
        $session = DiscoverySession::where('session_uuid', $sessionId)->firstOrFail();
        $conversation = $this->hydrateConversation($session->conversation_history);

        return new ConversationContextData(
            sessionId: $sessionId,
            currentPhase: $this->determinePhase($conversation, $session->extracted_data),
            itemName: $this->extractItemName($conversation),
            itemDescription: $this->extractItemDescription($conversation),
            itemCategory: $session->restaurant_context['category'] ?? null,
            collectedData: $session->extracted_data,
            userResponses: $this->extractUserResponses($conversation),
            interactionCount: $session->messages_count,
        );
    }

    public function generateNextPrompt(ConversationContextData $context): string
    {
        return $this->promptService->generatePhasePrompt(
            $context->currentPhase,
            $context->itemName,
            $context->collectedData,
            $context->userResponses
        );
    }

    public function streamResponse(
        string $sessionId,
        string $userInput,
        callable $onChunk
    ): void
    {
        $session = DiscoverySession::where('session_uuid', $sessionId)->firstOrFail();
        $conversation = $this->hydrateConversation($session->conversation_history);

        $userMessage = new AiMessageData('user', $userInput);
        $conversation[] = $userMessage;

        $stream = OpenAI::chat()->createStreamed([
            'model' => config('ai-discovery.ai.model', 'gemini-2.0-flash-exp'),
            'messages' => array_map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ], $conversation),
            'temperature' => config('ai-discovery.ai.temperature', 0.7),
            'max_tokens' => config('ai-discovery.ai.max_tokens', 2000),
        ]);

        $fullResponse = '';
        foreach ($stream as $response) {
            $chunk = $response->choices[0]->delta->content ?? '';
            $fullResponse .= $chunk;
            $onChunk($chunk);
        }

        // Save the complete response
        $assistantMessage = new AiMessageData('assistant', $fullResponse);
        $conversation[] = $assistantMessage;

        $session->update([
            'conversation_history' => array_map(fn($msg) => $msg->toArray(), $conversation),
            'messages_count' => $session->messages_count + 2,
        ]);
    }

    public function getUserActiveSessions(int $userId): DataCollection
    {
        $sessions = DiscoverySession::where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('updated_at', 'desc')
            ->get();

        return DiscoverySessionData::collection($sessions);
    }

    public function abandonSession(string $sessionId): void
    {
        DiscoverySession::where('session_uuid', $sessionId)
            ->update(['status' => 'abandoned']);
    }

    public function resumeSession(string $sessionId): ConversationContextData
    {
        return $this->getConversationContext($sessionId);
    }

    // Private helper methods

    private function hydrateConversation(array $history): array
    {
        return array_map(fn($msg) => AiMessageData::from($msg), $history);
    }

    private function determinePhase(array $conversation, array $extractedData): string
    {
        $messageCount = count($conversation);

        if ($messageCount <= 3) return 'initial';
        if (empty($extractedData['variants'])) return 'variants';
        if (empty($extractedData['modifiers'])) return 'modifiers';
        if (empty($extractedData['metadata'])) return 'metadata';

        return 'confirmation';
    }

    private function extractInteractiveElements(string $content): ?array
    {
        // Parse content for special markers indicating interactive elements
        // Example: [CHECKBOX: option1, option2, option3]
        // Example: [SELECT: size options|Small,Medium,Large]

        $elements = [];

        if (preg_match_all('/\[CHECKBOX: ([^\]]+)\]/i', $content, $matches)) {
            foreach ($matches[1] as $match) {
                $options = array_map('trim', explode(',', $match));
                $elements[] = [
                    'type' => 'checkbox',
                    'options' => $options,
                ];
            }
        }

        if (preg_match_all('/\[SELECT: ([^\|]+)\|([^\]]+)\]/i', $content, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $label = trim($matches[1][$i]);
                $options = array_map('trim', explode(',', $matches[2][$i]));
                $elements[] = [
                    'type' => 'select',
                    'label' => $label,
                    'options' => $options,
                ];
            }
        }

        return empty($elements) ? null : $elements;
    }

    private function extractPhaseData(string $phase, array $conversation, array $currentData): array
    {
        $latestMessages = array_slice($conversation, -3);

        switch ($phase) {
            case 'variants':
                $variants = $this->foodIntelligence->extractVariants(
                    ['conversation' => $latestMessages]
                );
                $currentData['variants'] = $variants->toArray();
                break;

            case 'modifiers':
                $modifiers = $this->foodIntelligence->extractModifiers(
                    ['conversation' => $latestMessages]
                );
                $currentData['modifiers'] = $modifiers->toArray();
                break;

            case 'metadata':
                // Extract allergens, nutritional info, etc.
                $currentData['metadata'] = $this->extractMetadata($latestMessages);
                break;
        }

        return $currentData;
    }

    private function generatePhaseSpecificPrompt(string $phase, array $extractedData, array $conversation): string
    {
        return $this->promptService->generatePhasePrompt(
            $phase,
            $this->extractItemName($conversation),
            $extractedData,
            $this->extractUserResponses($conversation)
        );
    }

    private function extractItemName(array $conversation): string
    {
        // Extract item name from initial conversation
        foreach ($conversation as $msg) {
            if ($msg->role === 'user' && str_contains($msg->content, 'item:')) {
                preg_match('/item:\s*([^,\n]+)/i', $msg->content, $matches);
                return $matches[1] ?? 'Unknown Item';
            }
        }
        return 'Unknown Item';
    }

    private function extractItemDescription(array $conversation): ?string
    {
        foreach ($conversation as $msg) {
            if ($msg->role === 'user' && str_contains($msg->content, 'description:')) {
                preg_match('/description:\s*([^\n]+)/i', $msg->content, $matches);
                return $matches[1] ?? null;
            }
        }
        return null;
    }

    private function extractUserResponses(array $conversation): array
    {
        return array_values(array_filter(
            array_map(fn($msg) => $msg->role === 'user' ? $msg->content : null, $conversation)
        ));
    }

    private function extractMetadata(array $messages): array
    {
        // Extract metadata from recent messages
        $metadata = [];

        foreach ($messages as $msg) {
            if (str_contains(strtolower($msg->content), 'allergen')) {
                $metadata['allergens'] = $this->extractAllergens($msg->content);
            }
            if (str_contains(strtolower($msg->content), 'calorie') || str_contains(strtolower($msg->content), 'nutrition')) {
                $metadata['nutritional'] = $this->extractNutritionalInfo($msg->content);
            }
        }

        return $metadata;
    }

    private function extractAllergens(string $content): array
    {
        $commonAllergens = ['gluten', 'dairy', 'nuts', 'soy', 'eggs', 'shellfish', 'fish'];
        $found = [];

        foreach ($commonAllergens as $allergen) {
            if (stripos($content, $allergen) !== false) {
                $found[] = $allergen;
            }
        }

        return $found;
    }

    private function extractNutritionalInfo(string $content): array
    {
        $nutritional = [];

        if (preg_match('/(\d+)\s*calories?/i', $content, $matches)) {
            $nutritional['calories'] = (int) $matches[1];
        }

        return $nutritional;
    }

    private function calculateConfidenceScores(
        DataCollection $variants,
        DataCollection $modifiers,
        array $extractedData
    ): array
    {
        $scores = [];

        // Calculate variant confidence
        if ($variants->count() > 0) {
            $variantConfidences = $variants->map(fn($v) => $v->confidence)->toArray();
            $scores['variants'] = array_sum($variantConfidences) / count($variantConfidences);
        }

        // Calculate modifier confidence
        if ($modifiers->count() > 0) {
            $modifierConfidences = $modifiers->map(fn($m) => $m->confidence)->toArray();
            $scores['modifiers'] = array_sum($modifierConfidences) / count($modifierConfidences);
        }

        // Calculate metadata confidence
        $scores['metadata'] = !empty($extractedData['metadata']) ? 0.85 : 0.0;

        // Overall confidence
        $scores['overall'] = array_sum($scores) / count($scores);

        return $scores;
    }
}