<?php

namespace Colame\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Colame\Staff\Enums\StaffStatus;
use Spatie\Permission\Traits\HasRoles;

class StaffMember extends Model
{
    use HasFactory, HasRoles;
    
    /**
     * The guard name for the model.
     *
     * @var string|array
     */
    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'date_of_birth',
        'hire_date',
        'national_id',
        'tax_id',
        'emergency_contacts',
        'bank_details',
        'hourly_rate',
        'monthly_salary',
        'status',
        'metadata',
        'terminated_at',
        'profile_photo_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'address' => 'array',
        'emergency_contacts' => 'array',
        'bank_details' => 'encrypted:array',
        'metadata' => 'array',
        'status' => StaffStatus::class,
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'terminated_at' => 'datetime',
    ];

    /**
     * Get the shifts for the staff member.
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * Get the attendance records for the staff member.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Get the emergency contacts for the staff member.
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    /**
     * Get roles with location assignments.
     */
    public function rolesWithLocations(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'staff_location_roles',
            'staff_member_id',
            'role_id'
        )->withPivot(['location_id', 'assigned_at', 'assigned_by', 'expires_at'])
          ->withTimestamps();
    }

    /**
     * Alias for rolesWithLocations for consistency
     */
    public function locationRoles(): BelongsToMany
    {
        return $this->rolesWithLocations();
    }

    /**
     * Simpler roles relationship without location data
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'staff_location_roles',
            'staff_member_id',
            'role_id'
        );
    }

    /**
     * Get locations through roles pivot
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(
            \Colame\Location\Models\Location::class,
            'staff_location_roles',
            'staff_member_id',
            'location_id'
        );
    }

    /**
     * Get roles for a specific location.
     */
    public function rolesAtLocation($locationId): BelongsToMany
    {
        return $this->rolesWithLocations()
            ->wherePivot('location_id', $locationId);
    }

    /**
     * Assign a role with optional location.
     */
    public function assignRoleWithLocation($role, $locationId = null, $assignedBy = null)
    {
        $roleId = is_numeric($role) ? $role : Role::findByName($role)->id;
        
        return $this->rolesWithLocations()->attach($roleId, [
            'location_id' => $locationId,
            'assigned_at' => now(),
            'assigned_by' => $assignedBy,
        ]);
    }

    /**
     * Remove a role with optional location.
     */
    public function removeRoleWithLocation($role, $locationId = null)
    {
        $roleId = is_numeric($role) ? $role : Role::findByName($role)->id;
        
        return $this->rolesWithLocations()
            ->wherePivot('location_id', $locationId)
            ->detach($roleId);
    }

    /**
     * Check if staff member has a role at a specific location.
     */
    public function hasRoleAtLocation($role, $locationId): bool
    {
        $roleName = is_numeric($role) ? Role::find($role)->name : $role;
        
        return $this->rolesWithLocations()
            ->where('name', $roleName)
            ->wherePivot('location_id', $locationId)
            ->exists();
    }

    /**
     * Get the full name of the staff member.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get years of service.
     */
    public function getYearsOfServiceAttribute(): int
    {
        return $this->hire_date->diffInYears(now());
    }

    /**
     * Check if staff member is active.
     */
    public function isActive(): bool
    {
        return $this->status === StaffStatus::ACTIVE;
    }

    /**
     * Get current active attendance record.
     */
    public function activeAttendance()
    {
        return $this->attendanceRecords()
            ->whereNull('clock_out_time')
            ->latest()
            ->first();
    }

    /**
     * Scope for active staff members.
     */
    public function scopeActive($query)
    {
        return $query->where('status', StaffStatus::ACTIVE);
    }

    /**
     * Scope for staff at a specific location.
     */
    public function scopeAtLocation($query, $locationId)
    {
        return $query->whereHas('rolesWithLocations', function ($q) use ($locationId) {
            $q->where('location_id', $locationId);
        });
    }
}