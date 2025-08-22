<?php

declare(strict_types=1);

namespace Colame\Onboarding\Services;

use App\Models\User;
use Colame\Location\Contracts\LocationRepositoryInterface;
use Colame\Location\Data\CreateLocationData;
use Colame\Onboarding\Contracts\OnboardingRepositoryInterface;
use Colame\Onboarding\Contracts\OnboardingServiceInterface;
use Colame\Onboarding\Data\AccountSetupData;
use Colame\Onboarding\Data\BusinessSetupData;
use Colame\Onboarding\Data\CompleteOnboardingData;
use Colame\Onboarding\Data\ConfigurationSetupData;
use Colame\Onboarding\Data\LocationSetupData;
use Colame\Onboarding\Data\OnboardingProgressData;
use Colame\Settings\Contracts\SettingServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OnboardingService implements OnboardingServiceInterface
{
    private array $steps = [
        'account' => 1,
        'business' => 2,
        'location' => 3,
        'configuration' => 4,
    ];
    
    public function __construct(
        private OnboardingRepositoryInterface $repository,
        private ?LocationRepositoryInterface $locationRepository = null,
        private ?SettingServiceInterface $settingService = null,
    ) {}
    
    public function getProgress(int $userId): ?OnboardingProgressData
    {
        return $this->repository->getProgressByUserId($userId);
    }
    
    public function processAccountSetup(int $userId, AccountSetupData $data): OnboardingProgressData
    {
        // Update the user's information
        $user = User::find($userId);
        if ($user) {
            $user->name = $data->getFullName();
            if (!empty($data->phone)) {
                $user->phone = $data->phone;
            }
            $user->save();
        }
        
        // Save account data to temporary storage
        $this->repository->saveStepData($userId, 'account', $data->toArray());
        
        // Mark step as completed
        $progress = $this->repository->markStepCompleted($userId, 'account');
        
        // Update current step to next
        $nextStep = $this->getNextStep($userId);
        if ($nextStep) {
            $progress = $this->repository->updateCurrentStep($userId, $nextStep);
        }
        
        return $progress;
    }
    
    public function processBusinessSetup(int $userId, BusinessSetupData $data): OnboardingProgressData
    {
        // Save business data
        $this->repository->saveStepData($userId, 'business', $data->toArray());
        
        // Mark step as completed
        $progress = $this->repository->markStepCompleted($userId, 'business');
        
        // Update to next step
        $nextStep = $this->getNextStep($userId);
        if ($nextStep) {
            $progress = $this->repository->updateCurrentStep($userId, $nextStep);
        }
        
        return $progress;
    }
    
    public function processLocationSetup(int $userId, LocationSetupData $data): OnboardingProgressData
    {
        // Save location data
        $this->repository->saveStepData($userId, 'location', $data->toArray());
        
        // Mark step as completed
        $progress = $this->repository->markStepCompleted($userId, 'location');
        
        // Update to next step
        $nextStep = $this->getNextStep($userId);
        if ($nextStep) {
            $progress = $this->repository->updateCurrentStep($userId, $nextStep);
        }
        
        return $progress;
    }
    
    public function processConfigurationSetup(int $userId, ConfigurationSetupData $data): OnboardingProgressData
    {
        // Save configuration data
        $this->repository->saveStepData($userId, 'configuration', $data->toArray());
        
        // Mark step as completed
        $progress = $this->repository->markStepCompleted($userId, 'configuration');
        
        // Check if all required steps are completed
        if ($this->areAllRequiredStepsCompleted($userId)) {
            $progress = $this->repository->updateCurrentStep($userId, 'complete');
        }
        
        return $progress;
    }
    
    public function completeOnboarding(int $userId): CompleteOnboardingData
    {
        $allData = $this->repository->getAllStepData($userId);
        
        if (!$this->areAllRequiredStepsCompleted($userId)) {
            throw new \Exception('Not all required onboarding steps are completed');
        }
        
        DB::beginTransaction();
        
        try {
            // Create user account if needed
            $user = User::find($userId);
            if (!$user && isset($allData['account'])) {
                $accountData = $allData['account'];
                $user = User::create([
                    'name' => $accountData['firstName'] . ' ' . $accountData['lastName'],
                    'email' => $accountData['email'],
                    'password' => Hash::make($accountData['password']),
                ]);
                $userId = $user->id;
            }
            
            // Create location if we have the repository
            if ($this->locationRepository && isset($allData['location'])) {
                $locationData = CreateLocationData::from($allData['location']);
                $location = $this->locationRepository->create($locationData->toArray());
                
                // Associate user with location
                $user->locations()->attach($location->id, [
                    'role' => 'owner',
                    'is_primary' => true,
                ]);
                
                // Set as default and current location
                $user->update([
                    'default_location_id' => $location->id,
                    'current_location_id' => $location->id,
                ]);
            }
            
            // Save organization settings if we have the service
            if ($this->settingService && isset($allData['business'], $allData['configuration'])) {
                $businessData = $allData['business'];
                $configData = $allData['configuration'];
                
                $settings = [
                    'organization.business_name' => $businessData['businessName'],
                    'organization.legal_name' => $businessData['legalName'] ?? $businessData['businessName'],
                    'organization.tax_id' => $businessData['taxId'],
                    'localization.currency' => $configData['currency'],
                    'localization.timezone' => $configData['timezone'],
                    'localization.date_format' => $configData['dateFormat'],
                    'localization.time_format' => $configData['timeFormat'],
                    'localization.language' => $configData['language'],
                ];
                
                foreach ($settings as $key => $value) {
                    $this->settingService->set($key, $value);
                }
            }
            
            // Mark onboarding as completed
            $this->repository->markCompleted($userId);
            
            DB::commit();
            
            return CompleteOnboardingData::fromSteps($allData);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function getNextStep(int $userId): ?string
    {
        $progress = $this->repository->getProgressByUserId($userId);
        
        if (!$progress) {
            return 'account';
        }
        
        $completedSteps = $progress->completedSteps;
        
        foreach ($this->steps as $step => $order) {
            if (!in_array($step, $completedSteps)) {
                return $step;
            }
        }
        
        return null;
    }
    
    public function getAvailableSteps(): array
    {
        return array_keys($this->steps);
    }
    
    public function needsOnboarding(int $userId): bool
    {
        return !$this->repository->isOnboardingCompleted($userId);
    }
    
    public function skipOnboarding(int $userId, string $reason): void
    {
        $this->repository->skipOnboarding($userId, $reason);
    }
    
    public function resetOnboarding(int $userId): void
    {
        $this->repository->resetProgress($userId);
    }
    
    private function areAllRequiredStepsCompleted(int $userId): bool
    {
        $progress = $this->repository->getProgressByUserId($userId);
        
        if (!$progress) {
            return false;
        }
        
        $requiredSteps = array_keys($this->steps);
        $completedSteps = $progress->completedSteps;
        
        foreach ($requiredSteps as $step) {
            if (!in_array($step, $completedSteps)) {
                return false;
            }
        }
        
        return true;
    }
}