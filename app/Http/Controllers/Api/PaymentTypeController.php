<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentTypeRequest;
use App\Http\Requests\UpdatePaymentTypeRequest;
use App\Services\PaymentTypeService;
use App\Services\MessageService;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Payment Type Management",
 *     description="Payment type management endpoints"
 * )
 */
class PaymentTypeController extends BaseController
{
    public function __construct(PaymentTypeService $paymentTypeService, MessageService $messageService)
    {
        parent::__construct($paymentTypeService, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/financial-management/payment-types",
     *     summary="Get all payment types",
     *     description="Retrieve a paginated list of all payment types",
     *     tags={"Payment Type Management"},
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
     *         description="Payment types retrieved successfully",
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
     *     path="/api/financial-management/payment-types/{id}",
     *     summary="Get a specific payment type",
     *     description="Retrieve detailed information about a specific payment type",
     *     tags={"Payment Type Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment type ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment type retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_type", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment type not found"
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
     *     path="/api/financial-management/payment-types/{id}",
     *     summary="Delete a payment type",
     *     description="Move a payment type to trash (soft delete)",
     *     tags={"Payment Type Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment type ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment type moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment type not found"
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
     *     path="/api/financial-management/payment-types",
     *     summary="Create a new payment type",
     *     description="Create a new payment type configuration",
     *     tags={"Payment Type Management"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code"},
     *             @OA\Property(property="name", type="string", example="Credit Card"),
     *             @OA\Property(property="code", type="string", example="CREDIT_CARD"),
     *             @OA\Property(property="description", type="string", example="Payment via credit card"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment type created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_type", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(StorePaymentTypeRequest $request)
    {
        try {
            $data = $request->validated();
            $paymentType = $this->service->store($data);
            return response($paymentType, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Put(
     *     path="/api/financial-management/payment-types/{id}",
     *     summary="Update a payment type",
     *     description="Update an existing payment type configuration",
     *     tags={"Payment Type Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment type ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code"},
     *             @OA\Property(property="name", type="string", example="Updated Credit Card"),
     *             @OA\Property(property="code", type="string", example="CREDIT_CARD"),
     *             @OA\Property(property="description", type="string", example="Updated payment via credit card"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment type updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_type", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment type not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(UpdatePaymentTypeRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $paymentType = $this->service->update($data, $id);
            return response($paymentType, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/options/payment-types",
     *     summary="Get active payment types for dropdown",
     *     description="Retrieve a list of active payment types for dropdown/select options",
     *     tags={"Payment Type Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active payment types retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_types", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Credit Card"),
     *                 @OA\Property(property="code", type="string", example="CREDIT_CARD"),
     *                 @OA\Property(property="description", type="string", example="Payment via credit card")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     * Get active payment types for dropdown
     */
    public function getActivePaymentTypes()
    {
        try {
            $paymentTypes = $this->service->getActivePaymentTypes();
            return response()->json($paymentTypes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch payment types'], 500);
        }
    }
}
