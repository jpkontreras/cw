<?php

namespace Colame\Staff\Repositories;

use Colame\Staff\Contracts\ShiftRepositoryInterface;
use Colame\Staff\Models\Shift;
use Colame\Staff\Data\ShiftData;
use Colame\Staff\Data\CreateShiftData;
use Colame\Staff\Data\UpdateShiftData;
use Spatie\LaravelData\DataCollection;

class ShiftRepository implements ShiftRepositoryInterface
{
    public function find(int $id): ?ShiftData
    {
        $shift = Shift::find($id);
        return $shift ? ShiftData::from($shift) : null;
    }

    public function all(): DataCollection
    {
        return ShiftData::collection(Shift::all());
    }

    public function create(CreateShiftData $data): ShiftData
    {
        $shift = Shift::create($data->toArray());
        return ShiftData::from($shift);
    }

    public function update(int $id, UpdateShiftData $data): ShiftData
    {
        $shift = Shift::findOrFail($id);
        
        // Filter out Optional values
        $updateData = collect($data->toArray())
            ->filter(fn($value) => !$value instanceof \Spatie\LaravelData\Optional)
            ->toArray();
            
        $shift->update($updateData);
        return ShiftData::from($shift);
    }

    public function delete(int $id): bool
    {
        return Shift::destroy($id) > 0;
    }

    public function getByFilters(array $filters): DataCollection
    {
        $query = Shift::query();
        
        // Handle location filter - skip if "all" or not numeric
        if (!empty($filters['location']) && $filters['location'] !== 'all' && is_numeric($filters['location'])) {
            $query->where('location_id', $filters['location']);
        }
        
        // Handle staff member filter - skip if "all" or not numeric
        if (!empty($filters['staff_member']) && $filters['staff_member'] !== 'all' && is_numeric($filters['staff_member'])) {
            $query->where('staff_member_id', $filters['staff_member']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('start_time', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('end_time', '<=', $filters['date_to']);
        }
        
        return ShiftData::collection($query->get());
    }

    public function getUpcomingShifts(int $staffId, int $days = 7): DataCollection
    {
        $shifts = Shift::where('staff_member_id', $staffId)
            ->where('start_time', '>=', now())
            ->where('start_time', '<=', now()->addDays($days))
            ->orderBy('start_time')
            ->get();
            
        return ShiftData::collection($shifts);
    }

    public function getShiftsByDate(string $date, ?int $locationId = null): DataCollection
    {
        $query = Shift::whereDate('start_time', $date);
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        return ShiftData::collection($query->get());
    }
    
    // Interface required methods
    public function getByStaffMember(int $staffId, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): DataCollection
    {
        $query = Shift::where('staff_member_id', $staffId);
        
        if ($from) {
            $query->where('start_time', '>=', $from);
        }
        
        if ($to) {
            $query->where('end_time', '<=', $to);
        }
        
        return ShiftData::collection($query->orderBy('start_time')->get());
    }
    
    public function getByLocation(int $locationId, \Carbon\Carbon $date): DataCollection
    {
        return ShiftData::collection(
            Shift::where('location_id', $locationId)
                ->whereDate('start_time', $date)
                ->orderBy('start_time')
                ->get()
        );
    }
    
    public function getUpcoming(int $staffId, int $days = 7): DataCollection
    {
        return $this->getUpcomingShifts($staffId, $days);
    }
    
    public function findConflicts(int $staffId, \Carbon\Carbon $start, \Carbon\Carbon $end): DataCollection
    {
        $shifts = Shift::where('staff_member_id', $staffId)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_time', [$start, $end])
                    ->orWhereBetween('end_time', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_time', '<=', $start)
                          ->where('end_time', '>=', $end);
                    });
            })
            ->get();
            
        return ShiftData::collection($shifts);
    }
    
    public function paginateWithFilters(array $filters, int $perPage): \App\Core\Data\PaginatedResourceData
    {
        $query = Shift::query();
        
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        
        if (!empty($filters['staff_member_id'])) {
            $query->where('staff_member_id', $filters['staff_member_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('start_time', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('end_time', '<=', $filters['date_to']);
        }
        
        $paginator = $query->orderBy('start_time')->paginate($perPage);
        
        return \App\Core\Data\PaginatedResourceData::fromPaginator($paginator, ShiftData::class);
    }
    
    public function getShiftsByDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end, ?int $locationId = null): DataCollection
    {
        $query = Shift::whereBetween('start_time', [$start, $end]);
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        return ShiftData::collection($query->orderBy('start_time')->get());
    }
}