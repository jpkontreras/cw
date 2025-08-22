<?php

declare(strict_types=1);

namespace Colame\Offer\Contracts;

use Colame\Offer\Data\OfferData;
use Spatie\LaravelData\DataCollection;
use App\Core\Data\PaginatedResourceData;

interface OfferRepositoryInterface
{
    public function find(int $id): ?OfferData;
    
    public function findByCode(string $code): ?OfferData;
    
    public function all(): DataCollection;
    
    public function paginate(int $perPage = 15, array $filters = []): PaginatedResourceData;
    
    public function getActive(): DataCollection;
    
    public function getActiveForLocation(int $locationId): DataCollection;
    
    public function getValidOffersForOrder(array $orderData): DataCollection;
    
    public function create(array $data): OfferData;
    
    public function update(int $id, array $data): OfferData;
    
    public function delete(int $id): bool;
    
    public function activate(int $id): bool;
    
    public function deactivate(int $id): bool;
    
    public function incrementUsage(int $id): bool;
    
    public function getUsageStats(int $id): array;
    
    public function getOfferStats(): array;
}