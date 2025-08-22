<?php

declare(strict_types=1);

namespace Colame\Onboarding\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Onboarding\Contracts\OnboardingServiceInterface;
use Colame\Onboarding\Data\AccountSetupData;
use Colame\Onboarding\Data\BusinessSetupData;
use Colame\Onboarding\Data\ConfigurationSetupData;
use Colame\Onboarding\Data\LocationSetupData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function __construct(
        private OnboardingServiceInterface $onboardingService
    ) {}
    
    /**
     * Get onboarding progress
     */
    public function getProgress(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $progress = $this->onboardingService->getProgress($userId);
        
        return response()->json([
            'progress' => $progress?->toArray(),
            'nextStep' => $this->onboardingService->getNextStep($userId),
            'needsOnboarding' => $this->onboardingService->needsOnboarding($userId),
            'availableSteps' => $this->onboardingService->getAvailableSteps(),
        ]);
    }
    
    /**
     * Process account setup
     */
    public function processAccount(Request $request): JsonResponse
    {
        $data = AccountSetupData::validateAndCreate($request);
        $userId = $request->user()->id;
        
        $progress = $this->onboardingService->processAccountSetup($userId, $data);
        
        return response()->json([
            'success' => true,
            'progress' => $progress->toArray(),
            'nextStep' => $this->onboardingService->getNextStep($userId),
        ]);
    }
    
    /**
     * Process business setup
     */
    public function processBusiness(Request $request): JsonResponse
    {
        $data = BusinessSetupData::validateAndCreate($request);
        $userId = $request->user()->id;
        
        $progress = $this->onboardingService->processBusinessSetup($userId, $data);
        
        return response()->json([
            'success' => true,
            'progress' => $progress->toArray(),
            'nextStep' => $this->onboardingService->getNextStep($userId),
        ]);
    }
    
    /**
     * Process location setup
     */
    public function processLocation(Request $request): JsonResponse
    {
        $data = LocationSetupData::validateAndCreate($request);
        $userId = $request->user()->id;
        
        $progress = $this->onboardingService->processLocationSetup($userId, $data);
        
        return response()->json([
            'success' => true,
            'progress' => $progress->toArray(),
            'nextStep' => $this->onboardingService->getNextStep($userId),
        ]);
    }
    
    /**
     * Process configuration setup
     */
    public function processConfiguration(Request $request): JsonResponse
    {
        $data = ConfigurationSetupData::validateAndCreate($request);
        $userId = $request->user()->id;
        
        $progress = $this->onboardingService->processConfigurationSetup($userId, $data);
        
        return response()->json([
            'success' => true,
            'progress' => $progress->toArray(),
            'nextStep' => $this->onboardingService->getNextStep($userId),
        ]);
    }
    
    /**
     * Complete onboarding
     */
    public function complete(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        try {
            $completeData = $this->onboardingService->completeOnboarding($userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Onboarding completed successfully',
                'data' => $completeData->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Skip onboarding
     */
    public function skip(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);
        
        $userId = $request->user()->id;
        $this->onboardingService->skipOnboarding($userId, $request->input('reason'));
        
        return response()->json([
            'success' => true,
            'message' => 'Onboarding skipped',
        ]);
    }
    
    /**
     * Reset onboarding
     */
    public function reset(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $this->onboardingService->resetOnboarding($userId);
        
        return response()->json([
            'success' => true,
            'message' => 'Onboarding has been reset',
        ]);
    }
}