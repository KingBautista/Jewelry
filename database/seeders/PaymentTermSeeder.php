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
        // Create Philippines-specific payment terms with schedules
        $paymentTerms = [
            [
                'name' => 'Bayad Agad (Cash Payment)',
                'code' => 'BAYAD_AGAD',
                'down_payment_percentage' => 100.00,
                'remaining_percentage' => 0.00,
                'term_months' => 1,
                'description' => 'Full cash payment - bayad agad',
                'active' => true,
                'schedules' => []
            ],
            [
                'name' => 'Hulugan Plan A (Installment Plan A)',
                'code' => 'HULUGAN_A',
                'down_payment_percentage' => 30.00,
                'remaining_percentage' => 70.00,
                'term_months' => 6,
                'description' => '6-month hulugan plan with 30% down payment',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 15.00, 'description' => 'Unang buwan na bayad'],
                    ['month_number' => 2, 'percentage' => 15.00, 'description' => 'Ikalawang buwan na bayad'],
                    ['month_number' => 3, 'percentage' => 15.00, 'description' => 'Ikatlong buwan na bayad'],
                    ['month_number' => 4, 'percentage' => 15.00, 'description' => 'Ikaapat na buwan na bayad'],
                    ['month_number' => 5, 'percentage' => 5.00, 'description' => 'Ikalimang buwan na bayad'],
                    ['month_number' => 6, 'percentage' => 5.00, 'description' => 'Ikaanim na buwan na bayad'],
                ]
            ],
            [
                'name' => 'Hulugan Plan B (Installment Plan B)',
                'code' => 'HULUGAN_B',
                'down_payment_percentage' => 20.00,
                'remaining_percentage' => 80.00,
                'term_months' => 12,
                'description' => '12-month hulugan plan with 20% down payment',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 8.00, 'description' => 'Unang buwan na bayad'],
                    ['month_number' => 2, 'percentage' => 8.00, 'description' => 'Ikalawang buwan na bayad'],
                    ['month_number' => 3, 'percentage' => 8.00, 'description' => 'Ikatlong buwan na bayad'],
                    ['month_number' => 4, 'percentage' => 8.00, 'description' => 'Ikaapat na buwan na bayad'],
                    ['month_number' => 5, 'percentage' => 8.00, 'description' => 'Ikalimang buwan na bayad'],
                    ['month_number' => 6, 'percentage' => 8.00, 'description' => 'Ikaanim na buwan na bayad'],
                    ['month_number' => 7, 'percentage' => 8.00, 'description' => 'Ikapitong buwan na bayad'],
                    ['month_number' => 8, 'percentage' => 8.00, 'description' => 'Ikawalong buwan na bayad'],
                    ['month_number' => 9, 'percentage' => 8.00, 'description' => 'Ikasiyam na buwan na bayad'],
                    ['month_number' => 10, 'percentage' => 8.00, 'description' => 'Ikasampung buwan na bayad'],
                    ['month_number' => 11, 'percentage' => 0.00, 'description' => 'Ikalabing-isang buwan na bayad'],
                    ['month_number' => 12, 'percentage' => 0.00, 'description' => 'Ikalabing-dalawang buwan na bayad'],
                ]
            ],
            [
                'name' => 'Suki Plan (Loyalty Plan)',
                'code' => 'SUKI_PLAN',
                'down_payment_percentage' => 50.00,
                'remaining_percentage' => 50.00,
                'term_months' => 3,
                'description' => 'Special plan para sa mga suki - 3 months',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 20.00, 'description' => 'Unang buwan na bayad'],
                    ['month_number' => 2, 'percentage' => 20.00, 'description' => 'Ikalawang buwan na bayad'],
                    ['month_number' => 3, 'percentage' => 10.00, 'description' => 'Ikatlong buwan na bayad'],
                ]
            ],
            [
                'name' => 'VIP Plan (Premium Plan)',
                'code' => 'VIP_PLAN',
                'down_payment_percentage' => 10.00,
                'remaining_percentage' => 90.00,
                'term_months' => 18,
                'description' => 'Premium plan para sa VIP customers - 18 months',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 5.00, 'description' => 'Unang buwan na bayad'],
                    ['month_number' => 2, 'percentage' => 5.00, 'description' => 'Ikalawang buwan na bayad'],
                    ['month_number' => 3, 'percentage' => 5.00, 'description' => 'Ikatlong buwan na bayad'],
                    ['month_number' => 4, 'percentage' => 5.00, 'description' => 'Ikaapat na buwan na bayad'],
                    ['month_number' => 5, 'percentage' => 5.00, 'description' => 'Ikalimang buwan na bayad'],
                    ['month_number' => 6, 'percentage' => 5.00, 'description' => 'Ikaanim na buwan na bayad'],
                    ['month_number' => 7, 'percentage' => 5.00, 'description' => 'Ikapitong buwan na bayad'],
                    ['month_number' => 8, 'percentage' => 5.00, 'description' => 'Ikawalong buwan na bayad'],
                    ['month_number' => 9, 'percentage' => 5.00, 'description' => 'Ikasiyam na buwan na bayad'],
                    ['month_number' => 10, 'percentage' => 5.00, 'description' => 'Ikasampung buwan na bayad'],
                    ['month_number' => 11, 'percentage' => 5.00, 'description' => 'Ikalabing-isang buwan na bayad'],
                    ['month_number' => 12, 'percentage' => 5.00, 'description' => 'Ikalabing-dalawang buwan na bayad'],
                    ['month_number' => 13, 'percentage' => 5.00, 'description' => 'Ikalabing-tatlong buwan na bayad'],
                    ['month_number' => 14, 'percentage' => 5.00, 'description' => 'Ikalabing-apat na buwan na bayad'],
                    ['month_number' => 15, 'percentage' => 5.00, 'description' => 'Ikalabing-lima na buwan na bayad'],
                    ['month_number' => 16, 'percentage' => 5.00, 'description' => 'Ikalabing-anim na buwan na bayad'],
                    ['month_number' => 17, 'percentage' => 5.00, 'description' => 'Ikalabing-pitong buwan na bayad'],
                    ['month_number' => 18, 'percentage' => 5.00, 'description' => 'Ikalabing-walong buwan na bayad'],
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

        // Create additional Philippines-specific payment terms to reach 25 total
        $faker = \Faker\Factory::create();
        $termNames = ['Hulugan Plan', 'Bayad Plan', 'Utang Plan', 'Layaway Plan', 'Financing Plan', 'Payment Option', 'Credit Option', 'Installment Option', 'Payment Scheme', 'Credit Scheme', 'Suki Plan', 'VIP Plan', 'Premium Plan', 'Special Plan', 'Custom Plan'];
        $termCodes = ['HULUGAN', 'BAYAD', 'UTANG', 'LAYAWAY', 'FINANCING', 'OPTION', 'SCHEME', 'SUKI', 'VIP', 'PREMIUM', 'SPECIAL', 'CUSTOM'];
        
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