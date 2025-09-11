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

        // Create additional random discounts to reach 25 total
        $faker = \Faker\Factory::create();
        $discountTypes = ['fixed', 'percentage'];
        $discountNames = ['First-time Buyer', 'Bulk Purchase', 'Loyalty Discount', 'Seasonal Sale', 'VIP Customer', 'Student Discount', 'Senior Discount', 'Employee Discount', 'Corporate Discount', 'Holiday Sale', 'Clearance Sale', 'Flash Sale', 'Member Discount', 'Referral Bonus', 'Birthday Special'];
        $discountCodes = ['FIRST_TIME', 'BULK', 'LOYALTY', 'SEASONAL', 'VIP', 'STUDENT', 'SENIOR', 'EMPLOYEE', 'CORPORATE', 'HOLIDAY', 'CLEARANCE', 'FLASH', 'MEMBER', 'REFERRAL', 'BIRTHDAY'];
        
        // Calculate how many more discounts we need to reach 25 total
        $existingCount = count($discounts);
        $additionalCount = 25 - $existingCount;
        
        for ($i = 0; $i < $additionalCount; $i++) {
            $discountType = $faker->randomElement($discountTypes);
            $discountCode = $faker->randomElement($discountCodes) . '_' . $faker->unique()->numberBetween(1, 999);
            $discountName = $faker->randomElement($discountNames) . ' ' . $faker->numberBetween(1, 10);
            
            $amount = $discountType === 'fixed' 
                ? $faker->randomFloat(2, 50, 2000) 
                : $faker->randomFloat(2, 5, 50);
            
            $validFrom = $faker->dateTimeBetween('-1 month', '+1 month');
            $validUntil = $faker->optional(0.7)->dateTimeBetween($validFrom, '+6 months');
            
            Discount::firstOrCreate(['code' => $discountCode], [
                'name' => $discountName,
                'code' => $discountCode,
                'amount' => $amount,
                'type' => $discountType,
                'description' => $faker->sentence(),
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'usage_limit' => $faker->optional(0.6)->numberBetween(10, 500),
                'used_count' => $faker->numberBetween(0, 50),
                'active' => $faker->boolean(75), // 75% active
            ]);
        }
    }
}