<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Discount;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific discounts
        $discounts = [
            [
                'name' => 'First-time Buyer',
                'code' => 'FIRST_TIME',
                'amount' => 1000.00,
                'type' => 'fixed',
                'description' => 'Special discount for first-time buyers',
                'valid_from' => now(),
                'valid_until' => now()->addMonths(6),
                'usage_limit' => 100,
                'used_count' => 0,
                'active' => true,
            ],
            [
                'name' => 'Bulk Purchase',
                'code' => 'BULK',
                'amount' => 10.00,
                'type' => 'percentage',
                'description' => 'Discount for bulk purchases',
                'valid_from' => now(),
                'valid_until' => now()->addMonths(3),
                'usage_limit' => 50,
                'used_count' => 0,
                'active' => true,
            ],
            [
                'name' => 'Loyalty Discount',
                'code' => 'LOYALTY',
                'amount' => 5.00,
                'type' => 'percentage',
                'description' => 'Loyalty discount for returning customers',
                'valid_from' => now(),
                'valid_until' => null,
                'usage_limit' => null,
                'used_count' => 0,
                'active' => true,
            ],
            [
                'name' => 'Seasonal Sale',
                'code' => 'SEASONAL',
                'amount' => 500.00,
                'type' => 'fixed',
                'description' => 'Seasonal sale discount',
                'valid_from' => now(),
                'valid_until' => now()->addMonths(2),
                'usage_limit' => 200,
                'used_count' => 0,
                'active' => true,
            ],
            [
                'name' => 'VIP Customer',
                'code' => 'VIP',
                'amount' => 15.00,
                'type' => 'percentage',
                'description' => 'VIP customer discount',
                'valid_from' => now(),
                'valid_until' => null,
                'usage_limit' => null,
                'used_count' => 0,
                'active' => true,
            ],
        ];

        foreach ($discounts as $discount) {
            Discount::firstOrCreate(['code' => $discount['code']], $discount);
        }

        // Create additional random discounts using factory
        Discount::factory(3)->create();
    }
}