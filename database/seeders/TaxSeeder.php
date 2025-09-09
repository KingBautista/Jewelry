<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tax;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific taxes
        $taxes = [
            [
                'name' => 'VAT',
                'code' => 'VAT',
                'rate' => 12.00,
                'description' => 'Value Added Tax',
                'active' => true,
            ],
            [
                'name' => 'Sales Tax',
                'code' => 'SALES_TAX',
                'rate' => 8.00,
                'description' => 'Sales Tax on Jewelry',
                'active' => true,
            ],
            [
                'name' => 'Service Tax',
                'code' => 'SERVICE_TAX',
                'rate' => 10.00,
                'description' => 'Service Tax for Jewelry Services',
                'active' => true,
            ],
            [
                'name' => 'Withholding Tax',
                'code' => 'WHT',
                'rate' => 2.00,
                'description' => 'Withholding Tax',
                'active' => true,
            ],
        ];

        foreach ($taxes as $tax) {
            Tax::firstOrCreate(['code' => $tax['code']], $tax);
        }

        // Create additional random taxes using factory
        Tax::factory(3)->create();
    }
}