<?php

declare(strict_types=1);

namespace Colame\Onboarding\Traits;

use Colame\Onboarding\Models\OnboardingProgress;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Onboard\Concerns\GetsOnboarded;

trait HasOnboarding
{
    use GetsOnboarded;
    
    /**
     * Get the onboarding progress for the user.
     */
    public function onboardingProgress(): HasOne
    {
        return $this->hasOne(OnboardingProgress::class, 'user_id');
    }
    
    /**
     * Check if the user has completed onboarding.
     */
    public function hasCompletedOnboarding(): bool
    {
        return $this->onboardingProgress?->is_completed ?? false;
    }
    
    /**
     * Check if the user needs onboarding.
     */
    public function needsOnboarding(): bool
    {
        return !$this->hasCompletedOnboarding();
    }
    
    /**
     * Mark onboarding as completed.
     */
    public function completeOnboarding(): void
    {
        $progress = $this->onboardingProgress()->firstOrCreate([
            'user_id' => $this->id,
        ], [
            'step' => 'complete',
            'completed_steps' => ['account', 'business', 'location', 'configuration'],
            'data' => [],
        ]);
        
        $progress->markAsCompleted();
        $progress->save();
    }
    
    /**
     * Configure the onboarding steps.
     */
    public function configureOnboarding(): \Spatie\Onboard\OnboardingManager
    {
        return $this->onboarding()
            ->addStep('Complete Account Setup')
                ->link('/onboarding/account')
                ->cta('Setup Account')
                ->completeIf(function () {
                    return $this->onboardingProgress?->hasCompletedStep('account') ?? false;
                })
            ->addStep('Add Business Information')
                ->link('/onboarding/business')
                ->cta('Add Business')
                ->completeIf(function () {
                    return $this->onboardingProgress?->hasCompletedStep('business') ?? false;
                })
            ->addStep('Configure Location')
                ->link('/onboarding/location')
                ->cta('Setup Location')
                ->completeIf(function () {
                    return $this->onboardingProgress?->hasCompletedStep('location') ?? false;
                })
            ->addStep('System Configuration')
                ->link('/onboarding/configuration')
                ->cta('Configure System')
                ->completeIf(function () {
                    return $this->onboardingProgress?->hasCompletedStep('configuration') ?? false;
                });
    }
}