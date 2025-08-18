<?php

namespace Colame\Staff\Database\Seeders;

use Illuminate\Database\Seeder;
use Colame\Staff\Models\StaffMember;
use Colame\Staff\Models\Role;
use Spatie\Permission\Models\Permission;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Staff management
            'staff.view',
            'staff.create',
            'staff.edit',
            'staff.delete',
            'staff.manage-roles',
            
            // Attendance
            'attendance.view',
            'attendance.clock-in',
            'attendance.clock-out',
            'attendance.edit',
            'attendance.reports',
            
            // Schedule
            'schedule.view',
            'schedule.create',
            'schedule.edit',
            'schedule.delete',
            'schedule.approve-swaps',
            
            // Reports
            'reports.view',
            'reports.export',
            'reports.financial',
            
            // System
            'system.admin',
            'system.settings',
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        
        // Create default roles with metadata
        $roles = [
            [
                'name' => 'super-admin',
                'metadata' => [
                    'hierarchy_level' => 100,
                    'description' => 'Full system access',
                    'is_system' => true,
                ],
                'permissions' => $permissions,
            ],
            [
                'name' => 'manager',
                'metadata' => [
                    'hierarchy_level' => 70,
                    'description' => 'Restaurant manager with staff management capabilities',
                    'is_system' => true,
                ],
                'permissions' => [
                    'staff.view',
                    'staff.create',
                    'staff.edit',
                    'staff.manage-roles',
                    'attendance.view',
                    'attendance.edit',
                    'attendance.reports',
                    'schedule.view',
                    'schedule.create',
                    'schedule.edit',
                    'schedule.approve-swaps',
                    'reports.view',
                    'reports.export',
                ],
            ],
            [
                'name' => 'supervisor',
                'metadata' => [
                    'hierarchy_level' => 50,
                    'description' => 'Shift supervisor with limited management capabilities',
                    'is_system' => true,
                ],
                'permissions' => [
                    'staff.view',
                    'attendance.view',
                    'attendance.edit',
                    'schedule.view',
                    'schedule.edit',
                    'reports.view',
                ],
            ],
            [
                'name' => 'cashier',
                'metadata' => [
                    'hierarchy_level' => 30,
                    'description' => 'Cashier with order processing capabilities',
                    'is_system' => true,
                ],
                'permissions' => [
                    'attendance.clock-in',
                    'attendance.clock-out',
                    'schedule.view',
                ],
            ],
            [
                'name' => 'waiter',
                'metadata' => [
                    'hierarchy_level' => 30,
                    'description' => 'Waiter/waitress for table service',
                    'is_system' => true,
                ],
                'permissions' => [
                    'attendance.clock-in',
                    'attendance.clock-out',
                    'schedule.view',
                ],
            ],
            [
                'name' => 'chef',
                'metadata' => [
                    'hierarchy_level' => 40,
                    'description' => 'Kitchen chef',
                    'is_system' => true,
                ],
                'permissions' => [
                    'attendance.clock-in',
                    'attendance.clock-out',
                    'schedule.view',
                ],
            ],
            [
                'name' => 'kitchen-staff',
                'metadata' => [
                    'hierarchy_level' => 20,
                    'description' => 'Kitchen support staff',
                    'is_system' => true,
                ],
                'permissions' => [
                    'attendance.clock-in',
                    'attendance.clock-out',
                    'schedule.view',
                ],
            ],
        ];
        
        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => 'web']
            );
            
            // Update or create metadata
            $role->metadata()->updateOrCreate(
                ['role_id' => $role->id],
                $roleData['metadata']
            );
            
            // Sync permissions
            $role->syncPermissions($roleData['permissions']);
        }
        
        // Create sample staff members in development
        if (app()->environment('local', 'development')) {
            // Create a manager
            $manager = StaffMember::factory()->active()->create([
                'first_name' => 'Maria',
                'last_name' => 'Rodriguez',
                'email' => 'manager@colame.test',
                'employee_code' => 'MGR001',
            ]);
            
            // Create supervisors
            $supervisor1 = StaffMember::factory()->active()->create([
                'first_name' => 'Carlos',
                'last_name' => 'Gonzalez',
                'email' => 'supervisor1@colame.test',
                'employee_code' => 'SUP001',
            ]);
            
            $supervisor2 = StaffMember::factory()->active()->create([
                'first_name' => 'Ana',
                'last_name' => 'Martinez',
                'email' => 'supervisor2@colame.test',
                'employee_code' => 'SUP002',
            ]);
            
            // Create regular staff
            StaffMember::factory()->count(5)->active()->create();
            StaffMember::factory()->count(2)->onLeave()->create();
            StaffMember::factory()->count(1)->inactive()->create();
            
            // Assign roles (would need location IDs in real scenario)
            $managerRole = Role::where('name', 'manager')->first();
            $supervisorRole = Role::where('name', 'supervisor')->first();
            
            if ($managerRole) {
                $manager->assignRole($managerRole);
            }
            
            if ($supervisorRole) {
                $supervisor1->assignRole($supervisorRole);
                $supervisor2->assignRole($supervisorRole);
            }
        }
    }
}