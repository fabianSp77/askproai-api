# Security & Audit Trail Guide

## Overview

The Security & Audit system provides comprehensive tracking, compliance features, and security controls for the Business Portal. It includes audit logging, role-based permissions, two-factor authentication, and compliance reporting.

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                Security Dashboard                        │
│  (Audit Logs, Access Control, Compliance Reports)      │
├─────────────────────────────────────────────────────────┤
│              Security Service Layer                      │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │Audit Logger │  │Permission    │  │2FA Service   │ │
│  │            │  │Manager       │  │              │ │
│  └─────────────┘  └──────────────┘  └──────────────┘ │
├─────────────────────────────────────────────────────────┤
│                  Data Layer                              │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │audit_logs   │  │portal_       │  │portal_       │ │
│  │             │  │permissions   │  │sessions      │ │
│  └─────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────┘
```

## Database Schema

### audit_logs
```sql
CREATE TABLE audit_logs (
    id bigint PRIMARY KEY,
    company_id bigint NOT NULL,
    user_id bigint,
    user_type varchar(50), -- portal_user, admin, system
    action varchar(255) NOT NULL,
    action_type enum('create','read','update','delete','login','export'),
    model_type varchar(255),
    model_id bigint,
    old_values json,
    new_values json,
    changes json,
    ip_address varchar(45),
    user_agent text,
    session_id varchar(255),
    correlation_id varchar(36), -- UUID for tracking related actions
    risk_level enum('low','medium','high'),
    metadata json,
    created_at timestamp,
    
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_model (model_type, model_id),
    INDEX idx_action_time (action, created_at),
    INDEX idx_correlation (correlation_id),
    INDEX idx_risk (risk_level, created_at)
);
```

### portal_permissions
```sql
CREATE TABLE portal_permissions (
    id bigint PRIMARY KEY,
    name varchar(255) NOT NULL,
    slug varchar(255) NOT NULL UNIQUE,
    description text,
    category varchar(100), -- calls, appointments, billing, etc.
    risk_level enum('low','medium','high','critical'),
    requires_2fa boolean DEFAULT false,
    is_active boolean DEFAULT true,
    created_at timestamp,
    updated_at timestamp,
    
    INDEX idx_slug (slug),
    INDEX idx_category (category)
);
```

### portal_user_permissions
```sql
CREATE TABLE portal_user_permissions (
    id bigint PRIMARY KEY,
    portal_user_id bigint NOT NULL,
    permission_id bigint NOT NULL,
    granted_by bigint,
    granted_at timestamp,
    expires_at timestamp,
    conditions json, -- Additional conditions/restrictions
    
    FOREIGN KEY (portal_user_id) REFERENCES portal_users(id),
    FOREIGN KEY (permission_id) REFERENCES portal_permissions(id),
    UNIQUE KEY unique_user_permission (portal_user_id, permission_id)
);
```

### portal_sessions
```sql
CREATE TABLE portal_sessions (
    id varchar(255) PRIMARY KEY,
    portal_user_id bigint NOT NULL,
    ip_address varchar(45),
    user_agent text,
    payload text NOT NULL,
    last_activity int NOT NULL,
    created_at timestamp,
    expires_at timestamp,
    is_active boolean DEFAULT true,
    terminated_at timestamp,
    termination_reason varchar(255),
    
    INDEX idx_user (portal_user_id),
    INDEX idx_last_activity (last_activity),
    INDEX idx_active (is_active, expires_at)
);
```

### security_events
```sql
CREATE TABLE security_events (
    id bigint PRIMARY KEY,
    event_type varchar(100) NOT NULL, -- failed_login, permission_denied, etc.
    severity enum('info','warning','error','critical'),
    company_id bigint,
    user_id bigint,
    ip_address varchar(45),
    user_agent text,
    details json,
    resolved boolean DEFAULT false,
    resolved_at timestamp,
    resolved_by bigint,
    created_at timestamp,
    
    INDEX idx_type_severity (event_type, severity),
    INDEX idx_unresolved (resolved, severity, created_at)
);
```

## Implementation

### Audit Logging Service

```php
namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    protected $correlationId;
    
    public function __construct()
    {
        $this->correlationId = request()->header('X-Correlation-ID') ?? Str::uuid();
    }
    
    public function log(
        string $action,
        string $actionType = 'read',
        $model = null,
        array $changes = [],
        array $metadata = []
    ): AuditLog {
        $user = Auth::user();
        
        // Determine risk level
        $riskLevel = $this->calculateRiskLevel($action, $actionType, $model);
        
        // Prepare audit data
        $auditData = [
            'company_id' => $user?->company_id ?? $model?->company_id ?? null,
            'user_id' => $user?->id,
            'user_type' => $this->getUserType($user),
            'action' => $action,
            'action_type' => $actionType,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
            'correlation_id' => $this->correlationId,
            'risk_level' => $riskLevel,
            'metadata' => array_merge($metadata, [
                'url' => Request::fullUrl(),
                'method' => Request::method(),
                'timestamp' => now()->toIso8601String()
            ])
        ];
        
        // Add model information if provided
        if ($model) {
            $auditData['model_type'] = get_class($model);
            $auditData['model_id'] = $model->id;
            
            if (!empty($changes)) {
                $auditData['old_values'] = $changes['old'] ?? [];
                $auditData['new_values'] = $changes['new'] ?? [];
                $auditData['changes'] = $changes['diff'] ?? [];
            }
        }
        
        // Create audit log
        $log = AuditLog::create($auditData);
        
        // Check for suspicious activity
        $this->checkSuspiciousActivity($log);
        
        return $log;
    }
    
    protected function calculateRiskLevel(string $action, string $actionType, $model): string
    {
        // High risk actions
        $highRiskActions = [
            'user.permissions.changed',
            'billing.payment.processed',
            'data.export.complete',
            'settings.security.updated'
        ];
        
        if (in_array($action, $highRiskActions)) {
            return 'high';
        }
        
        // Critical actions
        if ($actionType === 'delete' || strpos($action, 'admin') !== false) {
            return 'high';
        }
        
        // Medium risk
        if (in_array($actionType, ['create', 'update'])) {
            return 'medium';
        }
        
        return 'low';
    }
    
    protected function checkSuspiciousActivity(AuditLog $log): void
    {
        // Check for rapid repeated actions
        $recentActions = AuditLog::where('user_id', $log->user_id)
            ->where('action', $log->action)
            ->where('created_at', '>', now()->subMinutes(5))
            ->count();
        
        if ($recentActions > 10) {
            $this->createSecurityEvent('rapid_repeated_actions', 'warning', $log);
        }
        
        // Check for unusual access patterns
        if ($this->isUnusualAccessPattern($log)) {
            $this->createSecurityEvent('unusual_access_pattern', 'warning', $log);
        }
        
        // Check for high-risk action from new location
        if ($log->risk_level === 'high' && $this->isNewLocation($log)) {
            $this->createSecurityEvent('high_risk_new_location', 'error', $log);
        }
    }
    
    public function query(): AuditQueryBuilder
    {
        return new AuditQueryBuilder();
    }
}
```

### Permission Management

```php
namespace App\Services;

use App\Models\PortalUser;
use App\Models\PortalPermission;
use Illuminate\Support\Collection;

class PermissionService
{
    protected $cache = [];
    
    public function hasPermission(PortalUser $user, string $permission): bool
    {
        // Super admin bypass
        if ($user->is_super_admin) {
            return true;
        }
        
        // Check cache
        $cacheKey = "{$user->id}:{$permission}";
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        // Check direct permissions
        $hasPermission = $user->permissions()
            ->where('slug', $permission)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->exists();
        
        // Check role-based permissions
        if (!$hasPermission && $user->role) {
            $hasPermission = $this->roleHasPermission($user->role, $permission);
        }
        
        // Cache result
        $this->cache[$cacheKey] = $hasPermission;
        
        // Log permission check for high-risk permissions
        if ($this->isHighRiskPermission($permission)) {
            app(AuditLogService::class)->log(
                "permission.checked.{$permission}",
                'read',
                null,
                [],
                ['granted' => $hasPermission]
            );
        }
        
        return $hasPermission;
    }
    
    public function grantPermission(
        PortalUser $user,
        string $permission,
        ?PortalUser $grantedBy = null,
        ?array $conditions = null,
        ?\DateTime $expiresAt = null
    ): void {
        $permissionModel = PortalPermission::where('slug', $permission)->firstOrFail();
        
        // Check if granter has permission to grant
        if ($grantedBy && !$this->canGrantPermission($grantedBy, $permission)) {
            throw new \Exception('You do not have permission to grant this permission');
        }
        
        // Create permission record
        $user->permissions()->attach($permissionModel->id, [
            'granted_by' => $grantedBy?->id,
            'granted_at' => now(),
            'expires_at' => $expiresAt,
            'conditions' => $conditions ? json_encode($conditions) : null
        ]);
        
        // Clear cache
        unset($this->cache["{$user->id}:{$permission}"]);
        
        // Audit log
        app(AuditLogService::class)->log(
            'permission.granted',
            'create',
            $user,
            [],
            [
                'permission' => $permission,
                'granted_by' => $grantedBy?->email,
                'expires_at' => $expiresAt?->format('Y-m-d H:i:s')
            ]
        );
        
        // Send notification
        $user->notify(new PermissionGrantedNotification($permission));
    }
    
    public function revokePermission(
        PortalUser $user,
        string $permission,
        ?PortalUser $revokedBy = null
    ): void {
        $permissionModel = PortalPermission::where('slug', $permission)->firstOrFail();
        
        $user->permissions()->detach($permissionModel->id);
        
        // Clear cache
        unset($this->cache["{$user->id}:{$permission}"]);
        
        // Audit log
        app(AuditLogService::class)->log(
            'permission.revoked',
            'delete',
            $user,
            [],
            [
                'permission' => $permission,
                'revoked_by' => $revokedBy?->email
            ]
        );
    }
    
    protected function isHighRiskPermission(string $permission): bool
    {
        $highRiskPermissions = [
            'billing.manage',
            'users.delete',
            'settings.security',
            'data.export.all'
        ];
        
        return in_array($permission, $highRiskPermissions);
    }
}
```

### Two-Factor Authentication

```php
namespace App\Services;

use App\Models\PortalUser;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorAuthService
{
    protected $google2fa;
    
    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }
    
    public function enable(PortalUser $user): array
    {
        // Generate secret
        $secret = $this->google2fa->generateSecretKey();
        
        // Store encrypted secret
        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => false // Not enabled until confirmed
        ]);
        
        // Generate QR code
        $qrCode = $this->google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret
        );
        
        // Audit log
        app(AuditLogService::class)->log(
            '2fa.setup.initiated',
            'update',
            $user
        );
        
        return [
            'secret' => $secret,
            'qr_code' => $qrCode,
            'recovery_codes' => $this->generateRecoveryCodes($user)
        ];
    }
    
    public function confirm(PortalUser $user, string $code): bool
    {
        $secret = decrypt($user->two_factor_secret);
        
        if ($this->google2fa->verifyKey($secret, $code)) {
            $user->update([
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => now()
            ]);
            
            // Audit log
            app(AuditLogService::class)->log(
                '2fa.enabled',
                'update',
                $user,
                [],
                ['method' => 'authenticator_app']
            );
            
            return true;
        }
        
        return false;
    }
    
    public function verify(PortalUser $user, string $code): bool
    {
        if (!$user->two_factor_enabled) {
            return true;
        }
        
        $secret = decrypt($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, $code);
        
        if ($valid) {
            // Update last used
            $user->update(['two_factor_last_used_at' => now()]);
            
            // Log successful 2FA
            app(AuditLogService::class)->log(
                '2fa.verified',
                'read',
                $user
            );
        } else {
            // Log failed attempt
            $this->logFailedAttempt($user);
        }
        
        return $valid;
    }
    
    protected function generateRecoveryCodes(PortalUser $user): array
    {
        $codes = [];
        
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::random(10);
        }
        
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes))
        ]);
        
        return $codes;
    }
    
    protected function logFailedAttempt(PortalUser $user): void
    {
        SecurityEvent::create([
            'event_type' => '2fa_failed',
            'severity' => 'warning',
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => [
                'attempts' => $this->getRecentFailedAttempts($user)
            ]
        ]);
        
        // Lock account after 5 failed attempts
        if ($this->getRecentFailedAttempts($user) >= 5) {
            $user->update(['locked_at' => now()]);
            
            SecurityEvent::create([
                'event_type' => 'account_locked',
                'severity' => 'error',
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'details' => ['reason' => 'too_many_2fa_failures']
            ]);
        }
    }
}
```

### Session Management

```php
namespace App\Services;

use App\Models\PortalSession;
use App\Models\PortalUser;
use Illuminate\Support\Facades\DB;

class SessionManagementService
{
    public function createSession(PortalUser $user): string
    {
        // Terminate other sessions if single session enforced
        if ($user->enforce_single_session) {
            $this->terminateUserSessions($user, 'new_session_started');
        }
        
        // Create new session
        $sessionId = Str::random(40);
        
        PortalSession::create([
            'id' => $sessionId,
            'portal_user_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => serialize([]),
            'last_activity' => time(),
            'created_at' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120))
        ]);
        
        // Audit log
        app(AuditLogService::class)->log(
            'session.created',
            'create',
            $user,
            [],
            ['session_id' => $sessionId]
        );
        
        return $sessionId;
    }
    
    public function validateSession(string $sessionId): ?PortalUser
    {
        $session = PortalSession::where('id', $sessionId)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$session) {
            return null;
        }
        
        // Check for suspicious activity
        if ($this->isSessionSuspicious($session)) {
            $this->terminateSession($session, 'suspicious_activity');
            return null;
        }
        
        // Update last activity
        $session->update(['last_activity' => time()]);
        
        return $session->user;
    }
    
    public function terminateSession(PortalSession $session, string $reason): void
    {
        $session->update([
            'is_active' => false,
            'terminated_at' => now(),
            'termination_reason' => $reason
        ]);
        
        // Audit log
        app(AuditLogService::class)->log(
            'session.terminated',
            'delete',
            $session->user,
            [],
            [
                'session_id' => $session->id,
                'reason' => $reason
            ]
        );
    }
    
    public function getActiveSessions(PortalUser $user): Collection
    {
        return PortalSession::where('portal_user_id', $user->id)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->orderBy('last_activity', 'desc')
            ->get();
    }
    
    protected function isSessionSuspicious(PortalSession $session): bool
    {
        // Check for IP changes
        if ($session->ip_address !== request()->ip()) {
            return true;
        }
        
        // Check for unusual user agent changes
        if ($this->hasSignificantUserAgentChange($session->user_agent, request()->userAgent())) {
            return true;
        }
        
        // Check for rapid location changes
        if ($this->hasRapidLocationChange($session)) {
            return true;
        }
        
        return false;
    }
}
```

## Frontend Implementation

### Audit Log Viewer

```javascript
// AuditLogViewer.jsx
import React, { useState, useEffect } from 'react';
import { Table, Tag, Input, DatePicker, Select, Button, Drawer } from 'antd';
import { SearchOutlined, FilterOutlined, ExportOutlined } from '@ant-design/icons';

function AuditLogViewer({ companyId }) {
    const [logs, setLogs] = useState([]);
    const [filters, setFilters] = useState({});
    const [selectedLog, setSelectedLog] = useState(null);
    const [loading, setLoading] = useState(false);
    
    const columns = [
        {
            title: 'Timestamp',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => format(new Date(date), 'MMM d, yyyy HH:mm:ss'),
            sorter: true,
            width: 180,
        },
        {
            title: 'User',
            dataIndex: 'user',
            key: 'user',
            render: (user) => (
                <div>
                    <strong>{user?.name || 'System'}</strong>
                    <br />
                    <small>{user?.email}</small>
                </div>
            ),
            width: 200,
        },
        {
            title: 'Action',
            dataIndex: 'action',
            key: 'action',
            render: (action, record) => (
                <div>
                    <Tag color={getActionColor(record.action_type)}>
                        {record.action_type.toUpperCase()}
                    </Tag>
                    <span>{action}</span>
                </div>
            ),
        },
        {
            title: 'Resource',
            dataIndex: 'model_type',
            key: 'model_type',
            render: (type, record) => (
                type ? `${type} #${record.model_id}` : '-'
            ),
            width: 150,
        },
        {
            title: 'Risk Level',
            dataIndex: 'risk_level',
            key: 'risk_level',
            render: (level) => (
                <Tag color={getRiskColor(level)}>
                    {level?.toUpperCase()}
                </Tag>
            ),
            width: 100,
            filters: [
                { text: 'Low', value: 'low' },
                { text: 'Medium', value: 'medium' },
                { text: 'High', value: 'high' },
            ],
        },
        {
            title: 'IP Address',
            dataIndex: 'ip_address',
            key: 'ip_address',
            width: 120,
        },
        {
            title: 'Details',
            key: 'actions',
            render: (_, record) => (
                <Button
                    size="small"
                    onClick={() => setSelectedLog(record)}
                >
                    View Details
                </Button>
            ),
            width: 100,
        },
    ];
    
    const getActionColor = (type) => {
        const colors = {
            create: 'green',
            read: 'blue',
            update: 'orange',
            delete: 'red',
            login: 'purple',
            export: 'cyan',
        };
        return colors[type] || 'default';
    };
    
    const getRiskColor = (level) => {
        const colors = {
            low: 'green',
            medium: 'orange',
            high: 'red',
        };
        return colors[level] || 'default';
    };
    
    const fetchLogs = async (params = {}) => {
        setLoading(true);
        try {
            const response = await api.get('/portal/audit-logs', {
                params: { company_id: companyId, ...filters, ...params }
            });
            setLogs(response.data.data);
        } finally {
            setLoading(false);
        }
    };
    
    const exportLogs = async () => {
        const response = await api.post('/portal/audit-logs/export', {
            filters: { company_id: companyId, ...filters }
        });
        
        // Download CSV
        const blob = new Blob([response.data], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `audit-logs-${format(new Date(), 'yyyy-MM-dd')}.csv`;
        a.click();
    };
    
    return (
        <div className="audit-log-viewer">
            <div className="audit-filters">
                <Input.Search
                    placeholder="Search actions, users, or IPs..."
                    onSearch={(value) => setFilters({ ...filters, search: value })}
                    style={{ width: 300 }}
                />
                
                <DatePicker.RangePicker
                    onChange={(dates) => setFilters({
                        ...filters,
                        date_from: dates?.[0]?.format('YYYY-MM-DD'),
                        date_to: dates?.[1]?.format('YYYY-MM-DD')
                    })}
                />
                
                <Select
                    placeholder="Action Type"
                    style={{ width: 150 }}
                    allowClear
                    onChange={(value) => setFilters({ ...filters, action_type: value })}
                >
                    <Select.Option value="create">Create</Select.Option>
                    <Select.Option value="read">Read</Select.Option>
                    <Select.Option value="update">Update</Select.Option>
                    <Select.Option value="delete">Delete</Select.Option>
                    <Select.Option value="login">Login</Select.Option>
                    <Select.Option value="export">Export</Select.Option>
                </Select>
                
                <Button
                    icon={<FilterOutlined />}
                    onClick={() => fetchLogs()}
                >
                    Apply Filters
                </Button>
                
                <Button
                    icon={<ExportOutlined />}
                    onClick={exportLogs}
                >
                    Export
                </Button>
            </div>
            
            <Table
                columns={columns}
                dataSource={logs}
                loading={loading}
                rowKey="id"
                pagination={{
                    pageSize: 50,
                    showSizeChanger: true,
                }}
                onChange={(pagination, filters, sorter) => {
                    fetchLogs({
                        page: pagination.current,
                        per_page: pagination.pageSize,
                        sort_by: sorter.field,
                        sort_order: sorter.order
                    });
                }}
            />
            
            <AuditLogDetailDrawer
                log={selectedLog}
                visible={!!selectedLog}
                onClose={() => setSelectedLog(null)}
            />
        </div>
    );
}
```

### Permission Manager

```javascript
// PermissionManager.jsx
import React, { useState, useEffect } from 'react';
import { Table, Switch, Button, Modal, Form, Select, DatePicker } from 'antd';
import { LockOutlined, UnlockOutlined } from '@ant-design/icons';

function PermissionManager({ userId }) {
    const [user, setUser] = useState(null);
    const [permissions, setPermissions] = useState([]);
    const [availablePermissions, setAvailablePermissions] = useState([]);
    const [grantModalVisible, setGrantModalVisible] = useState(false);
    
    useEffect(() => {
        fetchUserPermissions();
        fetchAvailablePermissions();
    }, [userId]);
    
    const fetchUserPermissions = async () => {
        const response = await api.get(`/portal/users/${userId}/permissions`);
        setUser(response.data.user);
        setPermissions(response.data.permissions);
    };
    
    const columns = [
        {
            title: 'Permission',
            dataIndex: 'name',
            key: 'name',
            render: (name, record) => (
                <div>
                    <strong>{name}</strong>
                    <br />
                    <small>{record.description}</small>
                </div>
            ),
        },
        {
            title: 'Category',
            dataIndex: 'category',
            key: 'category',
            render: (category) => (
                <Tag>{category}</Tag>
            ),
            filters: [
                { text: 'Calls', value: 'calls' },
                { text: 'Appointments', value: 'appointments' },
                { text: 'Billing', value: 'billing' },
                { text: 'Team', value: 'team' },
                { text: 'Settings', value: 'settings' },
            ],
        },
        {
            title: 'Risk Level',
            dataIndex: 'risk_level',
            key: 'risk_level',
            render: (level) => (
                <Tag color={getRiskColor(level)}>
                    {level?.toUpperCase()}
                </Tag>
            ),
        },
        {
            title: 'Granted By',
            dataIndex: 'granted_by',
            key: 'granted_by',
            render: (grantedBy) => grantedBy?.name || 'System',
        },
        {
            title: 'Expires',
            dataIndex: 'expires_at',
            key: 'expires_at',
            render: (date) => date ? format(new Date(date), 'MMM d, yyyy') : 'Never',
        },
        {
            title: 'Status',
            key: 'status',
            render: (_, record) => (
                <Switch
                    checked={record.is_active}
                    onChange={(checked) => togglePermission(record.id, checked)}
                    checkedChildren={<UnlockOutlined />}
                    unCheckedChildren={<LockOutlined />}
                />
            ),
        },
        {
            title: 'Actions',
            key: 'actions',
            render: (_, record) => (
                <Button
                    size="small"
                    danger
                    onClick={() => revokePermission(record.id)}
                >
                    Revoke
                </Button>
            ),
        },
    ];
    
    const togglePermission = async (permissionId, active) => {
        await api.put(`/portal/users/${userId}/permissions/${permissionId}`, {
            is_active: active
        });
        fetchUserPermissions();
    };
    
    const grantPermission = async (values) => {
        await api.post(`/portal/users/${userId}/permissions`, values);
        setGrantModalVisible(false);
        fetchUserPermissions();
    };
    
    const revokePermission = async (permissionId) => {
        Modal.confirm({
            title: 'Revoke Permission',
            content: 'Are you sure you want to revoke this permission?',
            onOk: async () => {
                await api.delete(`/portal/users/${userId}/permissions/${permissionId}`);
                fetchUserPermissions();
            }
        });
    };
    
    return (
        <div className="permission-manager">
            <div className="permission-header">
                <h3>Permissions for {user?.name}</h3>
                <Button
                    type="primary"
                    onClick={() => setGrantModalVisible(true)}
                >
                    Grant Permission
                </Button>
            </div>
            
            <Table
                columns={columns}
                dataSource={permissions}
                rowKey="id"
                pagination={false}
            />
            
            <Modal
                title="Grant Permission"
                visible={grantModalVisible}
                onCancel={() => setGrantModalVisible(false)}
                footer={null}
            >
                <Form onFinish={grantPermission} layout="vertical">
                    <Form.Item
                        name="permission_id"
                        label="Permission"
                        rules={[{ required: true }]}
                    >
                        <Select
                            showSearch
                            placeholder="Select permission"
                            optionFilterProp="children"
                        >
                            {availablePermissions.map(perm => (
                                <Select.Option key={perm.id} value={perm.id}>
                                    {perm.name} ({perm.category})
                                </Select.Option>
                            ))}
                        </Select>
                    </Form.Item>
                    
                    <Form.Item
                        name="expires_at"
                        label="Expires At (Optional)"
                    >
                        <DatePicker
                            showTime
                            style={{ width: '100%' }}
                        />
                    </Form.Item>
                    
                    <Form.Item
                        name="conditions"
                        label="Conditions (JSON)"
                    >
                        <Input.TextArea
                            placeholder='{"branch_ids": [1, 2]}'
                            rows={3}
                        />
                    </Form.Item>
                    
                    <Form.Item>
                        <Button type="primary" htmlType="submit" block>
                            Grant Permission
                        </Button>
                    </Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
```

### Security Dashboard

```javascript
// SecurityDashboard.jsx
import React, { useState, useEffect } from 'react';
import { Row, Col, Card, Statistic, Alert, Timeline, Tag } from 'antd';
import { Line, Column, Pie } from '@ant-design/plots';
import {
    ShieldCheckOutlined,
    WarningOutlined,
    LockOutlined,
    UserOutlined
} from '@ant-design/icons';

function SecurityDashboard({ companyId }) {
    const [metrics, setMetrics] = useState(null);
    const [events, setEvents] = useState([]);
    const [threats, setThreats] = useState([]);
    
    useEffect(() => {
        fetchSecurityMetrics();
        fetchRecentEvents();
        fetchActiveThreats();
    }, [companyId]);
    
    const fetchSecurityMetrics = async () => {
        const response = await api.get('/portal/security/metrics', {
            params: { company_id: companyId }
        });
        setMetrics(response.data);
    };
    
    const fetchRecentEvents = async () => {
        const response = await api.get('/portal/security/events/recent', {
            params: { company_id: companyId, limit: 10 }
        });
        setEvents(response.data);
    };
    
    const fetchActiveThreats = async () => {
        const response = await api.get('/portal/security/threats/active', {
            params: { company_id: companyId }
        });
        setThreats(response.data);
    };
    
    return (
        <div className="security-dashboard">
            {threats.length > 0 && (
                <Alert
                    message="Active Security Threats Detected"
                    description={`${threats.length} unresolved security issues require attention`}
                    type="error"
                    showIcon
                    closable
                    action={
                        <Button size="small" danger>
                            View Threats
                        </Button>
                    }
                />
            )}
            
            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                <Col span={6}>
                    <Card>
                        <Statistic
                            title="Security Score"
                            value={metrics?.security_score}
                            suffix="/ 100"
                            prefix={<ShieldCheckOutlined />}
                            valueStyle={{
                                color: metrics?.security_score >= 80 ? '#3f8600' : '#cf1322'
                            }}
                        />
                    </Card>
                </Col>
                
                <Col span={6}>
                    <Card>
                        <Statistic
                            title="Active Sessions"
                            value={metrics?.active_sessions}
                            prefix={<UserOutlined />}
                        />
                    </Card>
                </Col>
                
                <Col span={6}>
                    <Card>
                        <Statistic
                            title="Failed Logins (24h)"
                            value={metrics?.failed_logins_24h}
                            prefix={<WarningOutlined />}
                            valueStyle={{ color: '#faad14' }}
                        />
                    </Card>
                </Col>
                
                <Col span={6}>
                    <Card>
                        <Statistic
                            title="2FA Adoption"
                            value={metrics?.two_fa_adoption}
                            suffix="%"
                            prefix={<LockOutlined />}
                            valueStyle={{ color: '#52c41a' }}
                        />
                    </Card>
                </Col>
            </Row>
            
            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                <Col span={12}>
                    <Card title="Login Activity (7 Days)">
                        <Line
                            data={metrics?.login_activity || []}
                            xField="date"
                            yField="value"
                            seriesField="type"
                            smooth
                        />
                    </Card>
                </Col>
                
                <Col span={12}>
                    <Card title="Security Events by Type">
                        <Pie
                            data={metrics?.events_by_type || []}
                            angleField="count"
                            colorField="type"
                            radius={0.8}
                            label={{
                                type: 'inner',
                                offset: '-30%',
                                content: '{percentage}',
                            }}
                        />
                    </Card>
                </Col>
            </Row>
            
            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                <Col span={24}>
                    <Card title="Recent Security Events">
                        <Timeline>
                            {events.map(event => (
                                <Timeline.Item
                                    key={event.id}
                                    color={getEventColor(event.severity)}
                                    dot={getEventIcon(event.event_type)}
                                >
                                    <div className="security-event">
                                        <div className="event-header">
                                            <Tag color={getEventColor(event.severity)}>
                                                {event.severity.toUpperCase()}
                                            </Tag>
                                            <span className="event-type">
                                                {event.event_type}
                                            </span>
                                            <span className="event-time">
                                                {format(new Date(event.created_at), 'MMM d, HH:mm')}
                                            </span>
                                        </div>
                                        <div className="event-details">
                                            {event.details.message}
                                            {event.user && (
                                                <span className="event-user">
                                                    - {event.user.name}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </Timeline.Item>
                            ))}
                        </Timeline>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}
```

## Compliance Features

### GDPR Compliance

```php
namespace App\Services\Compliance;

class GDPRComplianceService
{
    public function exportUserData(PortalUser $user): array
    {
        $data = [
            'personal_information' => $user->only([
                'name', 'email', 'phone', 'created_at'
            ]),
            'audit_logs' => AuditLog::where('user_id', $user->id)->get(),
            'sessions' => PortalSession::where('portal_user_id', $user->id)->get(),
            'permissions' => $user->permissions()->get(),
            'login_history' => $this->getLoginHistory($user),
            'data_processing_consents' => $user->consents()->get()
        ];
        
        // Log data export
        app(AuditLogService::class)->log(
            'gdpr.data_exported',
            'export',
            $user,
            [],
            ['export_id' => Str::uuid()]
        );
        
        return $data;
    }
    
    public function deleteUserData(PortalUser $user): void
    {
        DB::transaction(function () use ($user) {
            // Anonymize audit logs
            AuditLog::where('user_id', $user->id)->update([
                'user_id' => null,
                'ip_address' => 'ANONYMIZED',
                'user_agent' => 'ANONYMIZED'
            ]);
            
            // Delete sessions
            PortalSession::where('portal_user_id', $user->id)->delete();
            
            // Delete permissions
            $user->permissions()->detach();
            
            // Anonymize user record
            $user->update([
                'name' => 'Deleted User',
                'email' => 'deleted_' . Str::random(10) . '@example.com',
                'phone' => null,
                'deleted_at' => now()
            ]);
            
            // Log deletion
            app(AuditLogService::class)->log(
                'gdpr.data_deleted',
                'delete',
                null,
                [],
                ['user_id' => $user->id]
            );
        });
    }
}
```

### Compliance Reporting

```php
namespace App\Services\Compliance;

class ComplianceReportService
{
    public function generateAccessReport(int $companyId, string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        return [
            'data_access' => $this->getDataAccessReport($companyId, $startDate),
            'permission_changes' => $this->getPermissionChanges($companyId, $startDate),
            'high_risk_actions' => $this->getHighRiskActions($companyId, $startDate),
            'failed_access_attempts' => $this->getFailedAccessAttempts($companyId, $startDate),
            'data_exports' => $this->getDataExports($companyId, $startDate),
            'retention_compliance' => $this->checkRetentionCompliance($companyId)
        ];
    }
    
    protected function getDataAccessReport($companyId, $startDate): array
    {
        return DB::table('audit_logs')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->where('action_type', 'read')
            ->select(
                'model_type',
                DB::raw('COUNT(*) as access_count'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users')
            )
            ->groupBy('model_type')
            ->get()
            ->toArray();
    }
    
    protected function checkRetentionCompliance($companyId): array
    {
        $issues = [];
        
        // Check for data older than retention period
        $retentionPeriod = config('compliance.data_retention_days', 730); // 2 years
        
        $oldAuditLogs = AuditLog::where('company_id', $companyId)
            ->where('created_at', '<', now()->subDays($retentionPeriod))
            ->count();
        
        if ($oldAuditLogs > 0) {
            $issues[] = [
                'type' => 'retention_exceeded',
                'message' => "{$oldAuditLogs} audit logs exceed retention period",
                'severity' => 'medium'
            ];
        }
        
        return $issues;
    }
}
```

## Best Practices

### 1. Audit Everything Important

```php
// In controllers
public function updateCompanySettings(Request $request)
{
    $company = auth()->user()->company;
    $oldSettings = $company->settings;
    
    $company->update(['settings' => $request->settings]);
    
    // Audit the change
    app(AuditLogService::class)->log(
        'company.settings.updated',
        'update',
        $company,
        [
            'old' => $oldSettings,
            'new' => $request->settings,
            'diff' => array_diff_assoc($request->settings, $oldSettings)
        ]
    );
}
```

### 2. Implement Least Privilege

```php
// Permission checks in services
public function exportCustomerData($customerId)
{
    if (!$this->permissionService->hasPermission(auth()->user(), 'customers.export')) {
        throw new UnauthorizedException('You do not have permission to export customer data');
    }
    
    // Additional condition checks
    $customer = Customer::findOrFail($customerId);
    if ($customer->company_id !== auth()->user()->company_id) {
        throw new UnauthorizedException('You can only export data from your own company');
    }
    
    // Proceed with export...
}
```

### 3. Regular Security Reviews

```bash
# Security audit command
php artisan security:audit --company=1

# Output includes:
- Users without 2FA
- Stale sessions
- Unused permissions
- Suspicious activity patterns
- Compliance issues
```

### 4. Incident Response

```php
// Security incident handling
class SecurityIncidentHandler
{
    public function handleIncident(SecurityEvent $event): void
    {
        // Immediate actions
        match($event->severity) {
            'critical' => $this->handleCritical($event),
            'error' => $this->handleError($event),
            'warning' => $this->handleWarning($event),
            default => $this->log($event)
        };
    }
    
    protected function handleCritical(SecurityEvent $event): void
    {
        // Lock affected accounts
        // Notify administrators
        // Create incident report
        // Initiate forensic logging
    }
}
```

## Troubleshooting

### Common Issues

1. **Audit logs growing too large**
   ```bash
   # Archive old logs
   php artisan audit:archive --older-than=90
   
   # Optimize table
   OPTIMIZE TABLE audit_logs;
   ```

2. **Permission cache issues**
   ```php
   // Clear permission cache
   app(PermissionService::class)->clearCache();
   
   // Rebuild permission index
   php artisan permissions:rebuild
   ```

3. **Session conflicts**
   ```bash
   # Clear stale sessions
   php artisan sessions:cleanup
   
   # Force logout all users
   php artisan sessions:clear --company=1
   ```

### Monitoring

```bash
# Security health check
php artisan security:health

# Real-time monitoring
tail -f storage/logs/security.log

# Metrics
- Failed login attempts per hour
- Permission denial rate
- Audit log volume
- Active threat count
```

---

*For more information, see the [main documentation](./BUSINESS_PORTAL_COMPLETE_DOCUMENTATION.md)*