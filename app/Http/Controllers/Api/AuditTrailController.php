<?php

namespace App\Http\Controllers\Api;

use App\Traits\Auditable;
use App\Services\AuditTrailService;
use App\Services\MessageService;
use Illuminate\Http\Request;

class AuditTrailController extends BaseController
{
    use Auditable;

    protected $auditService;

    public function __construct(AuditTrailService $auditService, MessageService $messageService)
    {
        $this->auditService = $auditService;
        parent::__construct($auditService, $messageService);
    }

    /**
     * Export audit trail data.
     */
    public function export(Request $request)
    {
        try {
            $format = $request->format ?? 'csv';
            $filters = $request->only([
                'search', 'module', 'action', 'user_id', 
                'start_date', 'end_date'
            ]);

            $this->logExport("Exported audit trail as {$format}", $format, 0);
            
            return $this->auditService->exportAuditTrail($filters, $format);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Get available audit trail modules.
     */
    public function modules()
    {
        try {
            $modules = $this->auditService->getModules();
            return response()->json($modules);
        } catch (\Exception $e) {
            \Log::error('Get modules error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch modules'], 500);
        }
    }

    /**
     * Get available audit trail actions.
     */
    public function actions()
    {
        try {
            $actions = $this->auditService->getActions();
            return response()->json($actions);
        } catch (\Exception $e) {
            \Log::error('Get actions error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch actions'], 500);
        }
    }

    /**
     * Get audit trail statistics.
     */
    public function stats(Request $request)
    {
        try {
            $filters = $request->only([
                'search', 'module', 'action', 'user_id', 
                'start_date', 'end_date'
            ]);

            $stats = $this->auditService->getStatistics($filters);
            
            $this->logAudit('VIEW', 'Viewed audit trail statistics');
            
            return response()->json($stats);
        } catch (\Exception $e) {
            return $this->messageService->responseError();
        }
    }

    /**
     * Get the module name for audit trail.
     */
    protected function getModuleName()
    {
        return 'Audit Trail';
    }
}