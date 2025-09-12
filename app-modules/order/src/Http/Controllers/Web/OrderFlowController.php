<?php

namespace Colame\Order\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Colame\Order\Data\Session\StartOrderFlowData;
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
        $data = StartOrderFlowData::validateAndCreate($request->all());

        try {
            $result = $this->sessionService->startSession($data);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
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
            'payment_method' => 'nullable|string|in:cash,card,transfer',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'customer_email' => 'nullable|email',
            'notes' => 'nullable|string',
        ]);

        $result = $this->sessionService->convertToOrder($orderUuid, $validated);

        // Check if there's an error
        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        // Add success flag if not present
        if (!isset($result['success'])) {
            $result['success'] = true;
        }

        return response()->json($result);
    }
}
