<?php

declare(strict_types=1);

namespace Colame\Business\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'legal_name',
        'tax_id',
        'type',
        'status',
        'owner_id',
        'email',
        'phone',
        'website',
        'address',
        'address_line_2',
        'city',
        'state',
        'country',
        'postal_code',
        'currency',
        'timezone',
        'locale',
        'settings',
        'subscription_tier',
        'trial_ends_at',
        'subscription_ends_at',
        'features',
        'limits',
        'logo_url',
        'primary_color',
        'secondary_color',
        'metadata',
        'is_demo',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'settings' => 'array',
        'features' => 'array',
        'limits' => 'array',
        'metadata' => 'array',
        'is_demo' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Business $business) {
            // Generate slug if not provided
            if (empty($business->slug)) {
                $business->slug = Str::slug($business->name);
                
                // Ensure uniqueness
                $originalSlug = $business->slug;
                $count = 1;
                while (static::where('slug', $business->slug)->exists()) {
                    $business->slug = $originalSlug . '-' . $count++;
                }
            }

            // Set default limits based on subscription tier
            if (empty($business->limits)) {
                $business->limits = $business->getDefaultLimits();
            }

            // Set default features based on subscription tier
            if (empty($business->features)) {
                $business->features = $business->getDefaultFeatures();
            }
        });
    }

    /**
     * Get the owner of the business.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the users that belong to the business.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_users')
            ->withPivot([
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
            ])
            ->withTimestamps()
            ->using(BusinessUser::class);
    }

    /**
     * Get the business users pivot records.
     */
    public function businessUsers(): HasMany
    {
        return $this->hasMany(BusinessUser::class);
    }

    /**
     * Location relationships are handled through LocationRepositoryInterface
     * to maintain module boundaries.
     * 
     * Use app(LocationRepositoryInterface::class) to access location data
     * filtered by business_id.
     */

    /**
     * Get the current subscription for the business.
     */
    public function currentSubscription(): HasOne
    {
        return $this->hasOne(BusinessSubscription::class)
            ->where('status', 'active')
            ->latest();
    }

    /**
     * Get all subscriptions for the business.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(BusinessSubscription::class);
    }

    /**
     * Check if the business is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the business is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the business has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return !$this->subscription_ends_at || $this->subscription_ends_at->isFuture();
    }

    /**
     * Get default limits based on subscription tier.
     */
    protected function getDefaultLimits(): array
    {
        return match($this->subscription_tier) {
            'basic' => [
                'locations' => 1,
                'users' => 5,
                'items' => 100,
                'orders_per_month' => 1000,
            ],
            'pro' => [
                'locations' => 5,
                'users' => 25,
                'items' => 1000,
                'orders_per_month' => 10000,
            ],
            'enterprise' => [
                'locations' => null,
                'users' => null,
                'items' => null,
                'orders_per_month' => null,
            ],
            default => [
                'locations' => 1,
                'users' => 5,
                'items' => 100,
                'orders_per_month' => 1000,
            ],
        };
    }

    /**
     * Get default features based on subscription tier.
     */
    protected function getDefaultFeatures(): array
    {
        return match($this->subscription_tier) {
            'basic' => [
                'basic_reporting',
                'inventory_management',
                'order_management',
                'staff_management',
            ],
            'pro' => [
                'basic_reporting',
                'advanced_reporting',
                'inventory_management',
                'order_management',
                'staff_management',
                'multi_location',
                'api_access',
                'custom_branding',
            ],
            'enterprise' => [
                'basic_reporting',
                'advanced_reporting',
                'custom_reporting',
                'inventory_management',
                'order_management',
                'staff_management',
                'multi_location',
                'api_access',
                'custom_branding',
                'white_label',
                'dedicated_support',
                'custom_integrations',
            ],
            default => [
                'basic_reporting',
                'inventory_management',
                'order_management',
                'staff_management',
            ],
        };
    }

    /**
     * Check if the business has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Check if the business has reached a limit.
     */
    public function hasReachedLimit(string $resource): bool
    {
        $limit = $this->limits[$resource] ?? null;
        
        if ($limit === null) {
            return false; // No limit set or unlimited
        }

        $current = match($resource) {
            'locations' => DB::table('locations')->where('business_id', $this->id)->count(),
            'users' => $this->users()->count(),
            'items' => 0, // Will need to be implemented when items module is updated
            default => 0,
        };

        return $current >= $limit;
    }
}