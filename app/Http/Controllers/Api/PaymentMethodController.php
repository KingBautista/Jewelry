<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Services\PaymentMethodService;
use App\Services\MessageService;

class PaymentMethodController extends BaseController
{
    public function __construct(PaymentMethodService $paymentMethodService, MessageService $messageService)
    {
        parent::__construct($paymentMethodService, $messageService);
    }

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