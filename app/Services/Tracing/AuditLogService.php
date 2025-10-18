<?php

namespace App\Services\Tracing;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Audit Log Service - Compliance and regulatory logging
 *
 * Records all significant system actions for:
 * - Regulatory compliance (GDPR, HIPAA, SOC2)
 * - Security audits
 * - Debugging and troubleshooting
 * - Forensics and incident investigation
 *
 * Audit log captures:
 * - Who: User/system making the change
 * - What: The action performed
 * - When: Timestamp
 * - Where: Service/endpoint
 * - Why: Reason/correlation ID
 * - How: Success/failure and details
 */
class AuditLogService
{
    /**
     * Log action to audit trail
     *
     * @param string $action Action name
     * @param string $resourceType Type of resource being audited
     * @param mixed $resourceId ID of resource
     * @param array $details Action details
     * @param string $status Status (success/failure)
     * @param ?string $failureReason Reason if failed
     */
    public static function logAction(
        string $action,
        string $resourceType,
        mixed $resourceId,
        array $details = [],
        string $status = 'success',
        ?string $failureReason = null
    ): void {
        try {
            $auditLog = [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'company_id' => company_scope(),
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'details' => json_encode($details),
                'status' => $status,
                'failure_reason' => $failureReason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'correlation_id' => app(RequestCorrelationService::class)->getId(),
                'http_method' => request()->method(),
                'http_path' => request()->path(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            // Store in audit logs table
            DB::table('audit_logs')->insert($auditLog);

            // Also log to system logs
            $logMessage = "{$action} on {$resourceType}:{$resourceId} - {$status}";
            if ($status === 'failure') {
                Log::warning("AUDIT: {$logMessage}", [
                    'user_id' => $auditLog['user_id'],
                    'reason' => $failureReason,
                ]);
            } else {
                Log::info("AUDIT: {$logMessage}", [
                    'user_id' => $auditLog['user_id'],
                ]);
            }

        } catch (Exception $e) {
            Log::error("Failed to log audit action", [
                'error' => $e->getMessage(),
                'action' => $action,
            ]);
        }
    }

    /**
     * Log data access
     *
     * @param string $resourceType Type of resource accessed
     * @param mixed $resourceId ID of resource
     * @param string $accessType Type of access (read, download, export)
     */
    public static function logDataAccess(
        string $resourceType,
        mixed $resourceId,
        string $accessType = 'read'
    ): void {
        self::logAction(
            "DATA_ACCESS_{$accessType}",
            $resourceType,
            $resourceId,
            ['access_type' => $accessType]
        );
    }

    /**
     * Log data modification
     *
     * @param string $resourceType Type of resource modified
     * @param mixed $resourceId ID of resource
     * @param array $changes Changes made (before/after)
     * @param string $action Action type (create/update/delete)
     */
    public static function logDataModification(
        string $resourceType,
        mixed $resourceId,
        array $changes = [],
        string $action = 'update'
    ): void {
        self::logAction(
            strtoupper($action),
            $resourceType,
            $resourceId,
            ['changes' => $changes]
        );
    }

    /**
     * Log authentication event
     *
     * @param string $eventType Type of auth event (login, logout, mfa, etc.)
     * @param bool $success Whether successful
     * @param ?string $reason Reason if failed
     */
    public static function logAuthEvent(
        string $eventType,
        bool $success = true,
        ?string $reason = null
    ): void {
        self::logAction(
            "AUTH_{$eventType}",
            'user',
            auth()->id() ?? 'unknown',
            ['event_type' => $eventType],
            $success ? 'success' : 'failure',
            $reason
        );
    }

    /**
     * Log permission change
     *
     * @param string $userId User affected
     * @param string $permission Permission changed
     * @param bool $granted Whether permission was granted or revoked
     */
    public static function logPermissionChange(
        string $userId,
        string $permission,
        bool $granted = true
    ): void {
        self::logAction(
            $granted ? 'PERMISSION_GRANTED' : 'PERMISSION_REVOKED',
            'user',
            $userId,
            ['permission' => $permission, 'granted' => $granted]
        );
    }

    /**
     * Log security event
     *
     * @param string $eventType Type of security event
     * @param string $severity Severity level (low, medium, high, critical)
     * @param array $details Event details
     */
    public static function logSecurityEvent(
        string $eventType,
        string $severity = 'high',
        array $details = []
    ): void {
        $details['severity'] = $severity;

        self::logAction(
            "SECURITY_{$eventType}",
            'system',
            'security',
            $details,
            'logged',
            null
        );

        // For critical security events, also alert
        if ($severity === 'critical') {
            Log::alert("SECURITY ALERT: {$eventType}", $details);
        }
    }

    /**
     * Log API call
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param int $statusCode Response status code
     * @param int $responseTime Response time in ms
     * @param array $details Additional details
     */
    public static function logApiCall(
        string $endpoint,
        string $method,
        int $statusCode,
        int $responseTime,
        array $details = []
    ): void {
        $details['response_time_ms'] = $responseTime;
        $details['status_code'] = $statusCode;

        self::logAction(
            "API_{$method}",
            'api_endpoint',
            $endpoint,
            $details,
            $statusCode < 400 ? 'success' : 'failure'
        );
    }

    /**
     * Get audit logs for resource
     *
     * @param string $resourceType Resource type
     * @param mixed $resourceId Resource ID
     * @param int $days Number of days to look back
     * @return array Audit logs
     */
    public static function getResourceAuditLog(
        string $resourceType,
        mixed $resourceId,
        int $days = 30
    ): array {
        try {
            return DB::table('audit_logs')
                ->where('resource_type', $resourceType)
                ->where('resource_id', $resourceId)
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->orderByDesc('created_at')
                ->get()
                ->map(fn($log) => [
                    'action' => $log->action,
                    'status' => $log->status,
                    'user_email' => $log->user_email,
                    'details' => json_decode($log->details, true),
                    'timestamp' => $log->created_at,
                ])
                ->toArray();

        } catch (Exception $e) {
            Log::warning("Failed to get audit log", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get user audit log
     *
     * @param string $userId User ID
     * @param int $limit Number of entries to return
     * @return array User's audit log
     */
    public static function getUserAuditLog(string $userId, int $limit = 100): array
    {
        try {
            return DB::table('audit_logs')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(fn($log) => [
                    'action' => $log->action,
                    'resource_type' => $log->resource_type,
                    'resource_id' => $log->resource_id,
                    'status' => $log->status,
                    'timestamp' => $log->created_at,
                ])
                ->toArray();

        } catch (Exception $e) {
            Log::warning("Failed to get user audit log", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get audit logs by action
     *
     * @param string $action Action name
     * @param int $days Days to look back
     * @return array Matching audit logs
     */
    public static function getLogsByAction(string $action, int $days = 7): array
    {
        try {
            return DB::table('audit_logs')
                ->where('action', $action)
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->orderByDesc('created_at')
                ->get()
                ->toArray();

        } catch (Exception $e) {
            Log::warning("Failed to get logs by action", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get failed actions
     *
     * @param int $days Days to look back
     * @param int $limit Number of entries
     * @return array Failed audit logs
     */
    public static function getFailedActions(int $days = 7, int $limit = 100): array
    {
        try {
            return DB::table('audit_logs')
                ->where('status', 'failure')
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->toArray();

        } catch (Exception $e) {
            Log::warning("Failed to get failed actions", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Search audit logs
     *
     * @param array $criteria Search criteria
     * @return array Matching audit logs
     */
    public static function search(array $criteria): array
    {
        try {
            $query = DB::table('audit_logs');

            if (isset($criteria['user_id'])) {
                $query->where('user_id', $criteria['user_id']);
            }

            if (isset($criteria['action'])) {
                $query->where('action', 'like', "%{$criteria['action']}%");
            }

            if (isset($criteria['resource_type'])) {
                $query->where('resource_type', $criteria['resource_type']);
            }

            if (isset($criteria['status'])) {
                $query->where('status', $criteria['status']);
            }

            if (isset($criteria['start_date'])) {
                $query->where('created_at', '>=', $criteria['start_date']);
            }

            if (isset($criteria['end_date'])) {
                $query->where('created_at', '<=', $criteria['end_date']);
            }

            if (isset($criteria['company_id'])) {
                $query->where('company_id', $criteria['company_id']);
            }

            return $query->orderByDesc('created_at')
                ->limit($criteria['limit'] ?? 100)
                ->get()
                ->toArray();

        } catch (Exception $e) {
            Log::warning("Failed to search audit logs", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get audit statistics
     *
     * @param int $days Days to analyze
     * @return array Statistics
     */
    public static function getStatistics(int $days = 30): array
    {
        try {
            $logsCount = DB::table('audit_logs')
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->count();

            $successCount = DB::table('audit_logs')
                ->where('status', 'success')
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->count();

            $failureCount = DB::table('audit_logs')
                ->where('status', 'failure')
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->count();

            $topActions = DB::table('audit_logs')
                ->selectRaw('action, COUNT(*) as count')
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            return [
                'period_days' => $days,
                'total_logs' => $logsCount,
                'successful_actions' => $successCount,
                'failed_actions' => $failureCount,
                'success_rate' => $logsCount > 0 ? round(($successCount / $logsCount) * 100, 2) : 0,
                'top_actions' => $topActions->toArray(),
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get audit statistics", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Generate audit report
     *
     * @param array $criteria Report criteria
     * @return array Audit report
     */
    public static function generateReport(array $criteria): array
    {
        try {
            $logs = self::search($criteria);
            $stats = self::getStatistics($criteria['days'] ?? 30);

            return [
                'report_generated_at' => Carbon::now()->toIso8601String(),
                'criteria' => $criteria,
                'statistics' => $stats,
                'logs' => $logs,
                'log_count' => count($logs),
            ];

        } catch (Exception $e) {
            Log::warning("Failed to generate audit report", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
