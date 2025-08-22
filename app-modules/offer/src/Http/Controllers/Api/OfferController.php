<?php

declare(strict_types=1);

namespace Colame\Offer\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Offer\Contracts\OfferServiceInterface;
use Colame\Offer\Contracts\OfferRepositoryInterface;
use Colame\Offer\Data\CreateOfferData;
use Colame\Offer\Data\UpdateOfferData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OfferController extends Controller
{
    public function __construct(
        private readonly OfferServiceInterface $service,
        private readonly OfferRepositoryInterface $repository,
    ) {}
    
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->get('search'),
            'type' => $request->get('type'),
            'is_active' => $request->has('is_active') ? (bool) $request->get('is_active') : null,
            'location_id' => $request->get('location_id'),
            'valid_only' => $request->get('valid_only', false),
            'sort_by' => $request->get('sort_by', 'priority'),
            'sort_direction' => $request->get('sort_direction', 'desc'),
        ];
        
        $perPage = (int) $request->get('per_page', 15);
        $paginatedOffers = $this->repository->paginate($perPage, $filters);
        $offersArray = $paginatedOffers->toArray();
        
        return response()->json([
            'data' => $offersArray['data'],
            'meta' => $offersArray['pagination'],
        ]);
    }
    
    public function store(Request $request): JsonResponse
    {
        $data = CreateOfferData::validateAndCreate($request);
        $offer = $this->service->createOffer($data->toArray());
        
        return response()->json([
            'message' => 'Offer created successfully',
            'data' => $offer,
        ], 201);
    }
    
    public function show(int $id): JsonResponse
    {
        $offer = $this->repository->find($id);
        
        if (!$offer) {
            return response()->json([
                'message' => 'Offer not found',
            ], 404);
        }
        
        return response()->json([
            'data' => $offer,
        ]);
    }
    
    public function update(Request $request, int $id): JsonResponse
    {
        $data = UpdateOfferData::validateAndCreate($request);
        $offer = $this->service->updateOffer($id, $data->toArray());
        
        return response()->json([
            'message' => 'Offer updated successfully',
            'data' => $offer,
        ]);
    }
    
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->deleteOffer($id);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'Offer not found',
            ], 404);
        }
        
        return response()->json([
            'message' => 'Offer deleted successfully',
        ]);
    }
    
    public function activate(int $id): JsonResponse
    {
        $activated = $this->repository->activate($id);
        
        if (!$activated) {
            return response()->json([
                'message' => 'Offer not found',
            ], 404);
        }
        
        return response()->json([
            'message' => 'Offer activated successfully',
        ]);
    }
    
    public function deactivate(int $id): JsonResponse
    {
        $deactivated = $this->repository->deactivate($id);
        
        if (!$deactivated) {
            return response()->json([
                'message' => 'Offer not found',
            ], 404);
        }
        
        return response()->json([
            'message' => 'Offer deactivated successfully',
        ]);
    }
    
    public function duplicate(int $id): JsonResponse
    {
        try {
            $newOffer = $this->service->duplicateOffer($id);
            
            return response()->json([
                'message' => 'Offer duplicated successfully',
                'data' => $newOffer,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }
    
    public function bulkAction(Request $request): JsonResponse
    {
        $action = $request->get('action');
        $offerIds = $request->get('offer_ids', []);
        
        if (empty($offerIds)) {
            return response()->json([
                'message' => 'No offers selected',
            ], 400);
        }
        
        switch ($action) {
            case 'activate':
                $count = $this->service->bulkActivate($offerIds);
                return response()->json([
                    'message' => "{$count} offers activated",
                    'count' => $count,
                ]);
                
            case 'deactivate':
                $count = $this->service->bulkDeactivate($offerIds);
                return response()->json([
                    'message' => "{$count} offers deactivated",
                    'count' => $count,
                ]);
                
            case 'delete':
                $count = 0;
                foreach ($offerIds as $id) {
                    if ($this->service->deleteOffer($id)) {
                        $count++;
                    }
                }
                return response()->json([
                    'message' => "{$count} offers deleted",
                    'count' => $count,
                ]);
                
            default:
                return response()->json([
                    'message' => 'Invalid action',
                ], 400);
        }
    }
    
    public function validateOffer(Request $request): JsonResponse
    {
        $offerId = $request->get('offer_id');
        $orderData = $request->get('order_data', []);
        
        if (!$offerId) {
            return response()->json([
                'message' => 'Offer ID is required',
            ], 400);
        }
        
        $isValid = $this->service->validateOfferForOrder($offerId, $orderData);
        
        return response()->json([
            'valid' => $isValid,
        ]);
    }
    
    public function apply(Request $request): JsonResponse
    {
        $offerId = $request->get('offer_id');
        $orderData = $request->get('order_data', []);
        
        if (!$offerId) {
            return response()->json([
                'message' => 'Offer ID is required',
            ], 400);
        }
        
        try {
            $appliedOffer = $this->service->applyOfferToOrder($offerId, $orderData);
            
            return response()->json([
                'message' => 'Offer applied successfully',
                'data' => $appliedOffer,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    
    public function applyBest(Request $request): JsonResponse
    {
        $orderData = $request->get('order_data', []);
        
        $appliedOffer = $this->service->applyBestOfferToOrder($orderData);
        
        if (!$appliedOffer) {
            return response()->json([
                'message' => 'No applicable offers found',
            ], 404);
        }
        
        return response()->json([
            'message' => 'Best offer applied successfully',
            'data' => $appliedOffer,
        ]);
    }
    
    public function available(Request $request): JsonResponse
    {
        $orderData = $request->all();
        
        $offers = $this->service->getAvailableOffersForOrder($orderData);
        
        return response()->json([
            'data' => $offers,
        ]);
    }
    
    public function analytics(Request $request, int $id): JsonResponse
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $analytics = $this->service->getOfferAnalytics($id, $startDate, $endDate);
        
        return response()->json([
            'data' => $analytics,
        ]);
    }
    
    public function checkCode(Request $request): JsonResponse
    {
        $code = $request->get('code');
        
        if (!$code) {
            return response()->json([
                'message' => 'Code is required',
            ], 400);
        }
        
        $offer = $this->repository->findByCode($code);
        
        if (!$offer) {
            return response()->json([
                'message' => 'Invalid offer code',
                'valid' => false,
            ], 404);
        }
        
        return response()->json([
            'valid' => true,
            'data' => $offer,
        ]);
    }
}