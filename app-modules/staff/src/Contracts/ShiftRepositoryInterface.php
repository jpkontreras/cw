<?php

namespace Colame\Staff\Contracts;

use App\Core\Data\PaginatedResourceData;
use Carbon\Carbon;
use Colame\Staff\Data\CreateShiftData;
use Colame\Staff\Data\ShiftData;
use Colame\Staff\Data\UpdateShiftData;
use Spatie\LaravelData\DataCollection;

interface ShiftRepositoryInterface
{
    public function find(int $id): ?ShiftData;
    
    public function create(CreateShiftData $data): ShiftData;
    
    public function update(int $id, UpdateShiftData $data): ShiftData;
    
    public function delete(int $id): bool;
    
    public function getByStaffMember(int $staffId, ?Carbon $from = null, ?Carbon $to = null): DataCollection;
    
    public function getByLocation(int $locationId, Carbon $date): DataCollection;
    
    public function getUpcoming(int $staffId, int $days = 7): DataCollection;
    
    public function findConflicts(int $staffId, Carbon $start, Carbon $end): DataCollection;
    
    public function paginateWithFilters(array $filters, int $perPage): PaginatedResourceData;
    
    public function getShiftsByDateRange(Carbon $start, Carbon $end, ?int $locationId = null): DataCollection;
}