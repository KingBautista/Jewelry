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
                return $query->orderBy('id', 'desc');
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
                    'Price', 'Payment Term', 'Tax', 'Fee', 'Discount', 'Subtotal',
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
