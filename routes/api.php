<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\NavigationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\FeeController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\PaymentTermController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PaymentTypeController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AuditTrailController;
use App\Http\Controllers\Api\CustomerPortalController;
use App\Http\Controllers\Api\EmailSettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
	// Validate password for current authenticated user
	Route::post('/validate-password', [AuthController::class, 'validatePassword']);
	Route::get('/user', [UserController::class, 'getUser']);
	Route::post('/profile', [UserController::class, 'updateProfile']);
	Route::post('/logout', [AuthController::class, 'logout']);

	/*
	|--------------------------------------------------------------------------
	| Dashboard Routes
	|--------------------------------------------------------------------------
	*/
	Route::prefix('dashboard')->group(function () {
		Route::get('/overview', [DashboardController::class, 'overview']);
		Route::get('/revenue', [DashboardController::class, 'revenue']);
		Route::get('/customers', [DashboardController::class, 'customers']);
		Route::get('/item-status', [DashboardController::class, 'itemStatus']);
		Route::get('/payment-breakdown', [DashboardController::class, 'paymentBreakdown']);
	});

	/*
	|--------------------------------------------------------------------------
	| Options Management Routes
	|--------------------------------------------------------------------------
	*/
	// Options Management Routes - All dropdown endpoints
	Route::prefix('options')->group(function () {
		// Media Management Options
		Route::get('/media/dates', [MediaController::class, 'dateFolder']);
		
		// Navigation Options
		Route::get('/navigations', [NavigationController::class, 'index']);
		Route::get('/navigations/{id}/sub', [NavigationController::class, 'getSubNavigations']);
		Route::get('/routes', [NavigationController::class, 'getRoutes']);
		
		// User Management Options
		Route::get('/users', [UserController::class, 'getUsersForDropdown']);
		Route::get('/roles', [RoleController::class, 'getRoles']);
		
		// Customer Management Options
		Route::get('/customers', [CustomerController::class, 'getCustomersForDropdown']);
		
		// Financial Management Options
		Route::get('/taxes', [TaxController::class, 'getActiveTaxes']);
		Route::get('/fees', [FeeController::class, 'getActiveFees']);
		Route::get('/discounts', [DiscountController::class, 'getActiveDiscounts']);
		Route::get('/payment-terms', [PaymentTermController::class, 'getActivePaymentTerms']);
		Route::get('/payment-methods', [PaymentMethodController::class, 'getActivePaymentMethods']);
		Route::get('/payment-types', [PaymentTypeController::class, 'getActivePaymentTypes']);
		
		// Invoice Management Options
		Route::get('/invoices', [InvoiceController::class, 'getInvoicesForDropdown']);
		Route::get('/invoices/search', [InvoiceController::class, 'searchInvoices']);
		Route::get('/invoice-statuses', [InvoiceController::class, 'getInvoiceStatuses']);
		
		// Payment Management Options
		Route::get('/payments', [PaymentController::class, 'getPaymentsForDropdown']);
		Route::get('/payment-types', [PaymentController::class, 'getPaymentTypes']);
		Route::get('/payment-statuses', [PaymentController::class, 'getPaymentStatuses']);
		Route::get('/payment-submissions', [PaymentController::class, 'getPaymentSubmissionsForDropdown']);
		
		// Item Status Options
		Route::get('/item-statuses', [PaymentController::class, 'getItemStatuses']);
	});

	// User Management Routes
	Route::prefix('user-management')->group(function () {
		Route::prefix('users')->group(function () {
			Route::get('/', [UserController::class, 'index']);
			Route::get('/{id}', [UserController::class, 'show']);
			Route::post('/', [UserController::class, 'store']);
			Route::put('/{id}', [UserController::class, 'update']);
			Route::delete('/{id}', [UserController::class, 'destroy']);
			Route::post('/bulk/delete', [UserController::class, 'bulkDelete']);
			Route::post('/bulk/restore', [UserController::class, 'bulkRestore']);
			Route::post('/bulk/force-delete', [UserController::class, 'bulkForceDelete']);
			Route::post('/bulk/role', [UserController::class, 'bulkChangeRole']);
			Route::post('/bulk/password', [UserController::class, 'bulkChangePassword']);
		});
		Route::prefix('archived/users')->group(function () {
			Route::get('/', [UserController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [UserController::class, 'restore']);
			Route::delete('/{id}', [UserController::class, 'forceDelete']);
		});
		Route::prefix('roles')->group(function () {
			Route::get('/', [RoleController::class, 'index']);
			Route::get('/{id}', [RoleController::class, 'show']);
			Route::post('/', [RoleController::class, 'store']);
			Route::put('/{id}', [RoleController::class, 'update']);
			Route::delete('/{id}', [RoleController::class, 'destroy']);
			Route::post('/bulk/delete', [RoleController::class, 'bulkDelete']);
			Route::post('/bulk/restore', [RoleController::class, 'bulkRestore']);
			Route::post('/bulk/force-delete', [RoleController::class, 'bulkForceDelete']);
			Route::post('/bulk/role', [RoleController::class, 'bulkChangeRole']);
		});
		Route::prefix('archived/roles')->group(function () {
			Route::get('/', [RoleController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [RoleController::class, 'restore']);
			Route::delete('/{id}', [RoleController::class, 'forceDelete']);
		});
	});

	// Content Management Routes
	Route::prefix('content-management')->group(function () {
		Route::apiResource('/media-library', MediaController::class);
		Route::post('/media-library/bulk/delete', [MediaController::class, 'bulkDelete']);
	});

	// System Settings Routes
	Route::prefix('system-settings')->group(function () {
		Route::prefix('navigation')->group(function () {
			Route::get('/', [NavigationController::class, 'index']);
			Route::get('/{id}', [NavigationController::class, 'show']);
			Route::post('/', [NavigationController::class, 'store']);
			Route::put('/{id}', [NavigationController::class, 'update']);
			Route::delete('/{id}', [NavigationController::class, 'destroy']);
			Route::post('/bulk/delete', [NavigationController::class, 'bulkDelete']);
			Route::post('/bulk/restore', [NavigationController::class, 'bulkRestore']);
			Route::post('/bulk/force-delete', [NavigationController::class, 'bulkForceDelete']);
			Route::post('/bulk/role', [NavigationController::class, 'bulkChangeRole']);
		});
		Route::prefix('archived/navigation')->group(function () {
			Route::get('/', [NavigationController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [NavigationController::class, 'restore']);
			Route::delete('/{id}', [NavigationController::class, 'forceDelete']);
		});
		
		// Audit Trail Routes
		Route::prefix('audit-trail')->group(function () {
			Route::get('/', [AuditTrailController::class, 'index']);
			Route::get('/stats', [AuditTrailController::class, 'stats']);
			Route::get('/modules', [AuditTrailController::class, 'modules']);
			Route::get('/actions', [AuditTrailController::class, 'actions']);
			Route::post('/export', [AuditTrailController::class, 'export']);
			Route::get('/{id}', [AuditTrailController::class, 'show']);
		});
		
		// Email Settings Routes
		Route::prefix('email-settings')->group(function () {
			Route::get('/', [EmailSettingController::class, 'index']);
			Route::get('/{key}', [EmailSettingController::class, 'show']);
			Route::put('/{key}', [EmailSettingController::class, 'update']);
			Route::get('/config/all', [EmailSettingController::class, 'getEmailSettings']);
			Route::post('/config/update', [EmailSettingController::class, 'updateEmailSettings']);
			Route::get('/mail-config', [EmailSettingController::class, 'getMailConfig']);
		});
	});

	// PROFILE ROUTES
	Route::post('/profile', [UserController::class, 'updateProfile']);

	// Financial Management Routes
	Route::prefix('financial-management')->group(function () {
		// Taxes Management
		Route::prefix('taxes')->group(function () {
			Route::get('/', [TaxController::class, 'index']);
			Route::get('/{id}', [TaxController::class, 'show']);
			Route::post('/', [TaxController::class, 'store']);
			Route::put('/{id}', [TaxController::class, 'update']);
			Route::delete('/{id}', [TaxController::class, 'destroy']);
			Route::post('/bulk/delete', [TaxController::class, 'bulkDelete']);
			Route::post('/bulk/restore', [TaxController::class, 'bulkRestore']);
			Route::post('/bulk/force-delete', [TaxController::class, 'bulkForceDelete']);
		});
		Route::prefix('archived/taxes')->group(function () {
			Route::get('/', [TaxController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [TaxController::class, 'restore']);
			Route::delete('/{id}', [TaxController::class, 'forceDelete']);
		});

		// Fees Management
		Route::prefix('fees')->group(function () {
			Route::get('/', [FeeController::class, 'index']);
			Route::get('/{id}', [FeeController::class, 'show']);
			Route::post('/', [FeeController::class, 'store']);
			Route::put('/{id}', [FeeController::class, 'update']);
			Route::delete('/{id}', [FeeController::class, 'destroy']);
			Route::post('/bulk/delete', [FeeController::class, 'bulkDelete']);
			Route::post('/bulk/restore', [FeeController::class, 'bulkRestore']);
			Route::post('/bulk/force-delete', [FeeController::class, 'bulkForceDelete']);
		});
		Route::prefix('archived/fees')->group(function () {
			Route::get('/', [FeeController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [FeeController::class, 'restore']);
			Route::delete('/{id}', [FeeController::class, 'forceDelete']);
		});

		// Discounts Management
		Route::prefix('discounts')->group(function () {
			Route::get('/', [DiscountController::class, 'index']);
			Route::get('/{id}', [DiscountController::class, 'show']);
			Route::post('/', [DiscountController::class, 'store']);
			Route::put('/{id}', [DiscountController::class, 'update']);
			Route::delete('/{id}', [DiscountController::class, 'destroy']);
			Route::post('/bulk/delete', [DiscountController::class, 'bulkDelete']);
			Route::post('/bulk/restore', [DiscountController::class, 'bulkRestore']);
			Route::post('/bulk/force-delete', [DiscountController::class, 'bulkForceDelete']);
			Route::post('/validate-code', [DiscountController::class, 'validateDiscountCode']);
		});
		Route::prefix('archived/discounts')->group(function () {
			Route::get('/', [DiscountController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [DiscountController::class, 'restore']);
			Route::delete('/{id}', [DiscountController::class, 'forceDelete']);
		});

		// Payment Terms Management
		Route::prefix('payment-terms')->group(function () {
			Route::get('/', [PaymentTermController::class, 'index']);
			Route::get('/{id}', [PaymentTermController::class, 'show']);
			Route::post('/', [PaymentTermController::class, 'store']);
			Route::put('/{id}', [PaymentTermController::class, 'update']);
			Route::delete('/{id}', [PaymentTermController::class, 'destroy']);
			Route::post('/bulk/delete', [PaymentTermController::class, 'bulkDelete']);
			Route::post('/bulk/restore', [PaymentTermController::class, 'bulkRestore']);
			Route::post('/bulk/force-delete', [PaymentTermController::class, 'bulkForceDelete']);
			Route::post('/generate-equal-schedule', [PaymentTermController::class, 'generateEqualSchedule']);
			Route::get('/{id}/validate-completeness', [PaymentTermController::class, 'validateCompleteness']);
		});
		Route::prefix('archived/payment-terms')->group(function () {
			Route::get('/', [PaymentTermController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [PaymentTermController::class, 'restore']);
			Route::delete('/{id}', [PaymentTermController::class, 'forceDelete']);
		});

		// Payment Methods Management
		Route::prefix('payment-methods')->group(function () {
			Route::get('/', [PaymentMethodController::class, 'index']);
			Route::get('/{id}', [PaymentMethodController::class, 'show']);
			Route::post('/', [PaymentMethodController::class, 'store']);
			Route::put('/{id}', [PaymentMethodController::class, 'update']);
			Route::delete('/{id}', [PaymentMethodController::class, 'destroy']);
			Route::post('/bulk/delete', [PaymentMethodController::class, 'bulkDelete']);
			Route::post('/bulk/restore', [PaymentMethodController::class, 'bulkRestore']);
			Route::post('/bulk/force-delete', [PaymentMethodController::class, 'bulkForceDelete']);
		});
		Route::prefix('archived/payment-methods')->group(function () {
			Route::get('/', [PaymentMethodController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [PaymentMethodController::class, 'restore']);
			Route::delete('/{id}', [PaymentMethodController::class, 'forceDelete']);
		});

		// Payment Types Management
		Route::prefix('payment-types')->group(function () {
			Route::get('/', [PaymentTypeController::class, 'index']);
			Route::get('/{id}', [PaymentTypeController::class, 'show']);
			Route::post('/', [PaymentTypeController::class, 'store']);
			Route::put('/{id}', [PaymentTypeController::class, 'update']);
			Route::delete('/{id}', [PaymentTypeController::class, 'destroy']);
			Route::post('/bulk/delete', [PaymentTypeController::class, 'bulkDelete']);
			Route::post('/bulk/restore', [PaymentTypeController::class, 'bulkRestore']);
			Route::post('/bulk/force-delete', [PaymentTypeController::class, 'bulkForceDelete']);
		});
		Route::prefix('archived/payment-types')->group(function () {
			Route::get('/', [PaymentTypeController::class, 'getTrashed']);
			Route::patch('/restore/{id}', [PaymentTypeController::class, 'restore']);
			Route::delete('/{id}', [PaymentTypeController::class, 'forceDelete']);
		});
	});

	// Customer Management Routes
	Route::prefix('customer-management')->group(function () {
		// Customers Management
		Route::prefix('customers')->group(function () {
			Route::get('/', [CustomerController::class, 'index']);
			Route::get('/statistics', [CustomerController::class, 'getCustomerStats']);
			Route::get('/export', [CustomerController::class, 'exportCustomers']);
			Route::get('/next-code', [CustomerController::class, 'nextCode']);
			Route::post('/', [CustomerController::class, 'store']);
			Route::get('/{id}', [CustomerController::class, 'show']);
			Route::put('/{id}', [CustomerController::class, 'update']);
			Route::delete('/{id}', [CustomerController::class, 'destroy']);
		});
	});

	// Invoice Management Routes
	Route::prefix('invoice-management')->group(function () {
		// Invoices Management
		Route::prefix('invoices')->group(function () {
			Route::get('/', [InvoiceController::class, 'index']);
			Route::get('/statistics', [InvoiceController::class, 'getInvoiceStats']);
			Route::get('/export', [InvoiceController::class, 'exportInvoices']);
			Route::post('/', [InvoiceController::class, 'store']);
			Route::get('/{id}', [InvoiceController::class, 'show']);
			Route::put('/{id}', [InvoiceController::class, 'update']);
			Route::delete('/{id}', [InvoiceController::class, 'destroy']);
			Route::patch('/{id}/cancel', [InvoiceController::class, 'cancel']);
			Route::get('/{id}/pdf', [InvoiceController::class, 'generatePdf']);
			Route::post('/{id}/send-email', [InvoiceController::class, 'sendEmail']);
		});
	});

	// Payment Management Routes
	Route::prefix('payment-management')->group(function () {
		// Payments Management
		Route::prefix('payments')->group(function () {
			Route::get('/', [PaymentController::class, 'index']);
			Route::get('/statistics', [PaymentController::class, 'getPaymentStats']);
			Route::get('/export', [PaymentController::class, 'exportPayments']);
			Route::post('/', [PaymentController::class, 'store']);
			Route::get('/{id}', [PaymentController::class, 'show']);
			Route::put('/{id}', [PaymentController::class, 'update']);
			Route::delete('/{id}', [PaymentController::class, 'destroy']);
			Route::patch('/{id}/approve', [PaymentController::class, 'approve']);
			Route::patch('/{id}/reject', [PaymentController::class, 'reject']);
			Route::patch('/{id}/confirm', [PaymentController::class, 'confirm']);
			Route::post('/{id}/send-update-invoice', [PaymentController::class, 'sendUpdateInvoice']);
			Route::post('/{id}/download-updated-invoice', [PaymentController::class, 'downloadUpdatedInvoice']);
		});
		
		// Payment Submissions
		Route::prefix('submissions')->group(function () {
			Route::get('/', [PaymentController::class, 'getPaymentSubmissions']);
			Route::post('/', [PaymentController::class, 'submitPayment']);
			Route::patch('/{id}/approve', [PaymentController::class, 'approveSubmission']);
			Route::patch('/{id}/reject', [PaymentController::class, 'rejectSubmission']);
		});
		
		// Payment Schedules
		Route::prefix('schedules')->group(function () {
			Route::get('/invoice/{invoiceId}', [PaymentController::class, 'getPaymentSchedules']);
		});
		
		// Item Status Management
		Route::prefix('item-status')->group(function () {
			Route::patch('/invoice/{invoiceId}', [PaymentController::class, 'updateItemStatus']);
		});
	});

	// Dashboard Routes
	Route::prefix('dashboard')->group(function () {
		Route::get('/statistics', [DashboardController::class, 'statistics']);
	});
});

// Public routes
Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/validate', [AuthController::class, 'activateUser']);
Route::post('/generate-password', [AuthController::class, 'genTempPassword']);
Route::post('/login', [AuthController::class, 'login']);

// Customer Portal Public Routes
Route::post('/customer/login', [CustomerPortalController::class, 'login']);
Route::post('/customer/forgot-password', [CustomerPortalController::class, 'forgotPassword']);

// Customer Portal Protected Routes
Route::middleware('auth:sanctum')->prefix('customer')->group(function () {
    Route::get('/user', [CustomerPortalController::class, 'getProfile']);
    Route::put('/user', [CustomerPortalController::class, 'updateProfile']);
    
    Route::get('/dashboard/overview', [CustomerPortalController::class, 'dashboardOverview']);
    Route::get('/invoices', [CustomerPortalController::class, 'getInvoices']);
    Route::get('/invoices/{id}', [CustomerPortalController::class, 'getInvoice']);
    Route::get('/invoices/{id}/pdf', [CustomerPortalController::class, 'downloadInvoicePdf']);
    Route::get('/invoices/{id}/payment-schedules', [CustomerPortalController::class, 'getPaymentSchedules']);
    
    Route::post('/payment-submission', [CustomerPortalController::class, 'submitPayment']);
    Route::get('/payment-submissions', [CustomerPortalController::class, 'getPaymentSubmissions']);
});
