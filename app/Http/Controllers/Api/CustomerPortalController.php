<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Invoice;
use App\Models\PaymentSubmission;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentSubmissionResource;

class CustomerPortalController extends Controller
{
    /**
     * Customer login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('user_email', $request->email)
            ->where('user_status', 1)
            ->where('user_role_id', 5)
            ->first();

        if (!$user) {
            return response([
                'errors' => ['Invalid email or password.'],
                'status' => false,
                'status_code' => 422,
            ], 422);
        }

        // Make user_salt visible for authentication
        $user->makeVisible(['user_salt']);

        if (!Hash::check($user->user_salt . $request->password . env("PEPPER_HASH"), $user->user_pass)) {
            return response([
                'errors' => ['Invalid email or password.'],
                'status' => false,
                'status_code' => 422,
            ], 422);
        }

        $user->tokens()->delete();
        $token = $user->createToken('customer-portal')->plainTextToken;

        return response([
            'user' => new CustomerResource($user),
            'token' => $token
        ]);
    }

    /**
     * Customer forgot password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,user_email',
        ]);

        $user = User::where('user_email', $request->email)
            ->whereHas('userRole', function($query) {
                $query->where('name', 'customer');
            })
            ->first();

        if ($user) {
            $salt = $user->user_salt;
            $new_password = \App\Helpers\PasswordHelper::generateSalt();
            $password = \App\Helpers\PasswordHelper::generatePassword($salt, $new_password);

            $user->update(['user_pass' => $password]);

            // Send email with new password
            \Illuminate\Support\Facades\Mail::to($user->user_email)->send(
                new \App\Mail\CustomerPasswordReset($user, $new_password)
            );

            return response([
                'message' => 'Your temporary password has been sent to your registered email.'
            ]);
        }

        return response([
            'message' => 'If the email exists, a password reset link has been sent.'
        ]);
    }

    /**
     * Get customer dashboard overview
     */
    public function dashboardOverview(Request $request)
    {
        $user = $request->user();
        
        $totalInvoices = Invoice::where('customer_id', $user->id)->count();
        $totalPaid = Payment::where('customer_id', $user->id)
            ->where('status', 'approved')
            ->sum('amount_paid');
        
        $outstandingBalance = Invoice::where('customer_id', $user->id)
            ->sum('remaining_balance');
        
        $overdueInvoices = Invoice::where('customer_id', $user->id)
            ->where('due_date', '<', now())
            ->where('payment_status', '!=', 'fully_paid')
            ->get();
        
        $upcomingDues = Invoice::where('customer_id', $user->id)
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(7))
            ->where('payment_status', '!=', 'fully_paid')
            ->get();

        return response([
            'total_invoices' => $totalInvoices,
            'total_paid' => $totalPaid,
            'outstanding_balance' => $outstandingBalance,
            'overdue_invoices' => InvoiceResource::collection($overdueInvoices),
            'upcoming_dues' => InvoiceResource::collection($upcomingDues),
        ]);
    }

    /**
     * Get customer invoices
     */
    public function getInvoices(Request $request)
    {
        $user = $request->user();
        
        $query = Invoice::where('customer_id', $user->id)
            ->with(['customer', 'paymentTerm', 'tax', 'fee', 'discount']);

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
                  ->orWhere('notes', 'like', '%' . $request->search . '%');
            });
        }

        // Status filter
        if ($request->has('status') && $request->status) {
            $query->where('payment_status', $request->status);
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response([
            'data' => InvoiceResource::collection($invoices->items()),
            'current_page' => $invoices->currentPage(),
            'last_page' => $invoices->lastPage(),
            'per_page' => $invoices->perPage(),
            'total' => $invoices->total(),
        ]);
    }

    /**
     * Get single invoice details
     */
    public function getInvoice(Request $request, $id)
    {
        $user = $request->user();
        
        $invoice = Invoice::where('customer_id', $user->id)
            ->with(['customer', 'paymentTerm', 'tax', 'fee', 'discount', 'items'])
            ->findOrFail($id);

        return response([
            'data' => new InvoiceResource($invoice)
        ]);
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoicePdf(Request $request, $id)
    {
        $user = $request->user();
        
        $invoice = Invoice::where('customer_id', $user->id)
            ->with(['customer', 'paymentTerm', 'tax', 'fee', 'discount', 'items'])
            ->findOrFail($id);

        // Generate PDF using the existing service
        $pdfService = new \App\Services\InvoiceService();
        $pdf = $pdfService->generatePdf($invoice);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-' . $invoice->invoice_number . '.pdf"'
        ]);
    }

    /**
     * Submit payment
     */
    public function submitPayment(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount_paid' => 'required|numeric|min:0.01',
            'expected_amount' => 'required|numeric|min:0.01',
            'reference_number' => 'required|string|max:191',
            'payment_method' => 'required|string|max:191',
            'receipt_images' => 'nullable|array',
            'receipt_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        
        // Verify invoice belongs to customer
        $invoice = Invoice::where('customer_id', $user->id)
            ->findOrFail($request->invoice_id);

        // Handle file uploads
        $receiptImages = [];
        if ($request->hasFile('receipt_images')) {
            foreach ($request->file('receipt_images') as $file) {
                $path = $file->store('payment-receipts', 'public');
                $receiptImages[] = $path;
            }
        }

        $paymentSubmission = PaymentSubmission::create([
            'invoice_id' => $request->invoice_id,
            'customer_id' => $user->id,
            'amount_paid' => $request->amount_paid,
            'expected_amount' => $request->expected_amount,
            'reference_number' => $request->reference_number,
            'receipt_images' => json_encode($receiptImages),
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        // Send notification email to admin
        \Illuminate\Support\Facades\Mail::to(env('ADMIN_EMAIL'))
            ->send(new \App\Mail\PaymentSubmissionNotification($paymentSubmission));

        return response([
            'message' => 'Payment submission successful. You will receive an email notification once it is reviewed.',
            'data' => new PaymentSubmissionResource($paymentSubmission)
        ]);
    }

    /**
     * Get payment submissions
     */
    public function getPaymentSubmissions(Request $request)
    {
        $user = $request->user();
        
        $submissions = PaymentSubmission::where('customer_id', $user->id)
            ->with(['invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response([
            'data' => PaymentSubmissionResource::collection($submissions->items()),
            'current_page' => $submissions->currentPage(),
            'last_page' => $submissions->lastPage(),
            'per_page' => $submissions->perPage(),
            'total' => $submissions->total(),
        ]);
    }

    /**
     * Get customer profile
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        return response([
            'data' => new CustomerResource($user)
        ]);
    }

    /**
     * Update customer profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'user_login' => 'required|string|max:191',
            'user_email' => 'required|email|unique:users,user_email,' . $request->user()->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $user->update($request->only(['user_login', 'user_email', 'phone', 'address']));

        return response([
            'message' => 'Profile updated successfully',
            'data' => new CustomerResource($user)
        ]);
    }
}
