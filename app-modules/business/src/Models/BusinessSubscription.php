<?php

declare(strict_types=1);

namespace Colame\Business\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSubscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'business_id',
        'plan_id',
        'plan_name',
        'price',
        'currency',
        'billing_cycle',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'trial_ends_at',
        'payment_method',
        'payment_metadata',
        'last_payment_at',
        'next_payment_at',
        'usage_limits',
        'current_usage',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'float',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'next_payment_at' => 'datetime',
        'payment_metadata' => 'array',
        'usage_limits' => 'array',
        'current_usage' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the business that owns the subscription.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the subscription is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' || $this->cancelled_at !== null;
    }

    /**
     * Check if the subscription has expired.
     */
    public function hasExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Renew the subscription.
     */
    public function renew(): void
    {
        $nextPeriod = match($this->billing_cycle) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };

        $this->update([
            'status' => 'active',
            'ends_at' => $nextPeriod,
            'last_payment_at' => now(),
            'next_payment_at' => $nextPeriod,
        ]);
    }

    /**
     * Update usage for a specific resource.
     */
    public function updateUsage(string $resource, int $amount): void
    {
        $currentUsage = $this->current_usage ?? [];
        $currentUsage[$resource] = $amount;
        
        $this->update(['current_usage' => $currentUsage]);
    }

    /**
     * Check if a usage limit has been reached.
     */
    public function hasReachedLimit(string $resource): bool
    {
        $limit = $this->usage_limits[$resource] ?? null;
        $current = $this->current_usage[$resource] ?? 0;
        
        if ($limit === null) {
            return false; // No limit or unlimited
        }
        
        return $current >= $limit;
    }
}