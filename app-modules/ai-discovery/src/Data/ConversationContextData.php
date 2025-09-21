<?php

namespace Colame\AiDiscovery\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\Required;

class ConversationContextData extends BaseData
{
    public function __construct(
        #[Required] public readonly string $sessionId,
        #[Required] public readonly string $currentPhase, // initial, variants, modifiers, metadata, confirmation
        public readonly string $itemName,
        public readonly ?string $itemDescription,
        public readonly ?string $itemCategory,
        public readonly array $collectedData = [],
        public readonly array $pendingQuestions = [],
        public readonly array $userResponses = [],
        public readonly ?array $suggestedOptions = null,
        public readonly int $interactionCount = 0,
        public readonly ?string $lastUserInput = null,
        public readonly ?string $nextPrompt = null,
    ) {}

    #[Computed]
    public function isInInitialPhase(): bool
    {
        return $this->currentPhase === 'initial';
    }

    #[Computed]
    public function isInVariantsPhase(): bool
    {
        return $this->currentPhase === 'variants';
    }

    #[Computed]
    public function isInModifiersPhase(): bool
    {
        return $this->currentPhase === 'modifiers';
    }

    #[Computed]
    public function isInMetadataPhase(): bool
    {
        return $this->currentPhase === 'metadata';
    }

    #[Computed]
    public function isInConfirmationPhase(): bool
    {
        return $this->currentPhase === 'confirmation';
    }

    #[Computed]
    public function progressPercentage(): int
    {
        $phases = ['initial' => 20, 'variants' => 40, 'modifiers' => 60, 'metadata' => 80, 'confirmation' => 100];
        return $phases[$this->currentPhase] ?? 0;
    }

    #[Computed]
    public function hasCollectedData(): bool
    {
        return !empty($this->collectedData);
    }
}

class AiMessageData extends BaseData
{
    public function __construct(
        #[Required] public readonly string $role, // system, user, assistant
        #[Required] public readonly string $content,
        public readonly ?array $interactiveElements = null, // checkboxes, selects, etc.
        public readonly ?array $metadata = null,
        public readonly ?string $timestamp = null,
    ) {}

    #[Computed]
    public function isSystemMessage(): bool
    {
        return $this->role === 'system';
    }

    #[Computed]
    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }

    #[Computed]
    public function isAssistantMessage(): bool
    {
        return $this->role === 'assistant';
    }

    #[Computed]
    public function hasInteractiveElements(): bool
    {
        return !empty($this->interactiveElements);
    }
}