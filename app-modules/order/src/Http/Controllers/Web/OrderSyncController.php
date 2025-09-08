<?php

namespace Colame\Order\Http\Controllers\Web;

use Colame\Order\Services\OrderSessionService;
use Colame\Item\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderSyncController
{
    public function __construct(
        private OrderSessionService $sessionService
    ) {}
    
    /**
     * Sync events from client - single endpoint for all operations
     * Client only sends IDs and quantities, server handles all pricing and validation
     */
    public function sync(Request $request, string $uuid): JsonResponse
    {
        // Validate session ownership (could check user, IP, etc.)
        $session = DB::table('order_sessions')->where('uuid', $uuid)->first();
        if (!$session) {
            return response()->json(['error' => 'Invalid session'], 404);
        }
        
        $validated = $request->validate([
            'events' => 'required|array',
            'events.*.id' => 'required|string',
            'events.*.type' => 'required|string',
            'events.*.timestamp' => 'required|integer',
            'events.*.data' => 'required|array',
        ]);
        
        $processed = [];
        $errors = [];
        $stateChanges = [];
        
        DB::beginTransaction();
        try {
            foreach ($validated['events'] as $event) {
                try {
                    $result = $this->processEvent($uuid, $event);
                    $processed[] = $event['id'];
                    
                    if ($result['stateChange'] ?? false) {
                        $stateChanges[] = $result['stateChange'];
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to process event', [
                        'session' => $uuid,
                        'event' => $event,
                        'error' => $e->getMessage()
                    ]);
                    
                    $errors[] = [
                        'id' => $event['id'],
                        'error' => 'Processing failed',
                        'message' => $e->getMessage()
                    ];
                }
            }
            
            DB::commit();
            
            // Return current state after processing
            $currentState = $this->getCurrentState($uuid);
            
            return response()->json([
                'success' => true,
                'processed' => $processed,
                'errors' => $errors,
                'state' => $currentState,
                'stateChanges' => $stateChanges,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'error' => 'Sync failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Process a single event - ALL business logic here
     */
    private function processEvent(string $uuid, array $event): array
    {
        $type = $event['type'];
        $data = $event['data'];
        $result = ['processed' => true];
        
        switch ($type) {
            case 'session_started':
                // Session already exists, just track it
                $this->sessionService->trackEvent($uuid, [
                    'event' => 'session_started',
                    'order_type' => $data['orderType'] ?? null,
                ]);
                break;
                
            case 'session_hydrate':
                // Just return current state, no tracking needed
                // The state will be returned at the end of the sync
                Log::info('Hydrating session', ['session' => $uuid]);
                $result['hydrated'] = true;
                break;
                
            case 'item_added':
            case 'item_modified':
                // Critical: Look up item from database, NEVER trust client prices
                $itemId = (int) $data['itemId'];
                $item = Item::find($itemId);
                
                if (!$item) {
                    throw new \Exception("Item {$itemId} not found");
                }
                
                // Check item availability
                if (!$item->is_active) {
                    throw new \Exception("Item {$item->name} is not available");
                }
                
                // NO PRICE VALIDATION - pricing happens at checkout
                
                if ($type === 'item_added') {
                    $this->sessionService->addToCart($uuid, [
                        'item_id' => $item->id,
                        'item_name' => $item->name,
                        'quantity' => (int) ($data['quantity'] ?? 1),
                        // NO UNIT_PRICE - will be calculated at checkout
                        'category' => $item->category->name ?? null,
                        'added_from' => $data['source'] ?? 'unknown',
                        'modifiers' => $data['modifiers'] ?? [],
                        'variants' => $data['variants'] ?? [],
                    ]);
                } else {
                    // For modifications, update the quantity
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
                
                $result['stateChange'] = [
                    'type' => 'cart_updated',
                    'itemId' => $item->id,
                    // NO PRICE - client gets it from state refresh
                ];
                break;
                
            case 'item_removed':
                $itemId = (int) $data['itemId'];
                $item = Item::find($itemId);
                
                if ($item) {
                    $this->sessionService->removeFromCart($uuid, [
                        'item_id' => $item->id,
                        'item_name' => $item->name,
                        'quantity' => (int) ($data['quantity'] ?? 1),
                        'reason' => $data['reason'] ?? 'user_removed',
                    ]);
                    
                    $result['stateChange'] = [
                        'type' => 'item_removed',
                        'itemId' => $item->id,
                        // NO PRICE - client gets updated state from server
                    ];
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
                
            case 'draft_saved':
                $this->sessionService->saveDraft($uuid, $data['autoSaved'] ?? false);
                break;
                
            default:
                Log::warning('Unknown event type', [
                    'session' => $uuid,
                    'type' => $type,
                    'data' => $data
                ]);
        }
        
        return $result;
    }
    
    /**
     * Get current state of the session
     */
    private function getCurrentState(string $uuid): array
    {
        $state = $this->sessionService->getSessionState($uuid);
        
        // Enrich with fresh prices from items table (FOR DISPLAY ONLY)
        if (!empty($state['cart_items'])) {
            $itemIds = array_column($state['cart_items'], 'id');
            $items = Item::whereIn('id', $itemIds)->get()->keyBy('id');
            
            foreach ($state['cart_items'] as &$cartItem) {
                if (isset($items[$cartItem['id']])) {
                    $item = $items[$cartItem['id']];
                    
                    // Enrich with current data from items table
                    $cartItem['name'] = $item->name;
                    $cartItem['available'] = $item->is_active;
                    
                    // CRITICAL: Fresh price from database for DISPLAY ONLY
                    // Client never sends prices, server is authoritative
                    $cartItem['price'] = $item->sale_price ?? $item->base_price;
                    $cartItem['unit_price'] = $item->sale_price ?? $item->base_price;
                }
            }
        }
        
        // Remove cart_value - calculated fresh at checkout
        unset($state['cart_value']);
        
        return $state;
    }
}