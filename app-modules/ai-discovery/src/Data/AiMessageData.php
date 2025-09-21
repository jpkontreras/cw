<?php

namespace Colame\AiDiscovery\Data;

use App\Core\Data\BaseData;

class AiMessageData extends BaseData
{
    public function __construct(
        public readonly string $role, // 'system', 'user', or 'assistant'
        public readonly string $content,
        public readonly ?array $interactiveElements = null,
        public readonly ?array $selections = null,
    ) {}

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
            'interactive_elements' => $this->interactiveElements,
            'selections' => $this->selections,
        ];
    }
}