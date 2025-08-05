<?php

namespace App\Services\MCP;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Services\SecurityAuditService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Exceptions\SecurityException;

/**
 * SECURE MCP Server for Company Management
 * 
 * Security Enhancements:
 * - User can only access their own company data
 * - No withoutGlobalScopes usage
 * - No arbitrary company access
 * - Audit logging for all sensitive operations
 * - Encrypted API key handling
 * - Prevents cross-tenant statistics access
 */
class SecureCompanyMCPServer
{
    protected Company $company;
    protected SecurityAuditService $auditService;
    protected DatabaseMCPServer $databaseMCP;
    protected SecureBranchMCPServer $branchMCP;
    
    public function __construct(
        DatabaseMCPServer $databaseMCP,
        SecureBranchMCPServer $branchMCP
    ) {
        $this->databaseMCP = $databaseMCP;
        $this->branchMCP = $branchMCP;
        $this->auditService = app(SecurityAuditService::class);
        
        // Get authenticated company context
        $user = Auth::user();
        if (!$user || !$user->company_id) {
            throw new SecurityException('No authenticated company context');
        }
        
        $this->company = Company::findOrFail($user->company_id);
        
        Log::info('SecureCompanyMCPServer: Initialized with company context', [
            'company_id' => $this->company->id,
            'user_id' => $user->id
        ]);
    }
    
    /**
     * Get authenticated company details
     */
    public function getCompany(): array
    {
        try {
            // SECURITY: User can only access their own company
            $company = Company::where('id', $this->company->id)
                ->withCount(['branches', 'staff', 'customers', 'appointments'])
                ->first();
                
            $this->auditService->logDataAccess('company_viewed', [
                'company_id' => $this->company->id
            ]);
            
            return [
                'success' => true,
                'data' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'legal_name' => $company->legal_name,
                    'tax_id' => $company->tax_id,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'website' => $company->website,
                    'industry' => $company->industry,
                    'address' => $company->address,
                    'city' => $company->city,
                    'postal_code' => $company->postal_code,
                    'country' => $company->country,
                    'timezone' => $company->timezone,
                    'currency' => $company->currency,
                    'is_active' => $company->is_active,
                    'settings' => $company->settings,
                    'integrations' => [
                        'calcom' => [
                            'active' => $company->calcom_integration_active,
                            'api_key_set' => !empty($company->calcom_api_key),
                            'team_slug' => $company->calcom_team_slug
                        ],
                        'retell' => [
                            'active' => $company->retell_integration_active,
                            'api_key_set' => !empty($company->retell_api_key),
                            'default_agent_id' => $company->retell_agent_id
                        ]
                    ],
                    'statistics' => [
                        'branches_count' => $company->branches_count,
                        'staff_count' => $company->staff_count,
                        'customers_count' => $company->customers_count,
                        'appointments_count' => $company->appointments_count
                    ],
                    'subscription' => [
                        'plan' => $company->subscription_plan,
                        'status' => $company->subscription_status,
                        'trial_ends_at' => $company->trial_ends_at
                    ],
                    'created_at' => $company->created_at,
                    'updated_at' => $company->updated_at
                ]
            ];
        } catch (\Exception $e) {
            Log::error('SecureCompanyMCP: Failed to get company', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get company: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Update authenticated company details
     */
    public function updateCompany(array $data): array
    {
        try {
            // SECURITY: User can only update their own company
            $company = Company::where('id', $this->company->id)->first();
            
            // SECURITY: Prevent changing critical fields
            unset($data['id']);
            unset($data['subscription_plan']);
            unset($data['subscription_status']);
            unset($data['trial_ends_at']);
            
            // Handle encrypted fields
            if (isset($data['calcom_api_key'])) {
                $data['calcom_api_key'] = encrypt($data['calcom_api_key']);
                $this->auditService->logSecurityEvent('api_key_updated', [
                    'company_id' => $this->company->id,
                    'type' => 'calcom'
                ]);
            }
            if (isset($data['retell_api_key'])) {
                $data['retell_api_key'] = encrypt($data['retell_api_key']);
                $this->auditService->logSecurityEvent('api_key_updated', [
                    'company_id' => $this->company->id,
                    'type' => 'retell'
                ]);
            }
            
            $company->update($data);
            
            // Clear cache
            Cache::forget("company_{$this->company->id}");
            Cache::tags(['companies'])->flush();
            
            Log::info('SecureCompanyMCP: Updated company', [
                'company_id' => $this->company->id,
                'updated_fields' => array_keys($data)
            ]);
            
            $this->auditService->logDataModification('company_updated', [
                'company_id' => $this->company->id,
                'fields' => array_keys($data)
            ]);
            
            return [
                'success' => true,
                'message' => 'Company updated successfully',
                'data' => $company->fresh()
            ];
        } catch (\Exception $e) {
            Log::error('SecureCompanyMCP: Failed to update company', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to update company: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Get company statistics (for authenticated company only)
     */
    public function getCompanyStatistics(array $options = []): array
    {
        try {
            $period = $options['period'] ?? 'month';
            $startDate = $this->getStartDate($period);
            
            // SECURITY: All queries scoped to authenticated company
            
            // Basic counts
            $branches = Branch::where('company_id', $this->company->id)->count();
            $staff = Staff::where('company_id', $this->company->id)->count();
            $customers = Customer::where('company_id', $this->company->id)->count();
            
            // Appointment statistics
            $appointments = DB::table('appointments')
                ->where('company_id', $this->company->id) // CRITICAL: Company scope
                ->where('created_at', '>=', $startDate)
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
                    COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled,
                    COUNT(CASE WHEN status = "no_show" THEN 1 END) as no_show,
                    AVG(CASE WHEN status = "completed" THEN TIMESTAMPDIFF(MINUTE, start_time, end_time) END) as avg_duration_minutes
                ')
                ->first();
            
            // Revenue statistics
            $revenue = DB::table('appointments')
                ->where('company_id', $this->company->id) // CRITICAL: Company scope
                ->where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('price') ?? 0;
            
            // Call statistics
            $calls = DB::table('calls')
                ->where('company_id', $this->company->id) // CRITICAL: Company scope
                ->where('created_at', '>=', $startDate)
                ->selectRaw('
                    COUNT(*) as total,
                    AVG(duration) as avg_duration_seconds,
                    COUNT(CASE WHEN appointment_booked = 1 THEN 1 END) as appointments_booked
                ')
                ->first();
            
            // Conversion rate
            $conversionRate = $calls->total > 0 
                ? round(($calls->appointments_booked / $calls->total) * 100, 2)
                : 0;
                
            $this->auditService->logDataAccess('statistics_viewed', [
                'company_id' => $this->company->id,
                'period' => $period
            ]);
            
            return [
                'success' => true,
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'data' => [
                    'overview' => [
                        'branches' => $branches,
                        'staff' => $staff,
                        'customers' => $customers
                    ],
                    'appointments' => [
                        'total' => $appointments->total,
                        'completed' => $appointments->completed,
                        'cancelled' => $appointments->cancelled,
                        'no_show' => $appointments->no_show,
                        'completion_rate' => $appointments->total > 0 
                            ? round(($appointments->completed / $appointments->total) * 100, 2) 
                            : 0,
                        'avg_duration_minutes' => round($appointments->avg_duration_minutes ?? 0)
                    ],
                    'calls' => [
                        'total' => $calls->total,
                        'appointments_booked' => $calls->appointments_booked,
                        'conversion_rate' => $conversionRate,
                        'avg_duration_seconds' => round($calls->avg_duration_seconds ?? 0)
                    ],
                    'revenue' => [
                        'total' => $revenue,
                        'currency' => $this->company->currency ?? 'EUR',
                        'per_appointment' => $appointments->completed > 0 
                            ? round($revenue / $appointments->completed, 2) 
                            : 0
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('SecureCompanyMCP: Failed to get company statistics', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Get company integrations status (authenticated company only)
     */
    public function getIntegrationsStatus(): array
    {
        try {
            $company = $this->company;
            
            // Get branch IDs for this company (secure)
            $branchIds = Branch::where('company_id', $this->company->id)->pluck('id');
            
            $integrations = [
                'calcom' => [
                    'configured' => !empty($company->calcom_api_key),
                    'active' => $company->calcom_integration_active,
                    'team_slug' => $company->calcom_team_slug,
                    'last_sync' => Cache::get("calcom_last_sync_{$this->company->id}"),
                    'event_types_count' => DB::table('calcom_event_types')
                        ->whereIn('branch_id', $branchIds)
                        ->count()
                ],
                'retell' => [
                    'configured' => !empty($company->retell_api_key),
                    'active' => $company->retell_integration_active,
                    'default_agent_id' => $company->retell_agent_id,
                    'agents_count' => DB::table('phone_numbers')
                        ->whereIn('branch_id', $branchIds)
                        ->whereNotNull('retell_agent_id')
                        ->distinct('retell_agent_id')
                        ->count()
                ],
                'email' => [
                    'configured' => !empty($company->smtp_host),
                    'active' => $company->email_notifications_enabled ?? true,
                    'provider' => $company->email_provider ?? 'smtp'
                ],
                'sms' => [
                    'configured' => !empty($company->sms_api_key),
                    'active' => $company->sms_notifications_enabled ?? false,
                    'provider' => $company->sms_provider ?? null
                ]
            ];
            
            $this->auditService->logDataAccess('integrations_status_viewed', [
                'company_id' => $this->company->id
            ]);
            
            return [
                'success' => true,
                'data' => $integrations,
                'all_configured' => collect($integrations)
                    ->filter(fn($i) => isset($i['configured']))
                    ->every(fn($i) => $i['configured'])
            ];
        } catch (\Exception $e) {
            Log::error('SecureCompanyMCP: Failed to get integrations status', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get integrations: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Update company settings (authenticated company only)
     */
    public function updateSettings(array $settings): array
    {
        try {
            $company = $this->company;
            
            // SECURITY: Sanitize settings to prevent injection
            $allowedSettings = [
                'notifications', 'locale', 'date_format', 'time_format',
                'business_hours', 'booking_rules', 'email_templates',
                'sms_templates', 'branding'
            ];
            
            $settings = array_intersect_key($settings, array_flip($allowedSettings));
            
            // Merge with existing settings
            $currentSettings = $company->settings ?? [];
            $newSettings = array_merge($currentSettings, $settings);
            
            $company->update(['settings' => $newSettings]);
            
            // Clear cache
            Cache::forget("company_settings_{$this->company->id}");
            
            Log::info('SecureCompanyMCP: Updated company settings', [
                'company_id' => $this->company->id,
                'updated_settings' => array_keys($settings)
            ]);
            
            $this->auditService->logDataModification('settings_updated', [
                'company_id' => $this->company->id,
                'settings' => array_keys($settings)
            ]);
            
            return [
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => $newSettings
            ];
        } catch (\Exception $e) {
            Log::error('SecureCompanyMCP: Failed to update settings', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Create a new company is NOT ALLOWED in secure version
     * Company creation should be handled by super admin only
     */
    public function createCompany(array $data): array
    {
        $this->auditService->logSecurityEvent('unauthorized_company_creation_attempt', [
            'user_id' => Auth::id(),
            'company_id' => $this->company->id
        ]);
        
        throw new SecurityException('Company creation not allowed through MCP server');
    }
    
    /**
     * Get start date based on period
     */
    protected function getStartDate(string $period): Carbon
    {
        switch ($period) {
            case 'day':
                return Carbon::today();
            case 'week':
                return Carbon::now()->startOfWeek();
            case 'month':
                return Carbon::now()->startOfMonth();
            case 'year':
                return Carbon::now()->startOfYear();
            default:
                return Carbon::now()->startOfMonth();
        }
    }
}