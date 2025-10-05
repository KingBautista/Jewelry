<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreFeeRequest;
use App\Http\Requests\UpdateFeeRequest;
use App\Services\FeeService;
use App\Services\MessageService;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Fees",
 *     description="Fee management endpoints"
 * )
 */
class FeeController extends BaseController
{
    public function __construct(FeeService $feeService, MessageService $messageService)
    {
        parent::__construct($feeService, $messageService);
    }

    /**
     * @OA\Get(
     *     path="/api/financial-management/fees",
     *     summary="Get all fees",
     *     description="Retrieve a paginated list of all fees",
     *     tags={"Fees"},
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
     *         description="Fees retrieved successfully",
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
     *     path="/api/financial-management/fees/{id}",
     *     summary="Get a specific fee",
     *     description="Retrieve detailed information about a specific fee",
     *     tags={"Fees"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Fee ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fee retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="fee", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fee not found"
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
     *     path="/api/financial-management/fees/{id}",
     *     summary="Delete a fee",
     *     description="Move a fee to trash (soft delete)",
     *     tags={"Fees"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Fee ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fee moved to trash successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fee not found"
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
     *     path="/api/financial-management/fees",
     *     summary="Create a new fee",
     *     description="Create a new fee configuration",
     *     tags={"Fees"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","amount","type"},
     *             @OA\Property(property="name", type="string", example="Processing Fee"),
     *             @OA\Property(property="amount", type="number", format="float", example=25.00),
     *             @OA\Property(property="type", type="string", enum={"fixed","percentage"}, example="fixed"),
     *             @OA\Property(property="description", type="string", example="Processing fee for all transactions"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Fee created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="fee", type="object")
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
    public function store(StoreFeeRequest $request)
    {
        try {
            $data = $request->validated();
            $fee = $this->service->store($data);
            return response($fee, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Put(
     *     path="/api/financial-management/fees/{id}",
     *     summary="Update a fee",
     *     description="Update an existing fee configuration",
     *     tags={"Fees"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Fee ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","amount","type"},
     *             @OA\Property(property="name", type="string", example="Updated Processing Fee"),
     *             @OA\Property(property="amount", type="number", format="float", example=30.00),
     *             @OA\Property(property="type", type="string", enum={"fixed","percentage"}, example="fixed"),
     *             @OA\Property(property="description", type="string", example="Updated processing fee"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fee updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="fee", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fee not found"
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
    public function update(UpdateFeeRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $fee = $this->service->update($data, $id);
            return response($fee, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/options/fees",
     *     summary="Get active fees for dropdown",
     *     description="Retrieve a list of active fees for dropdown/select options",
     *     tags={"Fees"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active fees retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="fees", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Processing Fee"),
     *                 @OA\Property(property="amount", type="number", example=25.00),
     *                 @OA\Property(property="type", type="string", example="fixed")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     * Get active fees for dropdown
     */
    public function getActiveFees()
    {
        try {
            $fees = $this->service->getActiveFees();
            return response()->json($fees);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch fees'], 500);
        }
    }
}