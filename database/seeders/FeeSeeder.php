<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Fee;

class FeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific fees
        $fees = [
            [
                'name' => 'Delivery Fee',
                'code' => 'DELIVERY',
                'amount' => 500.00,
                'type' => 'fixed',
                'description' => 'Standard delivery fee for jewelry items',
                'active' => true,
            ],
            [
                'name' => 'Processing Fee',
                'code' => 'PROCESSING',
                'amount' => 50.00,
                'type' => 'fixed',
                'description' => 'Processing fee for orders',
                'active' => true,
            ],
            [
                'name' => 'Installation Fee',
                'code' => 'INSTALLATION',
                'amount' => 1000.00,
                'type' => 'fixed',
                'description' => 'Installation fee for jewelry items',
                'active' => true,
            ],
            [
                'name' => 'Service Fee',
                'code' => 'SERVICE',
                'amount' => 5.00,
                'type' => 'percentage',
                'description' => 'Service fee percentage',
                'active' => true,
            ],
            [
                'name' => 'Handling Fee',
                'code' => 'HANDLING',
                'amount' => 2.50,
                'type' => 'percentage',
                'description' => 'Handling fee percentage',
                'active' => true,
            ],
        ];

        foreach ($fees as $fee) {
            Fee::firstOrCreate(['code' => $fee['code']], $fee);
        }

        // Create additional random fees to reach 25 total
        $faker = \Faker\Factory::create();
        $feeTypes = ['fixed', 'percentage'];
        $feeNames = ['Delivery Fee', 'Processing Fee', 'Installation Fee', 'Service Fee', 'Handling Fee', 'Shipping Fee', 'Packaging Fee', 'Insurance Fee', 'Customization Fee', 'Rush Fee', 'Express Fee', 'Standard Fee', 'Premium Fee', 'Basic Fee', 'Advanced Fee'];
        $feeCodes = ['DELIVERY', 'PROCESSING', 'INSTALLATION', 'SERVICE', 'HANDLING', 'SHIPPING', 'PACKAGING', 'INSURANCE', 'CUSTOM', 'RUSH', 'EXPRESS', 'STANDARD', 'PREMIUM', 'BASIC', 'ADVANCED'];
        
        // Calculate how many more fees we need to reach 25 total
        $existingCount = count($fees);
        $additionalCount = 25 - $existingCount;
        
        for ($i = 0; $i < $additionalCount; $i++) {
            $feeType = $faker->randomElement($feeTypes);
            $feeCode = $faker->randomElement($feeCodes) . '_' . $faker->unique()->numberBetween(1, 999);
            $feeName = $faker->randomElement($feeNames) . ' ' . $faker->numberBetween(1, 10);
            
            $amount = $feeType === 'fixed' 
                ? $faker->randomFloat(2, 10, 2000) 
                : $faker->randomFloat(2, 0.5, 15.0);
            
            Fee::firstOrCreate(['code' => $feeCode], [
                'name' => $feeName,
                'code' => $feeCode,
                'amount' => $amount,
                'type' => $feeType,
                'description' => $faker->sentence(),
                'active' => $faker->boolean(80), // 80% active
            ]);
        }
    }
}