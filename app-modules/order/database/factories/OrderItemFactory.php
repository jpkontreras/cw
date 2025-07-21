<?php

declare(strict_types=1);

namespace Colame\Order\Database\Factories;

use Colame\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Colame\Order\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model
     */
    protected $model = OrderItem::class;

    /**
     * Chilean menu items with realistic prices
     */
    private array $menuItems = [
        // Starters
        ['name' => 'Empanada de Pino', 'price' => 2500, 'course' => 'starter'],
        ['name' => 'Empanada de Queso', 'price' => 2300, 'course' => 'starter'],
        ['name' => 'Sopaipillas (6)', 'price' => 1800, 'course' => 'starter'],
        ['name' => 'Choripán', 'price' => 3500, 'course' => 'starter'],
        ['name' => 'Ceviche', 'price' => 6500, 'course' => 'starter'],
        
        // Main Courses
        ['name' => 'Completo Italiano', 'price' => 3500, 'course' => 'main'],
        ['name' => 'Churrasco Italiano', 'price' => 5500, 'course' => 'main'],
        ['name' => 'Barros Luco', 'price' => 5800, 'course' => 'main'],
        ['name' => 'Chacarero', 'price' => 6200, 'course' => 'main'],
        ['name' => 'Pastel de Choclo', 'price' => 7500, 'course' => 'main'],
        ['name' => 'Cazuela de Vacuno', 'price' => 6800, 'course' => 'main'],
        ['name' => 'Lomo a lo Pobre', 'price' => 9500, 'course' => 'main'],
        ['name' => 'Pescado Frito con Papas', 'price' => 8500, 'course' => 'main'],
        ['name' => 'Pollo Asado con Papas', 'price' => 7200, 'course' => 'main'],
        ['name' => 'Costillar de Cerdo', 'price' => 8900, 'course' => 'main'],
        
        // Desserts
        ['name' => 'Mote con Huesillo', 'price' => 2500, 'course' => 'dessert'],
        ['name' => 'Leche Asada', 'price' => 2800, 'course' => 'dessert'],
        ['name' => 'Torta Tres Leches', 'price' => 3500, 'course' => 'dessert'],
        ['name' => 'Kuchen de Frambuesa', 'price' => 3200, 'course' => 'dessert'],
        ['name' => 'Helado Artesanal (2 bolas)', 'price' => 2900, 'course' => 'dessert'],
        
        // Beverages
        ['name' => 'Bebida 350ml', 'price' => 1500, 'course' => 'beverage'],
        ['name' => 'Bebida 500ml', 'price' => 2000, 'course' => 'beverage'],
        ['name' => 'Jugo Natural', 'price' => 2500, 'course' => 'beverage'],
        ['name' => 'Pisco Sour', 'price' => 4500, 'course' => 'beverage'],
        ['name' => 'Terremoto', 'price' => 3800, 'course' => 'beverage'],
        ['name' => 'Cerveza Nacional', 'price' => 2800, 'course' => 'beverage'],
        ['name' => 'Cerveza Artesanal', 'price' => 3500, 'course' => 'beverage'],
        ['name' => 'Café Espresso', 'price' => 1800, 'course' => 'beverage'],
        ['name' => 'Café Cortado', 'price' => 2200, 'course' => 'beverage'],
    ];

    /**
     * Available modifiers
     */
    private array $modifiers = [
        ['id' => 1, 'name' => 'Extra Queso', 'price' => 800],
        ['id' => 2, 'name' => 'Extra Palta', 'price' => 1000],
        ['id' => 3, 'name' => 'Sin Cebolla', 'price' => 0],
        ['id' => 4, 'name' => 'Sin Mayo', 'price' => 0],
        ['id' => 5, 'name' => 'Extra Picante', 'price' => 0],
        ['id' => 6, 'name' => 'Doble Carne', 'price' => 2500],
        ['id' => 7, 'name' => 'Sin Tomate', 'price' => 0],
        ['id' => 8, 'name' => 'Extra Salsa Verde', 'price' => 500],
    ];

    /**
     * Define the model's default state
     */
    public function definition(): array
    {
        $item = $this->faker->randomElement($this->menuItems);
        $quantity = $this->faker->numberBetween(1, 4);
        
        // Random modifiers (20% chance)
        $selectedModifiers = [];
        if ($this->faker->boolean(20)) {
            $numModifiers = $this->faker->numberBetween(1, 2);
            $selectedModifiers = $this->faker->randomElements($this->modifiers, $numModifiers);
        }
        
        // Calculate total with modifiers
        $modifiersTotal = array_reduce($selectedModifiers, function ($carry, $modifier) {
            return $carry + $modifier['price'];
        }, 0);
        
        $unitPrice = $item['price'];
        $totalPrice = ($unitPrice + $modifiersTotal) * $quantity;

        return [
            'order_id' => null, // Will be set when creating with relationship
            'item_id' => $this->faker->numberBetween(1, 50),
            'item_name' => $item['name'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'status' => $this->faker->randomElement(OrderItem::VALID_STATUSES),
            'kitchen_status' => $this->faker->randomElement([
                OrderItem::KITCHEN_STATUS_PENDING,
                OrderItem::KITCHEN_STATUS_PREPARING,
                OrderItem::KITCHEN_STATUS_READY,
            ]),
            'course' => $item['course'],
            'notes' => $this->faker->optional(0.1)->randomElement([
                'Sin sal',
                'Bien cocido',
                'Poco cocido',
                'Para compartir',
            ]),
            'modifiers' => $selectedModifiers,
            'metadata' => [
                'preparation_time' => $this->faker->numberBetween(10, 30),
                'station' => $this->faker->randomElement(['grill', 'fryer', 'cold', 'bar']),
            ],
        ];
    }

    /**
     * Indicate that the item is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderItem::STATUS_PENDING,
            'kitchen_status' => OrderItem::KITCHEN_STATUS_PENDING,
            'prepared_at' => null,
            'served_at' => null,
        ]);
    }

    /**
     * Indicate that the item is preparing
     */
    public function preparing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderItem::STATUS_PREPARING,
            'kitchen_status' => OrderItem::KITCHEN_STATUS_PREPARING,
            'prepared_at' => null,
            'served_at' => null,
        ]);
    }

    /**
     * Indicate that the item is prepared
     */
    public function prepared(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderItem::STATUS_PREPARED,
            'kitchen_status' => OrderItem::KITCHEN_STATUS_READY,
            'prepared_at' => $this->faker->dateTimeBetween('-30 minutes', 'now'),
            'served_at' => null,
        ]);
    }

    /**
     * Indicate that the item is served
     */
    public function served(): static
    {
        $preparedAt = $this->faker->dateTimeBetween('-45 minutes', '-30 minutes');
        
        return $this->state(fn (array $attributes) => [
            'status' => OrderItem::STATUS_SERVED,
            'kitchen_status' => OrderItem::KITCHEN_STATUS_SERVED,
            'prepared_at' => $preparedAt,
            'served_at' => $this->faker->dateTimeBetween($preparedAt, 'now'),
        ]);
    }
}