<?php

declare(strict_types=1);

namespace Colame\Offer\Data;

use App\Core\Data\BaseData;
use Carbon\Carbon;

class OfferUsageData extends BaseData
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $offerId,
        public readonly ?int $orderId,
        public readonly ?int $customerId,
        public readonly ?string $customerEmail,
        public readonly float $discountAmount,
        public readonly float $orderAmount,
        public readonly ?string $code,
        public readonly ?array $metadata,
        public readonly Carbon $usedAt,
    ) {}
    
    public static function fromModel($usage): self
    {
        return new self(
            id: $usage->id,
            offerId: $usage->offer_id,
            orderId: $usage->order_id,
            customerId: $usage->customer_id,
            customerEmail: $usage->customer_email,
            discountAmount: $usage->discount_amount,
            orderAmount: $usage->order_amount,
            code: $usage->code,
            metadata: $usage->metadata,
            usedAt: Carbon::parse($usage->used_at),
        );
    }
}