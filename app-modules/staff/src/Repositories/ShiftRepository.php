<?php

namespace Colame\Staff\Repositories;

use Colame\Staff\Contracts\ShiftRepositoryInterface;
use Colame\Staff\Models\Shift;
use Colame\Staff\Data\ShiftData;
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

    public function create(array $data): ShiftData
    {
        $shift = Shift::create($data);
        return ShiftData::from($shift);
    }

    public function update(int $id, array $data): ?ShiftData
    {
        $shift = Shift::find($id);
        if (!$shift) {
            return null;
        }
        
        $shift->update($data);
        return ShiftData::from($shift);
    }

    public function delete(int $id): bool
    {
        return Shift::destroy($id) > 0;
    }

    public function getByFilters(array $filters): DataCollection
    {
        $query = Shift::query();
        
        if (!empty($filters['location'])) {
            $query->where('location_id', $filters['location']);
        }
        
        if (!empty($filters['staff_member'])) {
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
}