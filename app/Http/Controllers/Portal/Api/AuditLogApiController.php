<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use App\Services\AuditLogService;
use App\Models\AuditLog;

class AuditLogApiController extends BaseApiController
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Get audit logs
     */
    public function index(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if (!$user->hasPermission('audit.view')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $filters = $request->validate([
            'module' => 'nullable|string',
            'risk_level' => 'nullable|string',
            'action' => 'nullable|string',
            'user_id' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $logs = $this->auditLogService->getCompanyLogs(
            $company->id,
            $filters
        );

        // Transform logs for frontend
        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'user_name' => $log->user_name,
                'user_email' => $log->user_email,
                'action' => $log->action,
                'action_name' => $log->action_name,
                'module' => $log->module,
                'description' => $log->description,
                'risk_level' => $log->risk_level,
                'risk_color' => $log->risk_color,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->format('d.m.Y H:i:s'),
                'metadata' => $log->metadata,
            ];
        });

        return response()->json([
            'logs' => $logs,
            'available_actions' => AuditLog::ACTIONS,
            'modules' => ['calls', 'billing', 'customers', 'team', 'settings', 'permissions'],
        ]);
    }

    /**
     * Get user activity summary
     */
    public function userActivity(Request $request, $userId)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if (!$user->hasPermission('audit.view')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $days = $request->get('days', 30);
        $userType = $request->get('user_type', get_class($user));

        $summary = $this->auditLogService->getUserActivitySummary($userId, $userType, $days);

        return response()->json([
            'user_id' => $userId,
            'days' => $days,
            'activity_summary' => $summary,
        ]);
    }

    /**
     * Export audit logs
     */
    public function export(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if (!$user->hasPermission('audit.export')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Log the export action itself
        $this->auditLogService->log(
            action: 'export_audit_logs',
            module: 'audit',
            description: 'Exported audit logs',
            metadata: [
                'filters' => $request->except(['fields']),
                'exported_fields' => $request->get('fields', []),
            ]
        );

        // Get logs with filters
        $filters = $request->validate([
            'module' => 'nullable|string',
            'risk_level' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $query = AuditLog::where('company_id', $company->id);

        if (isset($filters['module'])) {
            $query->forModule($filters['module']);
        }

        if (isset($filters['risk_level'])) {
            $query->where('risk_level', $filters['risk_level']);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->dateRange($filters['date_from'], $filters['date_to']);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        // Prepare CSV data
        $csvData = [];
        $csvData[] = ['Datum', 'Uhrzeit', 'Benutzer', 'E-Mail', 'Aktion', 'Modul', 'Beschreibung', 'Risiko', 'IP-Adresse'];

        foreach ($logs as $log) {
            $csvData[] = [
                $log->created_at->format('d.m.Y'),
                $log->created_at->format('H:i:s'),
                $log->user_name,
                $log->user_email,
                $log->action_name,
                $log->module,
                $log->description,
                $log->risk_level,
                $log->ip_address,
            ];
        }

        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';
        
        return response()->streamDownload(function () use ($csvData) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            
            foreach ($csvData as $row) {
                fputcsv($file, $row, ';');
            }
            
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Log an export action
     */
    public function logExport(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:csv,pdf',
            'module' => 'required|string',
            'fields' => 'required|array',
            'call_id' => 'nullable|integer',
        ]);

        // Log the export
        app(AuditLogService::class)->logExport(
            $validated['type'],
            $validated['module'],
            $validated['fields'],
            $validated['call_id'] ? \App\Models\Call::find($validated['call_id']) : null
        );

        return response()->json(['success' => true]);
    }
}