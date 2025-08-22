<?php

declare(strict_types=1);

namespace Colame\Onboarding\Contracts;

use Colame\Onboarding\Data\OnboardingProgressData;

interface OnboardingRepositoryInterface
{
    /**
     * Get onboarding progress for a user
     */
    public function getProgressByUserId(int $userId): ?OnboardingProgressData;
    
    /**
     * Create or update onboarding progress
     */
    public function saveProgress(int $userId, array $data): OnboardingProgressData;
    
    /**
     * Mark a step as completed
     */
    public function markStepCompleted(int $userId, string $stepIdentifier): OnboardingProgressData;
    
    /**
     * Update the current step
     */
    public function updateCurrentStep(int $userId, string $stepIdentifier): OnboardingProgressData;
    
    /**
     * Save temporary step data
     */
    public function saveStepData(int $userId, string $stepIdentifier, array $data): OnboardingProgressData;
    
    /**
     * Mark onboarding as completed
     */
    public function markCompleted(int $userId): OnboardingProgressData;
    
    /**
     * Skip onboarding with reason
     */
    public function skipOnboarding(int $userId, string $reason): OnboardingProgressData;
    
    /**
     * Check if user has completed onboarding
     */
    public function isOnboardingCompleted(int $userId): bool;
    
    /**
     * Get all step data for a user
     */
    public function getAllStepData(int $userId): array;
    
    /**
     * Reset onboarding progress for a user
     */
    public function resetProgress(int $userId): bool;
}