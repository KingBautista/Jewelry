<?php
/**
 * Test Email Functionality
 * 
 * This script tests the email functionality for invoice sending.
 * Run this from the Laravel project root: php test-email.php
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Email Functionality...\n\n";

// Test 1: Check if DomPDF is working
echo "1. Testing PDF Generation...\n";
try {
    $testData = [
        'invoice' => (object) [
            'invoice_number' => 'TEST-001',
            'product_name' => 'Test Diamond Ring',
            'description' => 'Test product for email functionality',
            'price' => 1000.00,
            'formatted_price' => '₱1,000.00',
            'customer_name' => 'Test Customer',
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'draft',
            'status_text' => 'Draft',
            'payment_status' => 'unpaid',
            'payment_status_text' => 'Unpaid',
            'item_status' => 'pending',
            'item_status_text' => 'Pending',
            'subtotal' => 1000.00,
            'formatted_subtotal' => '₱1,000.00',
            'tax_amount' => 0,
            'formatted_tax_amount' => '₱0.00',
            'fee_amount' => 0,
            'formatted_fee_amount' => '₱0.00',
            'discount_amount' => 0,
            'formatted_discount_amount' => '₱0.00',
            'total_amount' => 1000.00,
            'formatted_total_amount' => '₱1,000.00',
            'total_paid_amount' => 0,
            'formatted_total_paid_amount' => '₱0.00',
            'remaining_balance' => 1000.00,
            'formatted_remaining_balance' => '₱1,000.00',
            'next_payment_due_date' => null,
            'payment_plan_created' => false,
            'notes' => 'Test invoice for email functionality',
            'active' => true,
            'customer' => (object) [
                'user_email' => 'bautistael23@gmail.com',
                'phone' => '+1234567890'
            ],
            'paymentTerm' => null,
            'tax' => null,
            'fee' => null,
            'discount' => null,
            'product_images' => []
        ]
    ];
    
    $pdf = Pdf::loadView('invoices.pdf', $testData);
    $pdf->setPaper('A4', 'portrait');
    $pdfContent = $pdf->output();
    
    echo "✅ PDF Generation: SUCCESS\n";
    echo "   PDF Size: " . strlen($pdfContent) . " bytes\n\n";
    
} catch (Exception $e) {
    echo "❌ PDF Generation: FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Check email configuration
echo "2. Testing Email Configuration...\n";
try {
    $mailer = config('mail.default');
    $host = config('mail.mailers.smtp.host');
    $port = config('mail.mailers.smtp.port');
    $username = config('mail.mailers.smtp.username');
    $fromAddress = config('mail.from.address');
    $fromName = config('mail.from.name');
    
    echo "   Mailer: $mailer\n";
    echo "   Host: $host\n";
    echo "   Port: $port\n";
    echo "   Username: $username\n";
    echo "   From: $fromName <$fromAddress>\n";
    
    if ($mailer === 'log') {
        echo "✅ Email Configuration: Using LOG driver (emails will be logged)\n\n";
    } elseif ($username && $host) {
        echo "✅ Email Configuration: SMTP configured\n\n";
    } else {
        echo "⚠️  Email Configuration: Using default settings\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Email Configuration: FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Test email sending (if configured)
echo "3. Testing Email Sending...\n";
try {
    if (config('mail.default') === 'log') {
        echo "   Using LOG driver - emails will be written to storage/logs/laravel.log\n";
    }
    
    // Create a simple test email
    Mail::send('emails.invoice', [
        'invoice' => $testData['invoice'],
        'customerName' => 'Test Customer'
    ], function ($message) use ($testData, $pdfContent) {
        $message->to('bautistael23@gmail.com', 'Test Customer')
                ->subject('Test Invoice - Jewelry Business')
                ->attachData($pdfContent, 'test-invoice.pdf', [
                    'mime' => 'application/pdf',
                ]);
    });
    
    echo "✅ Email Sending: SUCCESS\n";
    echo "   Email sent to: bautistael23@gmail.com\n";
    echo "   Subject: Test Invoice - Jewelry Business\n";
    echo "   Attachment: test-invoice.pdf\n\n";
    
} catch (Exception $e) {
    echo "❌ Email Sending: FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
}

echo "Test completed!\n";
echo "Check your email (bautistael23@gmail.com) for the test invoice.\n";
echo "If using LOG driver, check storage/logs/laravel.log for the email content.\n";
