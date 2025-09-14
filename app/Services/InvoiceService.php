<?php

namespace App\Services;

use App\Models\Invoice;
use App\Http\Resources\InvoiceResource;

class InvoiceService extends BaseService
{
    public function __construct()
    {
        // Pass the InvoiceResource class to the parent constructor
        parent::__construct(new InvoiceResource(new Invoice), new Invoice());
    }

    /**
     * Retrieve all resources with paginate.
     */
    public function list($perPage = 10, $trash = false)
    {
        $allInvoices = $this->getTotalCount();
        $trashedInvoices = $this->getTrashedCount();

        return InvoiceResource::collection(Invoice::query()
            ->with(['customer', 'paymentTerm', 'tax', 'fee', 'discount'])
            ->when($trash, function ($query) {
                return $query->onlyTrashed();
            })
            ->when(request('search'), function ($query) {
                return $query->search(request('search'));
            })
            ->when(request('status'), function ($query) {
                return $query->byStatus(request('status'));
            })
            ->when(request('payment_status'), function ($query) {
                return $query->byPaymentStatus(request('payment_status'));
            })
            ->when(request('item_status'), function ($query) {
                return $query->byItemStatus(request('item_status'));
            })
            ->when(request('customer_id'), function ($query) {
                return $query->byCustomer(request('customer_id'));
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
        )->additional(['meta' => ['all' => $allInvoices, 'trashed' => $trashedInvoices]]);
    }

    /**
     * Get invoice statistics
     */
    public function getInvoiceStats()
    {
        return [
            'total_invoices' => Invoice::count(),
            'draft_invoices' => Invoice::byStatus('draft')->count(),
            'sent_invoices' => Invoice::byStatus('sent')->count(),
            'paid_invoices' => Invoice::byStatus('paid')->count(),
            'overdue_invoices' => Invoice::byStatus('overdue')->count(),
            'cancelled_invoices' => Invoice::byStatus('cancelled')->count(),
            'total_amount' => Invoice::sum('total_amount'),
            'paid_amount' => Invoice::byStatus('paid')->sum('total_amount'),
            'pending_amount' => Invoice::whereIn('status', ['draft', 'sent'])->sum('total_amount'),
            'overdue_amount' => Invoice::byStatus('overdue')->sum('total_amount'),
            'invoices_this_month' => Invoice::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'invoices_by_status' => Invoice::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'invoices_by_customer' => Invoice::with('customer')
                ->join('users', 'invoices.customer_id', '=', 'users.id')
                ->join('user_meta', function($join) {
                    $join->on('users.id', '=', 'user_meta.user_id')
                         ->where('user_meta.meta_key', '=', 'first_name');
                })
                ->selectRaw('users.id, user_meta.meta_value as first_name, COUNT(*) as count')
                ->groupBy('users.id', 'user_meta.meta_value')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Get invoices for dropdown
     */
    public function getInvoicesForDropdown()
    {
        return Invoice::active()
            ->select('id', 'invoice_number', 'product_name', 'total_amount')
            ->get()
            ->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'product_name' => $invoice->product_name,
                    'total_amount' => $invoice->formatted_total_amount,
                ];
            })
            ->sortBy('invoice_number')
            ->values();
    }

    /**
     * Search invoices with detailed information
     */
    public function searchInvoices($search)
    {
        return Invoice::active()
            ->with(['customer', 'paymentTerm', 'paymentSchedules'])
            ->when($search, function ($query) use ($search) {
                return $query->search($search);
            })
            ->limit(20)
            ->get()
            ->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'product_name' => $invoice->product_name,
                    'total_amount' => $invoice->formatted_total_amount,
                    'customer' => $invoice->customer ? [
                        'id' => $invoice->customer->id,
                        'name' => $invoice->customer->full_name,
                        'email' => $invoice->customer->user_email,
                        'phone' => $invoice->customer->formatted_phone,
                        'address' => $invoice->customer->formatted_address,
                    ] : null,
                    'payment_term' => $invoice->paymentTerm ? [
                        'id' => $invoice->paymentTerm->id,
                        'name' => $invoice->paymentTerm->name,
                        'code' => $invoice->paymentTerm->code,
                        'down_payment_percentage' => $invoice->paymentTerm->down_payment_percentage,
                        'remaining_percentage' => $invoice->paymentTerm->remaining_percentage,
                        'term_months' => $invoice->paymentTerm->term_months,
                    ] : null,
                    'payment_schedules' => $invoice->paymentSchedules->map(function($schedule) {
                        return [
                            'id' => $schedule->id,
                            'payment_type' => $schedule->payment_type,
                            'due_date' => $schedule->due_date,
                            'expected_amount' => $schedule->expected_amount,
                            'status' => $schedule->status,
                            'payment_order' => $schedule->payment_order,
                        ];
                    }),
                    'payment_status' => $invoice->payment_status,
                    'remaining_balance' => $invoice->formatted_remaining_balance,
                    'total_paid_amount' => $invoice->formatted_total_paid_amount,
                ];
            });
    }

    /**
     * Export invoices data
     */
    public function exportInvoices($format = 'csv')
    {
        $invoices = Invoice::withTrashed()
            ->with(['customer', 'paymentTerm', 'tax', 'fee', 'discount'])
            ->get();
        
        if ($format === 'csv') {
            $filename = 'invoices_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($invoices) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'ID', 'Invoice Number', 'Customer Name', 'Product Name', 'Description',
                    'Price', 'Product Images', 'Payment Term', 'Tax', 'Fee', 'Discount', 'Subtotal',
                    'Tax Amount', 'Fee Amount', 'Discount Amount', 'Total Amount',
                    'Status', 'Issue Date', 'Due Date', 'Created At', 'Updated At'
                ]);

                // CSV data
                foreach ($invoices as $invoice) {
                    fputcsv($file, [
                        $invoice->id,
                        $invoice->invoice_number,
                        $invoice->customer_name,
                        $invoice->product_name,
                        $invoice->description,
                        $invoice->formatted_price,
                        is_array($invoice->product_images) ? implode('; ', $invoice->product_images) : $invoice->product_images,
                        $invoice->payment_term_name,
                        $invoice->tax_name,
                        $invoice->fee_name,
                        $invoice->discount_name,
                        $invoice->formatted_subtotal,
                        $invoice->formatted_tax_amount,
                        $invoice->formatted_fee_amount,
                        $invoice->formatted_discount_amount,
                        $invoice->formatted_total_amount,
                        $invoice->status_text,
                        $invoice->issue_date->format('Y-m-d'),
                        $invoice->due_date?->format('Y-m-d'),
                        $invoice->created_at->format('Y-m-d H:i:s'),
                        $invoice->updated_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return response()->json(['error' => 'Unsupported export format'], 400);
    }
}
