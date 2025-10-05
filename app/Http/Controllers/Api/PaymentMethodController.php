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