<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Colame\Location\Models\Location;
use Colame\Onboarding\Traits\HasOnboarding;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasOnboarding;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'current_location_id',
        'default_location_id',
        'current_business_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the locations accessible by this user.
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_user')
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Get the user's current location.
     */
    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    /**
     * Get the user's default location.
     */
    public function defaultLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }

    /**
     * Get the user's primary location.
     */
    public function primaryLocation()
    {
        return $this->locations()->wherePivot('is_primary', true)->first();
    }

    /**
     * Check if user has access to a specific location.
     */
    public function hasAccessToLocation(int $locationId): bool
    {
        return $this->locations()->where('location_id', $locationId)->exists();
    }

    /**
     * Get the user's role at a specific location.
     */
    public function getRoleAtLocation(int $locationId): ?string
    {
        $location = $this->locations()->where('location_id', $locationId)->first();
        
        return $location ? $location->pivot->role : null;
    }

    /**
     * Check if user is a manager at any location.
     */
    public function isManagerAtAnyLocation(): bool
    {
        return $this->locations()->wherePivot('role', 'manager')->exists();
    }

    /**
     * Get effective location (current > default > primary > first).
     */
    public function getEffectiveLocation(): ?Location
    {
        if ($this->current_location_id && $this->currentLocation) {
            return $this->currentLocation;
        }

        if ($this->default_location_id && $this->defaultLocation) {
            return $this->defaultLocation;
        }

        if ($primaryLocation = $this->primaryLocation()) {
            return $primaryLocation;
        }

        return $this->locations()->first();
    }

    /**
     * NOTE: Business relationships are handled through BusinessService
     * to maintain proper module boundaries. Use app(BusinessServiceInterface::class)
     * to access business-related data for this user.
     */
}
