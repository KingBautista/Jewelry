<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () { return redirect('/api/documentation'); });
Route::get('/check-shell-exec', function () {
    if (function_exists('shell_exec')) {
        try {   
            $output = shell_exec('echo Shell exec works');
            return 'shell_exec is enabled. Output: ' . $output;
        } catch (\Throwable $e) {
            return 'shell_exec exists but is restricted: ' . $e->getMessage();
        }
    } else {
        return 'shell_exec function does not exist or is disabled in php.ini';
    }
});

// Invoice preview route
Route::get('/invoice-preview/{id}', [App\Http\Controllers\Api\InvoiceController::class, 'preview'])->name('invoice.preview');

// Test route to show available invoices
Route::get('/test-invoices', function () {
    try {
        $invoices = \App\Models\Invoice::select('id', 'invoice_number', 'customer_name', 'total_amount', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        if ($invoices->isEmpty()) {
            return response('No invoices found. Please create an invoice first through the API or admin panel.', 404);
        }
        
        $html = '<h1>Available Invoices for Preview</h1>';
        $html .= '<ul>';
        foreach ($invoices as $invoice) {
            $html .= '<li>';
            $html .= '<strong>Invoice #' . $invoice->invoice_number . '</strong> - ';
            $html .= 'Customer: ' . $invoice->customer_name . ' - ';
            $html .= 'Amount: ₱' . number_format($invoice->total_amount, 2) . ' - ';
            $html .= '<a href="/invoice-preview/' . $invoice->id . '" target="_blank">Preview Invoice</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        
        return $html;
    } catch (\Exception $e) {
        return response('Database connection error: ' . $e->getMessage(), 500);
    }
});

Route::get('/mail-test', function () {
    try {
        \Mail::raw('Test email from Laravel via relay-hosting.secureserver.net', function ($m) {
            $m->to('bautistael23@gmail.com')->subject('Laravel SMTP Test');
        });
        return 'Mail sent! Check your inbox or spam folder.';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});


Route::get('/env-check', function () {
    return [
        'mailer' => config('mail.default'),
        'host' => config('mail.mailers.smtp.host'),
        'port' => config('mail.mailers.smtp.port'),
        'from' => config('mail.from'),
    ];
});
