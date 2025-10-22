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
        // Create formal payment terms for jewelry business in the Philippines
        $paymentTerms = [
            [
                'name' => 'Cash Payment',
                'code' => 'CASH_PAYMENT',
                'down_payment_percentage' => 100.00,
                'remaining_percentage' => 0.00,
                'term_months' => 1,
                'description' => 'Full cash payment upon purchase',
                'active' => true,
                'schedules' => []
            ],
            [
                'name' => '30 Days Net',
                'code' => 'NET_30',
                'down_payment_percentage' => 0.00,
                'remaining_percentage' => 100.00,
                'term_months' => 1,
                'description' => 'Payment due within 30 days of invoice date',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 100.00, 'description' => 'Payment due within 30 days']
                ]
            ],
            [
                'name' => '60 Days Net',
                'code' => 'NET_60',
                'down_payment_percentage' => 0.00,
                'remaining_percentage' => 100.00,
                'term_months' => 2,
                'description' => 'Payment due within 60 days of invoice date',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 0.00, 'description' => 'No payment required'],
                    ['month_number' => 2, 'percentage' => 100.00, 'description' => 'Payment due within 60 days']
                ]
            ],
            [
                'name' => '90 Days Net',
                'code' => 'NET_90',
                'down_payment_percentage' => 0.00,
                'remaining_percentage' => 100.00,
                'term_months' => 3,
                'description' => 'Payment due within 90 days of invoice date',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 0.00, 'description' => 'No payment required'],
                    ['month_number' => 2, 'percentage' => 0.00, 'description' => 'No payment required'],
                    ['month_number' => 3, 'percentage' => 100.00, 'description' => 'Payment due within 90 days']
                ]
            ],
            [
                'name' => '2/10 Net 30',
                'code' => '2_10_NET_30',
                'down_payment_percentage' => 0.00,
                'remaining_percentage' => 100.00,
                'term_months' => 1,
                'description' => '2% discount if paid within 10 days, otherwise net 30 days',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 100.00, 'description' => 'Payment due within 30 days (2% discount if paid within 10 days)']
                ]
            ],
            [
                'name' => '50% Down, 50% on Delivery',
                'code' => '50_50_DELIVERY',
                'down_payment_percentage' => 50.00,
                'remaining_percentage' => 50.00,
                'term_months' => 1,
                'description' => '50% down payment, 50% upon delivery',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 50.00, 'description' => 'Balance due upon delivery']
                ]
            ],
            [
                'name' => '30% Down, 70% in 30 Days',
                'code' => '30_70_30DAYS',
                'down_payment_percentage' => 30.00,
                'remaining_percentage' => 70.00,
                'term_months' => 1,
                'description' => '30% down payment, 70% due in 30 days',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 70.00, 'description' => 'Balance due in 30 days']
                ]
            ],
            [
                'name' => '3-Month Installment',
                'code' => '3_MONTH_INSTALLMENT',
                'down_payment_percentage' => 33.33,
                'remaining_percentage' => 66.67,
                'term_months' => 3,
                'description' => 'Equal payments over 3 months',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 33.33, 'description' => 'First month payment'],
                    ['month_number' => 2, 'percentage' => 33.33, 'description' => 'Second month payment'],
                    ['month_number' => 3, 'percentage' => 33.34, 'description' => 'Third month payment']
                ]
            ],
            [
                'name' => '6-Month Installment',
                'code' => '6_MONTH_INSTALLMENT',
                'down_payment_percentage' => 20.00,
                'remaining_percentage' => 80.00,
                'term_months' => 6,
                'description' => '20% down payment, balance in 6 equal installments',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 13.33, 'description' => 'Month 1 payment'],
                    ['month_number' => 2, 'percentage' => 13.33, 'description' => 'Month 2 payment'],
                    ['month_number' => 3, 'percentage' => 13.33, 'description' => 'Month 3 payment'],
                    ['month_number' => 4, 'percentage' => 13.33, 'description' => 'Month 4 payment'],
                    ['month_number' => 5, 'percentage' => 13.33, 'description' => 'Month 5 payment'],
                    ['month_number' => 6, 'percentage' => 13.35, 'description' => 'Month 6 payment']
                ]
            ],
            [
                'name' => '12-Month Installment',
                'code' => '12_MONTH_INSTALLMENT',
                'down_payment_percentage' => 15.00,
                'remaining_percentage' => 85.00,
                'term_months' => 12,
                'description' => '15% down payment, balance in 12 equal installments',
                'active' => true,
                'schedules' => [
                    ['month_number' => 1, 'percentage' => 7.08, 'description' => 'Month 1 payment'],
                    ['month_number' => 2, 'percentage' => 7.08, 'description' => 'Month 2 payment'],
                    ['month_number' => 3, 'percentage' => 7.08, 'description' => 'Month 3 payment'],
                    ['month_number' => 4, 'percentage' => 7.08, 'description' => 'Month 4 payment'],
                    ['month_number' => 5, 'percentage' => 7.08, 'description' => 'Month 5 payment'],
                    ['month_number' => 6, 'percentage' => 7.08, 'description' => 'Month 6 payment'],
                    ['month_number' => 7, 'percentage' => 7.08, 'description' => 'Month 7 payment'],
                    ['month_number' => 8, 'percentage' => 7.08, 'description' => 'Month 8 payment'],
                    ['month_number' => 9, 'percentage' => 7.08, 'description' => 'Month 9 payment'],
                    ['month_number' => 10, 'percentage' => 7.08, 'description' => 'Month 10 payment'],
                    ['month_number' => 11, 'percentage' => 7.08, 'description' => 'Month 11 payment'],
                    ['month_number' => 12, 'percentage' => 7.12, 'description' => 'Month 12 payment']
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

    }
}