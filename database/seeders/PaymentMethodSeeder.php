<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create essential payment methods for jewelry business
        $paymentMethods = [
            [
                'bank_name' => 'BPI (Bank of the Philippine Islands)',
                'account_name' => 'Illussso BPI Account.',
                'account_number' => '1234567890',
                'description' => 'BPI Business Savings Account for jewelry payments and transactions',
                'qr_code_image' => null,
                'active' => true,
            ],
            [
                'bank_name' => 'GCash',
                'account_name' => 'Illussso GCash Account.',
                'account_number' => '09171234567',
                'description' => 'GCash Business Account for mobile wallet payments',
                'qr_code_image' => null,
                'active' => true,
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::firstOrCreate(['account_number' => $method['account_number']], $method);
        }
    }
}