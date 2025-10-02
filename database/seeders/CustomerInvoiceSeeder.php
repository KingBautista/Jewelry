<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Tax;
use App\Models\Fee;
use App\Models\Discount;
use App\Models\PaymentTerm;
use App\Models\Payment;
use Carbon\Carbon;

class CustomerInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        // Get the customer user
        $customer = User::where('user_email', 'customer@invoice-system.com')->first();
        
        if (!$customer) {
            $this->command->error('Customer user not found. Please run UserSeeder first.');
            return;
        }

        $this->command->info('Creating sample invoices for customer: ' . $customer->user_email);

        // Create sample taxes, fees, discounts, and payment terms if they don't exist
        $tax = Tax::firstOrCreate([
            'name' => 'Sales Tax',
            'rate' => 8.5,
            'active' => 1
        ]);

        $fee = Fee::firstOrCreate([
            'name' => 'Processing Fee',
            'amount' => 15.00,
            'active' => 1
        ]);

        $discount = Discount::firstOrCreate([
            'name' => 'Early Payment Discount',
            'type' => 'percentage',
            'amount' => 5.0,
            'active' => 1
        ]);

        $paymentTerm = PaymentTerm::firstOrCreate([
            'name' => 'Net 30',
            'code' => 'NET30',
            'down_payment_percentage' => 0.00,
            'remaining_percentage' => 100.00,
            'term_months' => 1,
            'active' => 1
        ]);

        // Sample invoice data
        $invoices = [
            [
                'invoice_number' => 'INV-CUST-001',
                'issue_date' => Carbon::now()->subDays(15),
                'due_date' => Carbon::now()->addDays(15),
                'subtotal' => 500.00,
                'tax_amount' => 42.50,
                'fee_amount' => 15.00,
                'discount_amount' => 25.00,
                'total_amount' => 532.50,
                'total_paid_amount' => 0.00,
                'remaining_balance' => 532.50,
                'payment_status' => 'unpaid',
                'notes' => 'Jewelry repair services - Ring resizing and cleaning',
                'items' => [
                    [
                        'description' => 'Ring Resizing Service',
                        'quantity' => 1,
                        'unit_price' => 150.00,
                        'total_price' => 150.00
                    ],
                    [
                        'description' => 'Jewelry Cleaning Service',
                        'quantity' => 2,
                        'unit_price' => 25.00,
                        'total_price' => 50.00
                    ],
                    [
                        'description' => 'Prong Repair',
                        'quantity' => 1,
                        'unit_price' => 300.00,
                        'total_price' => 300.00
                    ]
                ]
            ],
            [
                'invoice_number' => 'INV-CUST-002',
                'issue_date' => Carbon::now()->subDays(30),
                'due_date' => Carbon::now()->subDays(5),
                'subtotal' => 1200.00,
                'tax_amount' => 102.00,
                'fee_amount' => 0.00,
                'discount_amount' => 0.00,
                'total_amount' => 1302.00,
                'total_paid_amount' => 651.00,
                'remaining_balance' => 651.00,
                'payment_status' => 'partially_paid',
                'notes' => 'Custom jewelry design and creation',
                'items' => [
                    [
                        'description' => 'Custom Ring Design',
                        'quantity' => 1,
                        'unit_price' => 800.00,
                        'total_price' => 800.00
                    ],
                    [
                        'description' => 'Gold Material (14k)',
                        'quantity' => 1,
                        'unit_price' => 400.00,
                        'total_price' => 400.00
                    ]
                ]
            ],
            [
                'invoice_number' => 'INV-CUST-003',
                'issue_date' => Carbon::now()->subDays(45),
                'due_date' => Carbon::now()->subDays(20),
                'subtotal' => 300.00,
                'tax_amount' => 25.50,
                'fee_amount' => 0.00,
                'discount_amount' => 0.00,
                'total_amount' => 325.50,
                'total_paid_amount' => 325.50,
                'remaining_balance' => 0.00,
                'payment_status' => 'fully_paid',
                'notes' => 'Watch repair and maintenance',
                'items' => [
                    [
                        'description' => 'Watch Battery Replacement',
                        'quantity' => 1,
                        'unit_price' => 50.00,
                        'total_price' => 50.00
                    ],
                    [
                        'description' => 'Watch Cleaning Service',
                        'quantity' => 1,
                        'unit_price' => 75.00,
                        'total_price' => 75.00
                    ],
                    [
                        'description' => 'Strap Replacement',
                        'quantity' => 1,
                        'unit_price' => 175.00,
                        'total_price' => 175.00
                    ]
                ]
            ],
            [
                'invoice_number' => 'INV-CUST-004',
                'issue_date' => Carbon::now()->subDays(60),
                'due_date' => Carbon::now()->subDays(35),
                'subtotal' => 750.00,
                'tax_amount' => 63.75,
                'fee_amount' => 0.00,
                'discount_amount' => 0.00,
                'total_amount' => 813.75,
                'total_paid_amount' => 0.00,
                'remaining_balance' => 813.75,
                'payment_status' => 'overdue',
                'notes' => 'Necklace repair and chain replacement',
                'items' => [
                    [
                        'description' => 'Chain Replacement (18k Gold)',
                        'quantity' => 1,
                        'unit_price' => 400.00,
                        'total_price' => 400.00
                    ],
                    [
                        'description' => 'Clasp Repair',
                        'quantity' => 1,
                        'unit_price' => 150.00,
                        'total_price' => 150.00
                    ],
                    [
                        'description' => 'Pendant Cleaning and Polishing',
                        'quantity' => 1,
                        'unit_price' => 200.00,
                        'total_price' => 200.00
                    ]
                ]
            ],
            [
                'invoice_number' => 'INV-CUST-005',
                'issue_date' => Carbon::now()->subDays(5),
                'due_date' => Carbon::now()->addDays(25),
                'subtotal' => 250.00,
                'tax_amount' => 21.25,
                'fee_amount' => 0.00,
                'discount_amount' => 0.00,
                'total_amount' => 271.25,
                'total_paid_amount' => 0.00,
                'remaining_balance' => 271.25,
                'payment_status' => 'unpaid',
                'notes' => 'Earring repair and maintenance',
                'items' => [
                    [
                        'description' => 'Earring Post Repair',
                        'quantity' => 2,
                        'unit_price' => 75.00,
                        'total_price' => 150.00
                    ],
                    [
                        'description' => 'Back Replacement',
                        'quantity' => 2,
                        'unit_price' => 50.00,
                        'total_price' => 100.00
                    ]
                ]
            ]
        ];

        foreach ($invoices as $invoiceData) {
            // Create the invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceData['invoice_number'],
                'customer_id' => $customer->id,
                'issue_date' => $invoiceData['issue_date'],
                'due_date' => $invoiceData['due_date'],
                'subtotal' => $invoiceData['subtotal'],
                'tax_amount' => $invoiceData['tax_amount'],
                'fee_amount' => $invoiceData['fee_amount'],
                'discount_amount' => $invoiceData['discount_amount'],
                'total_amount' => $invoiceData['total_amount'],
                'total_paid_amount' => $invoiceData['total_paid_amount'],
                'remaining_balance' => $invoiceData['remaining_balance'],
                'payment_status' => $invoiceData['payment_status'],
                'notes' => $invoiceData['notes'],
                'tax_id' => $tax->id,
                'fee_id' => $invoiceData['fee_amount'] > 0 ? $fee->id : null,
                'discount_id' => $invoiceData['discount_amount'] > 0 ? $discount->id : null,
                'payment_term_id' => $paymentTerm->id,
            ]);

            // Create invoice items
            foreach ($invoiceData['items'] as $itemData) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_name' => $itemData['description'],
                    'description' => $itemData['description'],
                    'price' => $itemData['total_price'],
                ]);
            }

            // Create payment records for paid invoices
            if ($invoiceData['payment_status'] === 'fully_paid' || $invoiceData['payment_status'] === 'partially_paid') {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $customer->id,
                    'amount_paid' => $invoiceData['total_paid_amount'],
                    'expected_amount' => $invoiceData['total_amount'],
                    'payment_date' => $invoiceData['issue_date']->addDays(rand(1, 10)),
                    'payment_type' => 'credit_card',
                    'reference_number' => 'PAY-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'status' => 'approved',
                ]);
            }

            $this->command->info("Created invoice: {$invoice->invoice_number} - {$invoice->payment_status}");
        }

        $this->command->info('Sample invoices created successfully!');
        $this->command->info('Total invoices: ' . count($invoices));
        $this->command->info('Unpaid: ' . collect($invoices)->where('payment_status', 'unpaid')->count());
        $this->command->info('Partially Paid: ' . collect($invoices)->where('payment_status', 'partially_paid')->count());
        $this->command->info('Fully Paid: ' . collect($invoices)->where('payment_status', 'fully_paid')->count());
        $this->command->info('Overdue: ' . collect($invoices)->where('payment_status', 'overdue')->count());
    }
}
