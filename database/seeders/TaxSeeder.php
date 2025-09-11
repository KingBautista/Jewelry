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

        // Create additional random taxes to reach 25 total
        $faker = \Faker\Factory::create();
        $taxTypes = ['VAT', 'Sales Tax', 'Service Tax', 'Withholding Tax', 'Excise Tax', 'Import Tax', 'Export Tax', 'Luxury Tax', 'Environmental Tax', 'Digital Tax'];
        $taxCodes = ['VAT', 'SALES', 'SERVICE', 'WHT', 'EXCISE', 'IMPORT', 'EXPORT', 'LUXURY', 'ENV', 'DIGITAL'];
        
        // Calculate how many more taxes we need to reach 25 total
        $existingCount = count($taxes);
        $additionalCount = 25 - $existingCount;
        
        for ($i = 0; $i < $additionalCount; $i++) {
            $taxType = $faker->randomElement($taxTypes);
            $taxCode = $faker->randomElement($taxCodes) . '_' . $faker->unique()->numberBetween(1, 999);
            
            Tax::firstOrCreate(['code' => $taxCode], [
                'name' => $taxType . ' ' . $faker->numberBetween(1, 20),
                'code' => $taxCode,
                'rate' => $faker->randomFloat(2, 0.5, 25.0),
                'description' => $faker->sentence(),
                'active' => $faker->boolean(85), // 85% active
            ]);
        }
    }
}