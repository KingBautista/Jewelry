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
        // Create specific fees
        $fees = [
            [
                'name' => 'Delivery Fee',
                'code' => 'DELIVERY',
                'amount' => 500.00,
                'type' => 'fixed',
                'description' => 'Standard delivery fee for jewelry items',
                'active' => true,
            ],
            [
                'name' => 'Processing Fee',
                'code' => 'PROCESSING',
                'amount' => 50.00,
                'type' => 'fixed',
                'description' => 'Processing fee for orders',
                'active' => true,
            ],
            [
                'name' => 'Installation Fee',
                'code' => 'INSTALLATION',
                'amount' => 1000.00,
                'type' => 'fixed',
                'description' => 'Installation fee for jewelry items',
                'active' => true,
            ],
            [
                'name' => 'Service Fee',
                'code' => 'SERVICE',
                'amount' => 5.00,
                'type' => 'percentage',
                'description' => 'Service fee percentage',
                'active' => true,
            ],
            [
                'name' => 'Handling Fee',
                'code' => 'HANDLING',
                'amount' => 2.50,
                'type' => 'percentage',
                'description' => 'Handling fee percentage',
                'active' => true,
            ],
        ];

        foreach ($fees as $fee) {
            Fee::firstOrCreate(['code' => $fee['code']], $fee);
        }

        // Create additional random fees using factory
        Fee::factory(3)->create();
    }
}