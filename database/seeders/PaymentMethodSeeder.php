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

        // Create additional random payment methods to reach 25 total
        $faker = \Faker\Factory::create();
        $bankNames = ['BDO', 'BPI', 'Metrobank', 'Security Bank', 'EastWest Bank', 'RCBC', 'PNB', 'UnionBank', 'Chinabank', 'Landbank', 'PSBank', 'Robinsons Bank', 'CIMB Bank', 'ING Bank', 'Maybank', 'AUB', 'Sterling Bank', 'UCPB', 'Philippine Bank of Communications', 'Bank of Commerce'];
        $accountTypes = ['Savings Account', 'Checking Account', 'Current Account', 'Business Account', 'Corporate Account'];
        
        // Calculate how many more payment methods we need to reach 25 total
        $existingCount = count($paymentMethods);
        $additionalCount = 25 - $existingCount;
        
        for ($i = 0; $i < $additionalCount; $i++) {
            $bankName = $faker->randomElement($bankNames);
            $accountType = $faker->randomElement($accountTypes);
            
            PaymentMethod::create([
                'bank_name' => $bankName,
                'account_name' => 'Jewelry Store Inc.',
                'account_number' => $faker->numerify('##########'),
                'description' => $bankName . ' ' . $accountType . ' for jewelry payments',
                'qr_code_image' => null,
                'active' => $faker->boolean(90), // 90% active
            ]);
        }
    }
}