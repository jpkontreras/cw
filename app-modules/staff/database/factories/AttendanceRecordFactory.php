<?php

namespace Colame\Staff\Database\Factories;

use Colame\Staff\Models\AttendanceRecord;
use Colame\Staff\Models\StaffMember;
use Colame\Location\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        $clockIn = $this->faker->dateTimeBetween('-30 days', 'now');
        $clockOut = $this->faker->optional(0.8)->dateTimeBetween($clockIn, 'now');
        
        return [
            'staff_member_id' => StaffMember::factory(),
            'location_id' => Location::factory(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'break_duration' => $clockOut ? $this->faker->numberBetween(0, 60) : null,
            'notes' => $this->faker->optional()->sentence(),
            'overtime_minutes' => $clockOut ? $this->faker->optional()->numberBetween(0, 120) : null,
        ];
    }

    public function clockedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'clock_out' => null,
            'break_duration' => null,
            'overtime_minutes' => null,
        ]);
    }

    public function clockedOut(): static
    {
        return $this->state(function (array $attributes) {
            $clockIn = $attributes['clock_in'] ?? now()->subHours(8);
            return [
                'clock_out' => now(),
                'break_duration' => $this->faker->numberBetween(15, 60),
            ];
        });
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'clock_in' => now()->startOfDay()->addHours($this->faker->numberBetween(6, 10)),
        ]);
    }
}