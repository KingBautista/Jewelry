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
        // Create Philippines-specific fees
        $fees = [
            [
                'name' => 'Metro Manila Delivery',
                'code' => 'MM_DELIVERY',
                'amount' => 150.00,
                'type' => 'fixed',
                'description' => 'Delivery fee within Metro Manila',
                'active' => true,
            ],
            [
                'name' => 'Provincial Delivery',
                'code' => 'PROVINCIAL_DELIVERY',
                'amount' => 300.00,
                'type' => 'fixed',
                'description' => 'Delivery fee to provinces',
                'active' => true,
            ],
            [
                'name' => 'Jewelry Appraisal Fee',
                'code' => 'APPRAISAL',
                'amount' => 500.00,
                'type' => 'fixed',
                'description' => 'Professional jewelry appraisal fee',
                'active' => true,
            ],
            [
                'name' => 'Jewelry Cleaning Service',
                'code' => 'CLEANING',
                'amount' => 200.00,
                'type' => 'fixed',
                'description' => 'Professional jewelry cleaning service',
                'active' => true,
            ],
            [
                'name' => 'Jewelry Repair Service',
                'code' => 'REPAIR',
                'amount' => 1000.00,
                'type' => 'fixed',
                'description' => 'Jewelry repair and restoration service',
                'active' => true,
            ],
            [
                'name' => 'Insurance Fee',
                'code' => 'INSURANCE',
                'amount' => 2.00,
                'type' => 'percentage',
                'description' => 'Jewelry insurance coverage fee',
                'active' => true,
            ],
            [
                'name' => 'Bank Transfer Fee',
                'code' => 'BANK_TRANSFER',
                'amount' => 25.00,
                'type' => 'fixed',
                'description' => 'Bank transfer processing fee',
                'active' => true,
            ],
            [
                'name' => 'Credit Card Processing',
                'code' => 'CC_PROCESSING',
                'amount' => 3.50,
                'type' => 'percentage',
                'description' => 'Credit card processing fee',
                'active' => true,
            ],
        ];

        foreach ($fees as $fee) {
            Fee::firstOrCreate(['code' => $fee['code']], $fee);
        }

        // Create additional Philippines-specific fees to reach 25 total
        $faker = \Faker\Factory::create();
        $feeTypes = ['fixed', 'percentage'];
        $feeNames = ['Metro Manila Delivery', 'Provincial Delivery', 'Jewelry Appraisal', 'Jewelry Cleaning', 'Jewelry Repair', 'Insurance Coverage', 'Bank Transfer', 'Credit Card Processing', 'Express Delivery', 'Same Day Delivery', 'Jewelry Customization', 'Engraving Service', 'Jewelry Resizing', 'Jewelry Polishing', 'Jewelry Authentication'];
        $feeCodes = ['MM_DELIVERY', 'PROVINCIAL_DELIVERY', 'APPRAISAL', 'CLEANING', 'REPAIR', 'INSURANCE', 'BANK_TRANSFER', 'CC_PROCESSING', 'EXPRESS', 'SAME_DAY', 'CUSTOMIZATION', 'ENGRAVING', 'RESIZING', 'POLISHING', 'AUTHENTICATION'];
        
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