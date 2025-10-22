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
        // Create valid Philippines formal taxes for business operations
        $taxes = [
            [
                'name' => 'Value Added Tax (VAT)',
                'code' => 'VAT',
                'rate' => 12.00,
                'description' => 'Standard VAT rate for goods and services in the Philippines',
                'active' => true,
            ],
            [
                'name' => 'Expanded Withholding Tax (EWT)',
                'code' => 'EWT',
                'rate' => 1.00,
                'description' => 'Expanded Withholding Tax on payments to suppliers and contractors',
                'active' => true,
            ],
            [
                'name' => 'Final Withholding Tax (FWT)',
                'code' => 'FWT',
                'rate' => 2.00,
                'description' => 'Final Withholding Tax on certain payments',
                'active' => true,
            ],
            [
                'name' => 'Local Business Tax',
                'code' => 'LBT',
                'rate' => 1.00,
                'description' => 'Local Business Tax imposed by city/municipality',
                'active' => true,
            ],
            [
                'name' => 'Documentary Stamp Tax',
                'code' => 'DST',
                'rate' => 1.50,
                'description' => 'Documentary Stamp Tax on receipts and documents',
                'active' => true,
            ],
            [
                'name' => 'Income Tax',
                'code' => 'INCOME_TAX',
                'rate' => 30.00,
                'description' => 'Corporate Income Tax rate',
                'active' => true,
            ],
            [
                'name' => 'Capital Gains Tax',
                'code' => 'CGT',
                'rate' => 6.00,
                'description' => 'Capital Gains Tax on sale of capital assets',
                'active' => true,
            ],
            [
                'name' => 'Donor\'s Tax',
                'code' => 'DONOR_TAX',
                'rate' => 6.00,
                'description' => 'Donor\'s Tax on gifts and donations',
                'active' => true,
            ],
            [
                'name' => 'Estate Tax',
                'code' => 'ESTATE_TAX',
                'rate' => 6.00,
                'description' => 'Estate Tax on inherited properties',
                'active' => true,
            ],
            [
                'name' => 'Real Property Tax',
                'code' => 'RPT',
                'rate' => 2.00,
                'description' => 'Real Property Tax on land and buildings',
                'active' => true,
            ],
            [
                'name' => 'Professional Tax',
                'code' => 'PROF_TAX',
                'rate' => 0.50,
                'description' => 'Professional Tax for licensed professionals',
                'active' => true,
            ],
            [
                'name' => 'Franchise Tax',
                'code' => 'FRANCHISE_TAX',
                'rate' => 2.00,
                'description' => 'Franchise Tax on franchise holders',
                'active' => true,
            ],
            [
                'name' => 'Amusement Tax',
                'code' => 'AMUSEMENT_TAX',
                'rate' => 10.00,
                'description' => 'Amusement Tax on entertainment venues',
                'active' => true,
            ],
            [
                'name' => 'Community Tax',
                'code' => 'COMMUNITY_TAX',
                'rate' => 0.25,
                'description' => 'Community Tax (Cedula) for individuals and businesses',
                'active' => true,
            ],
            [
                'name' => 'Transfer Tax',
                'code' => 'TRANSFER_TAX',
                'rate' => 0.50,
                'description' => 'Transfer Tax on real property transfers',
                'active' => true,
            ],
            [
                'name' => 'Percentage Tax',
                'code' => 'PERCENTAGE_TAX',
                'rate' => 3.00,
                'description' => 'Percentage Tax for non-VAT registered businesses',
                'active' => true,
            ],
            [
                'name' => 'Withholding Tax on Compensation',
                'code' => 'WTC',
                'rate' => 0.00,
                'description' => 'Withholding Tax on employee compensation (varies by income bracket)',
                'active' => true,
            ],
            [
                'name' => 'Withholding Tax on Government Money Payments',
                'code' => 'WTGMP',
                'rate' => 1.00,
                'description' => 'Withholding Tax on payments to government contractors',
                'active' => true,
            ],
            [
                'name' => 'Withholding Tax on Interest',
                'code' => 'WTI',
                'rate' => 20.00,
                'description' => 'Withholding Tax on bank interest and other interest income',
                'active' => true,
            ],
            [
                'name' => 'Withholding Tax on Dividends',
                'code' => 'WTD',
                'rate' => 10.00,
                'description' => 'Withholding Tax on dividend payments',
                'active' => true,
            ],
            [
                'name' => 'Withholding Tax on Royalties',
                'code' => 'WTR',
                'rate' => 20.00,
                'description' => 'Withholding Tax on royalty payments',
                'active' => true,
            ],
            [
                'name' => 'Withholding Tax on Rentals',
                'code' => 'WTRENT',
                'rate' => 5.00,
                'description' => 'Withholding Tax on rental income',
                'active' => true,
            ],
            [
                'name' => 'Withholding Tax on Professional Fees',
                'code' => 'WTPF',
                'rate' => 10.00,
                'description' => 'Withholding Tax on professional service fees',
                'active' => true,
            ],
            [
                'name' => 'Withholding Tax on Commissions',
                'code' => 'WTC',
                'rate' => 5.00,
                'description' => 'Withholding Tax on commission payments',
                'active' => true,
            ],
            [
                'name' => 'Withholding Tax on Prizes and Winnings',
                'code' => 'WTPW',
                'rate' => 20.00,
                'description' => 'Withholding Tax on prizes and winnings',
                'active' => true,
            ],
        ];

        foreach ($taxes as $tax) {
            Tax::firstOrCreate(['code' => $tax['code']], $tax);
        }
    }
}