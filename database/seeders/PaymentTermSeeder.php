<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentTerm;
use App\Models\PaymentTermSchedule;

class PaymentTermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific payment terms with schedules
        $paymentTerms = [
            [
                'name' => 'Installment Plan A',
                'code' => 'INSTALLMENT_A',
                'down_payment_percentage' => 30.00,
                'remaining_percentage' => 70.00,
                'term_months' => 5,
                'description' => '5-month installment plan with 30% down payment',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 10.00, 'description' => 'First month payment'],
                    ['month_number' => 2, 'percentage' => 20.00, 'description' => 'Second month payment'],
                    ['month_number' => 3, 'percentage' => 22.00, 'description' => 'Third month payment'],
                    ['month_number' => 4, 'percentage' => 15.00, 'description' => 'Fourth month payment'],
                    ['month_number' => 5, 'percentage' => 3.00, 'description' => 'Fifth month payment'],
                ]
            ],
            [
                'name' => 'Installment Plan B',
                'code' => 'INSTALLMENT_B',
                'down_payment_percentage' => 20.00,
                'remaining_percentage' => 80.00,
                'term_months' => 6,
                'description' => '6-month installment plan with 20% down payment',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 15.00, 'description' => 'First month payment'],
                    ['month_number' => 2, 'percentage' => 15.00, 'description' => 'Second month payment'],
                    ['month_number' => 3, 'percentage' => 15.00, 'description' => 'Third month payment'],
                    ['month_number' => 4, 'percentage' => 15.00, 'description' => 'Fourth month payment'],
                    ['month_number' => 5, 'percentage' => 10.00, 'description' => 'Fifth month payment'],
                    ['month_number' => 6, 'percentage' => 10.00, 'description' => 'Sixth month payment'],
                ]
            ],
            [
                'name' => 'Cash Payment',
                'code' => 'CASH',
                'down_payment_percentage' => 100.00,
                'remaining_percentage' => 0.00,
                'term_months' => 1,
                'description' => 'Full cash payment',
                'active' => true,
                'schedules' => []
            ],
            [
                'name' => 'Installment Plan C',
                'code' => 'INSTALLMENT_C',
                'down_payment_percentage' => 50.00,
                'remaining_percentage' => 50.00,
                'term_months' => 3,
                'description' => '3-month installment plan with 50% down payment',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 20.00, 'description' => 'First month payment'],
                    ['month_number' => 2, 'percentage' => 20.00, 'description' => 'Second month payment'],
                    ['month_number' => 3, 'percentage' => 10.00, 'description' => 'Third month payment'],
                ]
            ],
        ];

        foreach ($paymentTerms as $termData) {
            $schedules = $termData['schedules'];
            unset($termData['schedules']);
            
            $paymentTerm = PaymentTerm::firstOrCreate(['code' => $termData['code']], $termData);
            
            // Only create schedules if this is a new payment term
            if ($paymentTerm->wasRecentlyCreated) {
                foreach ($schedules as $schedule) {
                    $paymentTerm->schedules()->create($schedule);
                }
            }
        }

        // Create additional random payment terms to reach 25 total
        $faker = \Faker\Factory::create();
        $termNames = ['Installment Plan', 'Payment Plan', 'Credit Plan', 'Layaway Plan', 'Financing Plan', 'Payment Option', 'Credit Option', 'Installment Option', 'Payment Scheme', 'Credit Scheme'];
        $termCodes = ['INSTALLMENT', 'PAYMENT', 'CREDIT', 'LAYAWAY', 'FINANCING', 'OPTION', 'SCHEME'];
        
        // Calculate how many more payment terms we need to reach 25 total
        $existingCount = count($paymentTerms);
        $additionalCount = 25 - $existingCount;
        
        for ($i = 0; $i < $additionalCount; $i++) {
            $termName = $faker->randomElement($termNames) . ' ' . $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']) . $faker->numberBetween(1, 10);
            $termCode = $faker->randomElement($termCodes) . '_' . $faker->unique()->numberBetween(1, 999);
            
            $downPayment = $faker->randomFloat(2, 10, 80);
            $termMonths = $faker->numberBetween(1, 12);
            
            $paymentTerm = PaymentTerm::firstOrCreate(['code' => $termCode], [
                'name' => $termName,
                'code' => $termCode,
                'down_payment_percentage' => $downPayment,
                'remaining_percentage' => 100 - $downPayment,
                'term_months' => $termMonths,
                'description' => $faker->sentence(),
                'active' => $faker->boolean(85), // 85% active
            ]);
            
            // Create schedules for installment plans
            if ($paymentTerm->wasRecentlyCreated && $termMonths > 1 && (100 - $downPayment) > 0) {
                $remainingPercentage = 100 - $downPayment;
                $equalPercentage = $remainingPercentage / $termMonths;
                
                for ($j = 1; $j <= $termMonths; $j++) {
                    $percentage = $j === $termMonths 
                        ? $remainingPercentage - ($equalPercentage * ($termMonths - 1)) // Last month gets remainder
                        : $equalPercentage;
                    
                    $paymentTerm->schedules()->create([
                        'month_number' => $j,
                        'percentage' => round($percentage, 2),
                        'description' => "Month {$j} payment"
                    ]);
                }
            }
        }
    }
}