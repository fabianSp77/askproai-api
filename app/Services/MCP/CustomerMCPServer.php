<?php

namespace App\Services\MCP;

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * MCP Server for Customer Management
 * Handles customer data, history, and preferences
 */
class CustomerMCPServer
{
    protected DatabaseMCPServer $databaseMCP;
    
    public function __construct(DatabaseMCPServer $databaseMCP)
    {
        $this->databaseMCP = $databaseMCP;
    }
    
    /**
     * Get customer details
     */
    public function getCustomer(int $customerId): array
    {
        try {
            $customer = Customer::withoutGlobalScopes()
                ->withCount(['appointments', 'calls'])
                ->find($customerId);
                
            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Customer not found',
                    'data' => null
                ];
            }
            
            // Get appointment statistics
            $appointmentStats = DB::table('appointments')
                ->where('customer_id', $customerId)
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
                    COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled,
                    COUNT(CASE WHEN status = "no_show" THEN 1 END) as no_show,
                    MIN(start_time) as first_appointment,
                    MAX(start_time) as last_appointment
                ')
                ->first();
            
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
            Log::error('CustomerMCP: Failed to get customer', [
                'customer_id' => $customerId,
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
     * Create or find customer by phone
     */
    public function createOrFindByPhone(string $phone, array $data = []): array
    {
        try {
            // Normalize phone number
            $normalizedPhone = $this->normalizePhoneNumber($phone);
            
            // Try to find existing customer
            $customer = Customer::withoutGlobalScopes()
                ->where('phone', $normalizedPhone)
                ->where('company_id', $data['company_id'] ?? null)
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
                
                return [
                    'success' => true,
                    'created' => false,
                    'message' => 'Existing customer found',
                    'data' => $customer,
                    'customer_id' => $customer->id
                ];
            }
            
            // Create new customer
            $customer = Customer::create([
                'company_id' => $data['company_id'],
                'name' => $data['name'] ?? 'Unknown',
                'email' => $data['email'] ?? null,
                'phone' => $normalizedPhone,
                'preferred_language' => $data['preferred_language'] ?? 'de',
                'source' => $data['source'] ?? 'phone',
                'notes' => $data['notes'] ?? null,
                'tags' => $data['tags'] ?? []
            ]);
            
            Log::info('CustomerMCP: Created new customer', [
                'customer_id' => $customer->id,
                'phone' => $normalizedPhone
            ]);
            
            return [
                'success' => true,
                'created' => true,
                'message' => 'New customer created',
                'data' => $customer,
                'customer_id' => $customer->id
            ];
        } catch (\Exception $e) {
            Log::error('CustomerMCP: Failed to create/find customer', [
                'phone' => $phone,
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
     * Update customer details
     */
    public function updateCustomer(int $customerId, array $data): array
    {
        try {
            $customer = Customer::withoutGlobalScopes()->find($customerId);
            
            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Customer not found',
                    'data' => null
                ];
            }
            
            $customer->update($data);
            
            // Clear cache
            Cache::forget("customer_{$customerId}");
            Cache::tags(['customers', "company_{$customer->company_id}"])->flush();
            
            Log::info('CustomerMCP: Updated customer', [
                'customer_id' => $customerId,
                'updated_fields' => array_keys($data)
            ]);
            
            return [
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => $customer->fresh()
            ];
        } catch (\Exception $e) {
            Log::error('CustomerMCP: Failed to update customer', [
                'customer_id' => $customerId,
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
     * Get customer appointment history
     */
    public function getCustomerAppointments(int $customerId, array $options = []): array
    {
        try {
            $query = Appointment::withoutGlobalScopes()
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
            Log::error('CustomerMCP: Failed to get customer appointments', [
                'customer_id' => $customerId,
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
     * Get customer call history
     */
    public function getCustomerCalls(int $customerId, array $options = []): array
    {
        try {
            $query = Call::withoutGlobalScopes()
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
                
            return [
                'success' => true,
                'data' => $calls->map(function($call) {
                    return [
                        'id' => $call->id,
                        'retell_call_id' => $call->retell_call_id,
                        'start_timestamp' => $call->start_timestamp,
                        'end_timestamp' => $call->end_timestamp,
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
            Log::error('CustomerMCP: Failed to get customer calls', [
                'customer_id' => $customerId,
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
     * Check if customer is blacklisted
     */
    public function checkBlacklist(int $customerId): array
    {
        try {
            $customer = Customer::withoutGlobalScopes()
                ->select(['id', 'is_blacklisted', 'blacklist_reason', 'blacklisted_at'])
                ->find($customerId);
                
            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Customer not found',
                    'is_blacklisted' => false
                ];
            }
            
            return [
                'success' => true,
                'is_blacklisted' => $customer->is_blacklisted,
                'reason' => $customer->blacklist_reason,
                'blacklisted_at' => $customer->blacklisted_at
            ];
        } catch (\Exception $e) {
            Log::error('CustomerMCP: Failed to check blacklist', [
                'customer_id' => $customerId,
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
     * Update customer preferences
     */
    public function updatePreferences(int $customerId, array $preferences): array
    {
        try {
            $customer = Customer::withoutGlobalScopes()->find($customerId);
            
            if (!$customer) {
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
            
            Log::info('CustomerMCP: Updated customer preferences', [
                'customer_id' => $customerId,
                'updated_preferences' => array_keys($preferences)
            ]);
            
            return [
                'success' => true,
                'message' => 'Preferences updated successfully',
                'data' => $newPreferences
            ];
        } catch (\Exception $e) {
            Log::error('CustomerMCP: Failed to update preferences', [
                'customer_id' => $customerId,
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
     * Search customers
     */
    public function searchCustomers(array $criteria): array
    {
        try {
            $query = Customer::withoutGlobalScopes();
            
            // Apply search criteria
            if (!empty($criteria['query'])) {
                $searchTerm = '%' . $criteria['query'] . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm)
                      ->orWhere('email', 'like', $searchTerm)
                      ->orWhere('phone', 'like', $searchTerm);
                });
            }
            
            if (!empty($criteria['company_id'])) {
                $query->where('company_id', $criteria['company_id']);
            }
            
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
            Log::error('CustomerMCP: Failed to search customers', [
                'criteria' => $criteria,
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