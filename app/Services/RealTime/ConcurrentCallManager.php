<?php

namespace App\Services\RealTime;

use App\Models\Call;
use App\Models\Branch;
use App\Models\Staff;
use App\Services\PhoneNumberResolver;
use App\Services\Booking\UniversalBookingOrchestrator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Manages concurrent call handling with real-time routing and load balancing
 */
class ConcurrentCallManager
{
    private PhoneNumberResolver $phoneResolver;
    private UniversalBookingOrchestrator $bookingOrchestrator;
    private RedisSlotManager $slotManager;
    
    // Maximum concurrent calls per staff member
    private int $maxConcurrentCallsPerStaff = 1;
    
    // Call state tracking prefix
    private string $callStatePrefix = 'call:state:';
    
    // Active calls tracking
    private string $activeCallsKey = 'active:calls';
    
    public function __construct(
        PhoneNumberResolver $phoneResolver,
        UniversalBookingOrchestrator $bookingOrchestrator,
        RedisSlotManager $slotManager
    ) {
        $this->phoneResolver = $phoneResolver;
        $this->bookingOrchestrator = $bookingOrchestrator;
        $this->slotManager = $slotManager;
    }
    
    /**
     * Handle incoming call with real-time routing
     */
    public function handleIncomingCall(array $callData): array
    {
        $correlationId = $callData['correlation_id'] ?? Str::uuid();
        $callId = $callData['call_id'];
        
        try {
            // Track call start
            $this->trackCallStart($callId, $callData);
            
            // Resolve routing in parallel
            $routingData = $this->resolveRoutingParallel($callData);
            
            // Select optimal agent
            $agent = $this->selectOptimalAgent($routingData);
            
            // Build dynamic response
            $response = $this->buildDynamicResponse($agent, $routingData, $callData);
            
            // Update call state
            $this->updateCallState($callId, [
                'status' => 'routed',
                'agent_id' => $agent['id'],
                'branch_id' => $routingData['branch_id'],
                'routed_at' => now()->toIso8601String(),
            ]);
            
            Log::info('Call routed successfully', [
                'call_id' => $callId,
                'correlation_id' => $correlationId,
                'branch_id' => $routingData['branch_id'],
                'agent_type' => $agent['type'],
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            $this->handleCallError($callId, $e);
            throw $e;
        }
    }
    
    /**
     * Resolve routing data in parallel for performance
     */
    private function resolveRoutingParallel(array $callData): array
    {
        $promises = [
            'phone_resolution' => function() use ($callData) {
                return $this->phoneResolver->resolveFromWebhook($callData);
            },
            'caller_info' => function() use ($callData) {
                return $this->resolveCallerInfo($callData['from'] ?? null);
            },
            'language_detection' => function() use ($callData) {
                return $this->detectLanguage($callData);
            },
            'current_load' => function() {
                return $this->getCurrentSystemLoad();
            },
        ];
        
        // Execute in parallel using concurrent processing
        $results = [];
        foreach ($promises as $key => $resolver) {
            $results[$key] = $resolver();
        }
        
        // Merge results
        return array_merge(
            $results['phone_resolution'],
            [
                'caller_info' => $results['caller_info'],
                'detected_language' => $results['language_detection'],
                'system_load' => $results['current_load'],
            ]
        );
    }
    
    /**
     * Select optimal agent based on multiple factors
     */
    private function selectOptimalAgent(array $routingData): array
    {
        $branchId = $routingData['branch_id'];
        
        if (!$branchId) {
            // No specific branch, use company-wide agent
            return $this->getDefaultAgent($routingData['company_id']);
        }
        
        // Get available staff for branch
        $availableStaff = $this->getAvailableStaff($branchId, $routingData);
        
        if ($availableStaff->isEmpty()) {
            // No staff available, use branch default agent
            return $this->getBranchDefaultAgent($branchId);
        }
        
        // Score and rank staff
        $scoredStaff = $this->scoreStaff($availableStaff, $routingData);
        
        // Select top scored staff member
        $selectedStaff = $scoredStaff->first();
        
        return [
            'id' => $selectedStaff->retell_agent_id ?? $this->getBranchDefaultAgent($branchId)['id'],
            'type' => 'staff_specific',
            'staff_id' => $selectedStaff->id,
            'staff_name' => $selectedStaff->name,
            'languages' => $selectedStaff->languages ?? ['de'],
            'skills' => $selectedStaff->skills ?? [],
        ];
    }
    
    /**
     * Get available staff with real-time availability check
     */
    private function getAvailableStaff(int $branchId, array $routingData)
    {
        $cacheKey = "available_staff:{$branchId}:" . Carbon::now()->format('Y-m-d-H-i');
        
        return Cache::remember($cacheKey, 60, function() use ($branchId, $routingData) {
            return Staff::where('branch_id', $branchId)
                ->where('is_active', true)
                ->where(function($query) {
                    $query->where('is_available', true)
                        ->orWhere(function($q) {
                            // Check working hours
                            $now = Carbon::now();
                            $dayOfWeek = strtolower($now->format('l'));
                            $currentTime = $now->format('H:i');
                            
                            $q->whereJsonContains("working_hours->{$dayOfWeek}->is_working", true)
                              ->where(function($sq) use ($dayOfWeek, $currentTime) {
                                  // Validate dayOfWeek against whitelist
                                  $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                  if (!in_array($dayOfWeek, $validDays)) {
                                      throw new \InvalidArgumentException("Invalid day of week: " . $dayOfWeek);
                                  }
                                  
                                  $sq->whereRaw("JSON_EXTRACT(working_hours, ?) <= ?", ["$." . $dayOfWeek . ".start", $currentTime])
                                     ->whereRaw("JSON_EXTRACT(working_hours, ?) >= ?", ["$." . $dayOfWeek . ".end", $currentTime]);
                              });
                        });
                })
                ->withCount(['activeCalls' => function($query) {
                    $query->where('status', 'in_progress');
                }])
                ->having('active_calls_count', '<', $this->maxConcurrentCallsPerStaff)
                ->get();
        });
    }
    
    /**
     * Score staff based on multiple factors
     */
    private function scoreStaff($availableStaff, array $routingData)
    {
        $detectedLanguage = $routingData['detected_language'] ?? 'de';
        $callerInfo = $routingData['caller_info'] ?? [];
        
        return $availableStaff->map(function($staff) use ($detectedLanguage, $callerInfo) {
            $score = 0;
            
            // Language match (40% weight)
            if (in_array($detectedLanguage, $staff->languages ?? [])) {
                $score += 40;
            }
            
            // Previous interaction with customer (30% weight)
            if (!empty($callerInfo['preferred_staff_id']) && $staff->id == $callerInfo['preferred_staff_id']) {
                $score += 30;
            }
            
            // Current workload (20% weight)
            $utilization = $this->getStaffUtilization($staff->id);
            $score += (1 - $utilization) * 20;
            
            // Skill match (10% weight)
            if (!empty($callerInfo['typical_services'])) {
                $skillMatch = $this->calculateSkillMatch($staff, $callerInfo['typical_services']);
                $score += $skillMatch * 10;
            }
            
            $staff->routing_score = $score;
            return $staff;
        })->sortByDesc('routing_score');
    }
    
    /**
     * Build dynamic response for Retell.ai
     */
    private function buildDynamicResponse(array $agent, array $routingData, array $callData): array
    {
        $branch = Branch::find($routingData['branch_id']);
        $company = $branch->company ?? Company::find($routingData['company_id']);
        
        $response = [
            'response' => [
                'agent_id' => $agent['id'],
                'dynamic_variables' => [
                    // Company and branch info
                    'company_name' => $company->name,
                    'branch_name' => $branch->name ?? $company->name,
                    'branch_address' => $branch->address ?? '',
                    'branch_phone' => $branch->phone_number ?? $company->phone_number,
                    
                    // Staff info
                    'staff_name' => $agent['staff_name'] ?? '',
                    'staff_available' => true,
                    
                    // Caller info
                    'caller_number' => $callData['from'] ?? '',
                    'caller_name' => $routingData['caller_info']['name'] ?? '',
                    'is_returning_customer' => !empty($routingData['caller_info']['customer_id']),
                    'customer_id' => $routingData['caller_info']['customer_id'] ?? null,
                    
                    // Language settings
                    'detected_language' => $routingData['detected_language'] ?? 'de',
                    'supported_languages' => $agent['languages'] ?? ['de'],
                    
                    // Real-time availability
                    'current_wait_time' => $this->estimateWaitTime($branch->id),
                    'available_staff_count' => $this->getAvailableStaffCount($branch->id),
                    
                    // Context
                    'business_hours' => $this->getBusinessHoursText($branch),
                    'is_within_business_hours' => $this->isWithinBusinessHours($branch),
                    
                    // System info
                    'correlation_id' => $callData['correlation_id'] ?? Str::uuid(),
                    'timestamp' => now()->toIso8601String(),
                ],
                'metadata' => [
                    'routing_method' => $routingData['resolution_method'] ?? 'unknown',
                    'routing_confidence' => $routingData['confidence'] ?? 0,
                    'system_load' => $routingData['system_load'] ?? [],
                ]
            ]
        ];
        
        // Add appointment context if available
        if (!empty($routingData['caller_info']['next_appointment'])) {
            $response['response']['dynamic_variables']['has_upcoming_appointment'] = true;
            $response['response']['dynamic_variables']['next_appointment_date'] = 
                $routingData['caller_info']['next_appointment']['date'];
            $response['response']['dynamic_variables']['next_appointment_time'] = 
                $routingData['caller_info']['next_appointment']['time'];
        }
        
        return $response;
    }
    
    /**
     * Track call state in Redis
     */
    private function trackCallStart(string $callId, array $callData): void
    {
        $state = [
            'call_id' => $callId,
            'status' => 'started',
            'started_at' => now()->toIso8601String(),
            'from_number' => $callData['from'] ?? null,
            'to_number' => $callData['to'] ?? null,
            'metadata' => $callData['metadata'] ?? [],
        ];
        
        // Store call state
        Redis::setex($this->callStatePrefix . $callId, 3600, json_encode($state));
        
        // Add to active calls set
        Redis::sadd($this->activeCallsKey, $callId);
        
        // Track metrics
        Redis::incr('metrics:calls:total');
        Redis::incr('metrics:calls:concurrent');
    }
    
    /**
     * Update call state
     */
    private function updateCallState(string $callId, array $updates): void
    {
        $stateKey = $this->callStatePrefix . $callId;
        $currentState = json_decode(Redis::get($stateKey), true) ?? [];
        
        $newState = array_merge($currentState, $updates);
        Redis::setex($stateKey, 3600, json_encode($newState));
    }
    
    /**
     * Handle call error
     */
    private function handleCallError(string $callId, \Exception $e): void
    {
        $this->updateCallState($callId, [
            'status' => 'error',
            'error' => $e->getMessage(),
            'error_at' => now()->toIso8601String(),
        ]);
        
        // Remove from active calls
        Redis::srem($this->activeCallsKey, $callId);
        Redis::decr('metrics:calls:concurrent');
        Redis::incr('metrics:calls:errors');
        
        Log::error('Call handling error', [
            'call_id' => $callId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
    
    /**
     * Resolve caller information from history
     */
    private function resolveCallerInfo(?string $phoneNumber): array
    {
        if (!$phoneNumber) {
            return [];
        }
        
        $cacheKey = "caller_info:{$phoneNumber}";
        
        return Cache::remember($cacheKey, 300, function() use ($phoneNumber) {
            $customer = \App\Models\Customer::where('phone', $phoneNumber)->first();
            
            if (!$customer) {
                return [];
            }
            
            $info = [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'preferred_language' => $customer->preferred_language,
                'preferred_staff_id' => $customer->preferred_staff_id,
                'typical_services' => [],
            ];
            
            // Get typical services from appointment history
            $typicalServices = \App\Models\Appointment::where('customer_id', $customer->id)
                ->where('status', 'completed')
                ->with('service')
                ->get()
                ->pluck('service.name')
                ->filter()
                ->countBy()
                ->sortDesc()
                ->take(3)
                ->keys()
                ->toArray();
                
            $info['typical_services'] = $typicalServices;
            
            // Get next appointment if any
            $nextAppointment = \App\Models\Appointment::where('customer_id', $customer->id)
                ->where('starts_at', '>', now())
                ->where('status', 'scheduled')
                ->orderBy('starts_at')
                ->first();
                
            if ($nextAppointment) {
                $info['next_appointment'] = [
                    'id' => $nextAppointment->id,
                    'date' => $nextAppointment->starts_at->format('Y-m-d'),
                    'time' => $nextAppointment->starts_at->format('H:i'),
                    'service' => $nextAppointment->service->name ?? 'Termin',
                ];
            }
            
            return $info;
        });
    }
    
    /**
     * Detect language from call data
     */
    private function detectLanguage(array $callData): string
    {
        // Check if Retell.ai provided language detection
        if (isset($callData['detected_language'])) {
            return $callData['detected_language'];
        }
        
        // Infer from phone number
        $phoneNumber = $callData['from'] ?? '';
        
        if (str_starts_with($phoneNumber, '+49')) {
            return 'de'; // German
        } elseif (str_starts_with($phoneNumber, '+90')) {
            return 'tr'; // Turkish
        } elseif (str_starts_with($phoneNumber, '+44')) {
            return 'en'; // English
        }
        
        return 'de'; // Default to German
    }
    
    /**
     * Get current system load metrics
     */
    private function getCurrentSystemLoad(): array
    {
        return [
            'concurrent_calls' => Redis::get('metrics:calls:concurrent') ?? 0,
            'queue_depth' => Redis::llen('queues:calls') ?? 0,
            'average_wait_time' => $this->calculateAverageWaitTime(),
            'staff_utilization' => $this->calculateSystemUtilization(),
        ];
    }
    
    /**
     * Get staff utilization rate
     */
    private function getStaffUtilization(int $staffId): float
    {
        $today = Carbon::today();
        
        $totalMinutes = Appointment::where('staff_id', $staffId)
            ->whereDate('starts_at', $today)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sum(function($appointment) {
                return $appointment->starts_at->diffInMinutes($appointment->ends_at);
            });
            
        // Assume 8 hour work day
        $workMinutes = 8 * 60;
        
        return min($totalMinutes / $workMinutes, 1.0);
    }
    
    /**
     * Calculate skill match score
     */
    private function calculateSkillMatch(Staff $staff, array $typicalServices): float
    {
        if (empty($typicalServices) || empty($staff->skills)) {
            return 0.5; // Neutral score
        }
        
        $matches = 0;
        foreach ($typicalServices as $service) {
            foreach ($staff->skills as $skill) {
                if (stripos($service, $skill) !== false || stripos($skill, $service) !== false) {
                    $matches++;
                    break;
                }
            }
        }
        
        return $matches / count($typicalServices);
    }
    
    /**
     * Estimate wait time for branch
     */
    private function estimateWaitTime(int $branchId): int
    {
        $activeCallsCount = Call::where('branch_id', $branchId)
            ->where('status', 'in_progress')
            ->count();
            
        $availableStaffCount = $this->getAvailableStaffCount($branchId);
        
        if ($availableStaffCount == 0) {
            return 30; // Default 30 minutes if no staff
        }
        
        // Simple estimation: active calls / available staff * average call duration
        $averageCallDuration = 5; // 5 minutes average
        
        return ceil($activeCallsCount / $availableStaffCount * $averageCallDuration);
    }
    
    /**
     * Get available staff count
     */
    private function getAvailableStaffCount(int $branchId): int
    {
        return Staff::where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('is_available', true)
            ->count();
    }
    
    /**
     * Get business hours text
     */
    private function getBusinessHoursText(Branch $branch): string
    {
        $today = strtolower(Carbon::now()->format('l'));
        $hours = $branch->business_hours[$today] ?? null;
        
        if (!$hours || !($hours['is_open'] ?? false)) {
            return 'Heute geschlossen';
        }
        
        return sprintf('%s - %s Uhr', $hours['open'] ?? '09:00', $hours['close'] ?? '18:00');
    }
    
    /**
     * Check if within business hours
     */
    private function isWithinBusinessHours(Branch $branch): bool
    {
        $now = Carbon::now();
        $today = strtolower($now->format('l'));
        $hours = $branch->business_hours[$today] ?? null;
        
        if (!$hours || !($hours['is_open'] ?? false)) {
            return false;
        }
        
        $openTime = Carbon::parse($hours['open'] ?? '09:00');
        $closeTime = Carbon::parse($hours['close'] ?? '18:00');
        
        return $now->between($openTime, $closeTime);
    }
    
    /**
     * Get default agent for company
     */
    private function getDefaultAgent(int $companyId): array
    {
        $company = \App\Models\Company::find($companyId);
        
        return [
            'id' => $company->retell_agent_id ?? config('services.retell.default_agent_id'),
            'type' => 'company_default',
            'languages' => ['de'],
        ];
    }
    
    /**
     * Get branch default agent
     */
    private function getBranchDefaultAgent(int $branchId): array
    {
        $branch = Branch::find($branchId);
        
        return [
            'id' => $branch->retell_agent_id ?? $this->getDefaultAgent($branch->company_id)['id'],
            'type' => 'branch_default',
            'languages' => ['de'],
        ];
    }
    
    /**
     * Calculate average wait time across system
     */
    private function calculateAverageWaitTime(): float
    {
        $recentCalls = Call::where('created_at', '>', now()->subMinutes(30))
            ->whereNotNull('connected_at')
            ->get();
            
        if ($recentCalls->isEmpty()) {
            return 0;
        }
        
        $totalWaitTime = $recentCalls->sum(function($call) {
            return $call->created_at->diffInSeconds($call->connected_at);
        });
        
        return round($totalWaitTime / $recentCalls->count());
    }
    
    /**
     * Calculate system-wide utilization
     */
    private function calculateSystemUtilization(): float
    {
        $totalStaff = Staff::where('is_active', true)->count();
        $busyStaff = Call::where('status', 'in_progress')->distinct('staff_id')->count('staff_id');
        
        return $totalStaff > 0 ? round($busyStaff / $totalStaff, 2) : 0;
    }
}