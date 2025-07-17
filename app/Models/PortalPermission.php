<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PortalPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'module',
        'description',
        'is_critical',
        'admin_only',
        'metadata',
    ];

    protected $casts = [
        'is_critical' => 'boolean',
        'admin_only' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Default permissions that should exist
     */
    const DEFAULT_PERMISSIONS = [
        // Calls Module
        ['name' => 'calls.view_own', 'module' => 'calls', 'description' => 'Eigene Anrufe anzeigen'],
        ['name' => 'calls.view_all', 'module' => 'calls', 'description' => 'Alle Anrufe anzeigen'],
        ['name' => 'calls.edit_own', 'module' => 'calls', 'description' => 'Eigene Anrufe bearbeiten'],
        ['name' => 'calls.edit_all', 'module' => 'calls', 'description' => 'Alle Anrufe bearbeiten'],
        ['name' => 'calls.export', 'module' => 'calls', 'description' => 'Anrufe exportieren'],
        ['name' => 'calls.export_sensitive', 'module' => 'calls', 'description' => 'Sensible Anrufdaten exportieren', 'is_critical' => true],
        ['name' => 'calls.view_transcript', 'module' => 'calls', 'description' => 'Transkripte anzeigen'],
        ['name' => 'calls.delete', 'module' => 'calls', 'description' => 'Anrufe löschen', 'is_critical' => true],
        
        // Billing Module
        ['name' => 'billing.view', 'module' => 'billing', 'description' => 'Abrechnungen anzeigen', 'is_critical' => true],
        ['name' => 'billing.view_costs', 'module' => 'billing', 'description' => 'Kosten anzeigen', 'is_critical' => true],
        ['name' => 'billing.manage', 'module' => 'billing', 'description' => 'Abrechnungen verwalten', 'is_critical' => true, 'admin_only' => true],
        ['name' => 'billing.export', 'module' => 'billing', 'description' => 'Abrechnungen exportieren', 'is_critical' => true],
        
        // Customer Module
        ['name' => 'customers.view', 'module' => 'customers', 'description' => 'Kunden anzeigen'],
        ['name' => 'customers.edit', 'module' => 'customers', 'description' => 'Kunden bearbeiten'],
        ['name' => 'customers.create', 'module' => 'customers', 'description' => 'Kunden erstellen'],
        ['name' => 'customers.delete', 'module' => 'customers', 'description' => 'Kunden löschen', 'is_critical' => true],
        ['name' => 'customers.export', 'module' => 'customers', 'description' => 'Kundendaten exportieren', 'is_critical' => true],
        
        // Team Module
        ['name' => 'team.view', 'module' => 'team', 'description' => 'Team anzeigen'],
        ['name' => 'team.manage', 'module' => 'team', 'description' => 'Team verwalten', 'admin_only' => true],
        ['name' => 'team.permissions', 'module' => 'team', 'description' => 'Berechtigungen verwalten', 'is_critical' => true, 'admin_only' => true],
        
        // Settings Module
        ['name' => 'settings.view', 'module' => 'settings', 'description' => 'Einstellungen anzeigen'],
        ['name' => 'settings.edit', 'module' => 'settings', 'description' => 'Einstellungen bearbeiten', 'admin_only' => true],
        ['name' => 'settings.security', 'module' => 'settings', 'description' => 'Sicherheitseinstellungen', 'is_critical' => true, 'admin_only' => true],
        
        // Audit Module
        ['name' => 'audit.view', 'module' => 'audit', 'description' => 'Audit-Logs anzeigen', 'is_critical' => true, 'admin_only' => true],
        ['name' => 'audit.export', 'module' => 'audit', 'description' => 'Audit-Logs exportieren', 'is_critical' => true, 'admin_only' => true],
        
        // Analytics Module
        ['name' => 'analytics.view', 'module' => 'analytics', 'description' => 'Analytics anzeigen'],
        ['name' => 'analytics.export', 'module' => 'analytics', 'description' => 'Analytics exportieren'],
        ['name' => 'analytics.financial', 'module' => 'analytics', 'description' => 'Finanz-Analytics anzeigen', 'is_critical' => true],
    ];

    /**
     * Portal users that have this permission
     */
    public function portalUsers(): BelongsToMany
    {
        return $this->belongsToMany(PortalUser::class, 'portal_user_permissions')
            ->withPivot(['granted_at', 'granted_by_user_id', 'granted_by_user_type'])
            ->withTimestamps();
    }

    /**
     * Company settings for this permission
     */
    public function companySettings()
    {
        return $this->hasMany(CompanyPermissionSetting::class);
    }

    /**
     * Check if permission is available for a role
     */
    public function isAvailableForRole($role): bool
    {
        if ($this->admin_only && $role !== 'admin') {
            return false;
        }
        
        return true;
    }

    /**
     * Get permissions by module
     */
    public static function getByModule($module)
    {
        return static::where('module', $module)->get();
    }

    /**
     * Get critical permissions
     */
    public static function getCritical()
    {
        return static::where('is_critical', true)->get();
    }
}