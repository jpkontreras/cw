<?php

declare(strict_types=1);

namespace Colame\Onboarding\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Onboarding\Contracts\OnboardingServiceInterface;
use Colame\Onboarding\Data\AccountSetupData;
use Colame\Onboarding\Data\BusinessSetupData;
use Colame\Onboarding\Data\ConfigurationSetupData;
use Colame\Onboarding\Data\LocationSetupData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        private OnboardingServiceInterface $onboardingService
    ) {}

    /**
     * Show the onboarding start page
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $userId = $request->user()->id;
        $progress = $this->onboardingService->getProgress($userId);
        $nextStep = $this->onboardingService->getNextStep($userId);

        if (!$nextStep) {
            // If onboarding is complete, redirect to dashboard
            return redirect()->route('dashboard');
        }

        return Inertia::render('onboarding/index', [
            'progress' => $progress?->toArray(),
            'nextStep' => $nextStep,
            'availableSteps' => $this->onboardingService->getAvailableSteps(),
        ]);
    }

    /**
     * Show account setup step
     */
    public function accountSetup(Request $request): Response
    {
        $userId = $request->user()->id;
        $progress = $this->onboardingService->getProgress($userId);
        $user = $request->user();

        // Pre-fill with existing user data only if no saved data
        $savedData = $progress?->data['account'] ?? null;
        
        // Only use defaults if no saved data exists
        if (!$savedData) {
            $nameParts = explode(' ', $user->name ?: '');
            $savedData = [
                'firstName' => $nameParts[0] ?? '',
                'lastName' => $nameParts[1] ?? '',
                'phone' => '', // Don't pre-fill phone since we don't have it
            ];
        }

        return Inertia::render('onboarding/account-setup', [
            'progress' => $progress?->toArray(),
            'savedData' => $savedData,
            'user' => [
                'email' => $user->email,
                'name' => $user->name,
                'email_verified' => $user->hasVerifiedEmail(),
            ],
        ]);
    }

    /**
     * Process account setup
     */
    public function storeAccountSetup(Request $request)
    {
        $data = AccountSetupData::validateAndCreate($request);
        $userId = $request->user()->id;

        $progress = $this->onboardingService->processAccountSetup($userId, $data);

        return redirect()->route('onboarding.business');
    }

    /**
     * Show business setup step
     */
    public function businessSetup(Request $request): Response
    {
        $userId = $request->user()->id;
        $progress = $this->onboardingService->getProgress($userId);

        return Inertia::render('onboarding/business-setup', [
            'progress' => $progress?->toArray(),
            'savedData' => $progress?->data['business'] ?? null,
        ]);
    }

    /**
     * Process business setup
     */
    public function storeBusinessSetup(Request $request)
    {
        $data = BusinessSetupData::validateAndCreate($request);
        $userId = $request->user()->id;

        $progress = $this->onboardingService->processBusinessSetup($userId, $data);

        return redirect()->route('onboarding.location');
    }

    /**
     * Show location setup step
     */
    public function locationSetup(Request $request): Response
    {
        $userId = $request->user()->id;
        $progress = $this->onboardingService->getProgress($userId);

        return Inertia::render('onboarding/location-setup', [
            'progress' => $progress?->toArray(),
            'savedData' => $progress?->data['location'] ?? null,
        ]);
    }

    /**
     * Process location setup
     */
    public function storeLocationSetup(Request $request)
    {
        $data = LocationSetupData::validateAndCreate($request);
        $userId = $request->user()->id;

        $progress = $this->onboardingService->processLocationSetup($userId, $data);

        return redirect()->route('onboarding.configuration');
    }

    /**
     * Show configuration setup step
     */
    public function configurationSetup(Request $request): Response
    {
        $userId = $request->user()->id;
        $progress = $this->onboardingService->getProgress($userId);

        return Inertia::render('onboarding/configuration-setup', [
            'progress' => $progress?->toArray(),
            'savedData' => $progress?->data['configuration'] ?? null,
        ]);
    }

    /**
     * Process configuration setup
     */
    public function storeConfigurationSetup(Request $request)
    {
        $data = ConfigurationSetupData::validateAndCreate($request);
        $userId = $request->user()->id;

        $progress = $this->onboardingService->processConfigurationSetup($userId, $data);

        return redirect()->route('onboarding.review');
    }

    /**
     * Show review step
     */
    public function review(Request $request): Response
    {
        $userId = $request->user()->id;
        $progress = $this->onboardingService->getProgress($userId);

        if (!$progress || count($progress->completedSteps) < 4) {
            return redirect()->route('onboarding.index');
        }

        return Inertia::render('onboarding/review', [
            'progress' => $progress->toArray(),
            'data' => $progress->data,
        ]);
    }

    /**
     * Complete onboarding
     */
    public function complete(Request $request)
    {
        $userId = $request->user()->id;

        try {
            $completeData = $this->onboardingService->completeOnboarding($userId);
            
            // Force refresh the user to clear any cached data
            $request->user()->refresh();
            
            // Ensure the business context is set after onboarding
            $businessContext = app(\Colame\Business\Contracts\BusinessContextInterface::class);
            
            // Clear any cached businesses to force reload
            $reflection = new \ReflectionClass($businessContext);
            if ($reflection->hasProperty('cachedBusinesses')) {
                $prop = $reflection->getProperty('cachedBusinesses');
                $prop->setAccessible(true);
                $prop->setValue($businessContext, null);
            }
            
            $businesses = $businessContext->getAccessibleBusinesses();
            
            if (!empty($businesses)) {
                // Set the first (or only) business as current
                // Handle both array and object formats
                $firstBusiness = $businesses[0];
                $businessId = is_array($firstBusiness) ? $firstBusiness['id'] : $firstBusiness->id;
                $businessContext->setCurrentBusiness($businessId);
            }

            return redirect()->route('dashboard')->with('success', 'Onboarding completed successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Skip onboarding
     */
    public function skip(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $userId = $request->user()->id;
        $this->onboardingService->skipOnboarding($userId, $request->input('reason'));

        return redirect()->route('dashboard');
    }
}
