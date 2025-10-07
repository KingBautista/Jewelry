<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Customer Portal",
 *     description="Customer portal endpoints for self-service"
 * )
 */
class CustomerPortalController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/customer-portal/login",
     *     summary="Customer login",
     *     description="Authenticate customer and return access token",
     *     tags={"Customer Portal"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="customer@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="token", type="string", example="1|abc123...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid credentials"
     *     )
     * )
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
            ->where('user_role_id', 7)
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

        if (!Hash::check($user->user_salt . $request->password . (env("PEPPER_HASH") ?: ''), $user->user_pass)) {
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
     * @OA\Post(
     *     path="/api/customer-portal/forgot-password",
     *     summary="Customer forgot password",
     *     description="Send password reset email to customer",
     *     tags={"Customer Portal"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="customer@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset email sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Your temporary password has been sent to your registered email.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
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

            // Try to send email with better error handling
            try {
                \Illuminate\Support\Facades\Mail::to($user->user_email)->send(
                    new \App\Mail\CustomerPasswordReset($user, $new_password)
                );

                return response([
                    'message' => 'Your temporary password has been sent to your registered email.',
                    'status' => 'success'
                ]);
            } catch (\Exception $e) {
                // Log the email error for debugging
                \Log::error('Failed to send password reset email: ' . $e->getMessage(), [
                    'user_email' => $user->user_email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                // Return the password in response if email fails (for development/testing)
                if (config('app.debug') || config('app.env') === 'local') {
                    return response([
                        'message' => 'Password reset successful, but email could not be sent. Your new temporary password is: ' . $new_password,
                        'temporary_password' => $new_password,
                        'status' => 'warning',
                        'email_error' => 'Email service temporarily unavailable'
                    ]);
                } else {
                    // In production, don't expose the password
                    return response([
                        'message' => 'Password has been reset, but we encountered an issue sending the email. Please contact support.',
                        'status' => 'warning',
                        'email_error' => 'Email service temporarily unavailable'
                    ], 200);
                }
            }
        }

        return response([
            'message' => 'If the email exists, a password reset link has been sent.',
            'status' => 'info'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/customer-portal/dashboard",
     *     summary="Get customer dashboard overview",
     *     description="Retrieve customer dashboard statistics and overview",
     *     tags={"Customer Portal"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_invoices", type="integer", example=5),
     *             @OA\Property(property="total_paid", type="number", example=2500.00),
     *             @OA\Property(property="outstanding_balance", type="number", example=1500.00),
     *             @OA\Property(property="overdue_invoices", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="upcoming_dues", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/customer-portal/invoices",
     *     summary="Get customer invoices",
     *     description="Retrieve paginated list of customer invoices with filters",
     *     tags={"Customer Portal"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for invoice number or notes",
     *         @OA\Schema(type="string", example="INV-2024")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by payment status",
     *         @OA\Schema(type="string", enum={"pending","partial","fully_paid","overdue"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=3),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="total", type="integer", example=45)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/customer-portal/invoices/{id}",
     *     summary="Get single invoice details",
     *     description="Retrieve detailed information about a specific invoice",
     *     tags={"Customer Portal"},
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
     *         description="Invoice details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
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
     * @OA\Get(
     *     path="/api/customer-portal/invoices/{id}/pdf",
     *     summary="Download invoice PDF",
     *     description="Download invoice as PDF file",
     *     tags={"Customer Portal"},
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
     *         description="PDF file downloaded successfully",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
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
     * @OA\Post(
     *     path="/api/customer-portal/payments",
     *     summary="Submit payment",
     *     description="Submit a payment with receipt images for review",
     *     tags={"Customer Portal"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"invoice_id","amount_paid","expected_amount","reference_number","payment_method"},
     *                 @OA\Property(property="invoice_id", type="integer", example=1),
     *                 @OA\Property(property="amount_paid", type="number", format="float", example=500.00),
     *                 @OA\Property(property="expected_amount", type="number", format="float", example=500.00),
     *                 @OA\Property(property="reference_number", type="string", example="REF123456"),
     *                 @OA\Property(property="payment_method", type="string", example="Bank Transfer"),
     *                 @OA\Property(property="receipt_images", type="array", @OA\Items(type="string", format="binary")),
     *                 @OA\Property(property="notes", type="string", example="Payment notes")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment submission successful. You will receive an email notification once it is reviewed."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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

        $payment = Payment::create([
            'invoice_id' => $request->invoice_id,
            'customer_id' => $user->id,
            'payment_type' => 'partial', // Default type for customer submissions
            'amount_paid' => $request->amount_paid,
            'expected_amount' => $request->expected_amount,
            'reference_number' => $request->reference_number,
            'receipt_images' => $receiptImages,
            'status' => 'pending',
            'payment_date' => now()->toDateString(),
            'source' => 'customer_submission',
            'notes' => $request->notes,
        ]);

        // Send notification email to admin (if admin email is configured)
        $adminEmail = env('ADMIN_EMAIL');
        if ($adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                \Illuminate\Support\Facades\Mail::to($adminEmail)
                    ->send(new \App\Mail\PaymentSubmissionNotification($payment));
                \Log::info('Payment submission notification email sent to: ' . $adminEmail);
            } catch (\Exception $e) {
                \Log::error('Failed to send payment submission notification email: ' . $e->getMessage());
                // Continue execution even if email fails
            }
        } else {
            \Log::info('Admin email not configured, skipping email notification');
        }

        return response([
            'message' => 'Payment submission successful. You will receive an email notification once it is reviewed.',
            'data' => new PaymentResource($payment)
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/customer-portal/payments",
     *     summary="Get payment submissions",
     *     description="Retrieve paginated list of customer payment submissions",
     *     tags={"Customer Portal"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment submissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=2),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="total", type="integer", example=25)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     * Get payment submissions - shows all payments for the customer
     */
    public function getPaymentSubmissions(Request $request)
    {
        $user = $request->user();
        
        $payments = Payment::where('customer_id', $user->id)
            ->with(['invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response([
            'data' => PaymentResource::collection($payments->items()),
            'current_page' => $payments->currentPage(),
            'last_page' => $payments->lastPage(),
            'per_page' => $payments->perPage(),
            'total' => $payments->total(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/customer-portal/profile",
     *     summary="Get customer profile",
     *     description="Retrieve authenticated customer profile information",
     *     tags={"Customer Portal"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/customer-portal/profile",
     *     summary="Update customer profile",
     *     description="Update authenticated customer profile information",
     *     tags={"Customer Portal"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_login","user_email"},
     *             @OA\Property(property="user_login", type="string", example="updated_username"),
     *             @OA\Property(property="user_email", type="string", format="email", example="updated@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="address", type="string", example="123 Main St, City, State")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     * Update customer profile
     */
    public function updateProfile(Request $request)
    {
        \Log::info('Profile update request received:', $request->all());
        
        $request->validate([
            'user_login' => 'required|string|max:191',
            'user_email' => 'required|email|unique:users,user_email,' . $request->user()->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        \Log::info('Updating user:', ['user_id' => $user->id, 'user_email' => $user->user_email]);
        
        // Update basic user fields
        $user->update($request->only(['user_login', 'user_email']));
        
        // Update user meta fields (phone and address)
        $metaData = [];
        if ($request->has('phone')) {
            $metaData['phone'] = $request->phone;
        }
        if ($request->has('address')) {
            $metaData['address'] = $request->address;
        }
        
        \Log::info('Meta data to save:', $metaData);
        
        if (!empty($metaData)) {
            $user->saveUserMeta($metaData);
            \Log::info('User meta saved successfully');
        }

        \Log::info('Profile update completed successfully');
        
        return response([
            'message' => 'Profile updated successfully',
            'data' => new CustomerResource($user)
        ]);
    }
}
