<?php

namespace Colame\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Colame\Staff\Enums\AttendanceStatus;
use Colame\Staff\Enums\ClockMethod;

class AttendanceRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'staff_member_id',
        'shift_id',
        'location_id',
        'clock_in_time',
        'clock_out_time',
        'clock_in_method',
        'clock_out_method',
        'clock_in_location',
        'clock_out_location',
        'break_start',
        'break_end',
        'overtime_minutes',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'clock_in_method' => ClockMethod::class,
        'clock_out_method' => ClockMethod::class,
        'clock_in_location' => 'array',
        'clock_out_location' => 'array',
        'overtime_minutes' => 'integer',
        'status' => AttendanceStatus::class,
    ];

    /**
     * Get the staff member for this attendance record.
     */
    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    /**
     * Get the shift for this attendance record.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the location for this attendance record.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(\Colame\Location\Models\Location::class);
    }

    /**
     * Check if currently clocked in.
     */
    public function isActive(): bool
    {
        return $this->clock_in_time && !$this->clock_out_time;
    }

    /**
     * Calculate total hours worked.
     */
    public function getTotalHours(): ?float
    {
        if (!$this->clock_out_time) {
            return null;
        }

        $totalMinutes = $this->clock_in_time->diffInMinutes($this->clock_out_time);
        
        // Subtract break time if applicable
        if ($this->break_start && $this->break_end) {
            $breakMinutes = $this->break_start->diffInMinutes($this->break_end);
            $totalMinutes -= $breakMinutes;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Calculate regular hours (excluding overtime).
     */
    public function getRegularHours(): ?float
    {
        $totalHours = $this->getTotalHours();
        
        if (!$totalHours) {
            return null;
        }

        $overtimeHours = $this->overtime_minutes / 60;
        return max(0, $totalHours - $overtimeHours);
    }

    /**
     * Calculate overtime hours.
     */
    public function getOvertimeHours(): float
    {
        return round($this->overtime_minutes / 60, 2);
    }

    /**
     * Check if the attendance was late.
     */
    public function isLate(): bool
    {
        if (!$this->shift) {
            return false;
        }

        // Give 5 minutes grace period
        $graceTime = $this->shift->start_time->addMinutes(5);
        return $this->clock_in_time->gt($graceTime);
    }

    /**
     * Get the late minutes.
     */
    public function getLateMinutes(): int
    {
        if (!$this->isLate()) {
            return 0;
        }

        return $this->shift->start_time->diffInMinutes($this->clock_in_time);
    }

    /**
     * Scope for active (clocked in) records.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('clock_out_time');
    }

    /**
     * Scope for records on a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('clock_in_time', $date);
    }

    /**
     * Scope for records at a location.
     */
    public function scopeAtLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope for records for a staff member.
     */
    public function scopeForStaffMember($query, $staffMemberId)
    {
        return $query->where('staff_member_id', $staffMemberId);
    }

    /**
     * Scope for records with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}