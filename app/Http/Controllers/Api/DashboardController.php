<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\MessageService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;
    protected $messageService;

    public function __construct(DashboardService $dashboardService, MessageService $messageService)
    {
        $this->dashboardService = $dashboardService;
        $this->messageService = $messageService;
    }

    /**
     * Get dashboard statistics
     */
    public function statistics()
    {
        try {
            $stats = $this->dashboardService->getStatistics();
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }
}
