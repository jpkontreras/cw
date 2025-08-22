<?php

declare(strict_types=1);

namespace Colame\Onboarding\Http\Middleware;

use Closure;
use Colame\Onboarding\Contracts\OnboardingServiceInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function __construct(
        private OnboardingServiceInterface $onboardingService
    ) {}

    /**
     * Handle an incoming request.
     * 
     * This middleware is now applied at the route level, not globally.
     * It only runs on routes that explicitly include it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for non-authenticated users
        if (!$request->user()) {
            return $next($request);
        }

        // Check if onboarding is needed using the User model method
        if ($request->user()->needsOnboarding()) {
            // Check if feature is enabled
            if (!config('features.onboarding.enabled', true)) {
                return $next($request);
            }

            // For API requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Onboarding required',
                    'redirect' => route('onboarding.index'),
                ], 403);
            }

            // For web requests, redirect to onboarding
            return redirect()->route('onboarding.index')
                ->with('info', 'Please complete the onboarding process to continue.');
        }

        return $next($request);
    }
}
