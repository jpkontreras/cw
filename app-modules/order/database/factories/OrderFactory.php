<?php

declare(strict_types=1);

namespace Colame\Order\Database\Factories;

use Colame\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Colame\Order\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model
     */
    protected $model = Order::class;

    /**
     * Define the model's default state
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 10, 500);
        $taxAmount = $subtotal * 0.10;
        $discountAmount = $this->faker->boolean(20) ? $this->faker->randomFloat(2, 0, $subtotal * 0.20) : 0;
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        return [
            'user_id' => $this->faker->numberBetween(1, 100),
            'location_id' => $this->faker->numberBetween(1, 10),
            'status' => $this->faker->randomElement(Order::VALID_STATUSES),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'notes' => $this->faker->optional()->sentence(),
            'cancel_reason' => null,
            'customer_name' => $this->faker->optional()->name(),
            'customer_phone' => $this->faker->optional()->phoneNumber(),
            'metadata' => $this->faker->optional()->randomElements(['source' => 'web', 'device' => 'desktop']),
            'placed_at' => null,
            'confirmed_at' => null,
            'preparing_at' => null,
            'ready_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * Indicate that the order is in draft status
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_DRAFT,
        ]);
    }

    /**
     * Indicate that the order is placed
     */
    public function placed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PLACED,
            'placed_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the order is confirmed
     */
    public function confirmed(): static
    {
        $placedAt = $this->faker->dateTimeBetween('-2 hours', '-1 hour');
        
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_CONFIRMED,
            'placed_at' => $placedAt,
            'confirmed_at' => $this->faker->dateTimeBetween($placedAt, 'now'),
        ]);
    }

    /**
     * Indicate that the order is completed
     */
    public function completed(): static
    {
        $placedAt = $this->faker->dateTimeBetween('-3 hours', '-2 hours');
        $confirmedAt = $this->faker->dateTimeBetween($placedAt, '-90 minutes');
        $preparingAt = $this->faker->dateTimeBetween($confirmedAt, '-60 minutes');
        $readyAt = $this->faker->dateTimeBetween($preparingAt, '-30 minutes');
        
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_COMPLETED,
            'placed_at' => $placedAt,
            'confirmed_at' => $confirmedAt,
            'preparing_at' => $preparingAt,
            'ready_at' => $readyAt,
            'completed_at' => $this->faker->dateTimeBetween($readyAt, 'now'),
        ]);
    }

    /**
     * Indicate that the order is cancelled
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_CANCELLED,
            'cancel_reason' => $this->faker->randomElement([
                'Customer request',
                'Out of stock',
                'Restaurant closed',
                'Payment failed',
            ]),
            'cancelled_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }
}