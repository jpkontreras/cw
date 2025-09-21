<?php

namespace Colame\AiDiscovery\Contracts;

use Colame\AiDiscovery\Data\ConversationContextData;
use Colame\AiDiscovery\Data\DiscoverySessionData;
use Colame\AiDiscovery\Data\SimilarityMatchData;
use Spatie\LaravelData\DataCollection;

interface AiDiscoveryInterface
{
    /**
     * Start a new discovery session for an item
     */
    public function startDiscovery(
        string $itemName,
        array $context,
        ?string $itemDescription = null
    ): DiscoverySessionData;

    /**
     * Process user response in the discovery session
     */
    public function processUserResponse(
        string $sessionId,
        string $response,
        ?array $selections = null
    ): ConversationContextData;

    /**
     * Get suggestions from cache based on item similarity
     */
    public function suggestFromCache(
        string $itemName,
        float $threshold = 80.0
    ): ?SimilarityMatchData;

    /**
     * Complete the discovery session and extract final data
     */
    public function completeDiscovery(string $sessionId): DiscoverySessionData;

    /**
     * Get conversation context for a session
     */
    public function getConversationContext(string $sessionId): ConversationContextData;

    /**
     * Generate next AI prompt based on current context
     */
    public function generateNextPrompt(ConversationContextData $context): string;

    /**
     * Stream AI response for real-time UI updates
     */
    public function streamResponse(
        string $sessionId,
        string $userInput,
        callable $onChunk
    ): void;

    /**
     * Get all active discovery sessions for a user
     */
    public function getUserActiveSessions(int $userId): DataCollection;

    /**
     * Abandon a discovery session
     */
    public function abandonSession(string $sessionId): void;

    /**
     * Resume an existing discovery session
     */
    public function resumeSession(string $sessionId): ConversationContextData;
}