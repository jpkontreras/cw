<?php

declare(strict_types=1);

namespace Colame\Menu\Models;

use Illuminate\Database\Eloquent\Model;

class MenuAvailabilityRule extends Model
{
    protected $fillable = [
        'menu_id',
        'rule_type',
        'days_of_week',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'min_capacity',
        'max_capacity',
        'is_recurring',
        'recurrence_pattern',
        'priority',
        'metadata',
    ];
    
    protected $casts = [
        'menu_id' => 'integer',
        'days_of_week' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'start_date' => 'date',
        'end_date' => 'date',
        'min_capacity' => 'integer',
        'max_capacity' => 'integer',
        'is_recurring' => 'boolean',
        'priority' => 'integer',
        'metadata' => 'array',
    ];
    
    protected $attributes = [
        'is_recurring' => false,
        'priority' => 0,
    ];
    
    public const TYPE_TIME_BASED = 'time_based';
    public const TYPE_DAY_BASED = 'day_based';
    public const TYPE_DATE_RANGE = 'date_range';
    public const TYPE_CAPACITY_BASED = 'capacity_based';
    
    public const VALID_TYPES = [
        self::TYPE_TIME_BASED,
        self::TYPE_DAY_BASED,
        self::TYPE_DATE_RANGE,
        self::TYPE_CAPACITY_BASED,
    ];
    
    public const RECURRENCE_DAILY = 'daily';
    public const RECURRENCE_WEEKLY = 'weekly';
    public const RECURRENCE_MONTHLY = 'monthly';
    
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
    
    public function isActive(): bool
    {
        $now = now();
        
        switch ($this->rule_type) {
            case self::TYPE_TIME_BASED:
                return $this->checkTimeBasedRule($now);
                
            case self::TYPE_DAY_BASED:
                return $this->checkDayBasedRule($now);
                
            case self::TYPE_DATE_RANGE:
                return $this->checkDateRangeRule($now);
                
            case self::TYPE_CAPACITY_BASED:
                return $this->checkCapacityBasedRule();
                
            default:
                return false;
        }
    }
    
    protected function checkTimeBasedRule($now): bool
    {
        if (!$this->start_time || !$this->end_time) {
            return true;
        }
        
        $currentTime = $now->format('H:i');
        
        // Handle rules that span midnight
        if ($this->end_time < $this->start_time) {
            return $currentTime >= $this->start_time || $currentTime <= $this->end_time;
        }
        
        return $currentTime >= $this->start_time && $currentTime <= $this->end_time;
    }
    
    protected function checkDayBasedRule($now): bool
    {
        if (!$this->days_of_week || count($this->days_of_week) === 0) {
            return true;
        }
        
        $currentDay = strtolower($now->format('l'));
        return in_array($currentDay, $this->days_of_week);
    }
    
    protected function checkDateRangeRule($now): bool
    {
        $currentDate = $now->toDateString();
        
        if ($this->start_date && $currentDate < $this->start_date) {
            return false;
        }
        
        if ($this->end_date && $currentDate > $this->end_date) {
            return false;
        }
        
        return true;
    }
    
    protected function checkCapacityBasedRule(): bool
    {
        // TODO: Implement capacity checking logic
        // This would check current restaurant capacity, reservations, etc.
        return true;
    }
}