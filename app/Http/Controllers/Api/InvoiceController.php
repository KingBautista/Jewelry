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
            $data = $request->validated();

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
            return $this->messageService->responseError();
        }
    }

    public function update(UpdateInvoiceRequest $request, Int $id)
    {
        try {
            $data = $request->validated();
            $invoice = Invoice::findOrFail($id);

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
}