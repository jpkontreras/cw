<?php

namespace Colame\Location\Database\Seeders;

use Colame\Location\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main headquarters location
        $headquarters = Location::create([
            'code' => 'HQ-001',
            'name' => 'Headquarters - Santiago Centro',
            'type' => 'restaurant',
            'status' => 'active',
            'address' => 'Avenida Providencia 1234',
            'city' => 'Santiago',
            'state' => 'Región Metropolitana',
            'country' => 'CL',
            'postal_code' => '7500000',
            'phone' => '+56 2 1234 5678',
            'email' => 'hq@restaurant.cl',
            'timezone' => 'America/Santiago',
            'currency' => 'CLP',
            'opening_hours' => [
                'monday' => ['open' => '09:00', 'close' => '22:00'],
                'tuesday' => ['open' => '09:00', 'close' => '22:00'],
                'wednesday' => ['open' => '09:00', 'close' => '22:00'],
                'thursday' => ['open' => '09:00', 'close' => '22:00'],
                'friday' => ['open' => '09:00', 'close' => '23:00'],
                'saturday' => ['open' => '10:00', 'close' => '23:00'],
                'sunday' => ['open' => '10:00', 'close' => '21:00'],
            ],
            'delivery_radius' => 5.0,
            'capabilities' => ['dine_in', 'takeout', 'delivery', 'catering'],
            'is_default' => true,
            'metadata' => [
                'parking' => true,
                'wheelchair_accessible' => true,
                'wifi' => true,
            ],
        ]);

        // Create branch locations
        Location::create([
            'code' => 'LAS-001',
            'name' => 'Las Condes Branch',
            'type' => 'restaurant',
            'status' => 'active',
            'address' => 'Avenida Apoquindo 5678',
            'city' => 'Las Condes',
            'state' => 'Región Metropolitana',
            'country' => 'CL',
            'postal_code' => '7550000',
            'phone' => '+56 2 2345 6789',
            'email' => 'lascondes@restaurant.cl',
            'timezone' => 'America/Santiago',
            'currency' => 'CLP',
            'opening_hours' => [
                'monday' => ['open' => '11:00', 'close' => '22:00'],
                'tuesday' => ['open' => '11:00', 'close' => '22:00'],
                'wednesday' => ['open' => '11:00', 'close' => '22:00'],
                'thursday' => ['open' => '11:00', 'close' => '22:00'],
                'friday' => ['open' => '11:00', 'close' => '23:00'],
                'saturday' => ['open' => '11:00', 'close' => '23:00'],
                'sunday' => ['open' => '11:00', 'close' => '21:00'],
            ],
            'delivery_radius' => 3.0,
            'capabilities' => ['dine_in', 'takeout', 'delivery'],
            'parent_location_id' => $headquarters->id,
            'metadata' => [
                'parking' => true,
                'wheelchair_accessible' => true,
                'wifi' => true,
                'outdoor_seating' => true,
            ],
        ]);

        Location::create([
            'code' => 'VIT-001',
            'name' => 'Vitacura Branch',
            'type' => 'restaurant',
            'status' => 'active',
            'address' => 'Avenida Vitacura 9012',
            'city' => 'Vitacura',
            'state' => 'Región Metropolitana',
            'country' => 'CL',
            'postal_code' => '7630000',
            'phone' => '+56 2 3456 7890',
            'email' => 'vitacura@restaurant.cl',
            'timezone' => 'America/Santiago',
            'currency' => 'CLP',
            'opening_hours' => [
                'monday' => ['open' => '12:00', 'close' => '22:00'],
                'tuesday' => ['open' => '12:00', 'close' => '22:00'],
                'wednesday' => ['open' => '12:00', 'close' => '22:00'],
                'thursday' => ['open' => '12:00', 'close' => '22:00'],
                'friday' => ['open' => '12:00', 'close' => '23:00'],
                'saturday' => ['open' => '12:00', 'close' => '23:00'],
                'sunday' => ['open' => '12:00', 'close' => '21:00'],
            ],
            'capabilities' => ['dine_in', 'takeout'],
            'parent_location_id' => $headquarters->id,
            'metadata' => [
                'parking' => false,
                'wheelchair_accessible' => true,
                'wifi' => true,
                'valet_parking' => true,
            ],
        ]);

        // Create central kitchen
        Location::create([
            'code' => 'CK-001',
            'name' => 'Central Kitchen',
            'type' => 'central_kitchen',
            'status' => 'active',
            'address' => 'Camino Industrial 3456',
            'city' => 'San Bernardo',
            'state' => 'Región Metropolitana',
            'country' => 'CL',
            'postal_code' => '8050000',
            'phone' => '+56 2 4567 8901',
            'email' => 'kitchen@restaurant.cl',
            'timezone' => 'America/Santiago',
            'currency' => 'CLP',
            'opening_hours' => [
                'monday' => ['open' => '06:00', 'close' => '18:00'],
                'tuesday' => ['open' => '06:00', 'close' => '18:00'],
                'wednesday' => ['open' => '06:00', 'close' => '18:00'],
                'thursday' => ['open' => '06:00', 'close' => '18:00'],
                'friday' => ['open' => '06:00', 'close' => '18:00'],
                'saturday' => ['open' => '06:00', 'close' => '14:00'],
                'sunday' => ['open' => '00:00', 'close' => '00:00', 'isClosed' => true],
            ],
            'capabilities' => [],
            'metadata' => [
                'capacity' => '1000kg/day',
                'certifications' => ['HACCP', 'ISO 22000'],
            ],
        ]);

        // Create a maintenance location
        Location::create([
            'code' => 'MALL-001',
            'name' => 'Mall Plaza Location',
            'type' => 'restaurant',
            'status' => 'maintenance',
            'address' => 'Mall Plaza Vespucio, Local 234',
            'city' => 'La Florida',
            'state' => 'Región Metropolitana',
            'country' => 'CL',
            'postal_code' => '8240000',
            'phone' => '+56 2 5678 9012',
            'email' => 'mallplaza@restaurant.cl',
            'timezone' => 'America/Santiago',
            'currency' => 'CLP',
            'opening_hours' => [
                'monday' => ['open' => '10:00', 'close' => '21:00'],
                'tuesday' => ['open' => '10:00', 'close' => '21:00'],
                'wednesday' => ['open' => '10:00', 'close' => '21:00'],
                'thursday' => ['open' => '10:00', 'close' => '21:00'],
                'friday' => ['open' => '10:00', 'close' => '21:00'],
                'saturday' => ['open' => '10:00', 'close' => '21:00'],
                'sunday' => ['open' => '10:00', 'close' => '21:00'],
            ],
            'capabilities' => ['dine_in', 'takeout'],
            'parent_location_id' => $headquarters->id,
            'metadata' => [
                'renovation_date' => '2025-09-01',
                'expected_reopening' => '2025-10-01',
            ],
        ]);

        // Create warehouse
        Location::create([
            'code' => 'WH-001',
            'name' => 'Main Warehouse',
            'type' => 'warehouse',
            'status' => 'active',
            'address' => 'Ruta 5 Sur Km 23',
            'city' => 'San Bernardo',
            'state' => 'Región Metropolitana',
            'country' => 'CL',
            'postal_code' => '8051000',
            'phone' => '+56 2 6789 0123',
            'email' => 'warehouse@restaurant.cl',
            'timezone' => 'America/Santiago',
            'currency' => 'CLP',
            'opening_hours' => [
                'monday' => ['open' => '08:00', 'close' => '17:00'],
                'tuesday' => ['open' => '08:00', 'close' => '17:00'],
                'wednesday' => ['open' => '08:00', 'close' => '17:00'],
                'thursday' => ['open' => '08:00', 'close' => '17:00'],
                'friday' => ['open' => '08:00', 'close' => '17:00'],
                'saturday' => ['open' => '00:00', 'close' => '00:00', 'isClosed' => true],
                'sunday' => ['open' => '00:00', 'close' => '00:00', 'isClosed' => true],
            ],
            'capabilities' => [],
            'metadata' => [
                'storage_capacity' => '5000m2',
                'cold_storage' => true,
                'loading_docks' => 4,
            ],
        ]);

        $this->command->info('Location seeder completed successfully!');
    }
}