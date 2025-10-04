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
        // Create Philippines-specific discounts
        $discounts = [
            [
                'name' => 'Bagong Customer (New Customer)',
                'code' => 'BAGONG_CUSTOMER',
                'amount' => 2000.00,
                'type' => 'fixed',
                'description' => 'Special discount para sa mga bagong customer',
                'valid_from' => now(),
                'valid_until' => now()->addMonths(6),
                'usage_limit' => 100,
                'used_count' => 0,
                'active' => true,
            ],
            [
                'name' => 'Malaking Bili (Bulk Purchase)',
                'code' => 'MALAKING_BILI',
                'amount' => 15.00,
                'type' => 'percentage',
                'description' => 'Discount para sa malaking bili ng alahas',
                'valid_from' => now(),
                'valid_until' => now()->addMonths(3),
                'usage_limit' => 50,
                'used_count' => 0,
                'active' => true,
            ],
            [
                'name' => 'Suki Discount (Loyalty)',
                'code' => 'SUKI',
                'amount' => 8.00,
                'type' => 'percentage',
                'description' => 'Discount para sa mga suki at loyal customers',
                'valid_from' => now(),
                'valid_until' => null,
                'usage_limit' => null,
                'used_count' => 0,
                'active' => true,
            ],
            [
                'name' => 'Pasko Sale (Christmas Sale)',
                'code' => 'PASKO_SALE',
                'amount' => 1000.00,
                'type' => 'fixed',
                'description' => 'Special Christmas season discount',
                'valid_from' => now(),
                'valid_until' => now()->addMonths(2),
                'usage_limit' => 200,
                'used_count' => 0,
                'active' => true,
            ],
            [
                'name' => 'VIP Customer (Premium)',
                'code' => 'VIP_PREMIUM',
                'amount' => 20.00,
                'type' => 'percentage',
                'description' => 'Premium discount para sa VIP customers',
                'valid_from' => now(),
                'valid_until' => null,
                'usage_limit' => null,
                'used_count' => 0,
                'active' => true,
            ],
            [
                'name' => 'Senior Citizen Discount',
                'code' => 'SENIOR_CITIZEN',
                'amount' => 12.00,
                'type' => 'percentage',
                'description' => 'Special discount para sa mga senior citizens',
                'valid_from' => now(),
                'valid_until' => null,
                'usage_limit' => null,
                'used_count' => 0,
                'active' => true,
            ],
            [
                'name' => 'PWD Discount',
                'code' => 'PWD',
                'amount' => 10.00,
                'type' => 'percentage',
                'description' => 'Discount para sa mga Persons with Disabilities',
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

        // Create additional Philippines-specific discounts to reach 25 total
        $faker = \Faker\Factory::create();
        $discountTypes = ['fixed', 'percentage'];
        $discountNames = ['Bagong Customer', 'Malaking Bili', 'Suki Discount', 'Pasko Sale', 'VIP Customer', 'Senior Citizen', 'PWD Discount', 'Employee Discount', 'Corporate Discount', 'Holiday Sale', 'Clearance Sale', 'Flash Sale', 'Member Discount', 'Referral Bonus', 'Birthday Special', 'Valentine\'s Day', 'Mother\'s Day', 'Father\'s Day', 'Anniversary Sale', 'Back to School'];
        $discountCodes = ['BAGONG_CUSTOMER', 'MALAKING_BILI', 'SUKI', 'PASKO_SALE', 'VIP', 'SENIOR_CITIZEN', 'PWD', 'EMPLOYEE', 'CORPORATE', 'HOLIDAY', 'CLEARANCE', 'FLASH', 'MEMBER', 'REFERRAL', 'BIRTHDAY', 'VALENTINES', 'MOTHERS_DAY', 'FATHERS_DAY', 'ANNIVERSARY', 'BACK_TO_SCHOOL'];
        
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