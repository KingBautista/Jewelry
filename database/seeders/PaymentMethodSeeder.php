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
        // Create specific payment methods
        $paymentMethods = [
            [
                'bank_name' => 'BDO',
                'account_name' => 'Jewelry Store Inc.',
                'account_number' => '1234567890',
                'description' => 'BDO Savings Account for jewelry payments',
                'qr_code_image' => null,
                'active' => true,
            ],
            [
                'bank_name' => 'BPI',
                'account_name' => 'Jewelry Store Inc.',
                'account_number' => '0987654321',
                'description' => 'BPI Savings Account for jewelry payments',
                'qr_code_image' => null,
                'active' => true,
            ],
            [
                'bank_name' => 'Metrobank',
                'account_name' => 'Jewelry Store Inc.',
                'account_number' => '1122334455',
                'description' => 'Metrobank Savings Account for jewelry payments',
                'qr_code_image' => null,
                'active' => true,
            ],
            [
                'bank_name' => 'Security Bank',
                'account_name' => 'Jewelry Store Inc.',
                'account_number' => '5566778899',
                'description' => 'Security Bank Savings Account for jewelry payments',
                'qr_code_image' => null,
                'active' => true,
            ],
            [
                'bank_name' => 'EastWest Bank',
                'account_name' => 'Jewelry Store Inc.',
                'account_number' => '9988776655',
                'description' => 'EastWest Bank Savings Account for jewelry payments',
                'qr_code_image' => null,
                'active' => true,
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::create($method);
        }

        // Create additional random payment methods using factory
        PaymentMethod::factory(3)->create();
    }
}