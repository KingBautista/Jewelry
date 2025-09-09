<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\Services\TaxService;
use App\Services\MessageService;

class TaxController extends BaseController
{
    public function __construct(TaxService $taxService, MessageService $messageService)
    {
        parent::__construct($taxService, $messageService);
    }

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