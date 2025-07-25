<?php

declare(strict_types=1);

namespace Colame\Item\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Item\Contracts\ItemServiceInterface;
use Colame\Item\Data\CreateItemData;
use Colame\Item\Data\UpdateItemData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API controller for item management
 */
class ItemController extends Controller
{
    public function __construct(
        private ItemServiceInterface $itemService
    ) {}

    /**
     * Display a listing of items
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'active' => $request->boolean('active', false),
            'category_id' => $request->input('category_id'),
            'location_id' => $request->input('location_id'),
        ];

        $items = $this->itemService->getItems(array_filter($filters));

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Store a newly created item
     */
    public function store(CreateItemData $data): JsonResponse
    {
        $item = $this->itemService->createItem($data);

        return response()->json([
            'data' => $item,
            'message' => 'Item created successfully',
        ], 201);
    }

    /**
     * Display the specified item
     */
    public function show(int $id): JsonResponse
    {
        $item = $this->itemService->getItemWithRelations($id);

        return response()->json([
            'data' => $item,
        ]);
    }

    /**
     * Update the specified item
     */
    public function update(int $id, UpdateItemData $data): JsonResponse
    {
        $item = $this->itemService->updateItem($id, $data);

        return response()->json([
            'data' => $item,
            'message' => 'Item updated successfully',
        ]);
    }

    /**
     * Remove the specified item
     */
    public function destroy(int $id): JsonResponse
    {
        $this->itemService->deleteItem($id);

        return response()->json([
            'message' => 'Item deleted successfully',
        ]);
    }

    /**
     * Search items
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $items = $this->itemService->searchItems($query);

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Check item availability
     */
    public function checkAvailability(int $id, Request $request): JsonResponse
    {
        $quantity = $request->input('quantity', 1);
        $locationId = $request->input('location_id');

        $available = $this->itemService->checkAvailability($id, $quantity, $locationId);

        return response()->json([
            'available' => $available,
        ]);
    }

    /**
     * Get item price
     */
    public function getPrice(int $id, Request $request): JsonResponse
    {
        $locationId = $request->input('location_id');
        $price = $this->itemService->getPrice($id, $locationId);

        return response()->json([
            'price' => $price,
        ]);
    }
}