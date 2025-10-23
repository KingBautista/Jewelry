<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentTermRequest;
use App\Http\Requests\UpdatePaymentTermRequest;
use App\Services\PaymentTermService;
use App\Services\MessageService;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Payment Terms",
 *     description="Payment term management endpoints"
 * )
 */
class PaymentTermController extends BaseController
{
    public function __construct(PaymentTermService $paymentTermService, MessageService $messageService)
    {
        parent::__construct($paymentTermService, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/financial-management/payment-terms",
     *     summary="Get all payment terms",
     *     description="Retrieve a paginated list of all payment terms",
     *     tags={"Payment Terms"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment terms retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index()
    {
        return parent::index();
    }

    /**
     * @OA\Get(
     *     path="/api/financial-management/payment-terms/{id}",
     *     summary="Get a specific payment term",
     *     description="Retrieve detailed information about a specific payment term",
     *     tags={"Payment Terms"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment Term ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment term retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_term", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment term not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show($id, $withOutResource = false)
    {
        return parent::show($id, true);
    }

    /**
     * @OA\Delete(
     *     path="/api/financial-management/payment-terms/{id}",
     *     summary="Delete a payment term",
     *     description="Move a payment term to trash (soft delete)",
     *     tags={"Payment Terms"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment Term ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment term moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment term not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function destroy($id)
    {
        return parent::destroy($id);
    }

    /**
     * @OA\Post(
     *     path="/api/financial-management/payment-terms",
     *     summary="Create a new payment term",
     *     description="Create a new payment term with schedules",
     *     tags={"Payment Terms"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","description"},
     *             @OA\Property(property="name", type="string", example="Monthly Payment"),
     *             @OA\Property(property="description", type="string", example="Monthly payment schedule"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="schedules", type="array", @OA\Items(
     *                 @OA\Property(property="payment_order", type="integer", example=1),
     *                 @OA\Property(property="percentage", type="number", format="float", example=50.00),
     *                 @OA\Property(property="days_from_invoice", type="integer", example=30),
     *                 @OA\Property(property="description", type="string", example="First payment")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment term created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_term", type="object")
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
     */
    public function store(StorePaymentTermRequest $request)
    {
        try {
            $data = $request->validated();
            $schedules = $data['schedules'] ?? [];
            unset($data['schedules']);
            
            $paymentTerm = $this->service->storeWithSchedules($data, $schedules);
            return response($paymentTerm, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Put(
     *     path="/api/financial-management/payment-terms/{id}",
     *     summary="Update a payment term",
     *     description="Update an existing payment term with schedules",
     *     tags={"Payment Terms"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment Term ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","description"},
     *             @OA\Property(property="name", type="string", example="Updated Monthly Payment"),
     *             @OA\Property(property="description", type="string", example="Updated monthly payment schedule"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="schedules", type="array", @OA\Items(
     *                 @OA\Property(property="payment_order", type="integer", example=1),
     *                 @OA\Property(property="percentage", type="number", format="float", example=60.00),
     *                 @OA\Property(property="days_from_invoice", type="integer", example=30),
     *                 @OA\Property(property="description", type="string", example="Updated first payment")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment term updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_term", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment term not found"
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
     */
    public function update(UpdatePaymentTermRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $schedules = $data['schedules'] ?? [];
            unset($data['schedules']);
            
            $paymentTerm = $this->service->updateWithSchedules($data, $id, $schedules);
            return response($paymentTerm, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/options/payment-terms",
     *     summary="Get active payment terms for dropdown",
     *     description="Retrieve a list of active payment terms for dropdown/select options",
     *     tags={"Payment Terms"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active payment terms retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_terms", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Monthly Payment"),
     *                 @OA\Property(property="description", type="string", example="Monthly payment schedule")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     * Get active payment terms for dropdown
     */
    public function getActivePaymentTerms()
    {
        try {
            $paymentTerms = $this->service->getActivePaymentTerms();
            return response()->json($paymentTerms);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch payment terms'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/financial-management/payment-terms/generate-equal-schedule",
     *     summary="Generate equal payment schedule",
     *     description="Generate equal monthly payment schedule based on term months and percentage",
     *     tags={"Payment Terms"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"term_months","remaining_percentage"},
     *             @OA\Property(property="term_months", type="integer", example=6, description="Number of months for payment term"),
     *             @OA\Property(property="remaining_percentage", type="number", format="float", example=100.00, description="Remaining percentage to be paid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Equal payment schedule generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="schedules", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string", example="Generated 6 equal monthly payments of 16.67% each")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid parameters"
     *     )
     * )
     * Generate equal monthly payment schedule
     */
    public function generateEqualSchedule(Request $request)
    {
        try {
            $request->validate([
                'term_months' => 'required|integer|min:1|max:60',
                'remaining_percentage' => 'required|numeric|min:0|max:100'
            ]);

            $schedules = $this->service->generateEqualSchedule(
                $request->term_months,
                $request->remaining_percentage
            );

            return response()->json([
                'success' => true,
                'schedules' => $schedules,
                'message' => "Generated {$request->term_months} equal monthly payments of " . 
                           round($request->remaining_percentage / $request->term_months, 2) . "% each"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/financial-management/payment-terms/{id}/validate-completeness",
     *     summary="Validate payment term completeness",
     *     description="Validate if a payment term has complete and valid schedules",
     *     tags={"Payment Terms"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment Term ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment term validation completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="validation", type="object",
     *                 @OA\Property(property="is_complete", type="boolean", example=true),
     *                 @OA\Property(property="total_percentage", type="number", example=100.00),
     *                 @OA\Property(property="missing_percentage", type="number", example=0.00),
     *                 @OA\Property(property="issues", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid payment term"
     *     )
     * )
     * Validate payment term completeness
     */
    public function validateCompleteness(int $id)
    {
        try {
            $paymentTerm = $this->service->model::with('schedules')->findOrFail($id);
            $validation = $this->service->validateCompleteness($paymentTerm);
            
            return response()->json([
                'success' => true,
                'validation' => $validation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}