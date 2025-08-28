<?php

declare(strict_types=1);

namespace Colame\Location\Models;

use App\Models\User;
use Colame\Business\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToBusiness;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'status',
        'address',
        'address_line_2',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'timezone',
        'currency',
        'opening_hours',
        'delivery_radius',
        'capabilities',
        'parent_location_id',
        'manager_id',
        'metadata',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'opening_hours' => 'array',
        'capabilities' => 'array',
        'metadata' => 'array',
        'delivery_radius' => 'float',
        'is_default' => 'boolean',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the parent location.
     */
    public function parentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_location_id');
    }

    /**
     * Get the child locations.
     */
    public function childLocations(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_location_id');
    }

    /**
     * Get the location manager.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the users assigned to this location.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'location_user')
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Get the staff users for this location.
     */
    public function staff(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'staff');
    }

    /**
     * Get the managers for this location.
     */
    public function managers(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'manager');
    }

    /**
     * Get the location settings.
     */
    public function settings(): HasMany
    {
        return $this->hasMany(LocationSetting::class);
    }

    /**
     * Get users who have this as their current location.
     */
    public function currentUsers(): HasMany
    {
        return $this->hasMany(User::class, 'current_location_id');
    }

    /**
     * Get users who have this as their default location.
     */
    public function defaultUsers(): HasMany
    {
        return $this->hasMany(User::class, 'default_location_id');
    }

    /**
     * Scope to get only active locations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only default location.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Check if location is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if location is the default.
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Check if location has delivery capability.
     */
    public function hasDelivery(): bool
    {
        return in_array('delivery', $this->capabilities ?? []);
    }

    /**
     * Check if location has dine-in capability.
     */
    public function hasDineIn(): bool
    {
        return in_array('dine_in', $this->capabilities ?? []);
    }

    /**
     * Check if location has takeout capability.
     */
    public function hasTakeout(): bool
    {
        return in_array('takeout', $this->capabilities ?? []);
    }

    /**
     * Check if location has catering capability.
     */
    public function hasCatering(): bool
    {
        return in_array('catering', $this->capabilities ?? []);
    }

    /**
     * Get a specific setting value.
     */
    public function getSetting(string $key, $default = null)
    {
        $setting = $this->settings()->where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return $setting->getValue();
    }

    /**
     * Set a specific setting value.
     */
    public function setSetting(string $key, $value, string $type = 'string', ?string $description = null): void
    {
        $this->settings()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) || is_object($value) ? json_encode($value) : $value,
                'type' => $type,
                'description' => $description,
            ]
        );
    }

    /**
     * Get the full address as a single string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the display name with code.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }

    /**
     * Check if the location is open at a specific time.
     */
    public function isOpenAt(\DateTimeInterface $dateTime): bool
    {
        $dayOfWeek = strtolower($dateTime->format('l'));
        $time = $dateTime->format('H:i');

        $hours = $this->opening_hours[$dayOfWeek] ?? null;

        if (!$hours || !isset($hours['open']) || !isset($hours['close'])) {
            return false;
        }

        // Handle cases where closing time is after midnight
        if ($hours['close'] < $hours['open']) {
            return $time >= $hours['open'] || $time <= $hours['close'];
        }

        return $time >= $hours['open'] && $time <= $hours['close'];
    }

    /**
     * Check if the location is currently open.
     */
    public function isOpen(): bool
    {
        return $this->isOpenAt(now());
    }
}