<?php

declare(strict_types=1);

namespace Colame\Order\Services;

use Colame\Order\Models\Order;

class OrderStatusService
{
    /**
     * Define valid status transitions
     */
    private const TRANSITIONS = [
        'draft' => ['placed', 'cancelled'],
        'placed' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled', 'placed'],
        'preparing' => ['ready', 'confirmed', 'cancelled'],
        'ready' => ['delivering', 'completed', 'preparing', 'cancelled'],
        'delivering' => ['delivered', 'ready', 'cancelled'],
        'delivered' => ['completed', 'delivering'],
        'completed' => [], // Terminal state
        'cancelled' => [], // Terminal state
        'refunded' => [], // Terminal state
    ];

    /**
     * Transitions that require a reason
     */
    private const REQUIRES_REASON = [
        // Going backwards
        'confirmed->placed' => true,
        'preparing->confirmed' => true,
        'preparing->placed' => true,
        'ready->preparing' => true,
        'ready->confirmed' => true,
        'ready->placed' => true,
        'delivering->ready' => true,
        'delivering->preparing' => true,
        'delivered->delivering' => true,
        'delivered->ready' => true,
        // Cancellation always requires reason
        '*->cancelled' => true,
    ];

    /**
     * Check if a status transition is valid
     */
    public function canTransition(string $fromStatus, string $toStatus, ?string $orderType = null): bool
    {
        // Same status is not a transition
        if ($fromStatus === $toStatus) {
            return false;
        }

        // Check delivery-specific statuses
        if (in_array($toStatus, ['delivering', 'delivered']) && $orderType !== 'delivery') {
            return false;
        }

        // Check if transition is allowed
        return in_array($toStatus, self::TRANSITIONS[$fromStatus] ?? []);
    }

    /**
     * Check if a transition requires a reason
     */
    public function requiresReason(string $fromStatus, string $toStatus): bool
    {
        // Check specific transition
        $transitionKey = "{$fromStatus}->{$toStatus}";
        if (isset(self::REQUIRES_REASON[$transitionKey])) {
            return self::REQUIRES_REASON[$transitionKey];
        }

        // Check wildcard rules
        if ($toStatus === 'cancelled') {
            return true;
        }

        // Going backwards in the flow requires reason
        $fromIndex = array_search($fromStatus, array_keys(self::TRANSITIONS));
        $toIndex = array_search($toStatus, array_keys(self::TRANSITIONS));
        
        return $fromIndex !== false && $toIndex !== false && $toIndex < $fromIndex;
    }

    /**
     * Get available transitions for a given status
     */
    public function getAvailableTransitions(string $currentStatus, ?string $orderType = null): array
    {
        $transitions = self::TRANSITIONS[$currentStatus] ?? [];

        // Filter out delivery statuses for non-delivery orders
        if ($orderType !== 'delivery') {
            $transitions = array_filter($transitions, function ($status) {
                return !in_array($status, ['delivering', 'delivered']);
            });
        }

        return array_values($transitions);
    }

    /**
     * Validate and perform status transition
     */
    public function transitionStatus(Order $order, string $newStatus, ?string $reason = null): array
    {
        $currentStatus = $order->status;
        
        // Validate transition
        if (!$this->canTransition($currentStatus, $newStatus, $order->type)) {
            return [
                'success' => false,
                'error' => "Cannot transition from {$currentStatus} to {$newStatus}",
            ];
        }

        // Check if reason is required
        if ($this->requiresReason($currentStatus, $newStatus) && empty($reason)) {
            return [
                'success' => false,
                'error' => 'This status change requires a reason',
            ];
        }

        // Update status and timestamps
        $updateData = ['status' => $newStatus];
        
        // Set appropriate timestamp
        $timestampField = $this->getTimestampField($newStatus);
        if ($timestampField) {
            $updateData[$timestampField] = now();
        }

        // Clear future timestamps if going backwards
        if ($this->isBackwardTransition($currentStatus, $newStatus)) {
            $clearTimestamps = $this->getTimestampsToClear($currentStatus, $newStatus);
            foreach ($clearTimestamps as $field) {
                $updateData[$field] = null;
            }
        }

        // Update the order
        $order->update($updateData);

        // Log status change
        $this->logStatusChange($order, $currentStatus, $newStatus, $reason);

        return [
            'success' => true,
            'message' => "Order status updated to {$newStatus}",
        ];
    }

    /**
     * Get timestamp field for a status
     */
    private function getTimestampField(string $status): ?string
    {
        $fields = [
            'placed' => 'placed_at',
            'confirmed' => 'confirmed_at',
            'preparing' => 'preparing_at',
            'ready' => 'ready_at',
            'delivering' => 'delivering_at',
            'delivered' => 'delivered_at',
            'completed' => 'completed_at',
            'cancelled' => 'cancelled_at',
        ];

        return $fields[$status] ?? null;
    }

    /**
     * Check if transition is backwards
     */
    private function isBackwardTransition(string $fromStatus, string $toStatus): bool
    {
        $statusOrder = ['draft', 'placed', 'confirmed', 'preparing', 'ready', 'delivering', 'delivered', 'completed'];
        $fromIndex = array_search($fromStatus, $statusOrder);
        $toIndex = array_search($toStatus, $statusOrder);

        return $fromIndex !== false && $toIndex !== false && $toIndex < $fromIndex;
    }

    /**
     * Get timestamps to clear for backward transition
     */
    private function getTimestampsToClear(string $fromStatus, string $toStatus): array
    {
        $statusTimestamps = [
            'placed' => ['confirmed_at', 'preparing_at', 'ready_at', 'delivering_at', 'delivered_at', 'completed_at'],
            'confirmed' => ['preparing_at', 'ready_at', 'delivering_at', 'delivered_at', 'completed_at'],
            'preparing' => ['ready_at', 'delivering_at', 'delivered_at', 'completed_at'],
            'ready' => ['delivering_at', 'delivered_at', 'completed_at'],
            'delivering' => ['delivered_at', 'completed_at'],
            'delivered' => ['completed_at'],
        ];

        return $statusTimestamps[$toStatus] ?? [];
    }

    /**
     * Log status change
     */
    private function logStatusChange(Order $order, string $fromStatus, string $toStatus, ?string $reason = null): void
    {
        // TODO: Implement status history logging
        // This could be saved to a separate status_history table or activity log
    }
}