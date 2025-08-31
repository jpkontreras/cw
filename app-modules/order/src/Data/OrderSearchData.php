<?php

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\DateFormat;

class OrderSearchData extends BaseData
{
    public function __construct(
        public int $id,
        public string $orderNumber,
        public string $status,
        public string $type,
        public ?string $customerName,
        public ?string $customerPhone,
        public float $totalAmount,
        public string $paymentStatus,
        public ?int $locationId,
        public ?int $waiterId,
        public ?string $tableNumber,
        #[DateFormat('Y-m-d H:i:s')]
        public ?string $placedAt,
        #[DateFormat('Y-m-d H:i:s')]
        public string $createdAt,
        public ?float $searchScore = null,
        public ?string $matchReason = null,
    ) {}
    
    #[Computed]
    public function formattedTotal(): string
    {
        return '$' . number_format($this->totalAmount, 2);
    }
    
    #[Computed]
    public function statusBadgeColor(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'preparing' => 'orange',
            'ready' => 'green',
            'delivered' => 'gray',
            'cancelled' => 'red',
            default => 'gray',
        };
    }
    
    #[Computed]
    public function timeAgo(): string
    {
        $date = new \DateTime($this->placedAt ?? $this->createdAt);
        $now = new \DateTime();
        $diff = $now->diff($date);
        
        if ($diff->days > 0) {
            return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } else {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        }
    }
}