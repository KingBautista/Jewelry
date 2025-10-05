<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\UpdateDiscountRequest;
use App\Services\DiscountService;
use App\Services\MessageService;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Discounts",
 *     description="Discount management endpoints"
 * )
 */
class DiscountController extends BaseController
{
    public function __construct(DiscountService $discountService, MessageService $messageService)
    {
        parent::__construct($discountService, $messageService);
    }

    /**
     * @OA\Post(
     *     path="/api/financial-management/discounts",
     *     summary="Create a new discount",
     *     description="Create a new discount with code, type, and value",
     *     tags={"Discounts"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code","type","value","start_date","end_date"},
     *             @OA\Property(property="name", type="string", example="Summer Sale 2024"),
     *             @OA\Property(property="code", type="string", example="SUMMER2024"),
     *             @OA\Property(property="type", type="string", enum={"percentage","fixed"}, example="percentage"),
     *             @OA\Property(property="value", type="number", format="float", example=15.00),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-06-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2024-08-31"),
     *             @OA\Property(property="min_amount", type="number", format="float", example=100.00),
     *             @OA\Property(property="max_amount", type="number", format="float", example=1000.00),
     *             @OA\Property(property="usage_limit", type="integer", example=100),
     *             @OA\Property(property="description", type="string", example="Summer discount for all customers"),
     *             @OA\Property(property="active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Discount created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="discount", type="object")
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
    public function store(StoreDiscountRequest $request)
    {
        try {
            $data = $request->validated();
            $discount = $this->service->store($data);
            return response($discount, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Put(
     *     path="/api/financial-management/discounts/{id}",
     *     summary="Update a discount",
     *     description="Update an existing discount's information",
     *     tags={"Discounts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Discount ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code","type","value","start_date","end_date"},
     *             @OA\Property(property="name", type="string", example="Summer Sale 2024 Updated"),
     *             @OA\Property(property="code", type="string", example="SUMMER2024"),
     *             @OA\Property(property="type", type="string", enum={"percentage","fixed"}, example="percentage"),
     *             @OA\Property(property="value", type="number", format="float", example=20.00),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-06-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2024-08-31"),
     *             @OA\Property(property="min_amount", type="number", format="float", example=150.00),
     *             @OA\Property(property="max_amount", type="number", format="float", example=2000.00),
     *             @OA\Property(property="usage_limit", type="integer", example=200),
     *             @OA\Property(property="description", type="string", example="Updated summer discount"),
     *             @OA\Property(property="active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Discount updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="discount", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Discount not found"
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
    public function update(UpdateDiscountRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $discount = $this->service->update($data, $id);
            return response($discount, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/options/discounts",
     *     summary="Get active discounts for dropdown",
     *     description="Retrieve a list of active discounts for dropdown/select options",
     *     tags={"Discounts"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active discounts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="discounts", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Summer Sale 2024"),
     *                 @OA\Property(property="code", type="string", example="SUMMER2024"),
     *                 @OA\Property(property="type", type="string", example="percentage"),
     *                 @OA\Property(property="value", type="number", example=15.00)
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     * Get active discounts for dropdown
     */
    public function getActiveDiscounts()
    {
        try {
            $discounts = $this->service->getActiveDiscounts();
            return response()->json($discounts);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch discounts'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/financial-management/discounts/validate-code",
     *     summary="Validate discount code",
     *     description="Check if a discount code is valid and get its details",
     *     tags={"Discounts"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="SUMMER2024")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Discount code is valid",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=true),
     *             @OA\Property(property="discount", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Summer Sale 2024"),
     *                 @OA\Property(property="code", type="string", example="SUMMER2024"),
     *                 @OA\Property(property="type", type="string", example="percentage"),
     *                 @OA\Property(property="value", type="number", example=15.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Discount code is invalid or expired",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Discount code is invalid or expired")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     * Validate discount code
     */
    public function validateDiscountCode(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string'
            ]);

            $result = $this->service->validateDiscountCode($request->code);
            
            if ($result['valid']) {
                return response()->json([
                    'valid' => true,
                    'discount' => $result['discount']
                ]);
            } else {
                return response()->json([
                    'valid' => false,
                    'message' => $result['message']
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to validate discount code'], 500);
        }
    }
}