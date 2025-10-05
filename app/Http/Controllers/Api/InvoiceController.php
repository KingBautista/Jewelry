<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Services\InvoiceService;
use App\Services\MessageService;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Invoice Management",
 *     description="Invoice management endpoints"
 * )
 */
class InvoiceController extends BaseController
{
    public function __construct(InvoiceService $invoiceService, MessageService $messageService)
    {
        // Call the parent constructor to initialize services
        parent::__construct($invoiceService, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/invoice-management/invoices",
     *     summary="Get all invoices",
     *     description="Retrieve a paginated list of all invoices",
     *     tags={"Invoice Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index()
    {
        return parent::index();
    }

    /**
     * @OA\Get(
     *     path="/api/invoice-management/invoices/{id}",
     *     summary="Get a specific invoice",
     *     description="Retrieve detailed information about a specific invoice",
     *     tags={"Invoice Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Invoice ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="invoice", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show($id)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/invoice-management/invoices/{id}",
     *     summary="Delete an invoice",
     *     description="Move an invoice to trash (soft delete)",
     *     tags={"Invoice Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Invoice ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function destroy($id)
    {
        return parent::destroy($id);
    }

    /**
     * @OA\Post(
     *     path="/api/invoice-management/invoices",
     *     summary="Create a new invoice",
     *     description="Create a new invoice with products and customer information",
     *     tags={"Invoice Management"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customer_id","products"},
     *             @OA\Property(property="customer_id", type="integer", example=1),
     *             @OA\Property(property="invoice_number", type="string", example="INV-2024-001"),
     *             @OA\Property(property="issue_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-02-15"),
     *             @OA\Property(property="products", type="array", @OA\Items(
     *                 @OA\Property(property="name", type="string", example="Gold Ring"),
     *                 @OA\Property(property="description", type="string", example="18k Gold Ring"),
     *                 @OA\Property(property="quantity", type="integer", example=1),
     *                 @OA\Property(property="unit_price", type="number", format="float", example=500.00),
     *                 @OA\Property(property="tax_id", type="integer", example=1),
     *                 @OA\Property(property="discount_id", type="integer", example=1)
     *             )),
     *             @OA\Property(property="notes", type="string", example="Special instructions")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invoice created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invoice created successfully"),
     *             @OA\Property(property="invoice", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/invoice-management/invoices/{id}",
     *     summary="Update an invoice",
     *     description="Update an existing invoice with products and customer information",
     *     tags={"Invoice Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Invoice ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customer_id","products"},
     *             @OA\Property(property="customer_id", type="integer", example=1),
     *             @OA\Property(property="invoice_number", type="string", example="INV-2024-001"),
     *             @OA\Property(property="issue_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-02-15"),
     *             @OA\Property(property="products", type="array", @OA\Items(
     *                 @OA\Property(property="name", type="string", example="Gold Ring"),
     *                 @OA\Property(property="description", type="string", example="18k Gold Ring"),
     *                 @OA\Property(property="quantity", type="integer", example=1),
     *                 @OA\Property(property="unit_price", type="number", format="float", example=500.00),
     *                 @OA\Property(property="tax_id", type="integer", example=1),
     *                 @OA\Property(property="discount_id", type="integer", example=1)
     *             )),
     *             @OA\Property(property="notes", type="string", example="Updated instructions")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="invoice", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
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

    /**
     * @OA\Patch(
     *     path="/api/invoice-management/invoices/{id}/cancel",
     *     summary="Cancel an invoice",
     *     description="Cancel an existing invoice (only if not paid)",
     *     tags={"Invoice Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Invoice ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invoice has been cancelled.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot cancel paid invoice"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/invoice-management/invoices/{id}/pdf",
     *     summary="Generate invoice PDF",
     *     description="Generate and download invoice as PDF",
     *     tags={"Invoice Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Invoice ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF generated successfully",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/invoice-management/invoices/{id}/send-email",
     *     summary="Send invoice via email",
     *     description="Send invoice to customer via email with PDF attachment",
     *     tags={"Invoice Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Invoice ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invoice has been sent via email.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found"
     *     )
     * )
     */
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