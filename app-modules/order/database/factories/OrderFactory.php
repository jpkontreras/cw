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
        $subtotal = $this->faker->randomFloat(2, 10000, 150000);
        $taxAmount = $subtotal * 0.19; // Chilean IVA
        $tipAmount = $this->faker->boolean(30) ? $this->faker->randomFloat(2, 0, $subtotal * 0.10) : 0;
        $discountAmount = $this->faker->boolean(20) ? $this->faker->randomFloat(2, 0, $subtotal * 0.20) : 0;
        $totalAmount = $subtotal + $taxAmount + $tipAmount - $discountAmount;
        
        // Generate order type
        $type = $this->faker->randomElement(Order::VALID_TYPES);
        
        // Generate Chilean names
        $firstNames = ['Juan', 'María', 'Pedro', 'Camila', 'Diego', 'Javiera', 'Francisco', 'Valentina', 'Sebastián', 'Catalina', 'Pablo', 'Fernanda', 'Matías', 'Antonella', 'Nicolás', 'Isabella'];
        $lastNames = ['González', 'Muñoz', 'Rojas', 'Díaz', 'Pérez', 'Soto', 'Contreras', 'Silva', 'Martínez', 'Sepúlveda', 'Morales', 'Rodríguez', 'López', 'Fuentes', 'Hernández', 'Torres'];
        
        $customerName = $this->faker->randomElement($firstNames) . ' ' . $this->faker->randomElement($lastNames);
        
        // Generate order number with format ORD-YYYYMMDD-XXXX
        $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad((string) $this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);

        $data = [
            'order_number' => $orderNumber,
            'user_id' => $this->faker->numberBetween(1, 10),
            // Use an existing location ID if available, otherwise use 1 as fallback for tests
            'location_id' => \Colame\Location\Models\Location::inRandomOrder()->first()?->id ?? 1,
            'status' => $this->faker->randomElement(['placed', 'confirmed', 'preparing', 'ready', 'completed']),
            'type' => $type,
            'priority' => $this->faker->randomElement(['normal', 'normal', 'normal', 'high']), // 25% high priority
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'tip_amount' => $tipAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'payment_status' => $this->faker->randomElement(['pending', 'pending', 'paid', 'paid']), // 50% paid
            'notes' => $this->faker->optional(0.3)->sentence(),
            'special_instructions' => $this->faker->optional(0.2)->randomElement([
                'Sin cebolla por favor',
                'Extra picante',
                'Sin mayonesa',
                'Bien cocido',
                'Para llevar con servilletas extra',
            ]),
            'customer_name' => $customerName,
            'customer_phone' => '+569' . $this->faker->numberBetween(10000000, 99999999),
            'customer_email' => $this->faker->optional(0.3)->email(),
            'metadata' => [
                'source' => $this->faker->randomElement(['web', 'app', 'phone', 'walk-in']),
                'device' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
            ],
        ];
        
        // Add type-specific data
        if ($type === Order::TYPE_DINE_IN) {
            $data['table_number'] = $this->faker->numberBetween(1, 20);
            $data['waiter_id'] = $this->faker->numberBetween(1, 5);
        } elseif ($type === Order::TYPE_DELIVERY) {
            $streets = ['Av. Providencia', 'Av. Las Condes', 'Av. Apoquindo', 'Calle Estado', 'Av. Vitacura', 'Av. El Bosque', 'Av. Manquehue'];
            $data['delivery_address'] = $this->faker->randomElement($streets) . ' ' . $this->faker->numberBetween(100, 9999) . ', ' . $this->faker->randomElement(['Providencia', 'Las Condes', 'Vitacura', 'La Reina', 'Ñuñoa']);
        }
        
        return $data;
    }

    /**
     * Indicate that the order is in draft status
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the order is placed
     */
    public function placed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'placed',
            'placed_at' => $this->faker->dateTimeBetween('-30 minutes', 'now'),
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Indicate that the order is confirmed
     */
    public function confirmed(): static
    {
        $placedAt = $this->faker->dateTimeBetween('-45 minutes', '-30 minutes');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'placed_at' => $placedAt,
            'confirmed_at' => $this->faker->dateTimeBetween($placedAt, '-25 minutes'),
            'payment_status' => $this->faker->randomElement(['pending', 'paid']),
        ]);
    }
    
    /**
     * Indicate that the order is preparing
     */
    public function preparing(): static
    {
        $placedAt = $this->faker->dateTimeBetween('-60 minutes', '-45 minutes');
        $confirmedAt = (clone $placedAt)->modify('+' . $this->faker->numberBetween(2, 5) . ' minutes');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'preparing',
            'placed_at' => $placedAt,
            'confirmed_at' => $confirmedAt,
            'preparing_at' => $this->faker->dateTimeBetween($confirmedAt, '-30 minutes'),
            'payment_status' => 'paid',
        ]);
    }
    
    /**
     * Indicate that the order is ready
     */
    public function ready(): static
    {
        $placedAt = $this->faker->dateTimeBetween('-90 minutes', '-60 minutes');
        $confirmedAt = (clone $placedAt)->modify('+' . $this->faker->numberBetween(2, 5) . ' minutes');
        $preparingAt = (clone $confirmedAt)->modify('+' . $this->faker->numberBetween(3, 8) . ' minutes');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'ready',
            'placed_at' => $placedAt,
            'confirmed_at' => $confirmedAt,
            'preparing_at' => $preparingAt,
            'ready_at' => $this->faker->dateTimeBetween($preparingAt, '-15 minutes'),
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Indicate that the order is completed
     */
    public function completed(): static
    {
        $placedAt = $this->faker->dateTimeBetween('-3 hours', '-2 hours');
        $confirmedAt = (clone $placedAt)->modify('+' . $this->faker->numberBetween(2, 5) . ' minutes');
        $preparingAt = (clone $confirmedAt)->modify('+' . $this->faker->numberBetween(3, 8) . ' minutes');
        $readyAt = (clone $preparingAt)->modify('+' . $this->faker->numberBetween(15, 25) . ' minutes');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'placed_at' => $placedAt,
            'confirmed_at' => $confirmedAt,
            'preparing_at' => $preparingAt,
            'ready_at' => $readyAt,
            'completed_at' => $this->faker->dateTimeBetween($readyAt, '-30 minutes'),
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Indicate that the order is cancelled
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
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