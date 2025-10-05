<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\MessageService;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Dashboard analytics and statistics endpoints"
 * )
 */
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
     * @OA\Get(
     *     path="/api/dashboard/overview",
     *     summary="Get dashboard overview data",
     *     description="Retrieve comprehensive dashboard statistics and metrics",
     *     tags={"Dashboard"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard overview data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_revenue", type="number", format="float", example=125000.00),
     *             @OA\Property(property="total_customers", type="integer", example=150),
     *             @OA\Property(property="total_invoices", type="integer", example=75),
     *             @OA\Property(property="pending_payments", type="integer", example=12),
     *             @OA\Property(property="recent_activities", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
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