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
        // Create Philippines-specific taxes
        $taxes = [
            [
                'name' => 'VAT (Value Added Tax)',
                'code' => 'VAT',
                'rate' => 12.00,
                'description' => 'Philippines Standard VAT Rate - 12%',
                'active' => true,
            ],
            [
                'name' => 'Jewelry Excise Tax',
                'code' => 'JEWELRY_EXCISE',
                'rate' => 20.00,
                'description' => 'Excise Tax on Jewelry and Precious Metals',
                'active' => true,
            ],
            [
                'name' => 'Withholding Tax - Expanded',
                'code' => 'EWT',
                'rate' => 1.00,
                'description' => 'Expanded Withholding Tax for Services',
                'active' => true,
            ],
            [
                'name' => 'Withholding Tax - Final',
                'code' => 'FWT',
                'rate' => 2.00,
                'description' => 'Final Withholding Tax',
                'active' => true,
            ],
            [
                'name' => 'Local Business Tax',
                'code' => 'LBT',
                'rate' => 1.00,
                'description' => 'Local Business Tax (Municipal/City)',
                'active' => true,
            ],
            [
                'name' => 'Documentary Stamp Tax',
                'code' => 'DST',
                'rate' => 1.50,
                'description' => 'Documentary Stamp Tax on Receipts',
                'active' => true,
            ],
        ];

        foreach ($taxes as $tax) {
            Tax::firstOrCreate(['code' => $tax['code']], $tax);
        }

        // Create additional Philippines-specific taxes to reach 25 total
        $faker = \Faker\Factory::create();
        $taxTypes = ['VAT', 'Jewelry Excise Tax', 'Withholding Tax', 'Local Business Tax', 'Documentary Stamp Tax', 'Income Tax', 'Capital Gains Tax', 'Donor\'s Tax', 'Estate Tax', 'Real Property Tax', 'Professional Tax', 'Franchise Tax', 'Amusement Tax', 'Community Tax', 'Transfer Tax'];
        $taxCodes = ['VAT', 'JEWELRY_EXCISE', 'WHT', 'LBT', 'DST', 'INCOME', 'CGT', 'DONOR', 'ESTATE', 'RPT', 'PROF', 'FRANCHISE', 'AMUSEMENT', 'COMMUNITY', 'TRANSFER'];
        
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