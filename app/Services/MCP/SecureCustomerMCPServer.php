<?php

namespace App\Services\MCP;

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Company;
use App\Services\SecurityAuditService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Exceptions\SecurityException;

/**
 * SECURE MCP Server for Customer Management
 * 
 * Security Enhancements:
 * - All queries strictly scoped to authenticated company
 * - No withoutGlobalScopes usage
 * - Company context validation on all operations
 * - Audit logging for all data access
 * - Phone-based lookups respect company boundaries
 * - No arbitrary company fallbacks
 */
class SecureCustomerMCPServer
{
    protected Company $company;
    protected SecurityAuditService $auditService;
    protected DatabaseMCPServer $databaseMCP;
    
    public function __construct(DatabaseMCPServer $databaseMCP)
    {
        $this->databaseMCP = $databaseMCP;
        $this->auditService = app(SecurityAuditService::class);
        
        // Get authenticated company context
        $user = Auth::user();
        if (!$user || !$user->company_id) {
            throw new SecurityException('No authenticated company context');
        }
        
        $this->company = Company::findOrFail($user->company_id);
        
        Log::info('SecureCustomerMCPServer: Initialized with company context', [
            'company_id' => $this->company->id,
            'user_id' => $user->id
        ]);
    }
    
    /**
     * Get customer details (company-scoped)
     */
    public function getCustomer(int $customerId): array
    {
        try {
            // SECURITY: Verify customer belongs to authenticated company
            $customer = Customer::where('company_id', $this->company->id)
                ->withCount(['appointments', 'calls'])
                ->find($customerId);
                
            if (!$customer) {
                $this->auditService->logSecurityEvent('customer_access_denied', [
                    'customer_id' => $customerId,
                    'company_id' => $this->company->id,
                    'reason' => 'Customer not found or access denied'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Customer not found',
                    'data' => null
                ];
            }
            
            // SECURITY: All appointment stats must be company-scoped
            $appointmentStats = DB::table('appointments')
                ->where('customer_id', $customerId)
                ->where('company_id', $this->company->id) // CRITICAL: Company scope
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
                    COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled,
                    COUNT(CASE WHEN status = "no_show" THEN 1 END) as no_show,
                    MIN(start_time) as first_appointment,
                    MAX(start_time) as last_appointment
                ')
                ->first();
            
            $this->auditService->logDataAccess('customer_viewed', [
                'customer_id' => $customer->id,
                'company_id' => $this->company->id
            ]);
            
            return [
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'company_id' => $customer->company_id,
                    'preferred_language' => $customer->preferred_language,
                    'date_of_birth' => $customer->date_of_birth,
                    'address' => $customer->address,
                    'city' => $customer->city,
                    'postal_code' => $customer->postal_code,
                    'country' => $customer->country,
                    'notes' => $customer->notes,
                    'tags' => $customer->tags ?? [],
                    'preferences' => $customer->preferences ?? [],
                    'is_vip' => $customer->is_vip,
                    'is_blacklisted' => $customer->is_blacklisted,
                    'blacklist_reason' => $customer->blacklist_reason,
                    'statistics' => [
                        'appointments_count' => $customer->appointments_count,
                        'calls_count' => $customer->calls_count,
                        'appointments' => [
                            'total' => $appointmentStats->total,
                            'completed' => $appointmentStats->completed,
                            'cancelled' => $appointmentStats->cancelled,
                            'no_show' => $appointmentStats->no_show,
                            'completion_rate' => $appointmentStats->total > 0 
                                ? round(($appointmentStats->completed / $appointmentStats->total) * 100, 2)
                                : 0
                        ],
                        'first_appointment' => $appointmentStats->first_appointment,
                        'last_appointment' => $appointmentStats->last_appointment,
                        'customer_lifetime' => $appointmentStats->first_appointment 
                            ? Carbon::parse($appointmentStats->first_appointment)->diffInDays(now())
                            : 0
                    ],
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at
                ]
            ];
        } catch (\Exception $e) {
            Log::error('SecureCustomerMCP: Failed to get customer', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get customer: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Create or find customer by phone (company-scoped)
     */
    public function createOrFindByPhone(string $phone, array $data = []): array
    {
        try {
            // Normalize phone number
            $normalizedPhone = $this->normalizePhoneNumber($phone);
            
            // SECURITY: Always scope to authenticated company
            $customer = Customer::where('company_id', $this->company->id)
                ->where('phone', $normalizedPhone)
                ->first();
                
            if ($customer) {
                // Update with new data if provided
                if (!empty($data['name']) && empty($customer->name)) {
                    $customer->name = $data['name'];
                }
                if (!empty($data['email']) && empty($customer->email)) {
                    $customer->email = $data['email'];
                }
                $customer->save();
                
                $this->auditService->logDataAccess('customer_found_by_phone', [
                    'customer_id' => $customer->id,
                    'phone' => $normalizedPhone,
                    'company_id' => $this->company->id
                ]);
                
                return [
                    'success' => true,
                    'created' => false,
                    'message' => 'Existing customer found',
                    'data' => $customer,
                    'customer_id' => $customer->id
                ];
            }
            
            // SECURITY: Create customer only for authenticated company
            $customer = Customer::create([
                'company_id' => $this->company->id, // CRITICAL: Use authenticated company
                'name' => $data['name'] ?? 'Unknown',
                'email' => $data['email'] ?? null,
                'phone' => $normalizedPhone,
                'preferred_language' => $data['preferred_language'] ?? 'de',
                'source' => $data['source'] ?? 'phone',
                'notes' => $data['notes'] ?? null,
                'tags' => $data['tags'] ?? []
            ]);
            
            Log::info('SecureCustomerMCP: Created new customer', [
                'customer_id' => $customer->id,
                'phone' => $normalizedPhone,
                'company_id' => $this->company->id
            ]);
            
            $this->auditService->logDataModification('customer_created', [
                'customer_id' => $customer->id,
                'phone' => $normalizedPhone,
                'company_id' => $this->company->id
            ]);
            
            return [
                'success' => true,
                'created' => true,
                'message' => 'New customer created',
                'data' => $customer,
                'customer_id' => $customer->id
            ];
        } catch (\Exception $e) {
            Log::error('SecureCustomerMCP: Failed to create/find customer', [
                'phone' => $phone,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to create/find customer: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Update customer details (company-scoped)
     */
    public function updateCustomer(int $customerId, array $data): array
    {
        try {
            // SECURITY: Verify customer belongs to company
            $customer = Customer::where('company_id', $this->company->id)
                ->find($customerId);
            
            if (!$customer) {
                $this->auditService->logSecurityEvent('customer_update_denied', [
                    'customer_id' => $customerId,
                    'company_id' => $this->company->id,
                    'reason' => 'Customer not found or access denied'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Customer not found',
                    'data' => null
                ];
            }
            
            // SECURITY: Prevent changing company_id
            unset($data['company_id']);
            
            $customer->update($data);
            
            // Clear cache
            Cache::forget("customer_{$customerId}");
            Cache::tags(['customers', "company_{$this->company->id}"])->flush();
            
            Log::info('SecureCustomerMCP: Updated customer', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'updated_fields' => array_keys($data)
            ]);
            
            $this->auditService->logDataModification('customer_updated', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'fields' => array_keys($data)
            ]);
            
            return [
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => $customer->fresh()
            ];
        } catch (\Exception $e) {
            Log::error('SecureCustomerMCP: Failed to update customer', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to update customer: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Get customer appointment history (company-scoped)
     */
    public function getCustomerAppointments(int $customerId, array $options = []): array
    {
        try {
            // SECURITY: Verify customer belongs to company
            $customer = Customer::where('company_id', $this->company->id)
                ->find($customerId);
                
            if (!$customer) {
                $this->auditService->logSecurityEvent('appointment_access_denied', [
                    'customer_id' => $customerId,
                    'company_id' => $this->company->id,
                    'reason' => 'Customer not found or access denied'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Customer not found',
                    'data' => []
                ];
            }
            
            // SECURITY: All appointments must be company-scoped
            $query = Appointment::where('company_id', $this->company->id)
                ->where('customer_id', $customerId)
                ->with(['staff', 'service', 'branch']);
                
            // Apply filters
            if (!empty($options['status'])) {
                $query->where('status', $options['status']);
            }
            if (!empty($options['from_date'])) {
                $query->where('start_time', '>=', $options['from_date']);
            }
            if (!empty($options['to_date'])) {
                $query->where('start_time', '<=', $options['to_date']);
            }
            
            // Pagination
            $limit = $options['limit'] ?? 20;
            $offset = $options['offset'] ?? 0;
            
            $appointments = $query
                ->orderBy('start_time', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();
                
            $this->auditService->logDataAccess('customer_appointments_viewed', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'count' => $appointments->count()
            ]);
                
            return [
                'success' => true,
                'data' => $appointments->map(function($apt) {
                    return [
                        'id' => $apt->id,
                        'start_time' => $apt->start_time,
                        'end_time' => $apt->end_time,
                        'status' => $apt->status,
                        'staff_name' => $apt->staff->name ?? 'Unassigned',
                        'service_name' => $apt->service->name ?? 'General',
                        'branch_name' => $apt->branch->name,
                        'price' => $apt->price,
                        'notes' => $apt->notes
                    ];
                }),
                'count' => $appointments->count(),
                'total' => $query->count()
            ];
        } catch (\Exception $e) {
            Log::error('SecureCustomerMCP: Failed to get customer appointments', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get appointments: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Get customer call history (company-scoped)
     */
    public function getCustomerCalls(int $customerId, array $options = []): array
    {
        try {
            // SECURITY: Verify customer belongs to company
            $customer = Customer::where('company_id', $this->company->id)
                ->find($customerId);
                
            if (!$customer) {
                $this->auditService->logSecurityEvent('call_access_denied', [
                    'customer_id' => $customerId,
                    'company_id' => $this->company->id,
                    'reason' => 'Customer not found or access denied'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Customer not found',
                    'data' => []
                ];
            }
            
            // SECURITY: All calls must be company-scoped
            $query = Call::where('company_id', $this->company->id)
                ->where('customer_id', $customerId);
                
            // Apply filters
            if (!empty($options['from_date'])) {
                $query->where('created_at', '>=', $options['from_date']);
            }
            if (!empty($options['to_date'])) {
                $query->where('created_at', '<=', $options['to_date']);
            }
            
            // Pagination
            $limit = $options['limit'] ?? 20;
            $offset = $options['offset'] ?? 0;
            
            $calls = $query
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();
                
            $this->auditService->logDataAccess('customer_calls_viewed', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'count' => $calls->count()
            ]);
                
            return [
                'success' => true,
                'data' => $calls->map(function($call) {
                    return [
                        'id' => $call->id,
                        'retell_call_id' => $call->retell_call_id,
                        'start_timestamp' => $call->start_timestamp,
                        'ended_at' => $call->end_timestamp,
                        'duration' => $call->duration,
                        'status' => $call->status,
                        'direction' => $call->direction,
                        'appointment_booked' => $call->appointment_booked,
                        'summary' => $call->summary,
                        'created_at' => $call->created_at
                    ];
                }),
                'count' => $calls->count(),
                'total' => $query->count()
            ];
        } catch (\Exception $e) {
            Log::error('SecureCustomerMCP: Failed to get customer calls', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get calls: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Check if customer is blacklisted (company-scoped)
     */
    public function checkBlacklist(int $customerId): array
    {
        try {
            // SECURITY: Verify customer belongs to company
            $customer = Customer::where('company_id', $this->company->id)
                ->select(['id', 'is_blacklisted', 'blacklist_reason', 'blacklisted_at'])
                ->find($customerId);
                
            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Customer not found',
                    'is_blacklisted' => false
                ];
            }
            
            $this->auditService->logDataAccess('blacklist_checked', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'is_blacklisted' => $customer->is_blacklisted
            ]);
            
            return [
                'success' => true,
                'is_blacklisted' => $customer->is_blacklisted,
                'reason' => $customer->blacklist_reason,
                'blacklisted_at' => $customer->blacklisted_at
            ];
        } catch (\Exception $e) {
            Log::error('SecureCustomerMCP: Failed to check blacklist', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to check blacklist: ' . $e->getMessage(),
                'is_blacklisted' => false
            ];
        }
    }
    
    /**
     * Update customer preferences (company-scoped)
     */
    public function updatePreferences(int $customerId, array $preferences): array
    {
        try {
            // SECURITY: Verify customer belongs to company
            $customer = Customer::where('company_id', $this->company->id)
                ->find($customerId);
            
            if (!$customer) {
                $this->auditService->logSecurityEvent('preferences_update_denied', [
                    'customer_id' => $customerId,
                    'company_id' => $this->company->id,
                    'reason' => 'Customer not found or access denied'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Customer not found',
                    'data' => null
                ];
            }
            
            // Merge with existing preferences
            $currentPreferences = $customer->preferences ?? [];
            $newPreferences = array_merge($currentPreferences, $preferences);
            
            $customer->update(['preferences' => $newPreferences]);
            
            Log::info('SecureCustomerMCP: Updated customer preferences', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'updated_preferences' => array_keys($preferences)
            ]);
            
            $this->auditService->logDataModification('preferences_updated', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'preferences' => array_keys($preferences)
            ]);
            
            return [
                'success' => true,
                'message' => 'Preferences updated successfully',
                'data' => $newPreferences
            ];
        } catch (\Exception $e) {
            Log::error('SecureCustomerMCP: Failed to update preferences', [
                'customer_id' => $customerId,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to update preferences: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Search customers (company-scoped)
     */
    public function searchCustomers(array $criteria): array
    {
        try {
            // SECURITY: Always scope to authenticated company
            $query = Customer::where('company_id', $this->company->id);
            
            // Apply search criteria
            if (!empty($criteria['query'])) {
                $searchTerm = '%' . $criteria['query'] . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm)
                      ->orWhere('email', 'like', $searchTerm)
                      ->orWhere('phone', 'like', $searchTerm);
                });
            }
            
            // SECURITY: Ignore any company_id in criteria
            // Only search within authenticated company
            
            if (!empty($criteria['tags'])) {
                $query->whereJsonContains('tags', $criteria['tags']);
            }
            
            if (isset($criteria['is_vip'])) {
                $query->where('is_vip', $criteria['is_vip']);
            }
            
            if (isset($criteria['is_blacklisted'])) {
                $query->where('is_blacklisted', $criteria['is_blacklisted']);
            }
            
            // Pagination
            $limit = $criteria['limit'] ?? 20;
            $offset = $criteria['offset'] ?? 0;
            
            $customers = $query
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();
                
            $this->auditService->logDataAccess('customer_search', [
                'company_id' => $this->company->id,
                'criteria' => array_keys($criteria),
                'results_count' => $customers->count()
            ]);
                
            return [
                'success' => true,
                'data' => $customers->map(function($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'is_vip' => $customer->is_vip,
                        'is_blacklisted' => $customer->is_blacklisted,
                        'tags' => $customer->tags ?? []
                    ];
                }),
                'count' => $customers->count(),
                'total' => $query->count()
            ];
        } catch (\Exception $e) {
            Log::error('SecureCustomerMCP: Failed to search customers', [
                'criteria' => $criteria,
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to search customers: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Normalize phone number
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Ensure it starts with +
        if (!str_starts_with($phone, '+')) {
            // Assume German number if no country code
            if (str_starts_with($phone, '0')) {
                $phone = '+49' . substr($phone, 1);
            } else {
                $phone = '+' . $phone;
            }
        }
        
        return $phone;
    }
}