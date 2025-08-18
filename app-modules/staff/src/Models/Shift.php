<?php

namespace Colame\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Colame\Staff\Enums\ShiftStatus;

class Shift extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'staff_member_id',
        'location_id',
        'start_time',
        'end_time',
        'break_duration',
        'status',
        'actual_start',
        'actual_end',
        'notes',
        'created_by',
        'approved_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'break_duration' => 'integer',
        'status' => ShiftStatus::class,
    ];

    /**
     * Get the staff member for this shift.
     */
    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    /**
     * Get the location for this shift.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(\Colame\Location\Models\Location::class);
    }

    /**
     * Get the creator of this shift.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'created_by');
    }

    /**
     * Get the approver of this shift.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'approved_by');
    }

    /**
     * Get the attendance record for this shift.
     */
    public function attendance()
    {
        return $this->hasOne(AttendanceRecord::class);
    }

    /**
     * Calculate the duration in hours.
     */
    public function getDurationInHours(): float
    {
        return $this->start_time->diffInMinutes($this->end_time) / 60;
    }

    /**
     * Calculate working hours (excluding break).
     */
    public function getWorkingHours(): float
    {
        $totalMinutes = $this->start_time->diffInMinutes($this->end_time);
        $workingMinutes = $totalMinutes - $this->break_duration;
        return $workingMinutes / 60;
    }

    /**
     * Check if the shift is ongoing.
     */
    public function isOngoing(): bool
    {
        $now = now();
        return $this->start_time->lte($now) && $this->end_time->gte($now);
    }

    /**
     * Check if the shift is in the future.
     */
    public function isFuture(): bool
    {
        return $this->start_time->gt(now());
    }

    /**
     * Check if the shift is in the past.
     */
    public function isPast(): bool
    {
        return $this->end_time->lt(now());
    }

    /**
     * Scope for scheduled shifts.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', ShiftStatus::SCHEDULED);
    }

    /**
     * Scope for shifts in a date range.
     */
    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('start_time', [$start, $end]);
    }

    /**
     * Scope for shifts at a location.
     */
    public function scopeAtLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope for shifts for a staff member.
     */
    public function scopeForStaffMember($query, $staffMemberId)
    {
        return $query->where('staff_member_id', $staffMemberId);
    }
}