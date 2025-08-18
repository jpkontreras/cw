<?php

namespace Colame\Staff\Database\Seeders;

use Illuminate\Database\Seeder;
use Colame\Staff\Models\StaffMember;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class StaffRolesAndLocationsSeeder extends Seeder
{
    public function run(): void
    {
        // Create some basic roles if they don't exist
        $roles = [
            ['name' => 'Manager', 'guard_name' => 'web'],
            ['name' => 'Supervisor', 'guard_name' => 'web'],
            ['name' => 'Cashier', 'guard_name' => 'web'],
            ['name' => 'Server', 'guard_name' => 'web'],
            ['name' => 'Cook', 'guard_name' => 'web'],
            ['name' => 'Kitchen Staff', 'guard_name' => 'web'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate($roleData);
        }

        // Check if locations exist, if not we'll just use location IDs that might exist
        $existingLocationIds = DB::table('locations')->pluck('id')->toArray();
        
        if (empty($existingLocationIds)) {
            // Create minimal locations with all required fields
            $locations = [
                [
                    'id' => 1,
                    'code' => 'MAIN',
                    'name' => 'Main Restaurant',
                    'type' => 'restaurant',
                    'status' => 'active',
                    'address' => '123 Main St',
                    'city' => 'Santiago',
                    'state' => 'RM',
                    'postal_code' => '8320000',
                    'country' => 'CL',
                    'phone' => '+56 2 1234 5678',
                    'email' => 'main@colame.test',
                    'timezone' => 'America/Santiago',
                    'currency' => 'CLP',
                    'capabilities' => json_encode(['dine_in', 'takeout']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => 2,
                    'code' => 'DOWN',
                    'name' => 'Downtown Branch',
                    'type' => 'restaurant',
                    'status' => 'active',
                    'address' => '456 Downtown Ave',
                    'city' => 'Santiago',
                    'state' => 'RM',
                    'postal_code' => '8320001',
                    'country' => 'CL',
                    'phone' => '+56 2 2345 6789',
                    'email' => 'downtown@colame.test',
                    'timezone' => 'America/Santiago',
                    'currency' => 'CLP',
                    'capabilities' => json_encode(['dine_in', 'takeout', 'delivery']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => 3,
                    'code' => 'MALL',
                    'name' => 'Mall Location',
                    'type' => 'restaurant',
                    'status' => 'active',
                    'address' => '789 Mall Plaza',
                    'city' => 'Santiago',
                    'state' => 'RM',
                    'postal_code' => '8320002',
                    'country' => 'CL',
                    'phone' => '+56 2 3456 7890',
                    'email' => 'mall@colame.test',
                    'timezone' => 'America/Santiago',
                    'currency' => 'CLP',
                    'capabilities' => json_encode(['takeout']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            foreach ($locations as $location) {
                DB::table('locations')->insertOrIgnore($location);
            }
            
            $locationIds = [1, 2, 3];
        } else {
            $locationIds = $existingLocationIds;
        }

        // Assign roles and locations to existing staff members
        $staffMembers = StaffMember::all();
        $roleIds = Role::pluck('id')->toArray();

        foreach ($staffMembers as $index => $staff) {
            // Assign a role based on position in list
            $roleAssignments = [];
            
            if ($index === 0) {
                // First person is a manager
                $roleAssignments[] = [
                    'role_id' => Role::where('name', 'Manager')->first()->id,
                    'location_id' => 1,
                ];
            } elseif ($index < 3) {
                // Next two are supervisors
                $roleAssignments[] = [
                    'role_id' => Role::where('name', 'Supervisor')->first()->id,
                    'location_id' => $locationIds[$index % count($locationIds)],
                ];
            } elseif ($index < 6) {
                // Next three are cashiers
                $roleAssignments[] = [
                    'role_id' => Role::where('name', 'Cashier')->first()->id,
                    'location_id' => $locationIds[$index % count($locationIds)],
                ];
            } else {
                // Rest are servers or cooks
                $role = $index % 2 === 0 ? 'Server' : 'Cook';
                $roleAssignments[] = [
                    'role_id' => Role::where('name', $role)->first()->id,
                    'location_id' => $locationIds[$index % count($locationIds)],
                ];
            }

            // Assign roles with locations
            foreach ($roleAssignments as $assignment) {
                DB::table('staff_location_roles')->insertOrIgnore([
                    'staff_member_id' => $staff->id,
                    'role_id' => $assignment['role_id'],
                    'location_id' => $assignment['location_id'],
                    'assigned_at' => now(),
                    'assigned_by' => 1, // System user
                ]);
            }
        }

        $this->command->info('Staff roles and locations seeded successfully!');
    }
}