<?php

declare(strict_types=1);

namespace Colame\Business\Database\Seeders;

use App\Models\User;
use Colame\Business\Models\Business;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user or create one
        $user = User::first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Admin User',
                'email' => 'admin@colame.test',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Create a demo business
        $business = Business::create([
            'name' => 'Demo Restaurant',
            'slug' => 'demo-restaurant',
            'legal_name' => 'Demo Restaurant LLC',
            'tax_id' => '12-3456789',
            'type' => 'independent',
            'status' => 'active',
            'owner_id' => $user->id,
            'email' => 'contact@demo-restaurant.test',
            'phone' => '+56 9 1234 5678',
            'website' => 'https://demo-restaurant.test',
            'address' => 'Av. Providencia 1234',
            'city' => 'Santiago',
            'state' => 'RM',
            'country' => 'CL',
            'postal_code' => '7500000',
            'currency' => 'CLP',
            'timezone' => 'America/Santiago',
            'locale' => 'es_CL',
            'subscription_tier' => 'pro',
            'trial_ends_at' => now()->addDays(14),
            'is_demo' => true,
        ]);

        // Add the owner to the business
        $business->users()->attach($user->id, [
            'role' => 'owner',
            'is_owner' => true,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        // Set as current business for the user
        $user->current_business_id = $business->id;
        $user->save();

        // Create additional demo businesses if needed
        if (config('app.env') === 'local') {
            $franchiseBusiness = Business::create([
                'name' => 'Franchise Restaurant Chain',
                'slug' => 'franchise-chain',
                'type' => 'franchise',
                'status' => 'active',
                'owner_id' => $user->id,
                'email' => 'franchise@colame.test',
                'currency' => 'CLP',
                'timezone' => 'America/Santiago',
                'locale' => 'es_CL',
                'subscription_tier' => 'enterprise',
            ]);

            $franchiseBusiness->users()->attach($user->id, [
                'role' => 'owner',
                'is_owner' => true,
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }
    }
}