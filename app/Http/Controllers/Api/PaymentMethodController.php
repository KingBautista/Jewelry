<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Services\PaymentMethodService;
use App\Services\MessageService;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Payment Methods",
 *     description="Payment method management endpoints"
 * )
 */
class PaymentMethodController extends BaseController
{
    public function __construct(PaymentMethodService $paymentMethodService, MessageService $messageService)
    {
        parent::__construct($paymentMethodService, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/financial-management/payment-methods",
     *     summary="Get all payment methods",
     *     description="Retrieve a paginated list of all payment methods",
     *     tags={"Payment Methods"},
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
     *         description="Payment methods retrieved successfully",
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
     *     path="/api/financial-management/payment-methods/{id}",
     *     summary="Get a specific payment method",
     *     description="Retrieve detailed information about a specific payment method",
     *     tags={"Payment Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment Method ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment method retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_method", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment method not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show($id)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/financial-management/payment-methods/{id}",
     *     summary="Delete a payment method",
     *     description="Move a payment method to trash (soft delete)",
     *     tags={"Payment Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment Method ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment method moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment method not found"
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
     *     path="/api/financial-management/payment-methods",
     *     summary="Create a new payment method",
     *     description="Create a new payment method configuration",
     *     tags={"Payment Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","type"},
     *             @OA\Property(property="name", type="string", example="Credit Card"),
     *             @OA\Property(property="type", type="string", example="card"),
     *             @OA\Property(property="description", type="string", example="Credit card payments"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="requires_receipt", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment method created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_method", type="object")
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
    public function store(StorePaymentMethodRequest $request)
    {
        try {
            $data = $request->validated();
            $paymentMethod = $this->service->store($data);
            return response($paymentMethod, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Put(
     *     path="/api/financial-management/payment-methods/{id}",
     *     summary="Update a payment method",
     *     description="Update an existing payment method configuration",
     *     tags={"Payment Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment Method ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","type"},
     *             @OA\Property(property="name", type="string", example="Updated Credit Card"),
     *             @OA\Property(property="type", type="string", example="card"),
     *             @OA\Property(property="description", type="string", example="Updated credit card payments"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="requires_receipt", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment method updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_method", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment method not found"
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
    public function update(UpdatePaymentMethodRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $paymentMethod = $this->service->update($data, $id);
            return response($paymentMethod, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/options/payment-methods",
     *     summary="Get active payment methods for dropdown",
     *     description="Retrieve a list of active payment methods for dropdown/select options",
     *     tags={"Payment Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active payment methods retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_methods", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Credit Card"),
     *                 @OA\Property(property="type", type="string", example="card"),
     *                 @OA\Property(property="description", type="string", example="Credit card payments")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     * Get active payment methods for dropdown
     */
    public function getActivePaymentMethods()
    {
        try {
            $paymentMethods = $this->service->getActivePaymentMethods();
            return response()->json($paymentMethods);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch payment methods'], 500);
        }
    }
}