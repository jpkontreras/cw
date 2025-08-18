<?php

namespace Colame\Staff\Database\Factories;

use Colame\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffMemberFactory extends Factory
{
    protected $model = StaffMember::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'employee_code' => 'EMP' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'national_id' => $this->faker->unique()->numerify('##########'),
            'tax_id' => $this->faker->optional()->numerify('########'),
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
            'hire_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'status' => $this->faker->randomElement(['active', 'inactive', 'on_leave']),
            'address' => [
                'street' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'postal_code' => $this->faker->postcode(),
                'country' => $this->faker->country(),
            ],
            'emergency_contacts' => [
                [
                    'name' => $this->faker->name(),
                    'phone' => $this->faker->phoneNumber(),
                    'relationship' => $this->faker->randomElement(['spouse', 'parent', 'sibling', 'friend']),
                ],
            ],
            'bank_details' => null, // Will be set separately if needed
            'hourly_rate' => $this->faker->optional()->randomFloat(2, 15, 50),
            'monthly_salary' => $this->faker->optional()->randomFloat(2, 2000, 8000),
            'profile_photo_url' => $this->faker->optional()->imageUrl(200, 200, 'people'),
            'metadata' => [],
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function onLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on_leave',
        ]);
    }
}