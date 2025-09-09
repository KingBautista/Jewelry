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
	Route::post('/logout', [AuthController::class, 'logout']);

	/*
	|--------------------------------------------------------------------------
	| Options Management Routes
	|--------------------------------------------------------------------------
	*/
	Route::prefix('options')->group(function () {
		// media date folder
		Route::get('/dates', [MediaController::class, 'dateFolder']);
		// navigation-related routes
		Route::get('/navigations', [NavigationController::class, 'index']);  // Retrieve all categories for dropdown
		Route::get('/navigations/{id}', [NavigationController::class, 'getSubNavigations']);  // Retrieve subcategories for a specific category		
		Route::get('/routes', [NavigationController::class, 'getRoutes']);  // Retrieve all routes
		Route::get('/roles', [RoleController::class, 'getRoles']);  // Retrieve all routes
		// users for dropdowns
		Route::get('/users', [UserController::class, 'getUsersForDropdown']);
		// financial configurations for dropdowns
		Route::get('/taxes', [TaxController::class, 'getActiveTaxes']);
		Route::get('/fees', [FeeController::class, 'getActiveFees']);
		Route::get('/discounts', [DiscountController::class, 'getActiveDiscounts']);
		Route::get('/payment-terms', [PaymentTermController::class, 'getActivePaymentTerms']);
		Route::get('/payment-methods', [PaymentMethodController::class, 'getActivePaymentMethods']);
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
	});

	// PROFILE ROUTES
	Route::post('/profile', [UserController::class, 'updateProfile']);

	// Financial Configuration Routes
	Route::prefix('financial-config')->group(function () {
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
