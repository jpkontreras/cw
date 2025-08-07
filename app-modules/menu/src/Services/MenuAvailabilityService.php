<?php

declare(strict_types=1);

namespace Colame\Menu\Services;

use Colame\Menu\Contracts\MenuAvailabilityInterface;
use Colame\Menu\Contracts\MenuRepositoryInterface;
use Colame\Menu\Data\MenuAvailabilityData;
use Colame\Menu\Data\MenuAvailabilityRuleData;
use Colame\Menu\Data\MenuData;
use Colame\Menu\Models\Menu;
use Colame\Menu\Models\MenuSection;
use Colame\Menu\Models\MenuItem;
use Spatie\LaravelData\DataCollection;

class MenuAvailabilityService implements MenuAvailabilityInterface
{
    public function __construct(
        private MenuRepositoryInterface $menuRepository,
    ) {}
    
    public function isMenuAvailable(int $menuId): bool
    {
        return $this->isMenuAvailableAt($menuId, now());
    }
    
    public function isMenuAvailableAt(int $menuId, \DateTimeInterface $dateTime): bool
    {
        $menu = Menu::with('availabilityRules')->find($menuId);
        
        if (!$menu || !$menu->is_active) {
            return false;
        }
        
        // Check menu date range
        if ($menu->available_from && $dateTime < $menu->available_from) {
            return false;
        }
        
        if ($menu->available_until && $dateTime > $menu->available_until) {
            return false;
        }
        
        // If no availability rules, menu is always available when active
        if ($menu->availabilityRules->isEmpty()) {
            return true;
        }
        
        // Check each rule - menu is available if ANY rule passes
        foreach ($menu->availabilityRules as $rule) {
            if ($rule->isActive()) {
                return true;
            }
        }
        
        return false;
    }
    
    public function isMenuAvailableAtLocation(int $menuId, int $locationId): bool
    {
        $menuLocation = Menu::find($menuId)
            ->locations()
            ->where('location_id', $locationId)
            ->first();
        
        if (!$menuLocation || !$menuLocation->is_active) {
            return false;
        }
        
        return $this->isMenuAvailable($menuId);
    }
    
    public function isSectionAvailable(int $sectionId): bool
    {
        $section = MenuSection::find($sectionId);
        
        if (!$section || !$section->is_active) {
            return false;
        }
        
        // Check if parent menu is available
        if (!$this->isMenuAvailable($section->menu_id)) {
            return false;
        }
        
        return $section->isAvailable();
    }
    
    public function isItemAvailable(int $menuItemId): bool
    {
        $item = MenuItem::find($menuItemId);
        
        if (!$item || !$item->is_active) {
            return false;
        }
        
        // Check if section is available
        if (!$this->isSectionAvailable($item->menu_section_id)) {
            return false;
        }
        
        return true;
    }
    
    public function getMenuAvailability(int $menuId): MenuAvailabilityData
    {
        $menu = Menu::with('availabilityRules')->find($menuId);
        
        if (!$menu) {
            throw new \InvalidArgumentException("Menu not found");
        }
        
        $isAvailable = $this->isMenuAvailable($menuId);
        $nextAvailable = $isAvailable ? null : $this->getNextAvailableTime($menuId);
        
        // Build today's schedule
        $todaySchedule = $this->getTodaySchedule($menu);
        
        // Build week schedule
        $weekSchedule = $this->getWeekSchedule($menu);
        
        // Get active rules
        $activeRules = MenuAvailabilityRuleData::collect(
            $menu->availabilityRules->filter(fn($rule) => $rule->isActive()),
            DataCollection::class
        );
        
        return new MenuAvailabilityData(
            menuId: $menuId,
            isCurrentlyAvailable: $isAvailable,
            currentStatus: $this->getCurrentStatus($menu),
            nextAvailableTime: $nextAvailable,
            todaySchedule: $todaySchedule,
            weekSchedule: $weekSchedule,
            activeRules: $activeRules,
            restrictions: $this->getRestrictions($menu),
        );
    }
    
    public function getCurrentlyAvailableMenus(): DataCollection
    {
        return $this->menuRepository->getCurrentlyAvailable();
    }
    
    public function getAvailableMenusForLocation(int $locationId): DataCollection
    {
        $menus = Menu::whereHas('locations', function ($query) use ($locationId) {
            $query->where('location_id', $locationId)
                ->where('is_active', true);
        })
        ->where('is_active', true)
        ->get()
        ->filter(fn($menu) => $this->isMenuAvailable($menu->id));
        
        return MenuData::collect($menus, DataCollection::class);
    }
    
    public function getNextAvailableTime(int $menuId): ?\DateTimeInterface
    {
        $menu = Menu::with('availabilityRules')->find($menuId);
        
        if (!$menu || !$menu->is_active) {
            return null;
        }
        
        // If menu has no rules, it's always available
        if ($menu->availabilityRules->isEmpty()) {
            return null;
        }
        
        $now = now();
        $nextTimes = [];
        
        foreach ($menu->availabilityRules as $rule) {
            if ($rule->rule_type === 'time_based' && $rule->start_time) {
                $nextTime = $now->copy()->setTimeFromTimeString($rule->start_time);
                
                // If the time has passed today, try tomorrow
                if ($nextTime->lt($now)) {
                    $nextTime->addDay();
                }
                
                // Check if this day is valid for the rule
                if ($rule->days_of_week) {
                    $dayName = strtolower($nextTime->format('l'));
                    if (!in_array($dayName, $rule->days_of_week)) {
                        // Find next valid day
                        for ($i = 1; $i <= 7; $i++) {
                            $nextTime->addDay();
                            $dayName = strtolower($nextTime->format('l'));
                            if (in_array($dayName, $rule->days_of_week)) {
                                break;
                            }
                        }
                    }
                }
                
                $nextTimes[] = $nextTime;
            }
        }
        
        if (empty($nextTimes)) {
            return null;
        }
        
        // Return the earliest next available time
        return collect($nextTimes)->sort()->first();
    }
    
    public function meetsCapacityRequirements(int $menuId, int $currentCapacity): bool
    {
        $menu = Menu::with('availabilityRules')->find($menuId);
        
        if (!$menu) {
            return false;
        }
        
        $capacityRules = $menu->availabilityRules
            ->where('rule_type', 'capacity_based');
        
        if ($capacityRules->isEmpty()) {
            return true;
        }
        
        foreach ($capacityRules as $rule) {
            if ($rule->min_capacity && $currentCapacity < $rule->min_capacity) {
                return false;
            }
            
            if ($rule->max_capacity && $currentCapacity > $rule->max_capacity) {
                return false;
            }
        }
        
        return true;
    }
    
    private function getCurrentStatus(Menu $menu): string
    {
        if (!$menu->is_active) {
            return 'inactive';
        }
        
        if ($this->isMenuAvailable($menu->id)) {
            return 'available';
        }
        
        $nextTime = $this->getNextAvailableTime($menu->id);
        
        if ($nextTime && $nextTime->isToday()) {
            return 'available_later_today';
        }
        
        return 'not_available';
    }
    
    private function getTodaySchedule(Menu $menu): array
    {
        $today = strtolower(now()->format('l'));
        $schedule = [];
        
        foreach ($menu->availabilityRules as $rule) {
            if ($rule->rule_type === 'time_based' && 
                $rule->days_of_week && 
                in_array($today, $rule->days_of_week)) {
                $schedule[] = [
                    'start' => $rule->start_time,
                    'end' => $rule->end_time,
                ];
            }
        }
        
        return $schedule;
    }
    
    private function getWeekSchedule(Menu $menu): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $schedule = [];
        
        foreach ($days as $day) {
            $daySchedule = [];
            
            foreach ($menu->availabilityRules as $rule) {
                if ($rule->rule_type === 'time_based' && 
                    $rule->days_of_week && 
                    in_array($day, $rule->days_of_week)) {
                    $daySchedule[] = [
                        'start' => $rule->start_time,
                        'end' => $rule->end_time,
                    ];
                }
            }
            
            $schedule[$day] = $daySchedule;
        }
        
        return $schedule;
    }
    
    private function getRestrictions(Menu $menu): array
    {
        $restrictions = [];
        
        if ($menu->available_from) {
            $restrictions['available_from'] = $menu->available_from->format('Y-m-d');
        }
        
        if ($menu->available_until) {
            $restrictions['available_until'] = $menu->available_until->format('Y-m-d');
        }
        
        $capacityRules = $menu->availabilityRules
            ->where('rule_type', 'capacity_based');
        
        if ($capacityRules->isNotEmpty()) {
            $restrictions['capacity'] = [];
            foreach ($capacityRules as $rule) {
                if ($rule->min_capacity) {
                    $restrictions['capacity']['min'] = $rule->min_capacity;
                }
                if ($rule->max_capacity) {
                    $restrictions['capacity']['max'] = $rule->max_capacity;
                }
            }
        }
        
        return $restrictions;
    }
}