<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Exception;

/**
 * Base exception for all module-specific exceptions
 */
abstract class ModuleException extends Exception
{
    /**
     * Error code for API responses
     */
    protected string $errorCode = 'MODULE_ERROR';

    /**
     * HTTP status code
     */
    protected int $statusCode = 400;

    /**
     * Additional context data
     */
    protected array $context = [];

    /**
     * Get the error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get additional context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set additional context
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Convert to array for API response
     */
    public function toArray(): array
    {
        return [
            'error' => $this->getErrorCode(),
            'message' => $this->getMessage(),
            'context' => $this->getContext(),
        ];
    }
}