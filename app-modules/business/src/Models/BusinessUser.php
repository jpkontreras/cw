<?php

declare(strict_types=1);

namespace Colame\Business\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

class BusinessUser extends Pivot
{
    /**
     * The table associated with the model.
     */
    protected $table = 'business_users';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'business_id',
        'user_id',
        'role',
        'permissions',
        'status',
        'is_owner',
        'invitation_token',
        'invited_at',
        'joined_at',
        'invited_by',
        'last_accessed_at',
        'preferences',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'permissions' => 'array',
        'preferences' => 'array',
        'is_owner' => 'boolean',
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BusinessUser $businessUser) {
            // Generate invitation token if status is pending and token is not set
            if ($businessUser->status === 'pending' && !$businessUser->invitation_token) {
                $businessUser->invitation_token = Str::random(32);
                $businessUser->invited_at = now();
            }

            // Set joined_at if status is active
            if ($businessUser->status === 'active' && !$businessUser->joined_at) {
                $businessUser->joined_at = now();
            }
        });
    }

    /**
     * Get the business.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who invited this user.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if the user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the user is pending invitation.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Accept the invitation.
     */
    public function acceptInvitation(): void
    {
        $this->update([
            'status' => 'active',
            'joined_at' => now(),
            'invitation_token' => null,
        ]);
    }

    /**
     * Update last accessed time.
     */
    public function touchLastAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Owner has all permissions
        if ($this->is_owner) {
            return true;
        }

        // Check role-based permissions
        $rolePermissions = match($this->role) {
            'owner' => ['*'], // All permissions
            'admin' => ['manage_users', 'manage_settings', 'manage_locations', 'manage_staff', 'view_reports'],
            'manager' => ['manage_locations', 'manage_staff', 'view_reports', 'manage_operations'],
            'member' => ['access_business'],
            default => [],
        };

        if (in_array('*', $rolePermissions) || in_array($permission, $rolePermissions)) {
            return true;
        }

        // Check custom permissions
        return in_array($permission, $this->permissions ?? []);
    }
}