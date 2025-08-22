<?php

declare(strict_types=1);

namespace Colame\Onboarding\Contracts;

interface OnboardingStepInterface
{
    /**
     * Get the step identifier
     */
    public function getIdentifier(): string;
    
    /**
     * Get the step title
     */
    public function getTitle(): string;
    
    /**
     * Get the step description
     */
    public function getDescription(): string;
    
    /**
     * Check if the step is required
     */
    public function isRequired(): bool;
    
    /**
     * Get the step order
     */
    public function getOrder(): int;
    
    /**
     * Validate step data
     */
    public function validate(array $data): bool;
    
    /**
     * Process the step data
     */
    public function process(int $userId, array $data): bool;
    
    /**
     * Check if the step is completed for a user
     */
    public function isCompleted(int $userId): bool;
    
    /**
     * Get the step's frontend component name
     */
    public function getComponentName(): string;
}