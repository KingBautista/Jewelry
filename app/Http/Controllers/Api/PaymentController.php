<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Requests\StorePaymentSubmissionRequest;
use App\Services\PaymentService;
use App\Services\MessageService;
use App\Models\Payment;
// Removed PaymentSubmission import as we're using unified Payment table
use App\Models\Invoice;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Payment Management",
 *     description="Payment management endpoints"
 * )
 */
class PaymentController extends BaseController
{
    public function __construct(PaymentService $paymentService, MessageService $messageService)
    {
        // Call the parent constructor to initialize services
        parent::__construct($paymentService, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/payment-management/payments",
     *     summary="Get all payments",
     *     description="Retrieve a paginated list of all payments",
     *     tags={"Payment Management"},
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
     *         description="Payments retrieved successfully",
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
     * @OA\Delete(
     *     path="/api/payment-management/payments/{id}",
     *     summary="Delete a payment",
     *     description="Move a payment to trash (soft delete)",
     *     tags={"Payment Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
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
     *     path="/api/payment-management/payments",
     *     summary="Create a new payment",
     *     description="Create a new payment record with receipt images",
     *     tags={"Payment Management"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"invoice_id","amount","payment_method_id"},
     *                 @OA\Property(property="invoice_id", type="integer", example=1),
     *                 @OA\Property(property="amount", type="number", format="float", example=500.00),
     *                 @OA\Property(property="payment_method_id", type="integer", example=1),
     *                 @OA\Property(property="payment_date", type="string", format="date", example="2024-01-15"),
     *                 @OA\Property(property="reference_number", type="string", example="REF123456"),
     *                 @OA\Property(property="notes", type="string", example="Payment notes"),
     *                 @OA\Property(property="receipt_images", type="array", @OA\Items(type="string", format="binary"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment created successfully"),
     *             @OA\Property(property="payment", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(StorePaymentRequest $request)
    {
        try {
            $data = $request->validated();

            // Handle receipt image uploads
            if ($request->hasFile('receipt_images')) {
                $receiptImages = [];
                $files = $request->file('receipt_images');
                
                // Handle both single file and multiple files
                if (is_array($files)) {
                    foreach ($files as $file) {
                        if ($file && $file->isValid()) {
                            $path = $file->store('receipts', 'public');
                            $receiptImages[] = $path;
                        }
                    }
                } else {
                    // Single file
                    if ($files && $files->isValid()) {
                        $path = $files->store('receipts', 'public');
                        $receiptImages[] = $path;
                    }
                }
                
                if (!empty($receiptImages)) {
                    $data['receipt_images'] = $receiptImages; // Store all images as JSON array
                }
            }

            // Handle update vs create
            if (isset($data['payment_id'])) {
                $payment = $this->service->update($data, $data['payment_id']);
            } else {
                // Set source as admin_created for admin-created payments
                $data['source'] = 'admin_created';
                $payment = $this->service->store($data);    
            }
            
            // Handle selected payment schedules
            $selectedSchedules = $request->input('selected_schedules', []);
            if (!empty($selectedSchedules)) {
                // Store the selected schedules for this payment
                $payment->update(['selected_schedules' => $selectedSchedules]);
            }
            
            // Update invoice payment status (schedules will be marked as paid when payment is confirmed)
            $invoice = Invoice::find($data['invoice_id']);
            $invoice->updatePaymentStatus();
            
            return response($payment, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/payment-management/payments/{id}",
     *     summary="Get a specific payment",
     *     description="Retrieve detailed information about a specific payment",
     *     tags={"Payment Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show($id, $withOutResource = false)
    {
        try {
            $payment = Payment::with(['invoice.paymentSchedules', 'customer', 'paymentMethod', 'confirmedBy'])
                ->findOrFail($id);
            
            // Load all payment schedules for this invoice
            $allSchedules = $this->service->getAllPaymentSchedules($payment->invoice_id);
            $payment->payment_schedules = $allSchedules;
            
            // Load paid schedules for this payment (for edit mode)
            $paidSchedules = $this->service->getPaidSchedules($payment->invoice_id);
            $payment->paid_schedules = $paidSchedules;
            
            return response($payment);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Put(
     *     path="/api/payment-management/payments/{id}",
     *     summary="Update a payment",
     *     description="Update an existing payment record",
     *     tags={"Payment Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"invoice_id","amount","payment_method_id"},
     *             @OA\Property(property="invoice_id", type="integer", example=1),
     *             @OA\Property(property="amount", type="number", format="float", example=500.00),
     *             @OA\Property(property="payment_method_id", type="integer", example=1),
     *             @OA\Property(property="payment_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="reference_number", type="string", example="REF123456"),
     *             @OA\Property(property="notes", type="string", example="Updated payment notes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(UpdatePaymentRequest $request, Int $id)
    {
        try {
            $data = $request->validated();
            $payment = Payment::findOrFail($id);

            $payment = $this->service->update($data, $id);
            
            // Update invoice payment status
            $invoice = Invoice::find($payment->invoice_id);
            $invoice->updatePaymentStatus();
            
            return response($payment, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/payment-management/payments/{id}/confirm",
     *     summary="Confirm a payment",
     *     description="Confirm an approved payment and mark payment schedules as paid",
     *     tags={"Payment Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="selected_schedules", type="array", @OA\Items(type="integer"), example={1,2,3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment confirmed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment has been confirmed.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Only approved payments can be confirmed"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     )
     * )
     */
    public function confirm(Request $request, Int $id)
    {
        try {
            $payment = Payment::findOrFail($id);
            
            if ($payment->status !== 'approved') {
                return response(['message' => 'Only approved payments can be confirmed.'], 400);
            }
            
            $payment->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id()
            ]);
            
            // Mark selected payment schedules as paid (now that payment is confirmed)
            $selectedSchedules = $request->input('selected_schedules', []);
            if (empty($selectedSchedules) && $payment->selected_schedules) {
                $selectedSchedules = $payment->selected_schedules;
            }
            
            // If no selected schedules from request or payment, get all pending schedules for the invoice
            if (empty($selectedSchedules)) {
                $invoice = Invoice::find($payment->invoice_id);
                $pendingSchedules = $invoice->paymentSchedules()
                    ->where('status', 'pending')
                    ->orderBy('payment_order')
                    ->get();
                
                // Calculate how many schedules can be paid with the payment amount
                $remainingAmount = $payment->amount_paid;
                foreach ($pendingSchedules as $schedule) {
                    if ($remainingAmount <= 0) break;
                    
                    $scheduleAmount = min($remainingAmount, $schedule->expected_amount);
                    if ($scheduleAmount > 0) {
                        $selectedSchedules[] = $schedule->id;
                        $remainingAmount -= $scheduleAmount;
                    }
                }
            }
            
            if (!empty($selectedSchedules)) {
                $this->service->markSchedulesAsPaid($selectedSchedules, $payment->amount_paid);
            }
            
            // Get the invoice and check if all payment schedules are paid
            $invoice = Invoice::find($payment->invoice_id);
            
            // Check if all payment schedules for this invoice are paid
            $allSchedulesPaid = $invoice->paymentSchedules()
                ->where('status', '!=', 'paid')
                ->count() === 0;
            
            // If all schedules are paid, mark invoice as fully paid
            if ($allSchedulesPaid) {
                $invoice->update([
                    'status' => 'paid',
                    'payment_status' => 'fully_paid',
                    'total_paid_amount' => $invoice->total_amount,
                    'remaining_balance' => 0
                ]);
            } else {
                // Otherwise, update payment status normally
                $invoice->updatePaymentStatus();
            }
            
            return response(['message' => 'Payment has been confirmed.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/payment-management/payments/{id}/approve",
     *     summary="Approve a payment",
     *     description="Approve a pending payment submission",
     *     tags={"Payment Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment approved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment has been approved.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Only pending payments can be approved"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     )
     * )
     */
    public function approve(Int $id)
    {
        try {
            $payment = Payment::findOrFail($id);
            
            if ($payment->status !== 'pending') {
                return response(['message' => 'Only pending payments can be approved.'], 400);
            }
            
            $payment->update(['status' => 'approved']);
            
            return response(['message' => 'Payment has been approved.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/payment-management/payments/{id}/reject",
     *     summary="Reject a payment",
     *     description="Reject a pending payment submission with reason",
     *     tags={"Payment Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rejection_reason"},
     *             @OA\Property(property="rejection_reason", type="string", example="Invalid receipt or insufficient documentation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment rejected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment has been rejected.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Only pending payments can be rejected"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     )
     * )
     */
    public function reject(Request $request, Int $id)
    {
        try {
            $payment = Payment::findOrFail($id);
            
            if ($payment->status !== 'pending') {
                return response(['message' => 'Only pending payments can be rejected.'], 400);
            }
            
            $request->validate([
                'rejection_reason' => 'required|string|max:500'
            ]);
            
            $payment->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason
            ]);
            
            return response(['message' => 'Payment has been rejected.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    public function submitPayment(StorePaymentSubmissionRequest $request)
    {
        try {
            $data = $request->validated();
            $data['source'] = 'customer_submission';
            $data['payment_type'] = 'partial'; // Default for customer submissions
            $data['payment_date'] = now()->toDateString();
            $data['status'] = 'pending';

            $payment = Payment::create($data);
            
            return response($payment, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    public function approveSubmission(Request $request, Int $id)
    {
        try {
            $payment = Payment::findOrFail($id);
            
            if ($payment->status !== 'pending') {
                return response(['message' => 'Only pending payments can be approved.'], 400);
            }
            
            $payment->update([
                'status' => 'approved',
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id()
            ]);
            
            // Update invoice payment status
            $invoice = Invoice::find($payment->invoice_id);
            $invoice->updatePaymentStatus();
            
            return response(['message' => 'Payment has been approved.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    public function rejectSubmission(Request $request, Int $id)
    {
        try {
            $payment = Payment::findOrFail($id);
            
            if ($payment->status !== 'pending') {
                return response(['message' => 'Only pending payments can be rejected.'], 400);
            }
            
            $request->validate([
                'rejection_reason' => 'required|string|max:500'
            ]);
            
            $payment->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id()
            ]);
            
            return response(['message' => 'Payment has been rejected.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    public function getPaymentStats()
    {
        try {
            $stats = $this->service->getPaymentStats();
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch payment statistics'], 500);
        }
    }

    public function getPaymentSubmissions()
    {
        try {
            $submissions = $this->service->getPaymentSubmissions();
            return response()->json($submissions);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch payment submissions'], 500);
        }
    }

    public function getPaymentSchedules(Int $invoiceId)
    {
        try {
            $schedules = $this->service->getPaymentSchedules($invoiceId);
            return response()->json($schedules);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch payment schedules'], 500);
        }
    }

    public function updateItemStatus(Request $request, Int $invoiceId)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,packed,for_delivery,delivered,returned',
                'notes' => 'nullable|string|max:500'
            ]);
            
            $invoice = Invoice::findOrFail($invoiceId);
            
            // Create status record
            $invoice->itemStatuses()->create([
                'status' => $request->status,
                'status_date' => now()->toDateString(),
                'notes' => $request->notes,
                'updated_by' => auth()->id()
            ]);
            
            // Update invoice item status
            $invoice->update(['item_status' => $request->status]);
            
            return response(['message' => 'Item status has been updated.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    public function exportPayments(Request $request)
    {
        try {
            $format = $request->get('format', 'csv');
            return $this->service->exportPayments($format);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to export payments'], 500);
        }
    }

    public function sendUpdateInvoice(Int $id)
    {
        try {
            $payment = Payment::with(['invoice.customer', 'invoice.items', 'invoice.paymentSchedules'])
                ->findOrFail($id);
            
            if (!$payment->invoice) {
                return response(['message' => 'No invoice found for this payment.'], 400);
            }
            
            // Get payment history for this invoice
            $paymentHistory = Payment::where('invoice_id', $payment->invoice_id)
                ->where('status', 'confirmed')
                ->orderBy('payment_date', 'asc')
                ->get(['id', 'invoice_id', 'amount_paid', 'payment_date', 'receipt_images', 'status', 'reference_number', 'payment_type']);
            
            // Get paid payment schedules
            $paidSchedules = $payment->invoice->paymentSchedules()
                ->where('status', 'paid')
                ->orderBy('payment_order', 'asc')
                ->get();
            
            // Calculate totals
            $totalPaid = $paymentHistory->sum('amount_paid');
            $remainingBalance = $payment->invoice->total_amount - $totalPaid;
            
            // Send email with updated invoice
            $this->service->sendUpdateInvoiceEmail($payment->invoice, $paymentHistory, $paidSchedules, $totalPaid, $remainingBalance);
            
            return response(['message' => 'Updated invoice has been sent to the customer.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Download updated invoice PDF with payment history and paid transactions
     */
    public function downloadUpdatedInvoice(Int $id)
    {
        try {
            $payment = Payment::with(['invoice.customer', 'invoice.items', 'invoice.paymentSchedules'])
                ->findOrFail($id);
            
            if (!$payment->invoice) {
                return response(['message' => 'No invoice found for this payment.'], 400);
            }
            
            // Get payment history for this invoice
            $paymentHistory = Payment::where('invoice_id', $payment->invoice_id)
                ->where('status', 'confirmed')
                ->orderBy('payment_date', 'asc')
                ->get(['id', 'invoice_id', 'amount_paid', 'payment_date', 'receipt_images', 'status', 'reference_number', 'payment_type']);
            
            // Get paid payment schedules
            $paidSchedules = $payment->invoice->paymentSchedules()
                ->where('status', 'paid')
                ->orderBy('payment_order', 'asc')
                ->get();
            
            // Calculate totals
            $totalPaid = $paymentHistory->sum('amount_paid');
            $remainingBalance = $payment->invoice->total_amount - $totalPaid;
            
            // Get all receipt images from payment history and convert to base64
            $receiptImages = [];
            
            foreach ($paymentHistory as $paymentRecord) {
                if ($paymentRecord->receipt_images && is_array($paymentRecord->receipt_images)) {
                    foreach ($paymentRecord->receipt_images as $imagePath) {
                        $fullPath = storage_path('app/public/' . $imagePath);
                        
                        if (file_exists($fullPath)) {
                            $imageData = file_get_contents($fullPath);
                            $mimeType = mime_content_type($fullPath);
                            $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                            $receiptImages[] = $base64;
                        }
                    }
                }
            }
            
            // Generate PDF with updated information
            $pdf = \PDF::loadView('invoices.pdf-updated', [
                'invoice' => $payment->invoice,
                'paymentHistory' => $paymentHistory,
                'paidSchedules' => $paidSchedules,
                'totalPaid' => $totalPaid,
                'remainingBalance' => $remainingBalance,
                'receiptImages' => $receiptImages
            ]);
            $pdf->setPaper('A4', 'portrait');
            
            // Return PDF as download
            return $pdf->download("invoice-updated-{$payment->invoice->invoice_number}.pdf");
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate PDF'], 500);
        }
    }
}