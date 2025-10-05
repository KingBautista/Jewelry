<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\Services\TaxService;
use App\Services\MessageService;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Tax Management",
 *     description="Tax management endpoints"
 * )
 */
class TaxController extends BaseController
{
    public function __construct(TaxService $taxService, MessageService $messageService)
    {
        parent::__construct($taxService, $messageService);
    }

    /**
     * @OA\Post(
     *     path="/api/financial-management/taxes",
     *     summary="Create a new tax",
     *     description="Create a new tax rate configuration",
     *     tags={"Tax Management"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","rate"},
     *             @OA\Property(property="name", type="string", example="VAT"),
     *             @OA\Property(property="rate", type="number", format="float", example=15.00),
     *             @OA\Property(property="description", type="string", example="Value Added Tax"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tax created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="tax", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(StoreTaxRequest $request)
    {
        try {
            $data = $request->validated();
            $tax = $this->service->store($data);
            return response($tax, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Put(
     *     path="/api/financial-management/taxes/{id}",
     *     summary="Update a tax",
     *     description="Update an existing tax rate configuration",
     *     tags={"Tax Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Tax ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","rate"},
     *             @OA\Property(property="name", type="string", example="Updated VAT"),
     *             @OA\Property(property="rate", type="number", format="float", example=18.00),
     *             @OA\Property(property="description", type="string", example="Updated Value Added Tax"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tax updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="tax", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tax not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(UpdateTaxRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $tax = $this->service->update($data, $id);
            return response($tax, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/options/taxes",
     *     summary="Get active taxes for dropdown",
     *     description="Retrieve a list of active taxes for dropdown/select options",
     *     tags={"Tax Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active taxes retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="taxes", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="VAT"),
     *                 @OA\Property(property="rate", type="number", example=15.00),
     *                 @OA\Property(property="description", type="string", example="Value Added Tax")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     * Get active taxes for dropdown
     */
    public function getActiveTaxes()
    {
        try {
            $taxes = $this->service->getActiveTaxes();
            return response()->json($taxes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch taxes'], 500);
        }
    }
}