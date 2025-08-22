<?php

namespace Colame\Offer\Database\Seeders;

use Illuminate\Database\Seeder;
use Colame\Offer\Models\Offer;
use Carbon\Carbon;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $offers = [
            [
                'name' => 'Weekend Special',
                'description' => '20% off all orders during weekends',
                'type' => 'percentage',
                'value' => 20,
                'max_discount' => 50,
                'code' => 'WEEKEND20',
                'is_active' => true,
                'auto_apply' => false,
                'is_stackable' => false,
                'valid_days' => ['saturday', 'sunday'],
                'minimum_amount' => 30,
                'usage_limit' => 100,
                'usage_per_customer' => 2,
                'priority' => 10,
            ],
            [
                'name' => 'Happy Hour',
                'description' => '15% discount between 3 PM and 6 PM',
                'type' => 'happy_hour',
                'value' => 15,
                'max_discount' => 25,
                'is_active' => true,
                'auto_apply' => true,
                'is_stackable' => false,
                'valid_time_start' => '15:00',
                'valid_time_end' => '18:00',
                'priority' => 20,
            ],
            [
                'name' => 'Buy 2 Get 1 Free',
                'description' => 'Buy 2 items and get 1 free',
                'type' => 'buy_x_get_y',
                'value' => 100,
                'is_active' => true,
                'auto_apply' => true,
                'is_stackable' => false,
                'conditions' => [
                    'buy_quantity' => 2,
                    'get_quantity' => 1,
                    'discount_percent' => 100,
                ],
                'priority' => 15,
            ],
            [
                'name' => 'New Customer Welcome',
                'description' => '$10 off your first order',
                'type' => 'fixed',
                'value' => 10,
                'code' => 'WELCOME10',
                'is_active' => true,
                'auto_apply' => false,
                'is_stackable' => false,
                'minimum_amount' => 25,
                'usage_per_customer' => 1,
                'customer_segments' => ['new'],
                'priority' => 25,
            ],
            [
                'name' => 'Early Bird Special',
                'description' => '10% off orders before 11 AM',
                'type' => 'early_bird',
                'value' => 10,
                'max_discount' => 20,
                'is_active' => true,
                'auto_apply' => true,
                'is_stackable' => true,
                'valid_time_start' => '06:00',
                'valid_time_end' => '11:00',
                'priority' => 5,
            ],
            [
                'name' => 'Loyalty Reward',
                'description' => '5% cashback for VIP customers',
                'type' => 'loyalty',
                'value' => 5,
                'is_active' => true,
                'auto_apply' => true,
                'is_stackable' => true,
                'customer_segments' => ['vip'],
                'priority' => 30,
            ],
            [
                'name' => 'Staff Discount',
                'description' => '25% discount for staff members',
                'type' => 'staff',
                'value' => 25,
                'max_discount' => 100,
                'code' => 'STAFF25',
                'is_active' => true,
                'auto_apply' => false,
                'is_stackable' => false,
                'customer_segments' => ['staff'],
                'priority' => 50,
            ],
            [
                'name' => 'Summer Sale',
                'description' => '30% off selected items',
                'type' => 'percentage',
                'value' => 30,
                'max_discount' => 75,
                'code' => 'SUMMER30',
                'is_active' => true,
                'auto_apply' => false,
                'is_stackable' => false,
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now()->addMonths(3),
                'usage_limit' => 500,
                'priority' => 12,
            ],
            [
                'name' => 'Combo Deal',
                'description' => 'Special combo price for selected items',
                'type' => 'combo',
                'value' => 19.99,
                'is_active' => true,
                'auto_apply' => true,
                'is_stackable' => false,
                'conditions' => [
                    'combo_items' => [1, 2, 3], // Item IDs for the combo
                ],
                'priority' => 18,
            ],
            [
                'name' => 'Flash Sale',
                'description' => '50% off for the next 2 hours',
                'type' => 'percentage',
                'value' => 50,
                'max_discount' => 100,
                'is_active' => false, // Inactive by default
                'auto_apply' => true,
                'is_stackable' => false,
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now()->addHours(2),
                'usage_limit' => 50,
                'priority' => 100,
            ],
        ];
        
        foreach ($offers as $offerData) {
            Offer::create($offerData);
        }
    }
}