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

            $payment = $this->service->store($data);
            
            // Update invoice payment status
            $invoice = Invoice::find($data['invoice_id']);
            $invoice->updatePaymentStatus();
            
            return response($payment, 201);
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

    public function confirm(Int $id)
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
            
            // Update invoice payment status
            $invoice = Invoice::find($payment->invoice_id);
            $invoice->updatePaymentStatus();
            
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
}