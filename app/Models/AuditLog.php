<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'user_type',
        'user_name',
        'user_email',
        'ip_address',
        'user_agent',
        'action',
        'module',
        'description',
        'auditable_id',
        'auditable_type',
        'old_values',
        'new_values',
        'metadata',
        'risk_level',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Actions that should be logged
     */
    const ACTIONS = [
        // Exports
        'export_pdf' => 'PDF Export',
        'export_csv' => 'CSV Export',
        'export_customer_data' => 'Kundendaten Export',
        
        // Billing
        'view_billing' => 'Abrechnung angesehen',
        'view_costs' => 'Kosten angesehen',
        'create_invoice' => 'Rechnung erstellt',
        'download_invoice' => 'Rechnung heruntergeladen',
        'process_payment' => 'Zahlung verarbeitet',
        
        // Permissions
        'grant_permission' => 'Berechtigung erteilt',
        'revoke_permission' => 'Berechtigung entzogen',
        'update_permissions' => 'Berechtigungen aktualisiert',
        
        // User Management
        'create_user' => 'Benutzer erstellt',
        'update_user' => 'Benutzer aktualisiert',
        'delete_user' => 'Benutzer gelöscht',
        'login' => 'Anmeldung',
        'logout' => 'Abmeldung',
        
        // Data Access
        'view_sensitive_data' => 'Sensible Daten angesehen',
        'bulk_data_access' => 'Massendatenzugriff',
        
        // Settings
        'update_company_settings' => 'Firmeneinstellungen aktualisiert',
        'update_security_settings' => 'Sicherheitseinstellungen aktualisiert',
    ];

    /**
     * Risk levels for different actions
     */
    const RISK_LEVELS = [
        'export_pdf' => 'medium',
        'export_csv' => 'high',
        'export_customer_data' => 'critical',
        'view_billing' => 'medium',
        'view_costs' => 'medium',
        'process_payment' => 'critical',
        'grant_permission' => 'high',
        'revoke_permission' => 'high',
        'update_permissions' => 'high',
        'delete_user' => 'high',
        'update_security_settings' => 'critical',
    ];

    /**
     * Get the owning company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user that performed the action
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the auditable model
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for high risk actions
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    /**
     * Scope for specific module
     */
    public function scopeForModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Get formatted action name
     */
    public function getActionNameAttribute()
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    /**
     * Get risk level color
     */
    public function getRiskColorAttribute()
    {
        return match($this->risk_level) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray'
        };
    }
}