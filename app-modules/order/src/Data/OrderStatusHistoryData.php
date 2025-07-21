<?php

declare(strict_types=1);

namespace Colame\Order\Data;

use App\Core\Data\BaseData;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Order status history data transfer object
 */
#[TypeScript]
class OrderStatusHistoryData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $orderId,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly ?int $userId = null,
        public readonly ?string $reason = null,
        public readonly ?array $metadata = null,
        public readonly ?\DateTimeInterface $createdAt = null,
    ) {}

    /**
     * Get status transition label
     */
    public function getTransitionLabel(): string
    {
        return sprintf('%s → %s', $this->getStatusLabel($this->fromStatus), $this->getStatusLabel($this->toStatus));
    }

    /**
     * Get formatted status label
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'placed' => 'Placed',
            'confirmed' => 'Confirmed',
            'preparing' => 'Preparing',
            'ready' => 'Ready',
            'delivering' => 'Delivering',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => ucfirst($status),
        };
    }

    /**
     * Get from status label
     */
    public function getFromStatusLabel(): string
    {
        return $this->getStatusLabel($this->fromStatus);
    }

    /**
     * Get to status label
     */
    public function getToStatusLabel(): string
    {
        return $this->getStatusLabel($this->toStatus);
    }

    /**
     * Get status change description
     */
    public function getDescription(): string
    {
        $action = match ($this->toStatus) {
            'placed' => 'placed',
            'confirmed' => 'confirmed',
            'preparing' => 'started preparing',
            'ready' => 'marked as ready',
            'delivering' => 'out for delivery',
            'delivered' => 'delivered',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            default => "changed to {$this->toStatus}",
        };

        return "Order {$action}";
    }

    /**
     * Check if this was a cancellation
     */
    public function isCancellation(): bool
    {
        return $this->toStatus === 'cancelled';
    }

    /**
     * Check if this was a completion
     */
    public function isCompletion(): bool
    {
        return in_array($this->toStatus, ['completed', 'delivered']);
    }

    /**
     * Get duration in this status (if transitioned to another)
     */
    public function getDurationInMinutes(\DateTimeInterface $nextTransitionTime): int
    {
        return (int) $this->createdAt->diff($nextTransitionTime)->i;
    }
}