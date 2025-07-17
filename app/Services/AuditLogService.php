<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * Log an action
     */
    public function log(
        string $action,
        string $module,
        string $description,
        Model $auditable = null,
        array $oldValues = null,
        array $newValues = null,
        array $metadata = []
    ): AuditLog {
        $user = Auth::guard('portal')->user() ?: Auth::guard('web')->user();
        
        if (!$user) {
            throw new \Exception('No authenticated user for audit log');
        }

        $riskLevel = AuditLog::RISK_LEVELS[$action] ?? 'low';

        return AuditLog::create([
            'company_id' => $user->company_id ?? $user->company->id,
            'user_id' => $user->id,
            'user_type' => get_class($user),
            'user_name' => $user->name,
            'user_email' => $user->email,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'auditable_id' => $auditable?->id,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'risk_level' => $riskLevel,
        ]);
    }

    /**
     * Log data export
     */
    public function logExport(string $type, string $module, array $exportedFields, Model $model = null): void
    {
        $this->log(
            action: "export_{$type}",
            module: $module,
            description: "Exported {$type} from {$module}",
            auditable: $model,
            metadata: [
                'exported_fields' => $exportedFields,
                'record_count' => $model ? 1 : null,
                'export_format' => $type,
            ]
        );
    }

    /**
     * Log permission change
     */
    public function logPermissionChange(
        string $action,
        Model $targetUser,
        array $permissions,
        string $reason = null
    ): void {
        $this->log(
            action: $action,
            module: 'permissions',
            description: "{$action} for user {$targetUser->name}",
            auditable: $targetUser,
            oldValues: $action === 'revoke_permission' ? $permissions : null,
            newValues: $action === 'grant_permission' ? $permissions : null,
            metadata: [
                'reason' => $reason,
                'permission_count' => count($permissions),
            ]
        );
    }

    /**
     * Log billing access
     */
    public function logBillingAccess(string $specificAction, Model $billingRecord = null): void
    {
        $this->log(
            action: $specificAction,
            module: 'billing',
            description: "Accessed billing: {$specificAction}",
            auditable: $billingRecord,
            metadata: [
                'accessed_at' => now()->toIso8601String(),
            ]
        );
    }

    /**
     * Get audit logs for company
     */
    public function getCompanyLogs($companyId, array $filters = [])
    {
        $query = AuditLog::where('company_id', $companyId)
            ->with(['user', 'auditable']);

        if (isset($filters['module'])) {
            $query->forModule($filters['module']);
        }

        if (isset($filters['risk_level'])) {
            if ($filters['risk_level'] === 'high_and_critical') {
                $query->highRisk();
            } else {
                $query->where('risk_level', $filters['risk_level']);
            }
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->dateRange($filters['date_from'], $filters['date_to']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Get user activity summary
     */
    public function getUserActivitySummary($userId, $userType, $days = 30)
    {
        return AuditLog::where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('created_at', '>=', now()->subDays($days))
            ->select('action', 'module', \DB::raw('count(*) as count'))
            ->groupBy('action', 'module')
            ->get();
    }
}