<?php

declare(strict_types=1);

namespace Colame\OrderEs\Commands;

use Illuminate\Support\Str;

// Session Commands
final readonly class InitiateSession
{
    public string $sessionId;
    
    public function __construct(
        public ?int $userId,
        public int $locationId,
        public array $deviceInfo = [],
        public ?string $referrer = null,
        public array $metadata = [],
        ?string $sessionId = null
    ) {
        $this->sessionId = $sessionId ?? (string) Str::uuid();
    }
}