<?php

namespace Colame\Staff\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Role extends SpatieRole
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['hierarchy_level', 'description', 'is_system'];

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        static::created(function ($role) {
            // Auto-create metadata when role is created
            $role->metadata()->create([
                'hierarchy_level' => 10,
                'is_system' => false,
            ]);
        });

        static::deleting(function ($role) {
            // Metadata will be deleted automatically via cascade
        });
    }

    /**
     * Get the metadata for this role.
     */
    public function metadata(): HasOne
    {
        return $this->hasOne(RoleMetadata::class);
    }

    /**
     * Get hierarchy level from metadata.
     */
    public function getHierarchyLevelAttribute(): int
    {
        return $this->metadata?->hierarchy_level ?? 10;
    }

    /**
     * Get description from metadata.
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->metadata?->description;
    }

    /**
     * Get is_system from metadata.
     */
    public function getIsSystemAttribute(): bool
    {
        return $this->metadata?->is_system ?? false;
    }

    /**
     * Set hierarchy level in metadata.
     */
    public function setHierarchyLevelAttribute($value): void
    {
        if (!$this->exists) {
            return;
        }
        
        $this->metadata()->updateOrCreate(
            ['role_id' => $this->id],
            ['hierarchy_level' => $value]
        );
    }

    /**
     * Set description in metadata.
     */
    public function setDescriptionAttribute($value): void
    {
        if (!$this->exists) {
            return;
        }
        
        $this->metadata()->updateOrCreate(
            ['role_id' => $this->id],
            ['description' => $value]
        );
    }

    /**
     * Set is_system in metadata.
     */
    public function setIsSystemAttribute($value): void
    {
        if (!$this->exists) {
            return;
        }
        
        $this->metadata()->updateOrCreate(
            ['role_id' => $this->id],
            ['is_system' => $value]
        );
    }

    /**
     * Get the staff members with this role at a specific location.
     */
    public function staffAtLocation($locationId = null)
    {
        $query = $this->belongsToMany(
            StaffMember::class,
            'staff_location_roles',
            'role_id',
            'staff_member_id'
        )->withPivot(['location_id', 'assigned_at', 'assigned_by', 'expires_at'])
          ->withTimestamps();

        if ($locationId) {
            $query->wherePivot('location_id', $locationId);
        }

        return $query;
    }

    /**
     * Check if this role is higher in hierarchy than another role.
     */
    public function isHigherThan(Role $otherRole): bool
    {
        return $this->hierarchy_level > $otherRole->hierarchy_level;
    }

    /**
     * Check if this role is at least as high as another role.
     */
    public function isAtLeast(Role $otherRole): bool
    {
        return $this->hierarchy_level >= $otherRole->hierarchy_level;
    }

    /**
     * Scope to get system roles only.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get custom roles only.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope to get roles by minimum hierarchy level.
     */
    public function scopeMinLevel($query, int $level)
    {
        return $query->where('hierarchy_level', '>=', $level);
    }
}