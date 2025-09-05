<?php

namespace Colame\Order\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Order\Services\OrderSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderFlowController extends Controller
{
    public function __construct(
        private OrderSessionService $sessionService
    ) {}
    
    /**
     * Start a new order session
     */
    public function startSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'nullable|integer',
            'platform' => 'nullable|string|in:web,mobile,kiosk',
            'source' => 'nullable|string',
            'order_type' => 'nullable|string|in:dine_in,takeout,delivery',
            'referrer' => 'nullable|string',
        ]);
        
        $result = $this->sessionService->startSession($validated);
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
    
    /**
     * Track a generic event
     */
    public function trackEvent(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'event' => 'required|string',
            'query' => 'nullable|string',
            'filters' => 'nullable|array',
            'results_count' => 'nullable|integer',
            'search_id' => 'nullable|string',
            'category_id' => 'nullable|integer',
            'category_name' => 'nullable|string',
            'items_viewed' => 'nullable|integer',
            'time_spent' => 'nullable|integer',
            'item_id' => 'nullable|integer',
            'item_name' => 'nullable|string',
            'price' => 'nullable|numeric',
            'category' => 'nullable|string',
            'source' => 'nullable|string',
            'duration' => 'nullable|integer',
            'type' => 'nullable|string',
            'previous' => 'nullable|string',
            'table_number' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'fields' => 'nullable|array',
            'is_complete' => 'nullable|boolean',
            'validation_errors' => 'nullable|array',
            'method' => 'nullable|string',
        ]);
        
        $this->sessionService->trackEvent($orderUuid, $validated);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Add item to cart
     */
    public function addToCart(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|integer',
            'item_name' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'category' => 'nullable|string',
            'added_from' => 'nullable|string',
        ]);
        
        $this->sessionService->addToCart($orderUuid, $validated);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Remove item from cart
     */
    public function removeFromCart(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|integer',
            'item_name' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);
        
        $this->sessionService->removeFromCart($orderUuid, $validated);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Update cart item
     */
    public function updateCartItem(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|integer',
            'item_name' => 'required|string',
            'modification_type' => 'required|string',
            'changes' => 'required|array',
        ]);
        
        $this->sessionService->updateCartItem($orderUuid, $validated);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Get current session state
     */
    public function getSessionState(string $orderUuid): JsonResponse
    {
        $state = $this->sessionService->getSessionState($orderUuid);
        
        return response()->json($state);
    }
    
    /**
     * Recover a session
     */
    public function recoverSession(Request $request, string $orderUuid): JsonResponse
    {
        $result = $this->sessionService->recoverSession($orderUuid);
        
        return response()->json($result);
    }
    
    /**
     * Save draft
     */
    public function saveDraft(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'auto_saved' => 'nullable|boolean',
        ]);
        
        $this->sessionService->saveDraft($orderUuid, $validated['auto_saved'] ?? false);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Convert session to order
     */
    public function convertToOrder(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string',
            'customer_phone' => 'nullable|string',
            'customer_email' => 'nullable|email',
            'notes' => 'nullable|string',
        ]);
        
        $result = $this->sessionService->convertToOrder($orderUuid, $validated);
        
        return response()->json($result);
    }
}