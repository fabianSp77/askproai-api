<?php

namespace App\Services\MCP;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * MCP Server for Company Management
 * Handles company-wide operations, settings, and analytics
 */
class CompanyMCPServer
{
    protected DatabaseMCPServer $databaseMCP;
    protected BranchMCPServer $branchMCP;
    
    public function __construct(
        DatabaseMCPServer $databaseMCP,
        BranchMCPServer $branchMCP
    ) {
        $this->databaseMCP = $databaseMCP;
        $this->branchMCP = $branchMCP;
    }
    
    /**
     * Get company details by ID
     */
    public function getCompany(int $companyId): array
    {
        try {
            $company = Company::withoutGlobalScopes()
                ->withCount(['branches', 'staff', 'customers', 'appointments'])
                ->find($companyId);
                
            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ];
            }
            
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
            Log::error('CompanyMCP: Failed to get company', [
                'company_id' => $companyId,
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
     * Create a new company
     */
    public function createCompany(array $data): array
    {
        try {
            DB::beginTransaction();
            
            $company = Company::create([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'] ?? $data['name'],
                'tax_id' => $data['tax_id'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'website' => $data['website'] ?? null,
                'industry' => $data['industry'] ?? 'other',
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? 'DE',
                'timezone' => $data['timezone'] ?? 'Europe/Berlin',
                'currency' => $data['currency'] ?? 'EUR',
                'is_active' => $data['is_active'] ?? true,
                'settings' => $data['settings'] ?? [],
                'subscription_plan' => $data['subscription_plan'] ?? 'trial',
                'subscription_status' => $data['subscription_status'] ?? 'trialing',
                'trial_ends_at' => $data['trial_ends_at'] ?? Carbon::now()->addDays(14)
            ]);
            
            // Create default branch if requested
            if ($data['create_default_branch'] ?? true) {
                $this->branchMCP->createBranch([
                    'company_id' => $company->id,
                    'name' => $data['default_branch_name'] ?? 'Hauptfiliale',
                    'address' => $company->address,
                    'city' => $company->city,
                    'postal_code' => $company->postal_code,
                    'country' => $company->country,
                    'phone_number' => $company->phone,
                    'email' => $company->email,
                    'create_default_hours' => true
                ]);
            }
            
            DB::commit();
            
            Log::info('CompanyMCP: Created new company', [
                'company_id' => $company->id,
                'name' => $company->name
            ]);
            
            return [
                'success' => true,
                'message' => 'Company created successfully',
                'data' => $company,
                'company_id' => $company->id
            ];
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('CompanyMCP: Failed to create company', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to create company: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Update company details
     */
    public function updateCompany(int $companyId, array $data): array
    {
        try {
            $company = Company::withoutGlobalScopes()->find($companyId);
            
            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ];
            }
            
            // Handle encrypted fields
            if (isset($data['calcom_api_key'])) {
                $data['calcom_api_key'] = encrypt($data['calcom_api_key']);
            }
            if (isset($data['retell_api_key'])) {
                $data['retell_api_key'] = encrypt($data['retell_api_key']);
            }
            
            $company->update($data);
            
            // Clear cache
            Cache::forget("company_{$companyId}");
            Cache::tags(['companies'])->flush();
            
            Log::info('CompanyMCP: Updated company', [
                'company_id' => $companyId,
                'updated_fields' => array_keys($data)
            ]);
            
            return [
                'success' => true,
                'message' => 'Company updated successfully',
                'data' => $company->fresh()
            ];
        } catch (\Exception $e) {
            Log::error('CompanyMCP: Failed to update company', [
                'company_id' => $companyId,
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
     * Get company statistics
     */
    public function getCompanyStatistics(int $companyId, array $options = []): array
    {
        try {
            $period = $options['period'] ?? 'month'; // day, week, month, year
            $startDate = $this->getStartDate($period);
            
            // Basic counts
            $branches = Branch::where('company_id', $companyId)->count();
            $staff = Staff::where('company_id', $companyId)->count();
            $customers = Customer::where('company_id', $companyId)->count();
            
            // Appointment statistics
            $appointments = DB::table('appointments')
                ->where('company_id', $companyId)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
                    COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled,
                    COUNT(CASE WHEN status = "no_show" THEN 1 END) as no_show,
                    AVG(CASE WHEN status = "completed" THEN TIMESTAMPDIFF(MINUTE, start_time, end_time) END) as avg_duration_minutes
                ')
                ->first();
            
            // Revenue statistics (if price is tracked)
            $revenue = DB::table('appointments')
                ->where('company_id', $companyId)
                ->where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('price') ?? 0;
            
            // Call statistics
            $calls = DB::table('calls')
                ->where('company_id', $companyId)
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
                        'currency' => Company::find($companyId)->currency ?? 'EUR',
                        'per_appointment' => $appointments->completed > 0 
                            ? round($revenue / $appointments->completed, 2) 
                            : 0
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('CompanyMCP: Failed to get company statistics', [
                'company_id' => $companyId,
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
     * Get company integrations status
     */
    public function getIntegrationsStatus(int $companyId): array
    {
        try {
            $company = Company::withoutGlobalScopes()->find($companyId);
            
            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ];
            }
            
            $integrations = [
                'calcom' => [
                    'configured' => !empty($company->calcom_api_key),
                    'active' => $company->calcom_integration_active,
                    'team_slug' => $company->calcom_team_slug,
                    'last_sync' => Cache::get("calcom_last_sync_{$companyId}"),
                    'event_types_count' => DB::table('calcom_event_types')
                        ->whereIn('branch_id', Branch::where('company_id', $companyId)->pluck('id'))
                        ->count()
                ],
                'retell' => [
                    'configured' => !empty($company->retell_api_key),
                    'active' => $company->retell_integration_active,
                    'default_agent_id' => $company->retell_agent_id,
                    'agents_count' => DB::table('phone_numbers')
                        ->whereIn('branch_id', Branch::where('company_id', $companyId)->pluck('id'))
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
            
            return [
                'success' => true,
                'data' => $integrations,
                'all_configured' => collect($integrations)
                    ->filter(fn($i) => isset($i['configured']))
                    ->every(fn($i) => $i['configured'])
            ];
        } catch (\Exception $e) {
            Log::error('CompanyMCP: Failed to get integrations status', [
                'company_id' => $companyId,
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
     * Update company settings
     */
    public function updateSettings(int $companyId, array $settings): array
    {
        try {
            $company = Company::withoutGlobalScopes()->find($companyId);
            
            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ];
            }
            
            // Merge with existing settings
            $currentSettings = $company->settings ?? [];
            $newSettings = array_merge($currentSettings, $settings);
            
            $company->update(['settings' => $newSettings]);
            
            // Clear cache
            Cache::forget("company_settings_{$companyId}");
            
            Log::info('CompanyMCP: Updated company settings', [
                'company_id' => $companyId,
                'updated_settings' => array_keys($settings)
            ]);
            
            return [
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => $newSettings
            ];
        } catch (\Exception $e) {
            Log::error('CompanyMCP: Failed to update settings', [
                'company_id' => $companyId,
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