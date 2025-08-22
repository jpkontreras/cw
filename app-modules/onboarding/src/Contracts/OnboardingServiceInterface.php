<?php

declare(strict_types=1);

namespace Colame\Onboarding\Contracts;

use Colame\Onboarding\Data\AccountSetupData;
use Colame\Onboarding\Data\BusinessSetupData;
use Colame\Onboarding\Data\CompleteOnboardingData;
use Colame\Onboarding\Data\ConfigurationSetupData;
use Colame\Onboarding\Data\LocationSetupData;
use Colame\Onboarding\Data\OnboardingProgressData;

interface OnboardingServiceInterface
{
    /**
     * Get the current onboarding progress for a user
     */
    public function getProgress(int $userId): ?OnboardingProgressData;
    
    /**
     * Process account setup step
     */
    public function processAccountSetup(int $userId, AccountSetupData $data): OnboardingProgressData;
    
    /**
     * Process business setup step
     */
    public function processBusinessSetup(int $userId, BusinessSetupData $data): OnboardingProgressData;
    
    /**
     * Process location setup step
     */
    public function processLocationSetup(int $userId, LocationSetupData $data): OnboardingProgressData;
    
    /**
     * Process configuration setup step
     */
    public function processConfigurationSetup(int $userId, ConfigurationSetupData $data): OnboardingProgressData;
    
    /**
     * Complete the onboarding process
     */
    public function completeOnboarding(int $userId): CompleteOnboardingData;
    
    /**
     * Get the next step for a user
     */
    public function getNextStep(int $userId): ?string;
    
    /**
     * Get all available onboarding steps
     */
    public function getAvailableSteps(): array;
    
    /**
     * Check if a user needs onboarding
     */
    public function needsOnboarding(int $userId): bool;
    
    /**
     * Skip onboarding for a user
     */
    public function skipOnboarding(int $userId, string $reason): void;
    
    /**
     * Reset onboarding for a user
     */
    public function resetOnboarding(int $userId): void;
}