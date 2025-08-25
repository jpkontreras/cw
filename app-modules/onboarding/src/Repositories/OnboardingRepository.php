<?php

declare(strict_types=1);

namespace Colame\Onboarding\Repositories;

use Colame\Onboarding\Contracts\OnboardingRepositoryInterface;
use Colame\Onboarding\Data\OnboardingProgressData;
use Colame\Onboarding\Models\OnboardingProgress;

class OnboardingRepository implements OnboardingRepositoryInterface
{
    public function getProgressByUserId(int $userId): ?OnboardingProgressData
    {
        $progress = OnboardingProgress::where('user_id', $userId)->first();
        
        return $progress ? OnboardingProgressData::from($progress) : null;
    }
    
    public function saveProgress(int $userId, array $data): OnboardingProgressData
    {
        $progress = OnboardingProgress::updateOrCreate(
            ['user_id' => $userId],
            $data
        );
        
        return OnboardingProgressData::from($progress);
    }
    
    public function markStepCompleted(int $userId, string $stepIdentifier): OnboardingProgressData
    {
        $progress = OnboardingProgress::firstOrCreate(
            ['user_id' => $userId],
            [
                'step' => $stepIdentifier,
                'completed_steps' => [],
                'data' => [],
                'is_completed' => false,
            ]
        );
        
        $progress->addCompletedStep($stepIdentifier);
        $progress->save();
        
        return OnboardingProgressData::from($progress);
    }
    
    public function updateCurrentStep(int $userId, string $stepIdentifier): OnboardingProgressData
    {
        $progress = OnboardingProgress::firstOrCreate(
            ['user_id' => $userId],
            [
                'step' => $stepIdentifier,
                'completed_steps' => [],
                'data' => [],
                'is_completed' => false,
            ]
        );
        
        $progress->step = $stepIdentifier;
        $progress->save();
        
        return OnboardingProgressData::from($progress);
    }
    
    public function saveStepData(int $userId, string $stepIdentifier, array $data): OnboardingProgressData
    {
        $progress = OnboardingProgress::firstOrCreate(
            ['user_id' => $userId],
            [
                'step' => $stepIdentifier,
                'completed_steps' => [],
                'data' => [],
                'is_completed' => false,
            ]
        );
        
        $progress->setStepData($stepIdentifier, $data);
        $progress->save();
        
        return OnboardingProgressData::from($progress);
    }
    
    public function markCompleted(int $userId): OnboardingProgressData
    {
        $progress = OnboardingProgress::where('user_id', $userId)->firstOrFail();
        $progress->markAsCompleted();
        $progress->save();
        
        return OnboardingProgressData::from($progress);
    }
    
    public function skipOnboarding(int $userId, string $reason): OnboardingProgressData
    {
        $progress = OnboardingProgress::firstOrCreate(
            ['user_id' => $userId],
            [
                'step' => 'skipped',
                'completed_steps' => [],
                'data' => [],
                'is_completed' => false,
            ]
        );
        
        $progress->skip_reason = $reason;
        $progress->is_completed = true;
        $progress->completed_at = now();
        $progress->save();
        
        return OnboardingProgressData::from($progress);
    }
    
    public function isOnboardingCompleted(int $userId): bool
    {
        return OnboardingProgress::where('user_id', $userId)
            ->where('is_completed', true)
            ->exists();
    }
    
    public function getAllStepData(int $userId): array
    {
        $progress = OnboardingProgress::where('user_id', $userId)->first();
        
        return $progress ? ($progress->data ?? []) : [];
    }
    
    public function resetProgress(int $userId): bool
    {
        return OnboardingProgress::where('user_id', $userId)->delete() > 0;
    }
}