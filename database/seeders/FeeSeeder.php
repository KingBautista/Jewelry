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
        // Create realistic fees for jewelry business in the Philippines
        $fees = [
            [
                'name' => 'Metro Manila Delivery',
                'code' => 'MM_DELIVERY',
                'amount' => 150.00,
                'type' => 'fixed',
                'description' => 'Standard delivery fee within Metro Manila',
                'active' => true,
            ],
            [
                'name' => 'Provincial Delivery',
                'code' => 'PROVINCIAL_DELIVERY',
                'amount' => 300.00,
                'type' => 'fixed',
                'description' => 'Delivery fee to provinces outside Metro Manila',
                'active' => true,
            ],
            [
                'name' => 'Express Delivery',
                'code' => 'EXPRESS_DELIVERY',
                'amount' => 500.00,
                'type' => 'fixed',
                'description' => 'Same-day or next-day delivery service',
                'active' => true,
            ],
            [
                'name' => 'Jewelry Appraisal Fee',
                'code' => 'APPRAISAL',
                'amount' => 500.00,
                'type' => 'fixed',
                'description' => 'Professional jewelry appraisal and certification',
                'active' => true,
            ],
            [
                'name' => 'Jewelry Cleaning Service',
                'code' => 'CLEANING',
                'amount' => 200.00,
                'type' => 'fixed',
                'description' => 'Professional jewelry cleaning and polishing',
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
                'name' => 'Jewelry Resizing',
                'code' => 'RESIZING',
                'amount' => 300.00,
                'type' => 'fixed',
                'description' => 'Ring resizing service',
                'active' => true,
            ],
            [
                'name' => 'Engraving Service',
                'code' => 'ENGRAVING',
                'amount' => 150.00,
                'type' => 'fixed',
                'description' => 'Personalized engraving service',
                'active' => true,
            ],
            [
                'name' => 'Jewelry Authentication',
                'code' => 'AUTHENTICATION',
                'amount' => 800.00,
                'type' => 'fixed',
                'description' => 'Professional jewelry authentication and certification',
                'active' => true,
            ],
            [
                'name' => 'Insurance Fee',
                'code' => 'INSURANCE',
                'amount' => 2.00,
                'type' => 'percentage',
                'description' => 'Jewelry insurance coverage fee (2% of item value)',
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
                'name' => 'Credit Card Processing Fee',
                'code' => 'CC_PROCESSING',
                'amount' => 3.50,
                'type' => 'percentage',
                'description' => 'Credit card processing fee (3.5% of transaction)',
                'active' => true,
            ],
            [
                'name' => 'GCash Processing Fee',
                'code' => 'GCASH_PROCESSING',
                'amount' => 15.00,
                'type' => 'fixed',
                'description' => 'GCash payment processing fee',
                'active' => true,
            ],
            [
                'name' => 'PayMaya Processing Fee',
                'code' => 'PAYMAYA_PROCESSING',
                'amount' => 15.00,
                'type' => 'fixed',
                'description' => 'PayMaya payment processing fee',
                'active' => true,
            ],
            [
                'name' => 'GrabPay Processing Fee',
                'code' => 'GRABPAY_PROCESSING',
                'amount' => 20.00,
                'type' => 'fixed',
                'description' => 'GrabPay payment processing fee',
                'active' => true,
            ],
            [
                'name' => 'Installation Fee',
                'code' => 'INSTALLATION',
                'amount' => 200.00,
                'type' => 'fixed',
                'description' => 'Jewelry installation and setup fee',
                'active' => true,
            ],
            [
                'name' => 'Custom Design Fee',
                'code' => 'CUSTOM_DESIGN',
                'amount' => 2000.00,
                'type' => 'fixed',
                'description' => 'Custom jewelry design consultation and creation',
                'active' => true,
            ],
            [
                'name' => 'Warranty Extension',
                'code' => 'WARRANTY_EXTENSION',
                'amount' => 500.00,
                'type' => 'fixed',
                'description' => 'Extended warranty service fee',
                'active' => true,
            ],
            [
                'name' => 'Storage Fee',
                'code' => 'STORAGE',
                'amount' => 100.00,
                'type' => 'fixed',
                'description' => 'Monthly storage fee for jewelry items',
                'active' => true,
            ],
            [
                'name' => 'Consultation Fee',
                'code' => 'CONSULTATION',
                'amount' => 300.00,
                'type' => 'fixed',
                'description' => 'Jewelry consultation and advisory service',
                'active' => true,
            ],
        ];

        foreach ($fees as $fee) {
            Fee::firstOrCreate(['code' => $fee['code']], $fee);
        }
    }
}