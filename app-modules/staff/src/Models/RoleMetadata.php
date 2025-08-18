<?php

namespace Colame\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleMetadata extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'staff_role_metadata';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'role_id',
        'hierarchy_level',
        'description',
        'is_system',
        'permissions_summary',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hierarchy_level' => 'integer',
        'is_system' => 'boolean',
        'permissions_summary' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the role that owns this metadata.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if this role is higher in hierarchy than another.
     */
    public function isHigherThan(RoleMetadata $other): bool
    {
        return $this->hierarchy_level > $other->hierarchy_level;
    }

    /**
     * Check if this role is at least as high as another.
     */
    public function isAtLeast(RoleMetadata $other): bool
    {
        return $this->hierarchy_level >= $other->hierarchy_level;
    }

    /**
     * Get the hierarchy label based on level.
     */
    public function getHierarchyLabel(): string
    {
        if ($this->hierarchy_level >= 90) return 'Super Admin';
        if ($this->hierarchy_level >= 70) return 'Admin';
        if ($this->hierarchy_level >= 50) return 'Manager';
        if ($this->hierarchy_level >= 30) return 'Supervisor';
        return 'Staff';
    }

    /**
     * Scope to get system roles only.
     */
    public function scopeSystemRoles($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get custom roles only.
     */
    public function scopeCustomRoles($query)
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

    /**
     * Scope to get roles by maximum hierarchy level.
     */
    public function scopeMaxLevel($query, int $level)
    {
        return $query->where('hierarchy_level', '<=', $level);
    }
}