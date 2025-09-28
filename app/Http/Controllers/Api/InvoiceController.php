<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Services\InvoiceService;
use App\Services\MessageService;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends BaseController
{
    public function __construct(InvoiceService $invoiceService, MessageService $messageService)
    {
        // Call the parent constructor to initialize services
        parent::__construct($invoiceService, $messageService);
    }

    public function store(StoreInvoiceRequest $request)
    {
        try {
            // Check if this is an update operation
            $invoiceId = $request->input('invoice_id');
            
            if ($invoiceId) {
                // Handle update operation
                return $this->update($request, $invoiceId);
            }
            
            // Handle create operation
            $data = $request->validated();

            // Generate invoice number if not provided
            if (!isset($data['invoice_number'])) {
                $data['invoice_number'] = Invoice::generateInvoiceNumber();
            }

            // Set issue date if not provided
            if (!isset($data['issue_date'])) {
                $data['issue_date'] = now()->toDateString();
            }

            // Create the invoice first
            $invoice = $this->service->store($data);
            
            // Handle multiple products with file uploads
            if ($request->has('products')) {
                foreach ($request->input('products') as $index => $product) {
                    $productData = [
                        'invoice_id' => $invoice->id,
                        'product_name' => $product['product_name'],
                        'description' => $product['description'] ?? '',
                        'price' => $product['price'],
                        'product_images' => []
                    ];

                    // Handle file uploads for this product
                    if ($request->hasFile("products.{$index}.product_images")) {
                        $uploadedImages = [];
                        foreach ($request->file("products.{$index}.product_images") as $file) {
                            if ($file->isValid()) {
                                $path = $file->store('invoices/products', 'public');
                                $uploadedImages[] = asset('storage/' . $path);
                            }
                        }
                        $productData['product_images'] = $uploadedImages;
                    }

                    // Create invoice item
                    $invoice->items()->create($productData);
                }
            }

            // Calculate totals after creating the invoice
            $invoice->calculateTotals()->save();
            
            // Generate payment schedules if payment terms exist
            $invoice->generatePaymentSchedules();
            
            return response($invoice->load('items'), 201);
        } catch (\Exception $e) {
            \Log::error('Store Invoice Error: ' . $e->getMessage());
            return $this->messageService->responseError();
        }
    }

    public function update(StoreInvoiceRequest $request, Int $id)
    {
        try {
            $data = $request->validated();
            $invoice = Invoice::findOrFail($id);

            // Update invoice basic data
            $invoice = $this->service->update($data, $id);

            // Handle multiple products update
            if ($request->has('products')) {
                // Delete existing items
                $invoice->items()->delete();
                
                // Create new items
                foreach ($request->input('products') as $index => $product) {
                    $productData = [
                        'invoice_id' => $invoice->id,
                        'product_name' => $product['product_name'],
                        'description' => $product['description'] ?? '',
                        'price' => $product['price'],
                        'product_images' => []
                    ];

                    // Handle file uploads for this product
                    if ($request->hasFile("products.{$index}.product_images")) {
                        $uploadedImages = [];
                        foreach ($request->file("products.{$index}.product_images") as $file) {
                            if ($file->isValid()) {
                                $path = $file->store('invoices/products', 'public');
                                $uploadedImages[] = asset('storage/' . $path);
                            }
                        }
                        $productData['product_images'] = $uploadedImages;
                    }

                    // Create invoice item
                    $invoice->items()->create($productData);
                }
            }
            
            // Recalculate totals after updating
            $invoice->calculateTotals()->save();
            
            return response($invoice->load('items'), 200);
        } catch (\Exception $e) {
            \Log::error('Update Invoice Error: ' . $e->getMessage());
            return $this->messageService->responseError();
        }
    }

    public function cancel(Int $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            
            if ($invoice->status === 'paid') {
                return response(['message' => 'Cannot cancel a paid invoice.'], 400);
            }
            
            $invoice->update(['status' => 'cancelled']);
            
            return response(['message' => 'Invoice has been cancelled.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    public function generatePdf(Int $id)
    {
        try {
            $invoice = Invoice::with(['customer', 'paymentTerm', 'tax', 'fee', 'discount'])->findOrFail($id);
            
            // Generate PDF using DomPDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', compact('invoice'));
            $pdf->setPaper('A4', 'portrait');
            
            // Return PDF as download
            return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            return $this->messageService->responseError();
        }
    }

    public function sendEmail(Int $id)
    {
        try {
            $invoice = Invoice::with(['customer', 'paymentTerm', 'tax', 'fee', 'discount', 'items'])->findOrFail($id);
            
            // Generate PDF for email attachment
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', compact('invoice'));
            $pdf->setPaper('A4', 'portrait');
            $pdfContent = $pdf->output();
            
            // Get customer email or use test email
            $customerEmail = $invoice->customer ? $invoice->customer->user_email : 'bautistael23@gmail.com';
            $customerName = $invoice->customer_name;
            
            // Send email with PDF attachment
            \Mail::send('emails.invoice', [
                'invoice' => $invoice,
                'customerName' => $customerName
            ], function ($message) use ($invoice, $customerEmail, $customerName, $pdfContent) {
                $message->to($customerEmail, $customerName)
                        ->subject("Invoice {$invoice->invoice_number} - Jewelry Business")
                        ->attachData($pdfContent, "invoice-{$invoice->invoice_number}.pdf", [
                            'mime' => 'application/pdf',
                        ]);
            });
            
            // Update status to sent
            $invoice->update(['status' => 'sent']);
            
            \Log::info("Invoice {$invoice->invoice_number} sent to {$customerEmail}");
            
            return response(['message' => 'Invoice has been sent via email.'], 200);
        } catch (\Exception $e) {
            \Log::error('Email Sending Error: ' . $e->getMessage());
            return $this->messageService->responseError();
        }
    }

    public function getInvoiceStats()
    {
        try {
            $stats = $this->service->getInvoiceStats();
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch invoice statistics'], 500);
        }
    }

    public function exportInvoices(Request $request)
    {
        try {
            $format = $request->get('format', 'csv');
            return $this->service->exportInvoices($format);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to export invoices'], 500);
        }
    }

    public function getInvoicesForDropdown()
    {
        try {
            return response()->json($this->service->getInvoicesForDropdown());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch invoices for dropdown'], 500);
        }
    }

    public function searchInvoices()
    {
        try {
            $search = request('search');
            return response()->json($this->service->searchInvoices($search));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to search invoices'], 500);
        }
    }

    public function preview(Int $id)
    {
        try {
            $invoice = Invoice::with(['customer', 'paymentTerm', 'tax', 'fee', 'discount'])->findOrFail($id);
            return view('invoices.preview', compact('invoice'));
        } catch (\Exception $e) {
            \Log::error('Invoice Preview Error: ' . $e->getMessage());
            return response('Invoice not found', 404);
        }
    }
}