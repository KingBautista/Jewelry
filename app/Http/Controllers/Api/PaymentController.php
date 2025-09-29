<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Requests\StorePaymentSubmissionRequest;
use App\Services\PaymentService;
use App\Services\MessageService;
use App\Models\Payment;
use App\Models\PaymentSubmission;
use App\Models\Invoice;

class PaymentController extends BaseController
{
    public function __construct(PaymentService $paymentService, MessageService $messageService)
    {
        // Call the parent constructor to initialize services
        parent::__construct($paymentService, $messageService);
    }

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

    public function show($id)
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
            $data['submitted_at'] = now();

            $submission = PaymentSubmission::create($data);
            
            return response($submission, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    public function approveSubmission(Request $request, Int $id)
    {
        try {
            $submission = PaymentSubmission::findOrFail($id);
            
            if ($submission->status !== 'pending') {
                return response(['message' => 'Only pending submissions can be approved.'], 400);
            }
            
            $submission->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id()
            ]);
            
            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $submission->invoice_id,
                'customer_id' => $submission->customer_id,
                'payment_type' => 'partial', // Default type, can be customized
                'amount_paid' => $submission->amount_paid,
                'expected_amount' => $submission->expected_amount,
                'reference_number' => $submission->reference_number,
                'receipt_image' => $submission->receipt_images[0] ?? null, // Use first receipt
                'status' => 'approved',
                'payment_date' => now()->toDateString(),
                'notes' => 'Payment approved from customer submission'
            ]);
            
            // Update invoice payment status
            $invoice = Invoice::find($submission->invoice_id);
            $invoice->updatePaymentStatus();
            
            return response(['message' => 'Payment submission has been approved and payment created.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    public function rejectSubmission(Request $request, Int $id)
    {
        try {
            $submission = PaymentSubmission::findOrFail($id);
            
            if ($submission->status !== 'pending') {
                return response(['message' => 'Only pending submissions can be rejected.'], 400);
            }
            
            $request->validate([
                'rejection_reason' => 'required|string|max:500'
            ]);
            
            $submission->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id()
            ]);
            
            return response(['message' => 'Payment submission has been rejected.'], 200);
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
                ->get(['id', 'invoice_id', 'amount_paid', 'payment_date', 'receipt_images', 'status']);
            
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
}