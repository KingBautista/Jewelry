<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\PaymentSubmission;
use App\Models\InvoicePaymentSchedule;
use App\Models\InvoiceItemStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Models\PaymentMethod;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $invoices = Invoice::with(['customer', 'paymentSchedules'])->get();
        $paymentMethods = PaymentMethod::all();
        $paymentTypes = ['downpayment', 'monthly', 'full', 'partial', 'refund', 'reversal'];
        $statuses = ['pending', 'approved', 'confirmed', 'rejected'];
        $itemStatuses = ['pending', 'packed', 'for_delivery', 'delivered', 'returned'];

        // Create payments for existing invoices
        foreach ($invoices as $invoice) {
            // Create 1-3 payments per invoice
            $paymentCount = $faker->numberBetween(1, 3);
            
            for ($i = 0; $i < $paymentCount; $i++) {
                $paymentType = $faker->randomElement($paymentTypes);
                $status = $faker->randomElement($statuses);
                $amountPaid = $faker->randomFloat(2, 1000, $invoice->total_amount / 2);
                
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'payment_type' => $paymentType,
                    'payment_method_id' => $faker->randomElement($paymentMethods)?->id,
                    'amount_paid' => $amountPaid,
                    'expected_amount' => $faker->randomFloat(2, $amountPaid, $amountPaid * 1.2),
                    'reference_number' => 'PAY' . $faker->unique()->numerify('######'),
                    'receipt_images' => $faker->optional(0.7)->randomElements([
                        'receipts/sample_receipt_1.jpg',
                        'receipts/sample_receipt_2.jpg',
                        'receipts/sample_receipt_3.jpg'
                    ], $faker->numberBetween(1, 3)),
                    'status' => $status,
                    'rejection_reason' => $status === 'rejected' ? $faker->sentence() : null,
                    'payment_date' => $faker->dateTimeBetween($invoice->created_at, 'now'),
                    'confirmed_at' => $status === 'confirmed' ? $faker->dateTimeBetween($invoice->created_at, 'now') : null,
                    'confirmed_by' => $status === 'confirmed' ? User::inRandomOrder()->first()?->id : null,
                    'notes' => $faker->optional(0.4)->sentence(),
                ]);
            }
        }

        // Create payment submissions
        foreach ($invoices->take(10) as $invoice) {
            PaymentSubmission::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount_paid' => $faker->randomFloat(2, 1000, $invoice->total_amount / 2),
                'expected_amount' => $faker->randomFloat(2, 1000, $invoice->total_amount / 2),
                'reference_number' => 'SUB' . $faker->unique()->numerify('######'),
                'receipt_images' => $faker->optional(0.8)->randomElements([
                    'receipt1.jpg', 'receipt2.jpg', 'receipt3.jpg'
                ], $faker->numberBetween(1, 3)),
                'status' => $faker->randomElement(['pending', 'approved', 'rejected']),
                'rejection_reason' => $faker->optional(0.3)->sentence(),
                'submitted_at' => $faker->dateTimeBetween($invoice->created_at, 'now'),
                'reviewed_at' => $faker->optional(0.7)->dateTimeBetween($invoice->created_at, 'now'),
                'reviewed_by' => $faker->optional(0.7)->randomElement(User::pluck('id')->toArray()),
            ]);
        }

        // Create item status updates for invoices
        foreach ($invoices->take(15) as $invoice) {
            $statusCount = $faker->numberBetween(1, 3);
            $currentStatus = 'pending';
            
            for ($i = 0; $i < $statusCount; $i++) {
                $status = $faker->randomElement($itemStatuses);
                
                InvoiceItemStatus::create([
                    'invoice_id' => $invoice->id,
                    'status' => $status,
                    'status_date' => $faker->dateTimeBetween($invoice->created_at, 'now'),
                    'notes' => $faker->optional(0.5)->sentence(),
                    'updated_by' => User::inRandomOrder()->first()->id,
                ]);
                
                // Update invoice item status
                $invoice->update(['item_status' => $status]);
            }
        }

        // Update invoice payment statuses based on payments
        foreach ($invoices as $invoice) {
            $invoice->updatePaymentStatus();
        }
    }
}