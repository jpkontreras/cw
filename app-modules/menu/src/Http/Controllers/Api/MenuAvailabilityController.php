<?php

declare(strict_types=1);

namespace Colame\Menu\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Colame\Menu\Contracts\MenuAvailabilityInterface;
use Colame\Menu\Contracts\MenuRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class MenuAvailabilityController extends Controller
{
    public function __construct(
        private MenuAvailabilityInterface $availabilityService,
        private MenuRepositoryInterface $menuRepository,
    ) {}
    
    /**
     * Check if a menu is currently available
     */
    public function check(int $menuId, Request $request): JsonResponse
    {
        $request->validate([
            'datetime' => 'nullable|date',
            'location_id' => 'nullable|integer',
        ]);
        
        $datetime = $request->input('datetime') ? Carbon::parse($request->input('datetime')) : now();
        $locationId = $request->input('location_id');
        
        $isAvailable = $this->availabilityService->isMenuAvailableAtTime($menuId, $datetime, $locationId);
        $availability = $this->availabilityService->getMenuAvailability($menuId);
        
        return response()->json([
            'success' => true,
            'data' => [
                'menu_id' => $menuId,
                'is_available' => $isAvailable,
                'checked_at' => $datetime->toIso8601String(),
                'location_id' => $locationId,
                'availability_info' => $availability,
            ],
        ]);
    }
    
    /**
     * Get the full availability schedule for a menu
     */
    public function schedule(int $menuId, Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'location_id' => 'nullable|integer',
        ]);
        
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->addDays(7);
        $locationId = $request->input('location_id');
        
        $schedule = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $daySchedule = $this->availabilityService->getMenuScheduleForDay($menuId, $currentDate, $locationId);
            
            $schedule[] = [
                'date' => $currentDate->toDateString(),
                'day_of_week' => $currentDate->format('l'),
                'is_available' => $daySchedule['is_available'] ?? false,
                'time_slots' => $daySchedule['time_slots'] ?? [],
                'rules' => $daySchedule['rules'] ?? [],
            ];
            
            $currentDate->addDay();
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'menu_id' => $menuId,
                'location_id' => $locationId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'schedule' => $schedule,
            ],
        ]);
    }
    
    /**
     * Get all currently available menus
     */
    public function current(Request $request): JsonResponse
    {
        $request->validate([
            'location_id' => 'nullable|integer',
            'type' => 'nullable|string',
        ]);
        
        $locationId = $request->input('location_id');
        $type = $request->input('type');
        
        $menus = $this->menuRepository->all();
        
        // Filter by location if provided
        if ($locationId) {
            $menus = $menus->filter(function ($menu) use ($locationId) {
                return $menu->locations->contains('location_id', $locationId);
            });
        }
        
        // Filter by type if provided
        if ($type) {
            $menus = $menus->where('type', $type);
        }
        
        // Filter to only currently available menus
        $availableMenus = $menus->filter(function ($menu) use ($locationId) {
            return $this->availabilityService->isMenuAvailableAtTime($menu->id, now(), $locationId);
        });
        
        // Add next availability for unavailable menus
        $availableMenus = $availableMenus->map(function ($menu) {
            $availability = $this->availabilityService->getMenuAvailability($menu->id);
            $menu->availability_info = $availability;
            return $menu;
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'current_time' => now()->toIso8601String(),
                'location_id' => $locationId,
                'available_menus' => $availableMenus->values(),
                'total_count' => $menus->count(),
                'available_count' => $availableMenus->count(),
            ],
        ]);
    }
    
    /**
     * Get available menus for a specific location
     */
    public function byLocation(int $locationId, Request $request): JsonResponse
    {
        $request->validate([
            'datetime' => 'nullable|date',
            'include_inactive' => 'nullable|boolean',
        ]);
        
        $datetime = $request->input('datetime') ? Carbon::parse($request->input('datetime')) : now();
        $includeInactive = $request->boolean('include_inactive', false);
        
        $menus = $this->menuRepository->all();
        
        // Filter by location
        $locationMenus = $menus->filter(function ($menu) use ($locationId) {
            return $menu->locations->contains('location_id', $locationId);
        });
        
        // Filter by active status unless including inactive
        if (!$includeInactive) {
            $locationMenus = $locationMenus->where('isActive', true);
        }
        
        // Add availability status
        $locationMenus = $locationMenus->map(function ($menu) use ($datetime, $locationId) {
            $menu->is_available_at_time = $this->availabilityService->isMenuAvailableAtTime(
                $menu->id,
                $datetime,
                $locationId
            );
            $menu->availability_info = $this->availabilityService->getMenuAvailability($menu->id);
            return $menu;
        });
        
        // Sort by availability and then by sort order
        $locationMenus = $locationMenus->sortBy([
            ['is_available_at_time', 'desc'],
            ['sortOrder', 'asc'],
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'location_id' => $locationId,
                'checked_at' => $datetime->toIso8601String(),
                'menus' => $locationMenus->values(),
                'total_count' => $locationMenus->count(),
                'available_count' => $locationMenus->where('is_available_at_time', true)->count(),
            ],
        ]);
    }
    
    /**
     * Helper method to get menu schedule for a specific day
     */
    private function getMenuScheduleForDay(int $menuId, Carbon $date, ?int $locationId): array
    {
        $dayOfWeek = strtolower($date->format('l'));
        $availability = $this->availabilityService->getMenuAvailability($menuId);
        
        $timeSlots = [];
        $isAvailable = false;
        
        if (isset($availability['rules'])) {
            foreach ($availability['rules'] as $rule) {
                if ($rule['ruleType'] === 'time' && 
                    (!isset($rule['daysOfWeek']) || in_array($dayOfWeek, $rule['daysOfWeek']))) {
                    $timeSlots[] = [
                        'start' => $rule['startTime'],
                        'end' => $rule['endTime'],
                        'type' => $rule['metadata']['type'] ?? 'regular',
                    ];
                    
                    // Check if current time falls within this slot
                    $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $rule['startTime']);
                    $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $rule['endTime']);
                    
                    if (now()->between($startTime, $endTime)) {
                        $isAvailable = true;
                    }
                }
            }
        }
        
        return [
            'is_available' => $isAvailable,
            'time_slots' => $timeSlots,
            'rules' => $availability['rules'] ?? [],
        ];
    }
}