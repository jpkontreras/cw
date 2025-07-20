<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;

/**
 * Order data with related entities
 */
class OrderWithRelationsData extends BaseData
{
    public function __construct(
        public readonly OrderData $order,
        public readonly ?object $user = null,
        public readonly ?object $location = null,
        public readonly ?array $payments = null,
        public readonly ?array $offers = null,
    ) {}

    /**
     * Get user name
     */
    public function getUserName(): ?string
    {
        return $this->user?->name ?? $this->order->customerName;
    }

    /**
     * Get location name
     */
    public function getLocationName(): ?string
    {
        return $this->location?->name ?? null;
    }

    /**
     * Check if order is paid
     */
    public function isPaid(): bool
    {
        if (!$this->payments) {
            return false;
        }

        $totalPaid = array_sum(array_map(fn($payment) => $payment->amount ?? 0, $this->payments));
        return $totalPaid >= $this->order->totalAmount;
    }

    /**
     * Get remaining payment amount
     */
    public function getRemainingAmount(): float
    {
        if (!$this->payments) {
            return $this->order->totalAmount;
        }

        $totalPaid = array_sum(array_map(fn($payment) => $payment->amount ?? 0, $this->payments));
        return max(0, $this->order->totalAmount - $totalPaid);
    }

    /**
     * Get applied offer codes
     */
    public function getAppliedOfferCodes(): array
    {
        if (!$this->offers) {
            return [];
        }

        return array_map(fn($offer) => $offer->code ?? '', $this->offers);
    }

    /**
     * Convert to array with relations
     */
    public function toArray(): array
    {
        return [
            'order' => $this->order->toArray(),
            'user' => $this->user ? (array) $this->user : null,
            'location' => $this->location ? (array) $this->location : null,
            'payments' => $this->payments,
            'offers' => $this->offers,
            'isPaid' => $this->isPaid(),
            'remainingAmount' => $this->getRemainingAmount(),
        ];
    }
}