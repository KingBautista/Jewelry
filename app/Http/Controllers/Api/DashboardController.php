<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\MessageService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $service;
    protected $messageService;

    public function __construct(DashboardService $service, MessageService $messageService)
    {
        $this->service = $service;
        $this->messageService = $messageService;
    }

    /**
     * Get dashboard overview data
     */
    public function overview()
    {
        try {
            $data = $this->service->getOverviewData();
            return response($data, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Get revenue data for charts
     */
    public function revenue()
    {
        try {
            $data = $this->service->getRevenueData();
            return response($data, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Get customer summary data
     */
    public function customers()
    {
        try {
            $data = $this->service->getCustomerSummary();
            return response($data, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Get item status summary
     */
    public function itemStatus()
    {
        try {
            $data = $this->service->getItemStatusSummary();
            return response($data, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Get payment breakdown data
     */
    public function paymentBreakdown()
    {
        try {
            $data = $this->service->getPaymentBreakdown();
            return response($data, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }
}