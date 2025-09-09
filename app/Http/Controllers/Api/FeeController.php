<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreFeeRequest;
use App\Http\Requests\UpdateFeeRequest;
use App\Services\FeeService;
use App\Services\MessageService;

class FeeController extends BaseController
{
    public function __construct(FeeService $feeService, MessageService $messageService)
    {
        parent::__construct($feeService, $messageService);
    }

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