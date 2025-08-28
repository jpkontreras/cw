<?php

declare(strict_types=1);

namespace Colame\Onboarding\Services;

use App\Models\User;
use Colame\Business\Contracts\BusinessServiceInterface;
use Colame\Business\Data\CreateBusinessData;
use Colame\Location\Contracts\LocationRepositoryInterface;
use Colame\Location\Data\CreateLocationData;
use Colame\Location\Services\UserLocationService;
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
use Illuminate\Support\Str;

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
        private ?BusinessServiceInterface $businessService = null,
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
            $user->email = $data->email;
            $user->phone = $data->phone;

            // Update password if provided (for new users)
            if ($data->password) {
                $user->password = Hash::make($data->password);
            }

            $user->save();
        }

        // Generate employee code if not provided
        $dataArray = $data->toArray();
        if (empty($dataArray['employeeCode'])) {
            $dataArray['employeeCode'] = 'EMP' . str_pad((string)$userId, 5, '0', STR_PAD_LEFT);
        }

        // Save account data to temporary storage
        $this->repository->saveStepData($userId, 'account', $dataArray);

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
            // Update or create user account
            $user = User::find($userId);
            if (!$user && isset($allData['account'])) {
                $accountData = $allData['account'];
                $user = User::create([
                    'name' => $accountData['firstName'] . ' ' . $accountData['lastName'],
                    'email' => $accountData['email'],
                    'password' => isset($accountData['password']) ? Hash::make($accountData['password']) : Hash::make(uniqid()),
                    'phone' => $accountData['phone'] ?? null,
                ]);
                $userId = $user->id;
            }

            // Create the business if we have the service
            $businessId = null;
            if ($this->businessService && isset($allData['business'])) {
                $businessData = $allData['business'];

                // Generate a unique slug from the business name
                $baseSlug = Str::slug($businessData['businessName']);
                $slug = $baseSlug;
                $counter = 1;

                // Ensure slug is unique (basic check - BusinessService will validate)
                while ($counter < 100) {
                    try {
                        $createBusinessData = CreateBusinessData::from([
                            'name' => $businessData['businessName'],
                            'slug' => $slug,
                            'type' => $businessData['businessType'] ?? 'independent',
                            'legalName' => !empty($businessData['legalName']) ? $businessData['legalName'] : $businessData['businessName'],
                            'taxId' => !empty($businessData['taxId']) ? $businessData['taxId'] : null,
                            'email' => !empty($businessData['businessEmail']) ? $businessData['businessEmail'] : ($allData['account']['email'] ?? $user->email),
                            'phone' => !empty($businessData['businessPhone']) ? $businessData['businessPhone'] : ($allData['account']['phone'] ?? null),
                            'website' => !empty($businessData['website']) ? $businessData['website'] : null,
                            'description' => !empty($businessData['description']) ? $businessData['description'] : null,
                            'ownerId' => $userId,
                            'country' => 'CL', // Add required country
                            'currency' => 'CLP', // Add required currency
                            'timezone' => 'America/Santiago', // Add required timezone
                            'locale' => 'es_CL', // Add required locale
                        ]);

                        $business = $this->businessService->createBusiness($createBusinessData);
                        $businessId = $business->id;

                        // Owner is already added by createBusiness method in repository
                        // No need to add user again

                        // Create default subscription for the business
                        $this->businessService->createSubscription($businessId, [
                            'tier' => 'basic', // Default to basic plan (valid enum value)
                            'status' => 'active',
                            'startsAt' => now(),
                            'trialEndsAt' => now()->addDays(30), // 30-day trial
                        ]);

                        // Set as current business for the user
                        DB::table('user_business_preferences')->updateOrInsert(
                            ['user_id' => $userId],
                            [
                                'current_business_id' => $businessId,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]
                        );

                        break;
                    } catch (\Exception $e) {
                        // If slug exists, try again with a counter
                        if (str_contains($e->getMessage(), 'slug')) {
                            $counter++;
                            $slug = $baseSlug . '-' . $counter;
                        } else {
                            throw $e;
                        }
                    }
                }
            }

            // Assign role to user
            if ($user && isset($allData['account']['primaryRole'])) {
                // Assuming using Spatie Permission package
                // $user->assignRole($allData['account']['primaryRole']);
            }

            // Create location if we have the repository
            if ($this->locationRepository && isset($allData['location'])) {
                $locationDataArray = $allData['location'];

                // Generate unique location code if not provided
                if (empty($locationDataArray['code'])) {
                    // Use timestamp + user ID for uniqueness
                    $locationDataArray['code'] = 'LOC' . str_pad((string)($userId), 3, '0', STR_PAD_LEFT) . '_' . time();
                }

                // Map some fields from business data
                if (isset($allData['business'])) {
                    $locationDataArray['email'] = $locationDataArray['email'] ?? $allData['business']['businessEmail'] ?? $allData['account']['email'];
                }

                // Set manager to current user
                $locationDataArray['managerId'] = $userId;

                // Link to business if created
                if ($businessId !== null) {
                    $locationDataArray['businessId'] = $businessId;
                }

                $locationData = CreateLocationData::from($locationDataArray);
                $location = $this->locationRepository->create($locationData->toArray());

                // Associate user with location using UserLocationService
                $userLocationService = app(UserLocationService::class);
                $userLocationService->attachUserToLocation(
                    $user,
                    $location->id,
                    'manager', // Owner of the business is the location manager
                    true // is_primary
                );

                // Set as default and current location
                $userLocationService->setDefaultLocation($user, $location->id);
                $userLocationService->setCurrentLocation($user, $location->id);
            }

            // Save organization settings if we have the service
            // Temporarily disabled - settings service needs to be properly configured
            if (false && $this->settingService) {
                $settings = [];

                // Business settings
                if (isset($allData['business'])) {
                    $businessData = $allData['business'];
                    $settings['organization.business_name'] = $businessData['businessName'];
                    $settings['organization.legal_name'] = $businessData['legalName'];
                    $settings['organization.tax_id'] = $businessData['taxId'];
                    $settings['organization.email'] = $businessData['businessEmail'];
                    $settings['organization.phone'] = $businessData['businessPhone'];
                    $settings['organization.website'] = $businessData['website'] ?? null;
                    $settings['organization.fax'] = $businessData['fax'] ?? null;
                }

                // Location settings for organization address
                if (isset($allData['location'])) {
                    $locationData = $allData['location'];
                    $settings['organization.address'] = $locationData['address'];
                    $settings['organization.address_line_2'] = $locationData['addressLine2'] ?? null;
                    $settings['organization.city'] = $locationData['city'];
                    $settings['organization.state'] = $locationData['state'];
                    $settings['organization.postal_code'] = $locationData['postalCode'];
                    $settings['organization.country'] = $locationData['country'] ?? 'CL';
                }

                // Configuration settings
                if (isset($allData['configuration'])) {
                    $configData = $allData['configuration'];
                    $settings['localization.currency'] = $configData['currency'];
                    $settings['localization.timezone'] = $configData['timezone'];
                    $settings['localization.date_format'] = $configData['dateFormat'];
                    $settings['localization.time_format'] = $configData['timeFormat'];
                    $settings['localization.language'] = $configData['language'];
                    $settings['localization.decimal_separator'] = $configData['decimalSeparator'] ?? ',';
                    $settings['localization.thousands_separator'] = $configData['thousandsSeparator'] ?? '.';
                    $settings['localization.first_day_of_week'] = $configData['firstDayOfWeek'] ?? 1;

                    // Order settings
                    $settings['orders.prefix'] = $configData['orderPrefix'] ?? null;
                    $settings['orders.require_customer_phone'] = $configData['requireCustomerPhone'] ?? true;
                    $settings['orders.print_automatically'] = $configData['printAutomatically'] ?? false;
                    $settings['orders.auto_confirm'] = $configData['autoConfirmOrders'] ?? false;

                    // Tip settings
                    $settings['tips.enabled'] = $configData['enableTips'] ?? true;
                    $settings['tips.options'] = json_encode($configData['tipOptions'] ?? [10, 15, 20]);

                    // Notification settings
                    $settings['notifications.email'] = $configData['emailNotifications'] ?? true;
                    $settings['notifications.sms'] = $configData['smsNotifications'] ?? false;
                    $settings['notifications.push'] = $configData['pushNotifications'] ?? false;

                    // Branding
                    $settings['organization.logo_url'] = $configData['logoUrl'] ?? null;
                    $settings['branding.primary_color'] = $configData['primaryColor'] ?? null;
                    $settings['branding.secondary_color'] = $configData['secondaryColor'] ?? null;
                }

                foreach ($settings as $key => $value) {
                    if ($value !== null) {
                        $this->settingService->set($key, $value);
                    }
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
