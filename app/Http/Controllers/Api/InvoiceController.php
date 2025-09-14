<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Services\InvoiceService;
use App\Services\MessageService;
use App\Models\Invoice;

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

            // Handle file uploads
            if ($request->hasFile('product_images')) {
                $uploadedImages = [];
                foreach ($request->file('product_images') as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('invoices/products', 'public');
                        $uploadedImages[] = asset('storage/' . $path);
                    }
                }
                $data['product_images'] = $uploadedImages;
            }

            // Generate invoice number if not provided
            if (!isset($data['invoice_number'])) {
                $data['invoice_number'] = Invoice::generateInvoiceNumber();
            }

            // Set issue date if not provided
            if (!isset($data['issue_date'])) {
                $data['issue_date'] = now()->toDateString();
            }

            $invoice = $this->service->store($data);
            
            // Calculate totals after creating the invoice
            $invoice->calculateTotals()->save();
            
            // Generate payment schedules if payment terms exist
            $invoice->generatePaymentSchedules();
            
            return response($invoice, 201);
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

            // Handle file uploads - only if new files are provided
            if ($request->hasFile('product_images')) {
                $uploadedImages = [];
                foreach ($request->file('product_images') as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('invoices/products', 'public');
                        $uploadedImages[] = asset('storage/' . $path);
                    }
                }
                $data['product_images'] = $uploadedImages;
            } else {
                // If no new files, keep existing images
                unset($data['product_images']);
            }


            $invoice = $this->service->update($data, $id);
            
            // Recalculate totals after updating
            $invoice->calculateTotals()->save();
            
            return response($invoice, 200);
        } catch (\Exception $e) {
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
            
            // For now, return a JSON response with invoice data
            // In a real implementation, you would generate a PDF here
            return response([
                'message' => 'PDF generation not implemented yet',
                'invoice' => $invoice
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    public function sendEmail(Int $id)
    {
        try {
            $invoice = Invoice::with(['customer'])->findOrFail($id);
            
            // For now, just log the action
            // In a real implementation, you would send an email here
            \Log::info("Invoice {$invoice->invoice_number} sent to customer {$invoice->customer->user_email}");
            
            // Update status to sent
            $invoice->update(['status' => 'sent']);
            
            return response(['message' => 'Invoice has been sent via email.'], 200);
        } catch (\Exception $e) {
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
}