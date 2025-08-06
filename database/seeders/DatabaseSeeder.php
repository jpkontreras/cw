<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Assign user to default location if location module is available
        if (class_exists('\Colame\Location\Models\Location')) {
            $defaultLocation = \Colame\Location\Models\Location::where('is_default', true)->first();
            if ($defaultLocation) {
                $user->locations()->attach($defaultLocation->id, ['role' => 'manager', 'is_primary' => true]);
                $user->current_location_id = $defaultLocation->id;
                $user->default_location_id = $defaultLocation->id;
                $user->save();
            }
        }
    }
}
