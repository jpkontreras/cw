<?php

declare(strict_types=1);

namespace App\Core\Traits;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Trait for handling out-of-bounds pagination in controllers
 */
trait HandlesPaginationBounds
{
    /**
     * Check if pagination is out of bounds and handle appropriately
     * 
     * @param array $paginationData The pagination array from the response
     * @param Request $request The current request
     * @param string $routeName The route name to redirect to (for web controllers)
     * @return RedirectResponse|null Returns redirect if out of bounds, null otherwise
     */
    protected function handleOutOfBoundsPagination(array $paginationData, Request $request, string $routeName): ?RedirectResponse
    {
        $currentPage = $paginationData['current_page'] ?? 1;
        $lastPage = $paginationData['last_page'] ?? 1;
        
        if ($currentPage > $lastPage && $lastPage > 0) {
            // Redirect to page 1 when page is out of bounds
            return redirect()->route($routeName, array_merge($request->except('page'), ['page' => 1]));
        }
        
        return null;
    }
    
    /**
     * Check if pagination is out of bounds and return JSON error for APIs
     * 
     * @param array $paginationData The pagination array from the response
     * @return JsonResponse|null Returns JSON error if out of bounds, null otherwise
     */
    protected function handleOutOfBoundsPaginationApi(array $paginationData): ?JsonResponse
    {
        $currentPage = $paginationData['current_page'] ?? 1;
        $lastPage = $paginationData['last_page'] ?? 1;
        
        if ($currentPage > $lastPage && $lastPage > 0) {
            return response()->json([
                'error' => 'Page not found',
                'message' => "Page {$currentPage} does not exist. Last page is {$lastPage}.",
                'last_page' => $lastPage,
                'redirect_url' => request()->fullUrlWithQuery(['page' => 1]),
            ], 404);
        }
        
        return null;
    }
}