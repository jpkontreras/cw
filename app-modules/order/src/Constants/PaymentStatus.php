<?php

namespace Colame\Order\Constants;

class PaymentStatus
{
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const COMPLETED = 'completed';
    public const SUCCESS = 'success';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';
    public const REFUNDED = 'refunded';
    public const PARTIALLY_REFUNDED = 'partially_refunded';
    
    /**
     * Check if payment is successful
     */
    public static function isSuccessful(string $status): bool
    {
        return in_array($status, [self::COMPLETED, self::SUCCESS]);
    }
    
    /**
     * Check if payment is final
     */
    public static function isFinal(string $status): bool
    {
        return in_array($status, [
            self::COMPLETED,
            self::SUCCESS,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED
        ]);
    }
    
    /**
     * Get all statuses
     */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::COMPLETED,
            self::SUCCESS,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
            self::PARTIALLY_REFUNDED,
        ];
    }
}