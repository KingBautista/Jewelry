<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentTermRequest;
use App\Http\Requests\UpdatePaymentTermRequest;
use App\Services\PaymentTermService;
use App\Services\MessageService;

class PaymentTermController extends BaseController
{
    public function __construct(PaymentTermService $paymentTermService, MessageService $messageService)
    {
        parent::__construct($paymentTermService, $messageService);
    }

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