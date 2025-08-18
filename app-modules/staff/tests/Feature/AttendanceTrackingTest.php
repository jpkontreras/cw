<?php

namespace Colame\Staff\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Colame\Staff\Models\StaffMember;
use Colame\Staff\Models\AttendanceRecord;
use Colame\Location\Models\Location;

class AttendanceTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected StaffMember $staff;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->staff = StaffMember::factory()->create();
        $this->location = Location::factory()->create();
        
        $this->actingAs($this->user);
    }

    public function test_can_clock_in()
    {
        $response = $this->post(route('staff.attendance.clock-in'), [
            'staff_member_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'notes' => 'Starting shift',
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('attendance_records', [
            'staff_member_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'clock_out' => null,
        ]);
    }

    public function test_cannot_clock_in_twice()
    {
        // First clock in
        AttendanceRecord::create([
            'staff_member_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'clock_in' => now(),
        ]);
        
        // Try to clock in again
        $response = $this->post(route('staff.attendance.clock-in'), [
            'staff_member_id' => $this->staff->id,
            'location_id' => $this->location->id,
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_can_clock_out()
    {
        // Create a clock-in record
        $record = AttendanceRecord::create([
            'staff_member_id' => $this->staff->id,
            'location_id' => $this->location->id,
            'clock_in' => now()->subHours(4),
        ]);
        
        $response = $this->post(route('staff.attendance.clock-out'), [
            'staff_member_id' => $this->staff->id,
            'notes' => 'Ending shift',
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $record->refresh();
        $this->assertNotNull($record->clock_out);
    }

    public function test_cannot_clock_out_without_clocking_in()
    {
        $response = $this->post(route('staff.attendance.clock-out'), [
            'staff_member_id' => $this->staff->id,
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_can_view_attendance_records()
    {
        // Create some attendance records
        AttendanceRecord::factory()->count(10)->create([
            'staff_member_id' => $this->staff->id,
        ]);
        
        $response = $this->get(route('staff.attendance.index'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('staff/attendance/index')
            ->has('attendance')
            ->has('pagination')
            ->has('currentClockIns')
            ->has('stats')
        );
    }

    public function test_can_filter_attendance_by_date()
    {
        // Create records for different dates
        AttendanceRecord::factory()->create([
            'staff_member_id' => $this->staff->id,
            'clock_in' => now()->subDays(2),
        ]);
        
        AttendanceRecord::factory()->create([
            'staff_member_id' => $this->staff->id,
            'clock_in' => now(),
        ]);
        
        $response = $this->get(route('staff.attendance.index', [
            'date' => now()->toDateString(),
        ]));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('staff/attendance/index')
            ->has('attendance', 1)
        );
    }
}