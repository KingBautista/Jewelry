<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentSubmission;
use App\Models\InvoicePaymentSchedule;
use App\Http\Resources\PaymentResource;

class PaymentService extends BaseService
{
    public function __construct()
    {
        // Pass the PaymentResource class to the parent constructor
        parent::__construct(new PaymentResource(new Payment), new Payment());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        $allPayments = $this->getTotalCount();
        $trashedPayments = $this->getTrashedCount();

        return PaymentResource::collection(Payment::query()
            ->with(['invoice', 'customer', 'paymentMethod', 'confirmedBy'])
            ->when($trash, function ($query) {
                return $query->onlyTrashed();
            })
            ->when(request('search'), function ($query) {
                return $query->search(request('search'));
            })
            ->when(request('status'), function ($query) {
                return $query->byStatus(request('status'));
            })
            ->when(request('payment_type'), function ($query) {
                return $query->byPaymentType(request('payment_type'));
            })
            ->when(request('customer_id'), function ($query) {
                return $query->byCustomer(request('customer_id'));
            })
            ->when(request('invoice_id'), function ($query) {
                return $query->byInvoice(request('invoice_id'));
            })
            ->when(request('date_from') && request('date_to'), function ($query) {
                return $query->byDateRange(request('date_from'), request('date_to'));
            })
            ->when(request('order'), function ($query) {
                return $query->orderBy(request('order'), request('sort'));
            })
            ->when(!request('order'), function ($query) {
                return $query->orderBy('updated_at', 'desc');
            })
            ->paginate($perPage)->withQueryString()
        )->additional(['meta' => ['all' => $allPayments, 'trashed' => $trashedPayments]]);
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats()
    {
        return [
            'total_payments' => Payment::count(),
            'pending_payments' => Payment::pending()->count(),
            'approved_payments' => Payment::approved()->count(),
            'confirmed_payments' => Payment::confirmed()->count(),
            'rejected_payments' => Payment::rejected()->count(),
            'total_amount_paid' => Payment::confirmed()->sum('amount_paid'),
            'pending_amount' => Payment::pending()->sum('amount_paid'),
            'approved_amount' => Payment::approved()->sum('amount_paid'),
            'payments_this_month' => Payment::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'payments_by_type' => Payment::selectRaw('payment_type, COUNT(*) as count')
                ->groupBy('payment_type')
                ->get(),
            'payments_by_status' => Payment::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'payments_by_customer' => Payment::with('customer')
                ->join('users', 'payments.customer_id', '=', 'users.id')
                ->join('user_meta', function($join) {
                    $join->on('users.id', '=', 'user_meta.user_id')
                         ->where('user_meta.meta_key', '=', 'first_name');
                })
                ->selectRaw('users.id, user_meta.meta_value as first_name, COUNT(*) as count, SUM(payments.amount_paid) as total_amount')
                ->groupBy('users.id', 'user_meta.meta_value')
                ->orderBy('total_amount', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Get payment submissions
     */
    public function getPaymentSubmissions()
    {
        return PaymentSubmission::with(['invoice', 'customer', 'reviewedBy'])
            ->when(request('status'), function ($query) {
                return $query->byStatus(request('status'));
            })
            ->when(request('search'), function ($query) {
                return $query->search(request('search'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(request('per_page', 10));
    }

    /**
     * Get payment schedules for an invoice
     */
    public function getPaymentSchedules($invoiceId)
    {
        return InvoicePaymentSchedule::where('invoice_id', $invoiceId)
            ->orderBy('payment_order')
            ->get();
    }

    /**
     * Get payments for dropdown
     */
    public function getPaymentsForDropdown()
    {
        return Payment::confirmed()
            ->select('id', 'reference_number', 'amount_paid', 'payment_date')
            ->with(['invoice:id,invoice_number,product_name'])
            ->get()
            ->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'reference_number' => $payment->reference_number,
                    'amount_paid' => $payment->formatted_amount_paid,
                    'payment_date' => $payment->payment_date->format('Y-m-d'),
                    'invoice_number' => $payment->invoice?->invoice_number ?? 'N/A',
                    'product_name' => $payment->invoice?->product_name ?? 'N/A',
                ];
            })
            ->sortBy('payment_date')
            ->values();
    }

    /**
     * Export payments data
     */
    public function exportPayments($format = 'csv')
    {
        $payments = Payment::withTrashed()
            ->with(['invoice', 'customer', 'paymentMethod', 'confirmedBy'])
            ->get();
        
        if ($format === 'csv') {
            $filename = 'payments_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($payments) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'ID', 'Invoice Number', 'Customer Name', 'Payment Type', 'Amount Paid',
                    'Expected Amount', 'Reference Number', 'Payment Method', 'Status',
                    'Payment Date', 'Confirmed At', 'Confirmed By', 'Notes', 'Created At'
                ]);

                // CSV data
                foreach ($payments as $payment) {
                    fputcsv($file, [
                        $payment->id,
                        $payment->invoice->invoice_number,
                        $payment->customer_name,
                        $payment->payment_type,
                        $payment->formatted_amount_paid,
                        $payment->formatted_expected_amount,
                        $payment->reference_number,
                        $payment->payment_method_name,
                        $payment->status_text,
                        $payment->payment_date->format('Y-m-d'),
                        $payment->confirmed_at?->format('Y-m-d H:i:s'),
                        $payment->confirmed_by_name,
                        $payment->notes,
                        $payment->created_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return response()->json(['error' => 'Unsupported export format'], 400);
    }

    /**
     * Mark selected payment schedules as paid
     */
    public function markSchedulesAsPaid($scheduleIds, $amountPaid)
    {
        $schedules = InvoicePaymentSchedule::whereIn('id', $scheduleIds)->get();
        
        foreach ($schedules as $schedule) {
            // Calculate the amount to allocate to this schedule
            $scheduleAmount = min($amountPaid, $schedule->expected_amount - $schedule->paid_amount);
            
            if ($scheduleAmount > 0) {
                $schedule->updatePayment($scheduleAmount);
                $amountPaid -= $scheduleAmount;
            }
            
            // If no more amount to allocate, break
            if ($amountPaid <= 0) {
                break;
            }
        }
        
        return $schedules;
    }

    /**
     * Get payment schedules that are already paid (for edit mode)
     */
    public function getPaidSchedules($invoiceId)
    {
        return InvoicePaymentSchedule::where('invoice_id', $invoiceId)
            ->where('status', 'paid')
            ->orderBy('payment_order')
            ->get();
    }

    /**
     * Get all payment schedules for an invoice (for display purposes)
     */
    public function getAllPaymentSchedules($invoiceId)
    {
        return InvoicePaymentSchedule::where('invoice_id', $invoiceId)
            ->orderBy('payment_order')
            ->get();
    }

    /**
     * Send update invoice email with payment history and schedule details
     */
    public function sendUpdateInvoiceEmail($invoice, $paymentHistory, $paidSchedules, $totalPaid, $remainingBalance)
    {
        try {
            // Generate PDF with updated information
            $pdf = \PDF::loadView('invoices.pdf-updated', [
                'invoice' => $invoice,
                'paymentHistory' => $paymentHistory,
                'paidSchedules' => $paidSchedules,
                'totalPaid' => $totalPaid,
                'remainingBalance' => $remainingBalance
            ]);

            // Send email with PDF attachment
            \Mail::send('emails.invoice-updated', [
                'invoice' => $invoice,
                'customerName' => $invoice->customer->full_name ?? $invoice->customer->name,
                'paymentHistory' => $paymentHistory,
                'paidSchedules' => $paidSchedules,
                'totalPaid' => $totalPaid,
                'remainingBalance' => $remainingBalance
            ], function ($message) use ($invoice, $pdf) {
                $message->to($invoice->customer->user_email ?? $invoice->customer->email)
                    ->subject('Updated Invoice - ' . $invoice->invoice_number)
                    ->attachData($pdf->output(), 'invoice-updated-' . $invoice->invoice_number . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send update invoice email: ' . $e->getMessage());
            throw $e;
        }
    }
}
