<?php

declare(strict_types=1);

namespace Colame\OrderEs\QueryHandlers;

use Colame\OrderEs\Queries\{
    GetOrder,
    GetOrdersByStatus,
    GetKitchenOrders
};
use Colame\OrderEs\ReadModels\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class OrderQueryHandler
{
    public function handleGetOrder(GetOrder $query): ?Order
    {
        return Order::with('items')->find($query->orderId);
    }
    
    public function handleGetOrdersByStatus(GetOrdersByStatus $query): LengthAwarePaginator
    {
        $qb = Order::with('items')
            ->whereIn('status', $query->statuses);
            
        if ($query->locationId) {
            $qb->where('location_id', $query->locationId);
        }
        
        return $qb->orderBy('started_at', 'desc')
            ->paginate($query->perPage);
    }
    
    public function handleGetKitchenOrders(GetKitchenOrders $query): Collection
    {
        return Order::with('items')
            ->where('location_id', $query->locationId)
            ->whereIn('status', $query->statuses)
            ->orderBy('confirmed_at', 'asc')
            ->get();
    }
}