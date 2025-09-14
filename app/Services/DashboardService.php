<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get dashboard overview data
     */
    public function getOverviewData()
    {
        // Get current month data
        $currentMonth = Carbon::now()->format('Y-m');
        $previousMonth = Carbon::now()->subMonth()->format('Y-m');
        
        // Current month revenue
        $currentMonthRevenue = Invoice::where('deleted_at', null)
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])
            ->sum('total_amount');
            
        // Previous month revenue
        $previousMonthRevenue = Invoice::where('deleted_at', null)
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$previousMonth])
            ->sum('total_amount');
            
        // Yearly total revenue
        $yearlyRevenue = Invoice::where('deleted_at', null)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_amount');
            
        // Outstanding balances
        $outstandingBalances = Invoice::where('deleted_at', null)
            ->sum('remaining_balance');
            
        // Invoice statistics
        $invoiceStats = [
            'total_issued' => Invoice::where('deleted_at', null)->count(),
            'total_sent' => Invoice::where('deleted_at', null)
                ->whereIn('status', ['sent', 'paid'])
                ->count(),
            'total_cancelled' => Invoice::where('deleted_at', null)
                ->where('status', 'cancelled')
                ->count(),
        ];

        return [
            'revenue' => [
                'current_month' => (float) $currentMonthRevenue,
                'previous_month' => (float) $previousMonthRevenue,
                'yearly_total' => (float) $yearlyRevenue,
            ],
            'outstanding_balances' => (float) $outstandingBalances,
            'invoice_stats' => $invoiceStats,
        ];
    }

    /**
     * Get revenue data for charts
     */
    public function getRevenueData()
    {
        // Get last 12 months revenue data
        $revenueData = Invoice::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('SUM(total_paid_amount) as total_paid'),
                DB::raw('COUNT(*) as invoice_count')
            )
            ->where('deleted_at', null)
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return [
            'monthly_data' => $revenueData->map(function ($item) {
                return [
                    'month' => $item->month,
                    'revenue' => (float) $item->total_revenue,
                    'paid' => (float) $item->total_paid,
                    'invoice_count' => (int) $item->invoice_count,
                ];
            }),
        ];
    }

    /**
     * Get customer summary data
     */
    public function getCustomerSummary()
    {
        $customers = User::select(
                'users.id',
                'users.user_email',
                DB::raw('COALESCE(um.meta_value, users.user_email) as customer_name'),
                DB::raw('COUNT(i.id) as invoice_count'),
                DB::raw('SUM(i.total_amount) as total_amount'),
                DB::raw('SUM(i.total_paid_amount) as total_paid'),
                DB::raw('SUM(i.remaining_balance) as remaining_balance')
            )
            ->leftJoin('user_meta as um', function ($join) {
                $join->on('users.id', '=', 'um.user_id')
                     ->where('um.meta_key', '=', 'full_name');
            })
            ->leftJoin('invoices as i', function ($join) {
                $join->on('users.id', '=', 'i.customer_id')
                     ->whereNull('i.deleted_at');
            })
            ->whereNull('users.deleted_at')
            ->groupBy('users.id', 'users.user_email', 'um.meta_value')
            ->having('invoice_count', '>', 0)
            ->orderBy('total_amount', 'desc')
            ->limit(6)
            ->get();

        return [
            'top_customers' => $customers->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->customer_name,
                    'email' => $customer->user_email,
                    'invoice_count' => (int) $customer->invoice_count,
                    'total_amount' => (float) $customer->total_amount,
                    'total_paid' => (float) $customer->total_paid,
                    'remaining_balance' => (float) $customer->remaining_balance,
                ];
            }),
        ];
    }

    /**
     * Get item status summary
     */
    public function getItemStatusSummary()
    {
        $itemStatusData = Invoice::select(
                'item_status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total_amount')
            )
            ->where('deleted_at', null)
            ->groupBy('item_status')
            ->get();

        $statusSummary = [];
        foreach ($itemStatusData as $item) {
            $statusSummary[$item->item_status] = [
                'count' => (int) $item->count,
                'total_amount' => (float) $item->total_amount,
            ];
        }

        return [
            'status_summary' => $statusSummary,
        ];
    }

    /**
     * Get payment breakdown data
     */
    public function getPaymentBreakdown()
    {
        $paymentBreakdown = Invoice::select(
                'payment_status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('SUM(total_paid_amount) as total_paid'),
                DB::raw('SUM(remaining_balance) as remaining_balance')
            )
            ->where('deleted_at', null)
            ->groupBy('payment_status')
            ->get();

        $breakdown = [];
        foreach ($paymentBreakdown as $payment) {
            $breakdown[$payment->payment_status] = [
                'count' => (int) $payment->count,
                'total_amount' => (float) $payment->total_amount,
                'total_paid' => (float) $payment->total_paid,
                'remaining_balance' => (float) $payment->remaining_balance,
            ];
        }

        return [
            'payment_breakdown' => $breakdown,
        ];
    }

    /**
     * Get recent activity data
     */
    public function getRecentActivity()
    {
        $recentInvoices = Invoice::with(['customer'])
            ->where('deleted_at', null)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentPayments = Payment::with(['invoice'])
            ->where('deleted_at', null)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'recent_invoices' => $recentInvoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->user_email ?? 'Unknown',
                    'total_amount' => (float) $invoice->total_amount,
                    'payment_status' => $invoice->payment_status,
                    'item_status' => $invoice->item_status,
                    'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'recent_payments' => $recentPayments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount_paid' => (float) $payment->amount_paid,
                    'status' => $payment->status,
                    'invoice_number' => $payment->invoice->invoice_number ?? 'N/A',
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    }
}