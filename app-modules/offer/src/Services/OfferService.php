<?php

declare(strict_types=1);

namespace Colame\Offer\Services;

use App\Core\Services\BaseService;
use Colame\Offer\Contracts\OfferRepositoryInterface;
use Colame\Offer\Contracts\OfferServiceInterface;
use Colame\Offer\Contracts\OfferCalculatorInterface;
use Colame\Offer\Contracts\OfferValidatorInterface;
use Colame\Offer\Data\OfferData;
use Colame\Offer\Data\AppliedOfferData;
use Colame\Offer\Data\CreateOfferData;
use Colame\Offer\Data\UpdateOfferData;
use Colame\Offer\Models\OfferUsage;
use Spatie\LaravelData\DataCollection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OfferService extends BaseService implements OfferServiceInterface
{
    public function __construct(
        private readonly OfferRepositoryInterface $repository,
        private readonly OfferCalculatorInterface $calculator,
        private readonly OfferValidatorInterface $validator,
    ) {}
    
    public function createOffer(array $data): OfferData
    {
        $validated = CreateOfferData::validateAndCreate($data);
        
        return DB::transaction(function () use ($validated) {
            return $this->repository->create($validated->toArray());
        });
    }
    
    public function updateOffer(int $id, array $data): OfferData
    {
        $validated = UpdateOfferData::validateAndCreate($data);
        
        return DB::transaction(function () use ($id, $validated) {
            return $this->repository->update($id, $validated->toArray());
        });
    }
    
    public function deleteOffer(int $id): bool
    {
        return $this->repository->delete($id);
    }
    
    public function duplicateOffer(int $id, ?array $overrides = null): OfferData
    {
        $original = $this->repository->find($id);
        
        if (!$original) {
            throw new \InvalidArgumentException("Offer with ID {$id} not found");
        }
        
        $data = $original->toArray();
        unset($data['id'], $data['usageCount'], $data['createdAt'], $data['updatedAt']);
        
        // Apply overrides
        if ($overrides) {
            $data = array_merge($data, $overrides);
        }
        
        // Ensure unique code if duplicating
        if (isset($data['code']) && !isset($overrides['code'])) {
            $data['code'] = $data['code'] . '_copy_' . time();
        }
        
        // Set as inactive by default
        if (!isset($overrides['isActive'])) {
            $data['isActive'] = false;
        }
        
        return $this->createOffer($data);
    }
    
    public function getAvailableOffersForOrder(array $orderData): DataCollection
    {
        $offers = $this->repository->getValidOffersForOrder($orderData);
        
        // Filter offers based on validation
        $validOffers = [];
        foreach ($offers as $offer) {
            $validation = $this->validator->validate($offer, $orderData);
            if ($validation->isValid) {
                $validOffers[] = $offer;
            }
        }
        
        return OfferData::collect($validOffers, DataCollection::class);
    }
    
    public function applyOfferToOrder(int $offerId, array $orderData): AppliedOfferData
    {
        $offer = $this->repository->find($offerId);
        
        if (!$offer) {
            throw new \InvalidArgumentException("Offer with ID {$offerId} not found");
        }
        
        // Validate offer
        $validation = $this->validator->validate($offer, $orderData);
        if (!$validation->isValid) {
            throw new \InvalidArgumentException("Offer cannot be applied: {$validation->failureReason}");
        }
        
        // Calculate discount
        $calculation = $this->calculator->calculate($offer, $orderData);
        
        // Record usage
        if (!empty($orderData['customer_id'])) {
            $this->recordUsage($offerId, $orderData['customer_id'], $calculation->discountAmount);
        }
        
        return new AppliedOfferData(
            offerId: $offer->id,
            offerName: $offer->name,
            offerType: $offer->type,
            originalAmount: $calculation->originalAmount,
            discountAmount: $calculation->discountAmount,
            finalAmount: $calculation->finalAmount,
            code: $offer->code,
            orderId: $orderData['order_id'] ?? null,
            customerId: $orderData['customer_id'] ?? null,
            appliedToItems: $calculation->affectedItems,
            appliedAt: Carbon::now(),
        );
    }
    
    public function applyBestOfferToOrder(array $orderData): ?AppliedOfferData
    {
        $availableOffers = $this->getAvailableOffersForOrder($orderData);
        
        if ($availableOffers->isEmpty()) {
            return null;
        }
        
        // Find the best offer
        $bestOffer = $this->calculator->compareOffers($availableOffers->toArray(), $orderData);
        
        if (!$bestOffer) {
            return null;
        }
        
        return $this->applyOfferToOrder($bestOffer->id, $orderData);
    }
    
    public function validateOfferForOrder(int $offerId, array $orderData): bool
    {
        $offer = $this->repository->find($offerId);
        
        if (!$offer) {
            return false;
        }
        
        $validation = $this->validator->validate($offer, $orderData);
        
        return $validation->isValid;
    }
    
    public function calculateDiscount(OfferData $offer, array $orderData): float
    {
        $calculation = $this->calculator->calculate($offer, $orderData);
        
        return $calculation->discountAmount;
    }
    
    public function recordUsage(int $offerId, ?int $customerId = null, float $discountAmount = 0): bool
    {
        return DB::transaction(function () use ($offerId, $customerId, $discountAmount) {
            // Create usage record
            OfferUsage::create([
                'offer_id' => $offerId,
                'customer_id' => $customerId,
                'discount_amount' => $discountAmount,
                'order_amount' => 0, // To be updated by order service
                'used_at' => now(),
            ]);
            
            // Increment usage counter
            return $this->repository->incrementUsage($offerId);
        });
    }
    
    public function getOfferAnalytics(int $offerId, ?string $startDate = null, ?string $endDate = null): array
    {
        $stats = $this->repository->getUsageStats($offerId);
        
        if ($startDate && $endDate) {
            // Filter daily usage by date range
            $stats['daily_usage'] = array_filter($stats['daily_usage'], function ($day) use ($startDate, $endDate) {
                return $day['date'] >= $startDate && $day['date'] <= $endDate;
            });
        }
        
        // Calculate ROI if possible
        if ($stats['total_discount'] > 0 && $stats['total_order_value'] > 0) {
            $stats['roi'] = round((($stats['total_order_value'] - $stats['total_discount']) / $stats['total_discount']) * 100, 2);
        }
        
        return $stats;
    }
    
    public function bulkActivate(array $offerIds): int
    {
        $count = 0;
        foreach ($offerIds as $id) {
            if ($this->repository->activate($id)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    public function bulkDeactivate(array $offerIds): int
    {
        $count = 0;
        foreach ($offerIds as $id) {
            if ($this->repository->deactivate($id)) {
                $count++;
            }
        }
        
        return $count;
    }
}