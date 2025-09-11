<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentType;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default payment types
        $paymentTypes = [
            [
                'name' => 'Down Payment',
                'code' => 'DP',
                'description' => 'Initial payment made at the time of purchase',
                'is_active' => true,
            ],
            [
                'name' => 'Monthly Payment',
                'code' => 'MONTHLY',
                'description' => 'Regular monthly installment payment',
                'is_active' => true,
            ],
            [
                'name' => 'Full Payment',
                'code' => 'FULL',
                'description' => 'Complete payment for the entire amount',
                'is_active' => true,
            ],
            [
                'name' => 'Partial Payment',
                'code' => 'PARTIAL',
                'description' => 'Partial payment towards the total amount',
                'is_active' => true,
            ],
            [
                'name' => 'Refund',
                'code' => 'REFUND',
                'description' => 'Refund payment to customer',
                'is_active' => true,
            ],
            [
                'name' => 'Reversal',
                'code' => 'REVERSAL',
                'description' => 'Payment reversal or chargeback',
                'is_active' => true,
            ],
        ];

        foreach ($paymentTypes as $paymentType) {
            PaymentType::create($paymentType);
        }

        // Create additional custom payment types using Faker
        $faker = \Faker\Factory::create();
        
        for ($i = 0; $i < 19; $i++) {
            PaymentType::create([
                'name' => $faker->words(2, true) . ' Payment',
                'code' => strtoupper($faker->lexify('???') . $faker->numerify('###')),
                'description' => $faker->sentence(),
                'is_active' => $faker->boolean(90), // 90% active
            ]);
        }
    }
}