<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\User;
use App\Models\PaymentTerm;
use App\Models\Tax;
use App\Models\Fee;
use App\Models\Discount;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        
        // Create specific invoices with realistic data
        $invoices = [
            [
                'invoice_number' => 'INV000001',
                'customer_id' => User::customers()->first()->id,
                'product_name' => 'Diamond Engagement Ring',
                'description' => 'Beautiful 1-carat diamond engagement ring with platinum setting',
                'price' => 150000.00,
                'product_images' => ['invoices/products/diamond-ring-1.jpg', 'invoices/products/diamond-ring-2.jpg'],
                'payment_term_id' => PaymentTerm::first()->id,
                'tax_id' => Tax::first()->id,
                'fee_id' => Fee::first()->id,
                'discount_id' => Discount::first()->id,
                'shipping_address' => '123 Rizal Street, Manila, Metro Manila, 1000',
                'issue_date' => now()->subDays(5),
                'due_date' => now()->addDays(30),
                'status' => 'sent',
                'notes' => 'Special order for wedding',
                'active' => true,
            ],
            [
                'invoice_number' => 'INV000002',
                'customer_id' => User::customers()->skip(1)->first()->id,
                'product_name' => 'Gold Necklace Set',
                'description' => 'Elegant 18k gold necklace with matching earrings',
                'price' => 75000.00,
                'product_images' => ['invoices/products/gold-necklace-set.jpg'],
                'payment_term_id' => PaymentTerm::skip(1)->first()->id,
                'tax_id' => Tax::skip(1)->first()->id,
                'fee_id' => Fee::skip(1)->first()->id,
                'discount_id' => null,
                'shipping_address' => '456 Quezon Avenue, Quezon City, Metro Manila, 1100',
                'issue_date' => now()->subDays(3),
                'due_date' => now()->addDays(15),
                'status' => 'paid',
                'notes' => 'Cash payment',
                'active' => true,
            ],
            [
                'invoice_number' => 'INV000003',
                'customer_id' => User::customers()->skip(2)->first()->id,
                'product_name' => 'Pearl Earrings',
                'description' => 'Classic white pearl earrings with sterling silver',
                'price' => 25000.00,
                'product_images' => ['invoices/products/pearl-earrings.jpg'],
                'payment_term_id' => PaymentTerm::skip(2)->first()->id,
                'tax_id' => null,
                'fee_id' => null,
                'discount_id' => Discount::skip(1)->first()->id,
                'shipping_address' => '789 Makati Avenue, Makati City, Metro Manila, 1200',
                'issue_date' => now()->subDays(1),
                'due_date' => now()->addDays(7),
                'status' => 'draft',
                'notes' => 'Pending customer approval',
                'active' => true,
            ],
        ];

        foreach ($invoices as $invoiceData) {
            $invoice = Invoice::create($invoiceData);
            $invoice->calculateTotals()->save();
            
            // Generate payment schedules if payment terms exist
            if ($invoice->payment_term_id) {
                $invoice->generatePaymentSchedules();
            }
            
            // Create item status for some invoices
            if ($faker->boolean(60)) {
                $statuses = ['pending', 'packed', 'for_delivery', 'delivered'];
                $status = $faker->randomElement($statuses);
                
                \App\Models\InvoiceItemStatus::create([
                    'invoice_id' => $invoice->id,
                    'status' => $status,
                    'status_date' => $faker->dateTimeBetween($invoice->created_at, 'now'),
                    'notes' => $faker->optional(0.5)->sentence(),
                    'updated_by' => User::inRandomOrder()->first()->id,
                ]);
                
                $invoice->update(['item_status' => $status]);
            }
        }

        // Create additional random invoices to reach 25 total
        $faker = \Faker\Factory::create();
        $productNames = [
            'Diamond Ring', 'Gold Necklace', 'Pearl Earrings', 'Silver Bracelet', 'Ruby Pendant',
            'Emerald Ring', 'Sapphire Earrings', 'Platinum Chain', 'Diamond Earrings', 'Gold Watch',
            'Pearl Necklace', 'Silver Ring', 'Diamond Bracelet', 'Gold Earrings', 'Ruby Ring',
            'Emerald Pendant', 'Sapphire Necklace', 'Platinum Ring', 'Diamond Pendant', 'Gold Bracelet'
        ];
        $statuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
        $customers = User::customers()->get();
        $paymentTerms = PaymentTerm::all();
        $taxes = Tax::all();
        $fees = Fee::all();
        $discounts = Discount::all();
        
        // Calculate how many more invoices we need to reach 25 total
        $existingCount = count($invoices);
        $additionalCount = 25 - $existingCount;
        
        for ($i = 0; $i < $additionalCount; $i++) {
            $productName = $faker->randomElement($productNames) . ' ' . $faker->numberBetween(1, 10);
            $invoiceNumber = 'INV' . str_pad($existingCount + $i + 1, 6, '0', STR_PAD_LEFT);
            $customer = $faker->randomElement($customers);
            $issueDate = $faker->dateTimeBetween('-30 days', '+5 days');
            $dueDate = $faker->dateTimeBetween($issueDate, '+60 days');
            
            $invoiceData = [
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->id,
                'product_name' => $productName,
                'description' => $faker->sentence(),
                'price' => $faker->randomFloat(2, 5000, 200000),
                'product_images' => $faker->optional(0.7)->randomElements([
                    'invoices/products/sample-jewelry-1.jpg',
                    'invoices/products/sample-jewelry-2.jpg',
                    'invoices/products/sample-jewelry-3.jpg',
                    'invoices/products/sample-jewelry-4.jpg'
                ], $faker->numberBetween(1, 3)),
                'payment_term_id' => $faker->optional(0.8)->randomElement($paymentTerms)?->id,
                'tax_id' => $faker->optional(0.6)->randomElement($taxes)?->id,
                'fee_id' => $faker->optional(0.4)->randomElement($fees)?->id,
                'discount_id' => $faker->optional(0.3)->randomElement($discounts)?->id,
                'shipping_address' => $faker->optional(0.9)->address(),
                'issue_date' => $issueDate,
                'due_date' => $faker->optional(0.8)->dateTimeBetween($issueDate, '+60 days'),
                'status' => $faker->randomElement($statuses),
                'notes' => $faker->optional(0.4)->sentence(),
                'active' => $faker->boolean(90), // 90% active
            ];
            
            $invoice = Invoice::create($invoiceData);
            $invoice->calculateTotals()->save();
            
            // Generate payment schedules if payment terms exist
            if ($invoice->payment_term_id) {
                $invoice->generatePaymentSchedules();
            }
            
            // Create item status for some invoices
            if ($faker->boolean(60)) {
                $statuses = ['pending', 'packed', 'for_delivery', 'delivered'];
                $status = $faker->randomElement($statuses);
                
                \App\Models\InvoiceItemStatus::create([
                    'invoice_id' => $invoice->id,
                    'status' => $status,
                    'status_date' => $faker->dateTimeBetween($invoice->created_at, 'now'),
                    'notes' => $faker->optional(0.5)->sentence(),
                    'updated_by' => User::inRandomOrder()->first()->id,
                ]);
                
                $invoice->update(['item_status' => $status]);
            }
        }
    }
}