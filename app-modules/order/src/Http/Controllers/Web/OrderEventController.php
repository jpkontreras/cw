<?php

namespace Colame\Order\Http\Controllers\Web;

use Colame\Order\Services\OrderSessionService;
use Colame\Order\Services\OrderValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class OrderEventController
{
    public function __construct(
        private OrderSessionService $sessionService,
        private OrderValidationService $validationService
    ) {}
    
    /**
     * Handle batch of events from tracking engine
     */
    public function batchEvents(Request $request, string $uuid): JsonResponse
    {
        // Validate session token
        $sessionToken = $request->header('X-Session-Token');
        if (!$sessionToken || !$this->validateSessionToken($uuid, $sessionToken)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid session token'
            ], 401);
        }
        
        // Rate limiting per session
        $rateLimitKey = "event_batch:{$uuid}";
        if (!RateLimiter::attempt($rateLimitKey, 10, function() {})) {
            return response()->json([
                'success' => false,
                'error' => 'Too many requests'
            ], 429);
        }
        
        $validated = $request->validate([
            'sessionId' => 'required|string',
            'sessionToken' => 'required|string',
            'events' => 'required|array',
            'events.*.id' => 'required|string',
            'events.*.type' => 'required|string',
            'events.*.timestamp' => 'required|integer',
            'events.*.data' => 'required|array',
        ]);
        
        // Process each event
        $processed = [];
        $errors = [];
        
        foreach ($validated['events'] as $event) {
            try {
                // Validate and sanitize event data
                $sanitizedData = $this->sanitizeEventData($event['type'], $event['data']);
                
                // Process based on event type
                $this->processEvent($uuid, $event['type'], $sanitizedData);
                
                $processed[] = $event['id'];
            } catch (\Exception $e) {
                Log::error('Failed to process event', [
                    'session' => $uuid,
                    'event' => $event,
                    'error' => $e->getMessage()
                ]);
                
                $errors[] = [
                    'id' => $event['id'],
                    'error' => 'Processing failed'
                ];
            }
        }
        
        // Check if this is an Inertia request
        if ($request->header('X-Inertia')) {
            // For Inertia requests, return back to the same page
            return back()->with([
                'success' => true,
                'processed' => $processed,
                'errors' => $errors,
            ]);
        }
        
        // For regular AJAX requests, return JSON
        return response()->json([
            'success' => true,
            'processed' => $processed,
            'errors' => $errors,
        ]);
    }
    
    /**
     * Validate session token
     */
    private function validateSessionToken(string $uuid, string $token): bool
    {
        // For now, accept any token that starts with the session UUID
        // In production, implement proper token validation with signatures
        return str_starts_with($token, $uuid);
    }
    
    /**
     * Sanitize event data - remove any sensitive or tampered data
     */
    private function sanitizeEventData(string $eventType, array $data): array
    {
        // Remove any price/total fields that shouldn't come from client
        $sensitiveFields = ['price', 'unitPrice', 'total', 'subtotal', 'tax', 'discount'];
        foreach ($sensitiveFields as $field) {
            unset($data[$field]);
        }
        
        // Validate item IDs exist if present
        if (isset($data['itemId'])) {
            // TODO: Validate item exists in database
            // For now, just ensure it's an integer
            $data['itemId'] = (int) $data['itemId'];
        }
        
        // Validate quantities are reasonable
        if (isset($data['quantity'])) {
            $data['quantity'] = min(100, max(1, (int) $data['quantity']));
        }
        
        return $data;
    }
    
    /**
     * Process individual event
     */
    private function processEvent(string $uuid, string $eventType, array $data): void
    {
        // Map frontend event types to backend event methods
        switch ($eventType) {
            case 'session_started':
                // Session already exists, just log the event
                $this->sessionService->trackEvent($uuid, [
                    'event' => 'session_started',
                    'order_type' => $data['orderType'] ?? null,
                    'location_id' => $data['locationId'] ?? null,
                    'platform' => $data['platform'] ?? 'web',
                ]);
                break;
                
            case 'item_added':
                // Look up item details from database (price comes from server, never from client!)
                $item = \Colame\Item\Models\Item::find($data['itemId']);
                if ($item) {
                    // Use sale_price if available, otherwise base_price
                    $price = $item->sale_price ?? $item->base_price;
                    
                    if ($price !== null) {
                        $this->sessionService->addToCart($uuid, [
                            'item_id' => $item->id,
                            'item_name' => $item->name,
                            'quantity' => $data['quantity'] ?? 1,
                            'unit_price' => (float) $price, // Server-side price only!
                            'category' => $item->category->name ?? null,
                            'added_from' => $data['source'] ?? 'unknown',
                        ]);
                    } else {
                        \Log::warning('Item has no price', [
                            'itemId' => $data['itemId'],
                            'item' => $item->toArray(),
                            'session' => $uuid
                        ]);
                    }
                } else {
                    \Log::warning('Item not found', [
                        'itemId' => $data['itemId'],
                        'session' => $uuid
                    ]);
                }
                break;
                
            case 'item_removed':
                $item = \Colame\Item\Models\Item::find($data['itemId']);
                if ($item) {
                    $this->sessionService->removeFromCart($uuid, [
                        'item_id' => $item->id,
                        'item_name' => $item->name,
                        'quantity' => $data['quantity'] ?? 1,
                        'reason' => $data['reason'] ?? 'user_removed',
                    ]);
                }
                break;
                
            case 'item_modified':
                $item = \Colame\Item\Models\Item::find($data['itemId']);
                if ($item) {
                    $this->sessionService->updateCartItem($uuid, [
                        'item_id' => $item->id,
                        'item_name' => $item->name,
                        'modification_type' => 'quantity_change',
                        'changes' => [
                            'from' => $data['previousQuantity'] ?? 0,
                            'to' => $data['newQuantity'] ?? 0,
                        ],
                    ]);
                }
                break;
                
            case 'serving_type_changed':
                $this->sessionService->trackEvent($uuid, [
                    'event' => 'serving_type',
                    'type' => $data['type'],
                    'previous' => $data['previous'] ?? null,
                ]);
                break;
                
            case 'customer_info_provided':
                $this->sessionService->trackEvent($uuid, [
                    'event' => 'customer_info',
                    'fields' => array_keys($data['fields'] ?? []),
                    'is_complete' => $data['isComplete'] ?? false,
                ]);
                break;
                
            case 'payment_method_selected':
                $this->sessionService->trackEvent($uuid, [
                    'event' => 'payment_method',
                    'method' => $data['payment_method'] ?? $data['method'] ?? null,
                    'previous' => $data['previous'] ?? null,
                ]);
                break;
                
            case 'category_selected':
                $this->sessionService->trackEvent($uuid, [
                    'event' => 'category_browse',
                    'category_id' => $data['categoryId'] ?? null,
                    'category_name' => $data['categoryName'] ?? null,
                    'items_viewed' => $data['itemsViewed'] ?? 0,
                    'time_spent' => $data['timeSpent'] ?? 0,
                ]);
                break;
                
            case 'search_performed':
                $this->sessionService->trackEvent($uuid, [
                    'event' => 'search',
                    'query' => $data['query'] ?? '',
                    'filters' => $data['filters'] ?? [],
                    'results_count' => $data['resultsCount'] ?? 0,
                    'search_id' => $data['searchId'] ?? null,
                ]);
                break;
                
            case 'item_viewed':
                $this->sessionService->trackEvent($uuid, [
                    'event' => 'item_view',
                    'item_id' => $data['itemId'] ?? null,
                    'item_name' => $data['itemName'] ?? null,
                    'category' => $data['category'] ?? null,
                    'source' => $data['source'] ?? 'unknown',
                    'duration' => $data['duration'] ?? 0,
                ]);
                break;
                
            case 'session_recovered':
                $this->sessionService->trackEvent($uuid, [
                    'event' => 'session_recovered',
                    'session_age' => $data['sessionAge'] ?? 0,
                    'items_count' => $data['itemsCount'] ?? 0,
                ]);
                break;
                
            case 'draft_saved':
                $this->sessionService->saveDraft($uuid, $data['autoSaved'] ?? false);
                break;
                
            default:
                Log::warning('Unknown event type', [
                    'session' => $uuid,
                    'type' => $eventType,
                    'data' => $data
                ]);
        }
    }
}