<?php

namespace Colame\Staff\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Colame\Staff\Models\StaffMember;
use Colame\Staff\Models\Role;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_list_staff_members()
    {
        // Create test staff members
        StaffMember::factory()->count(5)->create();
        
        $response = $this->get(route('staff.index'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('staff/index')
            ->has('staffMembers')
            ->has('pagination')
        );
    }

    public function test_can_create_staff_member()
    {
        $staffData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'employee_code' => 'EMP001',
            'status' => 'active',
            'hire_date' => now()->toDateString(),
        ];
        
        $response = $this->post(route('staff.store'), $staffData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('staff_members', [
            'email' => 'john.doe@example.com',
            'employee_code' => 'EMP001',
        ]);
    }

    public function test_can_update_staff_member()
    {
        $staff = StaffMember::factory()->create();
        
        $updateData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
        ];
        
        $response = $this->put(route('staff.update', $staff->id), $updateData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('staff_members', [
            'id' => $staff->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
        ]);
    }

    public function test_can_delete_staff_member()
    {
        $staff = StaffMember::factory()->create();
        
        $response = $this->delete(route('staff.destroy', $staff->id));
        
        $response->assertRedirect(route('staff.index'));
        $this->assertDatabaseMissing('staff_members', [
            'id' => $staff->id,
        ]);
    }

    public function test_can_assign_role_to_staff()
    {
        $staff = StaffMember::factory()->create();
        $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);
        
        $response = $this->post(route('staff.assign-role', $staff->id), [
            'role_id' => $role->id,
            'location_id' => null,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('staff_location_roles', [
            'staff_member_id' => $staff->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_cannot_create_staff_with_duplicate_email()
    {
        StaffMember::factory()->create(['email' => 'duplicate@example.com']);
        
        $staffData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'duplicate@example.com',
            'phone' => '+1234567890',
            'employee_code' => 'EMP002',
            'status' => 'active',
            'hire_date' => now()->toDateString(),
        ];
        
        $response = $this->post(route('staff.store'), $staffData);
        
        $response->assertSessionHasErrors(['email']);
    }
}