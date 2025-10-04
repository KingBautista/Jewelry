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
        // Get the customer portal user
        $customer = User::where('user_email', 'customer-portal@invoice-system.com')->first();
        
        if (!$customer) {
            $this->command->error('Customer portal user not found. Please run UserSeeder first.');
            return;
        }

        $this->command->info('Creating sample invoices for customer portal user: ' . $customer->user_email);

        // Create sample taxes, fees, discounts, and payment terms if they don't exist
        $tax = Tax::firstOrCreate([
            'name' => 'VAT (Value Added Tax)',
            'rate' => 12.0,
            'active' => 1
        ]);

        $fee = Fee::firstOrCreate([
            'name' => 'Metro Manila Delivery',
            'amount' => 150.00,
            'active' => 1
        ]);

        $discount = Discount::firstOrCreate([
            'name' => 'Bagong Customer (New Customer)',
            'type' => 'fixed',
            'amount' => 2000.0,
            'active' => 1
        ]);

        $paymentTerm = PaymentTerm::firstOrCreate([
            'name' => 'Hulugan Plan A (Installment Plan A)',
            'code' => 'HULUGAN_A',
            'down_payment_percentage' => 30.00,
            'remaining_percentage' => 70.00,
            'term_months' => 6,
            'active' => 1
        ]);

        // Sample invoice data with Philippines jewelry services
        $invoices = [
            [
                'invoice_number' => 'INV-CUST-001',
                'issue_date' => Carbon::now()->subDays(15),
                'due_date' => Carbon::now()->addDays(15),
                'subtotal' => 5000.00,
                'tax_amount' => 600.00,
                'fee_amount' => 150.00,
                'discount_amount' => 2000.00,
                'total_amount' => 3750.00,
                'total_paid_amount' => 0.00,
                'remaining_balance' => 3750.00,
                'payment_status' => 'unpaid',
                'notes' => 'Serbisyo sa alahas - Pag-ayos ng singsing at paglilinis (Jewelry repair services - Ring resizing and cleaning)',
                'items' => [
                    [
                        'description' => 'Pag-ayos ng Singsing (Ring Resizing)',
                        'quantity' => 1,
                        'unit_price' => 1500.00,
                        'total_price' => 1500.00
                    ],
                    [
                        'description' => 'Paglilinis ng Alahas (Jewelry Cleaning)',
                        'quantity' => 2,
                        'unit_price' => 250.00,
                        'total_price' => 500.00
                    ],
                    [
                        'description' => 'Pagkumpuni ng Prong (Prong Repair)',
                        'quantity' => 1,
                        'unit_price' => 3000.00,
                        'total_price' => 3000.00
                    ]
                ]
            ],
            [
                'invoice_number' => 'INV-CUST-002',
                'issue_date' => Carbon::now()->subDays(30),
                'due_date' => Carbon::now()->subDays(5),
                'subtotal' => 12000.00,
                'tax_amount' => 1440.00,
                'fee_amount' => 0.00,
                'discount_amount' => 0.00,
                'total_amount' => 13440.00,
                'total_paid_amount' => 6720.00,
                'remaining_balance' => 6720.00,
                'payment_status' => 'partially_paid',
                'notes' => 'Custom na disenyo ng alahas at paggawa (Custom jewelry design and creation)',
                'items' => [
                    [
                        'description' => 'Custom na Disenyo ng Singsing (Custom Ring Design)',
                        'quantity' => 1,
                        'unit_price' => 8000.00,
                        'total_price' => 8000.00
                    ],
                    [
                        'description' => 'Materyal na Ginto (14k Gold Material)',
                        'quantity' => 1,
                        'unit_price' => 4000.00,
                        'total_price' => 4000.00
                    ]
                ]
            ],
            [
                'invoice_number' => 'INV-CUST-003',
                'issue_date' => Carbon::now()->subDays(45),
                'due_date' => Carbon::now()->subDays(20),
                'subtotal' => 3000.00,
                'tax_amount' => 360.00,
                'fee_amount' => 0.00,
                'discount_amount' => 0.00,
                'total_amount' => 3360.00,
                'total_paid_amount' => 3360.00,
                'remaining_balance' => 0.00,
                'payment_status' => 'fully_paid',
                'notes' => 'Pag-ayos at pagmamaintain ng relo (Watch repair and maintenance)',
                'items' => [
                    [
                        'description' => 'Pagpalit ng Baterya ng Relo (Watch Battery Replacement)',
                        'quantity' => 1,
                        'unit_price' => 500.00,
                        'total_price' => 500.00
                    ],
                    [
                        'description' => 'Paglilinis ng Relo (Watch Cleaning Service)',
                        'quantity' => 1,
                        'unit_price' => 750.00,
                        'total_price' => 750.00
                    ],
                    [
                        'description' => 'Pagpalit ng Strap (Strap Replacement)',
                        'quantity' => 1,
                        'unit_price' => 1750.00,
                        'total_price' => 1750.00
                    ]
                ]
            ],
            [
                'invoice_number' => 'INV-CUST-004',
                'issue_date' => Carbon::now()->subDays(60),
                'due_date' => Carbon::now()->subDays(35),
                'subtotal' => 7500.00,
                'tax_amount' => 900.00,
                'fee_amount' => 0.00,
                'discount_amount' => 0.00,
                'total_amount' => 8400.00,
                'total_paid_amount' => 0.00,
                'remaining_balance' => 8400.00,
                'payment_status' => 'overdue',
                'notes' => 'Pag-ayos ng kuwintas at pagpalit ng kadena (Necklace repair and chain replacement)',
                'items' => [
                    [
                        'description' => 'Pagpalit ng Kadena (18k Gold Chain Replacement)',
                        'quantity' => 1,
                        'unit_price' => 4000.00,
                        'total_price' => 4000.00
                    ],
                    [
                        'description' => 'Pagkumpuni ng Clasp (Clasp Repair)',
                        'quantity' => 1,
                        'unit_price' => 1500.00,
                        'total_price' => 1500.00
                    ],
                    [
                        'description' => 'Paglilinis at Pagpupulido ng Pendant (Pendant Cleaning and Polishing)',
                        'quantity' => 1,
                        'unit_price' => 2000.00,
                        'total_price' => 2000.00
                    ]
                ]
            ],
            [
                'invoice_number' => 'INV-CUST-005',
                'issue_date' => Carbon::now()->subDays(5),
                'due_date' => Carbon::now()->addDays(25),
                'subtotal' => 2500.00,
                'tax_amount' => 300.00,
                'fee_amount' => 0.00,
                'discount_amount' => 0.00,
                'total_amount' => 2800.00,
                'total_paid_amount' => 0.00,
                'remaining_balance' => 2800.00,
                'payment_status' => 'unpaid',
                'notes' => 'Pag-ayos at pagmamaintain ng hikaw (Earring repair and maintenance)',
                'items' => [
                    [
                        'description' => 'Pagkumpuni ng Post ng Hikaw (Earring Post Repair)',
                        'quantity' => 2,
                        'unit_price' => 750.00,
                        'total_price' => 1500.00
                    ],
                    [
                        'description' => 'Pagpalit ng Back (Back Replacement)',
                        'quantity' => 2,
                        'unit_price' => 500.00,
                        'total_price' => 1000.00
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
                    'payment_type' => 'hulugan',
                    'reference_number' => 'BAYAD-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'status' => 'approved',
                    'source' => 'admin_created',
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
