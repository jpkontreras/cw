<?php

namespace Colame\AiDiscovery\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\AiDiscovery\Contracts\AiDiscoveryInterface;
use Colame\AiDiscovery\Contracts\FoodIntelligenceInterface;
use Colame\AiDiscovery\Contracts\SimilarityCacheInterface;
use Colame\AiDiscovery\Data\DiscoverySessionData;
use Colame\AiDiscovery\Data\ConversationContextData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AiDiscoveryController extends Controller
{
    public function __construct(
        private AiDiscoveryInterface $aiDiscovery,
        private FoodIntelligenceInterface $foodIntelligence,
        private SimilarityCacheInterface $similarityCache
    ) {}

    /**
     * Start a new AI discovery session
     */
    public function startSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'item_description' => 'nullable|string|max:1000',
            'cuisine_type' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'price_tier' => 'nullable|in:low,medium,high',
            'category' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check for similar items in cache first
            $cachedMatch = $this->similarityCache->findSimilar(
                $request->item_name,
                80.0,
                $request->category
            );

            // Start discovery session
            $session = $this->aiDiscovery->startDiscovery(
                $request->item_name,
                [
                    'cuisine_type' => $request->cuisine_type ?? 'general',
                    'location' => $request->location ?? 'Chile',
                    'price_tier' => $request->price_tier ?? 'medium',
                    'category' => $request->category,
                ],
                $request->item_description
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'session' => $session->toArray(),
                    'cached_matches' => $cachedMatch?->toArray(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start discovery session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process user response in the discovery session
     */
    public function processResponse(Request $request, string $sessionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'response' => 'required|string',
            'selections' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $context = $this->aiDiscovery->processUserResponse(
                $sessionId,
                $request->response,
                $request->selections
            );

            return response()->json([
                'success' => true,
                'data' => $context->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process response',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream AI response for real-time updates
     */
    public function streamResponse(Request $request, string $sessionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'input' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $chunks = [];

            $this->aiDiscovery->streamResponse(
                $sessionId,
                $request->input,
                function($chunk) use (&$chunks) {
                    $chunks[] = $chunk;
                }
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'response' => implode('', $chunks),
                    'chunks' => $chunks,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stream response',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete the discovery session and get final data
     */
    public function completeSession(string $sessionId): JsonResponse
    {
        try {
            $session = $this->aiDiscovery->completeDiscovery($sessionId);

            return response()->json([
                'success' => true,
                'data' => $session->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get conversation context for a session
     */
    public function getContext(string $sessionId): JsonResponse
    {
        try {
            $context = $this->aiDiscovery->getConversationContext($sessionId);

            return response()->json([
                'success' => true,
                'data' => $context->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get context',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resume an existing session
     */
    public function resumeSession(string $sessionId): JsonResponse
    {
        try {
            $context = $this->aiDiscovery->resumeSession($sessionId);

            return response()->json([
                'success' => true,
                'data' => $context->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Abandon a session
     */
    public function abandonSession(string $sessionId): JsonResponse
    {
        try {
            $this->aiDiscovery->abandonSession($sessionId);

            return response()->json([
                'success' => true,
                'message' => 'Session abandoned successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to abandon session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's active sessions
     */
    public function getUserSessions(): JsonResponse
    {
        try {
            $userId = Auth::id() ?? 1; // Default to 1 for testing
            $sessions = $this->aiDiscovery->getUserActiveSessions($userId);

            return response()->json([
                'success' => true,
                'data' => $sessions->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user sessions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search for similar items in cache
     */
    public function searchSimilar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'threshold' => 'nullable|numeric|min:0|max:100',
            'category' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $matches = $this->similarityCache->findSimilar(
                $request->item_name,
                $request->threshold ?? 80.0,
                $request->category
            );

            return response()->json([
                'success' => true,
                'data' => $matches->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search similar items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze item structure using AI
     */
    public function analyzeItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $analysis = $this->foodIntelligence->analyzeItemStructure(
                $request->item_name,
                $request->description,
                $request->category
            );

            return response()->json([
                'success' => true,
                'data' => $analysis,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pricing suggestions for an item
     */
    public function getPricingSuggestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'price_tier' => 'nullable|in:low,medium,high',
            'competitor_prices' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $suggestions = $this->foodIntelligence->suggestPricingStrategy(
                ['name' => $request->item_name, 'category' => $request->category],
                [
                    'location' => $request->location ?? 'Chile',
                    'price_tier' => $request->price_tier ?? 'medium',
                ],
                $request->competitor_prices
            );

            return response()->json([
                'success' => true,
                'data' => $suggestions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get pricing suggestions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Identify allergens for an item
     */
    public function identifyAllergens(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'ingredients' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $allergens = $this->foodIntelligence->identifyAllergens(
                $request->item_name,
                $request->description,
                $request->ingredients
            );

            return response()->json([
                'success' => true,
                'data' => $allergens,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to identify allergens',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): JsonResponse
    {
        try {
            $stats = $this->similarityCache->getCacheStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cache statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Learn from user feedback
     */
    public function submitFeedback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'original_data' => 'required|array',
            'corrected_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->foodIntelligence->learnFromFeedback(
                $request->item_name,
                $request->original_data,
                $request->corrected_data
            );

            return response()->json([
                'success' => true,
                'message' => 'Feedback recorded successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record feedback',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}