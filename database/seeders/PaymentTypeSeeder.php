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
        // Create realistic payment types for jewelry business in the Philippines
        $paymentTypes = [
            [
                'name' => 'Cash Payment',
                'code' => 'CASH',
                'description' => 'Cash payment at point of sale',
                'is_active' => true,
            ],
            [
                'name' => 'Credit Card',
                'code' => 'CREDIT_CARD',
                'description' => 'Payment via credit card (Visa, Mastercard, etc.)',
                'is_active' => true,
            ],
            [
                'name' => 'Debit Card',
                'code' => 'DEBIT_CARD',
                'description' => 'Payment via debit card',
                'is_active' => true,
            ],
            [
                'name' => 'Bank Transfer',
                'code' => 'BANK_TRANSFER',
                'description' => 'Direct bank transfer payment',
                'is_active' => true,
            ],
            [
                'name' => 'GCash',
                'code' => 'GCASH',
                'description' => 'Payment via GCash mobile wallet',
                'is_active' => true,
            ],
            [
                'name' => 'PayMaya',
                'code' => 'PAYMAYA',
                'description' => 'Payment via PayMaya mobile wallet',
                'is_active' => true,
            ],
            [
                'name' => 'GrabPay',
                'code' => 'GRABPAY',
                'description' => 'Payment via GrabPay mobile wallet',
                'is_active' => true,
            ],
            [
                'name' => 'PayPal',
                'code' => 'PAYPAL',
                'description' => 'Payment via PayPal',
                'is_active' => true,
            ],
            [
                'name' => 'Check Payment',
                'code' => 'CHECK',
                'description' => 'Payment via personal or business check',
                'is_active' => true,
            ],
            [
                'name' => 'Down Payment',
                'code' => 'DOWN_PAYMENT',
                'description' => 'Initial down payment for installment plans',
                'is_active' => true,
            ],
            [
                'name' => 'Installment Payment',
                'code' => 'INSTALLMENT',
                'description' => 'Regular installment payment',
                'is_active' => true,
            ],
            [
                'name' => 'Final Payment',
                'code' => 'FINAL_PAYMENT',
                'description' => 'Final payment to complete the transaction',
                'is_active' => true,
            ],
            [
                'name' => 'Partial Payment',
                'code' => 'PARTIAL',
                'description' => 'Partial payment towards the total amount',
                'is_active' => true,
            ],
            [
                'name' => 'Advance Payment',
                'code' => 'ADVANCE',
                'description' => 'Advance payment for custom orders',
                'is_active' => true,
            ],
            [
                'name' => 'Balance Payment',
                'code' => 'BALANCE',
                'description' => 'Payment for remaining balance',
                'is_active' => true,
            ],
            [
                'name' => 'Refund',
                'code' => 'REFUND',
                'description' => 'Refund payment to customer',
                'is_active' => true,
            ],
            [
                'name' => 'Exchange',
                'code' => 'EXCHANGE',
                'description' => 'Payment for item exchange',
                'is_active' => true,
            ],
            [
                'name' => 'Store Credit',
                'code' => 'STORE_CREDIT',
                'description' => 'Payment using store credit or gift certificate',
                'is_active' => true,
            ],
            [
                'name' => 'Layaway Payment',
                'code' => 'LAYAWAY',
                'description' => 'Layaway plan payment',
                'is_active' => true,
            ],
            [
                'name' => 'Trade-in Credit',
                'code' => 'TRADE_IN',
                'description' => 'Payment using trade-in credit',
                'is_active' => true,
            ],
        ];

        foreach ($paymentTypes as $paymentType) {
            PaymentType::firstOrCreate(['code' => $paymentType['code']], $paymentType);
        }
    }
}