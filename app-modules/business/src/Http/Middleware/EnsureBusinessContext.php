<?php

declare(strict_types=1);

namespace Colame\Business\Http\Middleware;

use Closure;
use Colame\Business\Contracts\BusinessContextInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessContext
{
    public function __construct(
        private readonly BusinessContextInterface $businessContext
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        // Allow business management routes without requiring context
        $currentRouteName = $request->route()?->getName() ?? '';
        
        // Skip business context check for these routes
        $skipContextRoutes = [
            'businesses.*',    // All business management routes
            'onboarding.*',    // Onboarding routes
            'auth.*',          // Authentication routes
            'password.*',      // Password reset routes
            'verification.*',  // Email verification routes
            'settings.*',      // User settings routes
        ];
        
        // Check if current route should skip business context
        foreach ($skipContextRoutes as $pattern) {
            if (fnmatch($pattern, $currentRouteName)) {
                // Still share context if available, but don't enforce it
                if ($this->businessContext->getCurrentBusinessId()) {
                    $this->shareBusinessContext($request);
                }
                return $next($request);
            }
        }

        // Check if user has a current business context
        if (!$this->businessContext->getCurrentBusinessId()) {
            $businesses = $this->businessContext->getAccessibleBusinesses();
            
            // If user has no businesses, they need to complete onboarding or create a business
            if (empty($businesses)) {
                // Check if user has completed onboarding
                $user = auth()->user();
                if ($user && method_exists($user, 'hasCompletedOnboarding') && $user->hasCompletedOnboarding()) {
                    // User completed onboarding but has no businesses - redirect to business creation
                    return redirect()->route('businesses.create')
                        ->with('info', 'Please create a business to continue.');
                }
                
                return redirect()->route('onboarding.index')
                    ->with('info', 'Please complete the setup process to continue.');
            }
            
            // If user has exactly one business, auto-select it
            if (count($businesses) === 1) {
                $this->businessContext->setCurrentBusiness($businesses[0]->id);
            } else {
                // Multiple businesses but none selected
                return redirect()->route('businesses.index')
                    ->with('info', 'Please select a business to continue.');
            }
        }

        // Verify access to current business
        if (!$this->businessContext->hasCurrentAccess()) {
            $this->businessContext->clearCurrentBusiness();
            
            return redirect()->route('businesses.index')
                ->with('error', 'You no longer have access to the selected business.');
        }

        // Share business context with all views
        $this->shareBusinessContext($request);

        return $next($request);
    }
    
    /**
     * Share business context with views and session
     */
    private function shareBusinessContext(Request $request): void
    {
        if ($request->hasSession()) {
            $business = $this->businessContext->getCurrentBusiness();
            $request->session()->put('current_business', $business?->toArray());
            
            // Share with Inertia
            if (class_exists(\Inertia\Inertia::class)) {
                \Inertia\Inertia::share('currentBusiness', $business?->toArray());
                \Inertia\Inertia::share('currentBusinessRole', $this->businessContext->getCurrentRole());
            }
        }
    }
}