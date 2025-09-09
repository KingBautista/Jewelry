<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\UpdateDiscountRequest;
use App\Services\DiscountService;
use App\Services\MessageService;

class DiscountController extends BaseController
{
    public function __construct(DiscountService $discountService, MessageService $messageService)
    {
        parent::__construct($discountService, $messageService);
    }

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