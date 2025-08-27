<?php

declare(strict_types=1);

namespace Colame\Business\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Business\Contracts\BusinessContextInterface;
use Colame\Business\Contracts\BusinessServiceInterface;
use Colame\Business\Data\CreateBusinessData;
use Colame\Business\Data\InviteUserData;
use Colame\Business\Data\UpdateBusinessData;
use Colame\Business\Exceptions\BusinessException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function __construct(
        private readonly BusinessServiceInterface $businessService,
        private readonly BusinessContextInterface $businessContext,
    ) {}

    /**
     * Display a listing of businesses
     */
    public function index(Request $request): Response
    {
        $businesses = $this->businessService->getUserBusinesses($request->user()->id);
        $currentBusiness = $this->businessContext->getCurrentBusiness();

        return Inertia::render('business/index', [
            'businesses' => $businesses->toArray(),
            'currentBusiness' => $currentBusiness?->toArray(),
        ]);
    }

    /**
     * Show the form for creating a new business
     * Note: Business creation is handled through onboarding
     */
    public function create(): RedirectResponse
    {
        // Business creation is handled through the onboarding process
        return redirect()->route('businesses.index')
            ->with('info', 'New businesses are created through the onboarding process.');
    }

    /**
     * Store a newly created business
     * Note: Business creation is handled through onboarding
     */
    public function store(Request $request): RedirectResponse
    {
        // Business creation is handled through the onboarding process
        return redirect()->route('businesses.index')
            ->with('info', 'New businesses are created through the onboarding process.');
    }

    /**
     * Display the specified business
     */
    public function show(int $id): Response
    {
        $business = $this->businessService->getBusiness($id);
        
        if (!$business) {
            abort(404);
        }

        return Inertia::render('business/show', [
            'business' => $business->toArray(),
            'canEdit' => $this->businessContext->can('manage_settings'),
        ]);
    }

    /**
     * Show the form for editing the specified business
     */
    public function edit(int $id): Response
    {
        $business = $this->businessService->getBusiness($id);
        
        if (!$business) {
            abort(404);
        }

        if (!$this->businessContext->can('manage_settings')) {
            abort(403);
        }

        return Inertia::render('business/edit', [
            'business' => $business->toArray(),
            'types' => ['independent', 'franchise', 'corporate'],
            'currencies' => ['CLP', 'USD', 'EUR'],
            'timezones' => ['America/Santiago', 'America/New_York', 'Europe/London'],
        ]);
    }

    /**
     * Update the specified business
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        if (!$this->businessContext->can('manage_settings')) {
            abort(403);
        }

        $data = UpdateBusinessData::validateAndCreate($request->all());

        try {
            $business = $this->businessService->updateBusiness($id, $data);
            
            return redirect()
                ->route('businesses.show', $business->id)
                ->with('success', 'Business updated successfully.');
        } catch (BusinessException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['business' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified business
     */
    public function destroy(int $id): RedirectResponse
    {
        if (!$this->businessContext->isOwner()) {
            abort(403);
        }

        try {
            $this->businessService->deleteBusiness($id);
            
            return redirect()
                ->route('businesses.index')
                ->with('success', 'Business deleted successfully.');
        } catch (BusinessException $e) {
            return redirect()
                ->back()
                ->withErrors(['business' => $e->getMessage()]);
        }
    }

    /**
     * Switch to a different business
     */
    public function switch(int $id): RedirectResponse
    {
        try {
            $this->businessContext->switchBusiness($id);
            
            return redirect()
                ->route('dashboard')
                ->with('success', 'Switched business successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['business' => $e->getMessage()]);
        }
    }

    /**
     * Show current business
     */
    public function current(Request $request): RedirectResponse
    {
        $business = $this->businessContext->getCurrentBusiness();
        
        if (!$business) {
            return redirect()->route('businesses.index')
                ->with('warning', 'No current business selected.');
        }
        
        return redirect()->route('businesses.show', $business->id);
    }
    
    /**
     * Show current business settings
     */
    public function currentSettings(Request $request): RedirectResponse
    {
        $business = $this->businessContext->getCurrentBusiness();
        
        if (!$business) {
            return redirect()->route('businesses.index')
                ->with('warning', 'No current business selected.');
        }
        
        return redirect()->route('businesses.business.settings', $business->id);
    }
    
    /**
     * Show current business users
     */
    public function currentUsers(Request $request): RedirectResponse
    {
        $business = $this->businessContext->getCurrentBusiness();
        
        if (!$business) {
            return redirect()->route('businesses.index')
                ->with('warning', 'No current business selected.');
        }
        
        return redirect()->route('businesses.users.index', $business->id);
    }

    /**
     * Show business settings
     */
    public function settings(int $id): Response
    {
        $business = $this->businessService->getBusiness($id);
        
        if (!$business) {
            abort(404);
        }

        if (!$this->businessContext->can('manage_settings')) {
            abort(403);
        }

        return Inertia::render('business/settings', [
            'business' => $business->toArray(),
            'subscription' => $business->currentSubscription?->toArray(),
            'usage' => $this->businessService->getUsage($id),
        ]);
    }

    /**
     * Show user management page
     */
    public function users(int $id): Response
    {
        $business = $this->businessService->getBusiness($id);
        
        if (!$business) {
            abort(404);
        }
        
        $canManageUsers = $this->businessContext->can('manage_users');
        $users = $this->businessService->getBusinessUsers($id);

        return Inertia::render('business/users', [
            'business' => $business->toArray(),
            'users' => $users->toArray(),
            'currentUserId' => auth()->id(),
            'canManageUsers' => $canManageUsers,
        ]);
    }

    /**
     * Invite a user to the business
     */
    public function inviteUser(Request $request, int $id): RedirectResponse
    {
        if (!$this->businessContext->can('manage_users')) {
            abort(403);
        }

        $data = InviteUserData::validateAndCreate($request->all());

        try {
            $this->businessService->inviteUser($id, $data);
            
            return redirect()
                ->back()
                ->with('success', 'User invited successfully.');
        } catch (BusinessException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['invite' => $e->getMessage()]);
        }
    }

    /**
     * Remove a user from the business
     */
    public function removeUser(int $businessId, int $userId): RedirectResponse
    {
        if (!$this->businessContext->can('manage_users')) {
            abort(403);
        }

        try {
            $this->businessService->removeUser($businessId, $userId);
            
            return redirect()
                ->back()
                ->with('success', 'User removed successfully.');
        } catch (BusinessException $e) {
            return redirect()
                ->back()
                ->withErrors(['user' => $e->getMessage()]);
        }
    }

    /**
     * Update a user's role
     */
    public function updateUserRole(Request $request, int $businessId, int $userId): RedirectResponse
    {
        if (!$this->businessContext->can('manage_users')) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:owner,admin,manager,member'],
        ]);

        try {
            $this->businessService->updateUserRole($businessId, $userId, $validated['role']);
            
            return redirect()
                ->back()
                ->with('success', 'User role updated successfully.');
        } catch (BusinessException $e) {
            return redirect()
                ->back()
                ->withErrors(['role' => $e->getMessage()]);
        }
    }
    
    /**
     * Update business branding settings
     */
    public function updateBranding(Request $request, int $id): RedirectResponse
    {
        if (!$this->businessContext->can('manage_settings')) {
            abort(403);
        }
        
        $validated = $request->validate([
            'primaryColor' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondaryColor' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logoUrl' => ['nullable', 'url'],
        ]);
        
        try {
            $this->businessService->updateBusinessSettings($id, [
                'primaryColor' => $validated['primaryColor'] ?? null,
                'secondaryColor' => $validated['secondaryColor'] ?? null,
                'logoUrl' => $validated['logoUrl'] ?? null,
            ]);
            
            return redirect()
                ->back()
                ->with('success', 'Branding settings updated successfully.');
        } catch (BusinessException $e) {
            return redirect()
                ->back()
                ->withErrors(['branding' => $e->getMessage()]);
        }
    }
    
    /**
     * Update business features settings
     */
    public function updateFeatures(Request $request, int $id): RedirectResponse
    {
        if (!$this->businessContext->can('manage_settings')) {
            abort(403);
        }
        
        $validated = $request->validate([
            'enableOrders' => ['boolean'],
            'enableInventory' => ['boolean'],
            'enableReports' => ['boolean'],
            'enableOnlineOrdering' => ['boolean'],
            'enableTableReservations' => ['boolean'],
            'enableLoyaltyProgram' => ['boolean'],
        ]);
        
        // Convert boolean flags to features array
        $features = [];
        if ($validated['enableOrders'] ?? false) $features[] = 'orders';
        if ($validated['enableInventory'] ?? false) $features[] = 'inventory';
        if ($validated['enableReports'] ?? false) $features[] = 'reports';
        if ($validated['enableOnlineOrdering'] ?? false) $features[] = 'online_ordering';
        if ($validated['enableTableReservations'] ?? false) $features[] = 'reservations';
        if ($validated['enableLoyaltyProgram'] ?? false) $features[] = 'loyalty';
        
        try {
            $this->businessService->updateBusinessSettings($id, [
                'features' => $features,
            ]);
            
            return redirect()
                ->back()
                ->with('success', 'Features updated successfully.');
        } catch (BusinessException $e) {
            return redirect()
                ->back()
                ->withErrors(['features' => $e->getMessage()]);
        }
    }
    
    /**
     * Update business notification settings
     */
    public function updateNotifications(Request $request, int $id): RedirectResponse
    {
        if (!$this->businessContext->can('manage_settings')) {
            abort(403);
        }
        
        $validated = $request->validate([
            'emailNotifications' => ['boolean'],
            'smsNotifications' => ['boolean'],
            'pushNotifications' => ['boolean'],
            'dailyReports' => ['boolean'],
            'lowStockAlerts' => ['boolean'],
            'newOrderAlerts' => ['boolean'],
        ]);
        
        try {
            $this->businessService->updateBusinessSettings($id, [
                'settings' => [
                    'notifications' => $validated,
                ],
            ]);
            
            return redirect()
                ->back()
                ->with('success', 'Notification settings updated successfully.');
        } catch (BusinessException $e) {
            return redirect()
                ->back()
                ->withErrors(['notifications' => $e->getMessage()]);
        }
    }
}