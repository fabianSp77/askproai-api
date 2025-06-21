# MCP Service Layer Architecture - Complete Design

## Executive Summary

This document outlines the complete Model Context Protocol (MCP) service layer architecture for AskProAI. The design provides a robust, scalable, and maintainable foundation for multi-tenant SaaS operations with clear service boundaries, comprehensive error handling, and production-ready patterns.

## 1. Core MCP Services Architecture

### 1.1 Service Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        MCP Orchestrator                         │
│                   (Central Router & Coordinator)                │
└───────────────┬─────────────────────────────┬──────────────────┘
                │                             │
    ┌───────────▼───────────┐     ┌──────────▼──────────┐
    │   Core Services       │     │  Integration Services│
    ├───────────────────────┤     ├─────────────────────┤
    │ • TenantMCPService    │     │ • CalcomMCPService  │
    │ • BookingMCPService   │     │ • RetellMCPService  │
    │ • StaffMCPService     │     │ • StripeMCPService  │
    │ • RoutingMCPService   │     │ • SMSMCPService     │
    │ • CustomerMCPService  │     └─────────────────────┘
    └───────────────────────┘
```

### 1.2 TenantMCPService

**Purpose**: Manages company and branch operations with complete tenant isolation.

```php
<?php

namespace App\Services\MCP\Core;

use App\Services\MCP\BaseMCPService;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TenantMCPService extends BaseMCPService
{
    /**
     * Service operations mapping
     */
    protected array $operations = [
        'getTenant' => 'handleGetTenant',
        'updateTenant' => 'handleUpdateTenant',
        'listBranches' => 'handleListBranches',
        'getBranch' => 'handleGetBranch',
        'createBranch' => 'handleCreateBranch',
        'updateBranch' => 'handleUpdateBranch',
        'activateBranch' => 'handleActivateBranch',
        'deactivateBranch' => 'handleDeactivateBranch',
        'getTenantSettings' => 'handleGetTenantSettings',
        'updateTenantSettings' => 'handleUpdateTenantSettings',
        'getTenantQuota' => 'handleGetTenantQuota',
        'validateTenantAccess' => 'handleValidateTenantAccess',
    ];

    /**
     * Get tenant information with caching
     */
    protected function handleGetTenant(MCPRequest $request): MCPResponse
    {
        $tenantId = $request->getTenantId();
        
        $tenant = Cache::remember(
            "tenant:{$tenantId}",
            300, // 5 minutes
            fn() => Company::with(['branches', 'subscription'])->find($tenantId)
        );

        if (!$tenant) {
            return MCPResponse::error('Tenant not found');
        }

        return MCPResponse::success([
            'tenant' => $tenant->toArray(),
            'features' => $this->getTenantFeatures($tenant),
            'limits' => $this->getTenantLimits($tenant),
        ]);
    }

    /**
     * List all branches for a tenant
     */
    protected function handleListBranches(MCPRequest $request): MCPResponse
    {
        $tenantId = $request->getTenantId();
        $filters = $request->getParam('filters', []);
        
        $query = Branch::where('company_id', $tenantId);

        // Apply filters
        if (isset($filters['active'])) {
            $query->where('is_active', $filters['active']);
        }

        if (isset($filters['city'])) {
            $query->where('city', 'LIKE', "%{$filters['city']}%");
        }

        $branches = $query->with(['services', 'staff'])->get();

        return MCPResponse::success([
            'branches' => $branches->toArray(),
            'count' => $branches->count(),
        ]);
    }

    /**
     * Create new branch with validation
     */
    protected function handleCreateBranch(MCPRequest $request): MCPResponse
    {
        $tenantId = $request->getTenantId();
        $data = $request->getParam('branch');

        // Check branch limit
        $branchCount = Branch::where('company_id', $tenantId)->count();
        $limit = $this->getTenantBranchLimit($tenantId);

        if ($branchCount >= $limit) {
            return MCPResponse::error('Branch limit exceeded for tenant');
        }

        DB::beginTransaction();
        try {
            $branch = Branch::create([
                'company_id' => $tenantId,
                ...$data,
                'uuid' => \Str::uuid(),
                'is_active' => false, // Start inactive
            ]);

            // Initialize branch settings
            $this->initializeBranchSettings($branch);

            DB::commit();

            // Clear cache
            Cache::forget("tenant:{$tenantId}");

            return MCPResponse::success([
                'branch' => $branch->fresh()->toArray(),
                'message' => 'Branch created successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return MCPResponse::error('Failed to create branch: ' . $e->getMessage());
        }
    }

    /**
     * Get tenant-specific features based on subscription
     */
    private function getTenantFeatures(Company $tenant): array
    {
        return [
            'multi_branch' => $tenant->subscription?->plan !== 'basic',
            'advanced_analytics' => in_array($tenant->subscription?->plan, ['professional', 'enterprise']),
            'custom_branding' => $tenant->subscription?->plan === 'enterprise',
            'api_access' => $tenant->subscription?->plan !== 'basic',
            'sms_notifications' => $tenant->subscription?->features['sms'] ?? false,
            'whatsapp_integration' => $tenant->subscription?->features['whatsapp'] ?? false,
        ];
    }

    /**
     * Get tenant resource limits
     */
    private function getTenantLimits(Company $tenant): array
    {
        $plan = $tenant->subscription?->plan ?? 'basic';

        $limits = [
            'basic' => [
                'branches' => 1,
                'staff_per_branch' => 5,
                'monthly_bookings' => 500,
                'api_calls_per_hour' => 100,
            ],
            'professional' => [
                'branches' => 5,
                'staff_per_branch' => 20,
                'monthly_bookings' => 5000,
                'api_calls_per_hour' => 1000,
            ],
            'enterprise' => [
                'branches' => -1, // Unlimited
                'staff_per_branch' => -1,
                'monthly_bookings' => -1,
                'api_calls_per_hour' => 10000,
            ],
        ];

        return $limits[$plan] ?? $limits['basic'];
    }
}
```

### 1.3 BookingMCPService

**Purpose**: Orchestrates the complete booking lifecycle with transaction management.

```php
<?php

namespace App\Services\MCP\Core;

use App\Services\MCP\BaseMCPService;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use App\Services\Booking\UniversalBookingOrchestrator;
use App\Services\MCP\DistributedTransactionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingMCPService extends BaseMCPService
{
    protected UniversalBookingOrchestrator $bookingOrchestrator;
    protected DistributedTransactionManager $transactionManager;
    
    protected array $operations = [
        'createBooking' => 'handleCreateBooking',
        'updateBooking' => 'handleUpdateBooking',
        'cancelBooking' => 'handleCancelBooking',
        'rescheduleBooking' => 'handleRescheduleBooking',
        'confirmBooking' => 'handleConfirmBooking',
        'checkAvailability' => 'handleCheckAvailability',
        'getBookingSlots' => 'handleGetBookingSlots',
        'getBookingDetails' => 'handleGetBookingDetails',
        'processPhoneBooking' => 'handleProcessPhoneBooking',
    ];

    public function __construct(
        UniversalBookingOrchestrator $bookingOrchestrator,
        DistributedTransactionManager $transactionManager
    ) {
        parent::__construct();
        $this->bookingOrchestrator = $bookingOrchestrator;
        $this->transactionManager = $transactionManager;
    }

    /**
     * Create a new booking with distributed transaction support
     */
    protected function handleCreateBooking(MCPRequest $request): MCPResponse
    {
        $bookingData = $request->getParam('booking');
        $source = $request->getParam('source', 'api');
        
        // Start distributed transaction
        $transaction = $this->transactionManager->begin([
            'tenant_id' => $request->getTenantId(),
            'operation' => 'create_booking',
            'correlation_id' => $request->getCorrelationId(),
        ]);

        try {
            // Step 1: Lock time slot
            $transaction->addStep('lock_timeslot', function() use ($bookingData) {
                return $this->lockTimeSlot(
                    $bookingData['branch_id'],
                    $bookingData['start_time'],
                    $bookingData['duration']
                );
            });

            // Step 2: Create appointment in database
            $transaction->addStep('create_appointment', function() use ($bookingData, $request) {
                return $this->createAppointmentRecord($bookingData, $request->getTenantId());
            });

            // Step 3: Sync with Cal.com
            $transaction->addStep('sync_calcom', function($appointment) use ($bookingData) {
                return $this->syncWithCalcom($appointment, $bookingData);
            });

            // Step 4: Send notifications
            $transaction->addStep('send_notifications', function($appointment) {
                return $this->sendBookingNotifications($appointment);
            });

            // Execute transaction
            $result = $transaction->execute();

            if ($result->isSuccess()) {
                return MCPResponse::success([
                    'appointment' => $result->getData('appointment'),
                    'confirmation_number' => $result->getData('confirmation_number'),
                    'notifications_sent' => $result->getData('notifications'),
                ]);
            }

            // Transaction failed - already rolled back
            return MCPResponse::error($result->getError());

        } catch (\Exception $e) {
            $transaction->rollback();
            
            Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_data' => $bookingData,
            ]);

            return MCPResponse::error('Booking creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Process phone booking from Retell.ai
     */
    protected function handleProcessPhoneBooking(MCPRequest $request): MCPResponse
    {
        $callData = $request->getParam('call_data');
        $extractedInfo = $request->getParam('extracted_info');
        
        Log::info('Processing phone booking', [
            'call_id' => $callData['call_id'],
            'phone' => $callData['from_number'],
        ]);

        try {
            // Use the booking orchestrator
            $result = $this->bookingOrchestrator->processBookingRequest(
                [
                    'customer_phone' => $callData['from_number'],
                    'requested_service' => $extractedInfo['service'],
                    'requested_date' => $extractedInfo['date'],
                    'requested_time' => $extractedInfo['time'],
                    'notes' => $extractedInfo['notes'] ?? null,
                ],
                [
                    'source' => 'phone',
                    'call_id' => $callData['call_id'],
                    'agent_id' => $callData['agent_id'],
                ]
            );

            if ($result['success']) {
                return MCPResponse::success($result);
            }

            return MCPResponse::error($result['error'] ?? 'Booking failed');

        } catch (\Exception $e) {
            Log::error('Phone booking processing failed', [
                'error' => $e->getMessage(),
                'call_id' => $callData['call_id'],
            ]);

            return MCPResponse::error('Phone booking failed: ' . $e->getMessage());
        }
    }

    /**
     * Check availability across multiple branches
     */
    protected function handleCheckAvailability(MCPRequest $request): MCPResponse
    {
        $service = $request->getParam('service');
        $date = $request->getParam('date');
        $branches = $request->getParam('branches', []); // Optional branch filter
        
        try {
            $availability = $this->checkMultiBranchAvailability(
                $request->getTenantId(),
                $service,
                $date,
                $branches
            );

            return MCPResponse::success([
                'available' => !empty($availability),
                'slots' => $availability,
                'next_available' => $this->findNextAvailable($service, $date),
            ]);

        } catch (\Exception $e) {
            return MCPResponse::error('Availability check failed: ' . $e->getMessage());
        }
    }

    /**
     * Lock a time slot to prevent double booking
     */
    private function lockTimeSlot(int $branchId, string $startTime, int $duration): array
    {
        $lockKey = "booking_lock:{$branchId}:{$startTime}";
        $lockAcquired = Cache::add($lockKey, true, 300); // 5 minute lock

        if (!$lockAcquired) {
            throw new \Exception('Time slot is already being booked');
        }

        return [
            'lock_key' => $lockKey,
            'expires_at' => now()->addMinutes(5),
        ];
    }
}
```

### 1.4 StaffMCPService

**Purpose**: Manages staff availability, skills, and assignments.

```php
<?php

namespace App\Services\MCP\Core;

use App\Services\MCP\BaseMCPService;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use App\Models\Staff;
use App\Services\Booking\StaffSkillMatcher;
use Carbon\Carbon;

class StaffMCPService extends BaseMCPService
{
    protected StaffSkillMatcher $skillMatcher;
    
    protected array $operations = [
        'getStaffAvailability' => 'handleGetStaffAvailability',
        'updateStaffSchedule' => 'handleUpdateStaffSchedule',
        'findAvailableStaff' => 'handleFindAvailableStaff',
        'assignStaffToService' => 'handleAssignStaffToService',
        'getStaffWorkload' => 'handleGetStaffWorkload',
        'updateStaffSkills' => 'handleUpdateStaffSkills',
        'getStaffPerformance' => 'handleGetStaffPerformance',
        'blockStaffTime' => 'handleBlockStaffTime',
    ];

    /**
     * Get staff availability for a specific date range
     */
    protected function handleGetStaffAvailability(MCPRequest $request): MCPResponse
    {
        $staffId = $request->getParam('staff_id');
        $startDate = Carbon::parse($request->getParam('start_date'));
        $endDate = Carbon::parse($request->getParam('end_date'));
        
        $staff = Staff::find($staffId);
        
        if (!$staff || $staff->company_id !== $request->getTenantId()) {
            return MCPResponse::error('Staff member not found');
        }

        $availability = $this->calculateStaffAvailability($staff, $startDate, $endDate);

        return MCPResponse::success([
            'staff' => $staff->only(['id', 'name', 'email']),
            'availability' => $availability,
            'total_hours' => $this->calculateTotalHours($availability),
            'utilization_rate' => $this->calculateUtilization($staff, $startDate, $endDate),
        ]);
    }

    /**
     * Find available staff for a service at a specific time
     */
    protected function handleFindAvailableStaff(MCPRequest $request): MCPResponse
    {
        $serviceId = $request->getParam('service_id');
        $branchId = $request->getParam('branch_id');
        $dateTime = Carbon::parse($request->getParam('date_time'));
        $duration = $request->getParam('duration', 30);
        
        $availableStaff = $this->findQualifiedAvailableStaff(
            $request->getTenantId(),
            $branchId,
            $serviceId,
            $dateTime,
            $duration
        );

        return MCPResponse::success([
            'available_staff' => $availableStaff,
            'count' => count($availableStaff),
            'recommendations' => $this->getStaffRecommendations($availableStaff, $serviceId),
        ]);
    }

    /**
     * Calculate staff availability including breaks and existing appointments
     */
    private function calculateStaffAvailability(Staff $staff, Carbon $startDate, Carbon $endDate): array
    {
        $availability = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $daySchedule = $staff->getScheduleForDate($current);
            
            if ($daySchedule) {
                $slots = $this->generateTimeSlots(
                    $daySchedule['start'],
                    $daySchedule['end'],
                    30, // 30-minute slots
                    $this->getBlockedTimes($staff, $current)
                );

                $availability[$current->format('Y-m-d')] = [
                    'date' => $current->format('Y-m-d'),
                    'day_of_week' => $current->format('l'),
                    'working_hours' => $daySchedule,
                    'available_slots' => $slots,
                    'total_available_minutes' => count($slots) * 30,
                ];
            }

            $current->addDay();
        }

        return $availability;
    }

    /**
     * Find qualified and available staff members
     */
    private function findQualifiedAvailableStaff(
        int $tenantId,
        int $branchId,
        int $serviceId,
        Carbon $dateTime,
        int $duration
    ): array {
        $qualifiedStaff = Staff::where('company_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereHas('services', function($query) use ($serviceId) {
                $query->where('service_id', $serviceId);
            })
            ->with(['appointments' => function($query) use ($dateTime) {
                $query->whereDate('start_time', $dateTime->toDateString());
            }])
            ->get();

        return $qualifiedStaff->filter(function($staff) use ($dateTime, $duration) {
            return $this->isStaffAvailable($staff, $dateTime, $duration);
        })->map(function($staff) use ($serviceId) {
            return [
                'id' => $staff->id,
                'name' => $staff->name,
                'skill_level' => $staff->getSkillLevel($serviceId),
                'rating' => $staff->average_rating,
                'completed_services' => $staff->getCompletedServiceCount($serviceId),
            ];
        })->values()->toArray();
    }
}
```

### 1.5 RoutingMCPService

**Purpose**: Intelligent call and request routing based on business rules.

```php
<?php

namespace App\Services\MCP\Core;

use App\Services\MCP\BaseMCPService;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use App\Services\PhoneNumberResolver;
use App\Models\PhoneNumber;
use App\Models\Branch;

class RoutingMCPService extends BaseMCPService
{
    protected PhoneNumberResolver $phoneResolver;
    
    protected array $operations = [
        'routeIncomingCall' => 'handleRouteIncomingCall',
        'findNearestBranch' => 'handleFindNearestBranch',
        'getRoutingRules' => 'handleGetRoutingRules',
        'updateRoutingRules' => 'handleUpdateRoutingRules',
        'routeToFallback' => 'handleRouteToFallback',
        'getCallDistribution' => 'handleGetCallDistribution',
    ];

    /**
     * Route incoming call to appropriate branch/agent
     */
    protected function handleRouteIncomingCall(MCPRequest $request): MCPResponse
    {
        $phoneNumber = $request->getParam('phone_number');
        $callerLocation = $request->getParam('caller_location');
        $preferredLanguage = $request->getParam('language', 'de');
        
        try {
            // Step 1: Resolve phone number to branch
            $phoneRecord = PhoneNumber::where('number', $phoneNumber)->first();
            
            if (!$phoneRecord) {
                return $this->routeToDefaultBranch($request);
            }

            $branch = $phoneRecord->branch;
            
            // Step 2: Check branch availability
            if (!$this->isBranchAvailable($branch)) {
                return $this->routeToAlternateBranch($branch, $callerLocation);
            }

            // Step 3: Select appropriate agent
            $agent = $this->selectBestAgent($branch, $preferredLanguage);

            return MCPResponse::success([
                'routing_decision' => 'branch_specific',
                'branch' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'address' => $branch->full_address,
                ],
                'agent' => $agent,
                'estimated_wait_time' => $this->estimateWaitTime($branch),
                'routing_metadata' => [
                    'phone_number_matched' => true,
                    'branch_available' => true,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Call routing failed', [
                'error' => $e->getMessage(),
                'phone_number' => $phoneNumber,
            ]);

            return $this->routeToFallback($request);
        }
    }

    /**
     * Find nearest branch based on location
     */
    protected function handleFindNearestBranch(MCPRequest $request): MCPResponse
    {
        $location = $request->getParam('location');
        $serviceId = $request->getParam('service_id');
        $maxDistance = $request->getParam('max_distance', 50); // km
        
        $branches = Branch::where('company_id', $request->getTenantId())
            ->where('is_active', true)
            ->when($serviceId, function($query) use ($serviceId) {
                $query->whereHas('services', function($q) use ($serviceId) {
                    $q->where('service_id', $serviceId);
                });
            })
            ->get();

        $nearestBranches = $branches->map(function($branch) use ($location) {
            $distance = $this->calculateDistance(
                $location['lat'],
                $location['lng'],
                $branch->latitude,
                $branch->longitude
            );

            return [
                'branch' => $branch,
                'distance' => $distance,
            ];
        })
        ->filter(fn($item) => $item['distance'] <= $maxDistance)
        ->sortBy('distance')
        ->take(5)
        ->values();

        return MCPResponse::success([
            'branches' => $nearestBranches->map(fn($item) => [
                'id' => $item['branch']->id,
                'name' => $item['branch']->name,
                'address' => $item['branch']->full_address,
                'distance_km' => round($item['distance'], 1),
                'available_now' => $this->isBranchAvailable($item['branch']),
            ])->toArray(),
            'total_found' => $nearestBranches->count(),
        ]);
    }

    /**
     * Check if branch is currently available
     */
    private function isBranchAvailable(Branch $branch): bool
    {
        $now = now();
        $dayOfWeek = strtolower($now->format('l'));
        
        $businessHours = $branch->business_hours[$dayOfWeek] ?? null;
        
        if (!$businessHours || !$businessHours['is_open']) {
            return false;
        }

        $currentTime = $now->format('H:i');
        return $currentTime >= $businessHours['open'] && $currentTime <= $businessHours['close'];
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
```

### 1.6 IntegrationMCPService

**Purpose**: Coordinates external service integrations with circuit breaker pattern.

```php
<?php

namespace App\Services\MCP\Core;

use App\Services\MCP\BaseMCPService;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use App\Services\CircuitBreaker\CircuitBreaker;

class IntegrationMCPService extends BaseMCPService
{
    protected array $integrations = [];
    protected array $circuitBreakers = [];
    
    protected array $operations = [
        'syncCalendarEvent' => 'handleSyncCalendarEvent',
        'processRetellWebhook' => 'handleProcessRetellWebhook',
        'sendSMSNotification' => 'handleSendSMSNotification',
        'createStripeCharge' => 'handleCreateStripeCharge',
        'checkIntegrationHealth' => 'handleCheckIntegrationHealth',
        'retryFailedSync' => 'handleRetryFailedSync',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->initializeIntegrations();
    }

    /**
     * Initialize integration services with circuit breakers
     */
    private function initializeIntegrations(): void
    {
        $this->integrations = [
            'calcom' => app(CalcomMCPService::class),
            'retell' => app(RetellMCPService::class),
            'stripe' => app(StripeMCPService::class),
            'sms' => app(SMSMCPService::class),
        ];

        foreach ($this->integrations as $name => $service) {
            $this->circuitBreakers[$name] = new CircuitBreaker(
                $name,
                failureThreshold: 5,
                recoveryTimeout: 60,
                expectedExceptionClass: \Exception::class
            );
        }
    }

    /**
     * Sync calendar event with retry and fallback
     */
    protected function handleSyncCalendarEvent(MCPRequest $request): MCPResponse
    {
        $eventData = $request->getParam('event');
        $provider = $request->getParam('provider', 'calcom');
        
        return $this->executeWithCircuitBreaker($provider, function() use ($request, $eventData) {
            $result = $this->integrations[$provider]->syncEvent($eventData);
            
            if (!$result['success']) {
                // Try fallback provider
                if ($fallback = $this->getFallbackProvider($provider)) {
                    Log::warning("Primary provider {$provider} failed, trying {$fallback}");
                    $result = $this->integrations[$fallback]->syncEvent($eventData);
                }
            }

            return $result;
        });
    }

    /**
     * Execute integration call with circuit breaker protection
     */
    private function executeWithCircuitBreaker(string $integration, callable $operation): MCPResponse
    {
        try {
            $result = $this->circuitBreakers[$integration]->call($integration, $operation);
            return MCPResponse::success($result);
        } catch (\Exception $e) {
            Log::error("Integration {$integration} failed", [
                'error' => $e->getMessage(),
                'circuit_state' => $this->circuitBreakers[$integration]->getState(),
            ]);

            if ($this->circuitBreakers[$integration]->isOpen()) {
                return MCPResponse::error("Integration {$integration} is temporarily unavailable");
            }

            return MCPResponse::error($e->getMessage());
        }
    }

    /**
     * Check health of all integrations
     */
    protected function handleCheckIntegrationHealth(MCPRequest $request): MCPResponse
    {
        $health = [];

        foreach ($this->integrations as $name => $service) {
            $health[$name] = [
                'status' => $this->circuitBreakers[$name]->getState(),
                'available' => !$this->circuitBreakers[$name]->isOpen(),
                'failure_count' => $this->circuitBreakers[$name]->getFailureCount(),
                'last_failure' => $this->circuitBreakers[$name]->getLastFailureTime(),
            ];

            // Perform actual health check if circuit is not open
            if (!$this->circuitBreakers[$name]->isOpen()) {
                try {
                    $serviceHealth = $service->healthCheck();
                    $health[$name]['service_status'] = $serviceHealth;
                } catch (\Exception $e) {
                    $health[$name]['service_status'] = 'error';
                    $health[$name]['error'] = $e->getMessage();
                }
            }
        }

        return MCPResponse::success([
            'integrations' => $health,
            'overall_health' => $this->calculateOverallHealth($health),
        ]);
    }
}
```

## 2. Service Communication Patterns

### 2.1 Request/Response Pattern

All MCP services follow a standardized request/response pattern:

```php
// Client code example
$request = new MCPRequest(
    service: 'booking',
    operation: 'createBooking',
    params: [
        'customer_id' => 123,
        'service_id' => 456,
        'branch_id' => 789,
        'start_time' => '2025-06-20 14:00:00',
        'duration' => 60,
    ],
    tenantId: $tenantId,
    correlationId: $correlationId
);

$response = $orchestrator->route($request);

if ($response->isSuccess()) {
    $appointment = $response->getData();
} else {
    $error = $response->getError();
}
```

### 2.2 Event-Driven Communication

```php
<?php

namespace App\Services\MCP\Events;

use App\Services\MCP\MCPEvent;
use App\Services\MCP\MCPEventDispatcher;

class MCPEventDispatcher
{
    protected array $listeners = [];
    
    /**
     * Register event listener
     */
    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }
    
    /**
     * Dispatch event to all listeners
     */
    public function dispatch(MCPEvent $event): void
    {
        $eventName = $event->getName();
        
        if (!isset($this->listeners[$eventName])) {
            return;
        }
        
        foreach ($this->listeners[$eventName] as $listener) {
            try {
                $listener($event);
            } catch (\Exception $e) {
                Log::error('Event listener failed', [
                    'event' => $eventName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

// Usage example
$dispatcher->listen('booking.created', function(MCPEvent $event) {
    $booking = $event->getData();
    
    // Send confirmation email
    Mail::to($booking['customer_email'])->send(new BookingConfirmation($booking));
    
    // Update analytics
    Analytics::track('booking_created', [
        'branch_id' => $booking['branch_id'],
        'service_id' => $booking['service_id'],
        'value' => $booking['price'],
    ]);
});
```

### 2.3 Transaction Boundaries

```php
<?php

namespace App\Services\MCP;

class DistributedTransactionManager
{
    protected array $steps = [];
    protected array $completedSteps = [];
    protected array $compensations = [];
    
    /**
     * Add transaction step with compensation
     */
    public function addStep(string $name, callable $action, callable $compensation = null): self
    {
        $this->steps[$name] = [
            'action' => $action,
            'compensation' => $compensation,
        ];
        
        return $this;
    }
    
    /**
     * Execute transaction with automatic rollback on failure
     */
    public function execute(): TransactionResult
    {
        DB::beginTransaction();
        
        try {
            $results = [];
            
            foreach ($this->steps as $name => $step) {
                Log::info("Executing transaction step: {$name}");
                
                $result = $step['action']($results);
                $results[$name] = $result;
                $this->completedSteps[] = $name;
                
                // Store compensation if provided
                if ($step['compensation']) {
                    $this->compensations[$name] = [
                        'handler' => $step['compensation'],
                        'data' => $result,
                    ];
                }
            }
            
            DB::commit();
            
            return new TransactionResult(true, $results);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Transaction failed, executing compensations', [
                'error' => $e->getMessage(),
                'completed_steps' => $this->completedSteps,
            ]);
            
            $this->executeCompensations();
            
            return new TransactionResult(false, null, $e->getMessage());
        }
    }
    
    /**
     * Execute compensation actions in reverse order
     */
    protected function executeCompensations(): void
    {
        $compensationSteps = array_reverse($this->completedSteps);
        
        foreach ($compensationSteps as $step) {
            if (isset($this->compensations[$step])) {
                try {
                    $compensation = $this->compensations[$step];
                    $compensation['handler']($compensation['data']);
                    
                    Log::info("Compensation executed for step: {$step}");
                } catch (\Exception $e) {
                    Log::error("Compensation failed for step: {$step}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
```

## 3. API Design

### 3.1 RESTful Endpoints

```php
// routes/api.php

use App\Http\Controllers\Api\MCP\MCPController;

Route::prefix('api/v2/mcp')->middleware(['auth:api', 'tenant.context'])->group(function () {
    // Tenant Management
    Route::prefix('tenant')->group(function () {
        Route::get('/', [MCPController::class, 'getTenant']);
        Route::put('/', [MCPController::class, 'updateTenant']);
        Route::get('/branches', [MCPController::class, 'listBranches']);
        Route::post('/branches', [MCPController::class, 'createBranch']);
        Route::get('/branches/{id}', [MCPController::class, 'getBranch']);
        Route::put('/branches/{id}', [MCPController::class, 'updateBranch']);
    });
    
    // Booking Management
    Route::prefix('bookings')->group(function () {
        Route::post('/', [MCPController::class, 'createBooking']);
        Route::get('/{id}', [MCPController::class, 'getBooking']);
        Route::put('/{id}', [MCPController::class, 'updateBooking']);
        Route::post('/{id}/cancel', [MCPController::class, 'cancelBooking']);
        Route::post('/{id}/reschedule', [MCPController::class, 'rescheduleBooking']);
        Route::post('/check-availability', [MCPController::class, 'checkAvailability']);
    });
    
    // Staff Management
    Route::prefix('staff')->group(function () {
        Route::get('/', [MCPController::class, 'listStaff']);
        Route::get('/{id}/availability', [MCPController::class, 'getStaffAvailability']);
        Route::put('/{id}/schedule', [MCPController::class, 'updateStaffSchedule']);
        Route::get('/{id}/performance', [MCPController::class, 'getStaffPerformance']);
    });
    
    // Integration Health
    Route::get('/health', [MCPController::class, 'systemHealth']);
    Route::get('/integrations/health', [MCPController::class, 'integrationHealth']);
});
```

### 3.2 GraphQL Schema

```graphql
type Query {
  # Tenant queries
  tenant(id: ID!): Tenant
  branches(filters: BranchFilter): [Branch!]!
  
  # Booking queries
  bookings(filters: BookingFilter): BookingConnection!
  availability(input: AvailabilityInput!): AvailabilityResult!
  
  # Staff queries
  staff(branchId: ID!): [Staff!]!
  staffAvailability(staffId: ID!, dateRange: DateRangeInput!): StaffAvailability!
}

type Mutation {
  # Booking mutations
  createBooking(input: CreateBookingInput!): BookingResult!
  updateBooking(id: ID!, input: UpdateBookingInput!): BookingResult!
  cancelBooking(id: ID!, reason: String): BookingResult!
  
  # Staff mutations
  updateStaffSchedule(staffId: ID!, schedule: ScheduleInput!): Staff!
  assignStaffToService(staffId: ID!, serviceId: ID!): Staff!
}

type Subscription {
  # Real-time updates
  bookingUpdated(branchId: ID!): Booking!
  staffStatusChanged(branchId: ID!): StaffStatus!
  queuePositionUpdated(customerId: ID!): QueuePosition!
}

# Types
type Tenant {
  id: ID!
  name: String!
  branches: [Branch!]!
  settings: TenantSettings!
  subscription: Subscription!
}

type Branch {
  id: ID!
  name: String!
  address: Address!
  services: [Service!]!
  staff: [Staff!]!
  businessHours: BusinessHours!
}

type Booking {
  id: ID!
  customer: Customer!
  service: Service!
  staff: Staff
  branch: Branch!
  startTime: DateTime!
  endTime: DateTime!
  status: BookingStatus!
  price: Money!
}

# Input types
input CreateBookingInput {
  customerId: ID!
  serviceId: ID!
  branchId: ID
  staffId: ID
  startTime: DateTime!
  notes: String
}

input AvailabilityInput {
  serviceId: ID!
  date: Date!
  branchIds: [ID!]
  preferredStaffId: ID
}
```

### 3.3 WebSocket Real-time Updates

```php
<?php

namespace App\Services\MCP\Realtime;

use App\Services\MCP\MCPEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;

class MCPRealtimeService
{
    /**
     * Broadcast booking update
     */
    public function broadcastBookingUpdate(array $booking): void
    {
        broadcast(new BookingUpdated($booking))
            ->toOthers()
            ->on(new PrivateChannel("branch.{$booking['branch_id']}"));
    }
    
    /**
     * Broadcast staff status change
     */
    public function broadcastStaffStatus(int $staffId, string $status): void
    {
        $staff = Staff::find($staffId);
        
        broadcast(new StaffStatusChanged($staff, $status))
            ->on(new PrivateChannel("branch.{$staff->branch_id}"));
    }
    
    /**
     * Broadcast queue position update
     */
    public function broadcastQueueUpdate(int $customerId, int $position): void
    {
        broadcast(new QueuePositionUpdated($customerId, $position))
            ->on(new PrivateChannel("customer.{$customerId}"));
    }
}

// Client-side JavaScript
Echo.private(`branch.${branchId}`)
    .listen('BookingUpdated', (e) => {
        console.log('Booking updated:', e.booking);
        updateBookingDisplay(e.booking);
    })
    .listen('StaffStatusChanged', (e) => {
        console.log('Staff status changed:', e.staff, e.status);
        updateStaffStatus(e.staff.id, e.status);
    });
```

### 3.4 Rate Limiting and Authentication

```php
<?php

namespace App\Http\Middleware;

use App\Services\RateLimiter\ApiRateLimiter;
use Closure;

class MCPRateLimitMiddleware
{
    protected ApiRateLimiter $rateLimiter;
    
    public function __construct(ApiRateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }
    
    public function handle($request, Closure $next, string $tier = 'default')
    {
        $limits = [
            'basic' => ['requests' => 100, 'window' => 3600],
            'professional' => ['requests' => 1000, 'window' => 3600],
            'enterprise' => ['requests' => 10000, 'window' => 3600],
        ];
        
        $user = $request->user();
        $userTier = $user->company->subscription->plan ?? 'basic';
        $limit = $limits[$userTier];
        
        $key = "api_limit:{$user->id}";
        
        if (!$this->rateLimiter->attempt($key, $limit['requests'], $limit['window'])) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => $this->rateLimiter->availableIn($key),
            ], 429);
        }
        
        $response = $next($request);
        
        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $limit['requests']);
        $response->headers->set('X-RateLimit-Remaining', $this->rateLimiter->remaining($key));
        $response->headers->set('X-RateLimit-Reset', $this->rateLimiter->resetTime($key));
        
        return $response;
    }
}
```

## 4. Testing Strategy

### 4.1 Unit Testing

```php
<?php

namespace Tests\Unit\MCP;

use Tests\TestCase;
use App\Services\MCP\Core\BookingMCPService;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use Mockery;

class BookingMCPServiceTest extends TestCase
{
    protected BookingMCPService $service;
    protected $mockOrchestrator;
    protected $mockTransactionManager;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockOrchestrator = Mockery::mock(UniversalBookingOrchestrator::class);
        $this->mockTransactionManager = Mockery::mock(DistributedTransactionManager::class);
        
        $this->service = new BookingMCPService(
            $this->mockOrchestrator,
            $this->mockTransactionManager
        );
    }
    
    public function test_create_booking_success()
    {
        // Arrange
        $request = new MCPRequest(
            service: 'booking',
            operation: 'createBooking',
            params: [
                'booking' => [
                    'customer_id' => 1,
                    'service_id' => 2,
                    'branch_id' => 3,
                    'start_time' => '2025-06-20 14:00:00',
                    'duration' => 60,
                ],
            ],
            tenantId: 1
        );
        
        $mockTransaction = Mockery::mock();
        $this->mockTransactionManager
            ->shouldReceive('begin')
            ->once()
            ->andReturn($mockTransaction);
            
        $mockTransaction->shouldReceive('addStep')->times(4)->andReturnSelf();
        $mockTransaction->shouldReceive('execute')->once()->andReturn(
            new TransactionResult(true, [
                'appointment' => ['id' => 123],
                'confirmation_number' => 'CONF-123',
            ])
        );
        
        // Act
        $response = $this->service->createBooking($request);
        
        // Assert
        $this->assertTrue($response->isSuccess());
        $this->assertEquals(123, $response->getData()['appointment']['id']);
        $this->assertEquals('CONF-123', $response->getData()['confirmation_number']);
    }
    
    public function test_create_booking_handles_transaction_failure()
    {
        // Arrange
        $request = new MCPRequest(
            service: 'booking',
            operation: 'createBooking',
            params: ['booking' => []],
            tenantId: 1
        );
        
        $mockTransaction = Mockery::mock();
        $this->mockTransactionManager
            ->shouldReceive('begin')
            ->once()
            ->andReturn($mockTransaction);
            
        $mockTransaction->shouldReceive('addStep')->andReturnSelf();
        $mockTransaction->shouldReceive('execute')->once()->andThrow(
            new \Exception('Time slot no longer available')
        );
        $mockTransaction->shouldReceive('rollback')->once();
        
        // Act
        $response = $this->service->createBooking($request);
        
        // Assert
        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('Time slot no longer available', $response->getError());
    }
}
```

### 4.2 Integration Testing

```php
<?php

namespace Tests\Integration\MCP;

use Tests\TestCase;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCP\MCPRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MCPOrchestratorIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    protected MCPOrchestrator $orchestrator;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orchestrator = app(MCPOrchestrator::class);
        $this->seedTestData();
    }
    
    public function test_complete_booking_flow_through_orchestrator()
    {
        // Create a company with branch and services
        $company = Company::factory()->create();
        $branch = Branch::factory()->for($company)->create();
        $service = Service::factory()->create();
        $branch->services()->attach($service);
        
        $staff = Staff::factory()->for($company)->for($branch)->create();
        $staff->services()->attach($service);
        
        // Create booking request
        $request = new MCPRequest(
            service: 'booking',
            operation: 'createBooking',
            params: [
                'booking' => [
                    'customer_phone' => '+49 30 12345678',
                    'customer_name' => 'Test Customer',
                    'customer_email' => 'test@example.com',
                    'service_id' => $service->id,
                    'branch_id' => $branch->id,
                    'start_time' => now()->addDay()->setHour(14)->setMinute(0)->format('Y-m-d H:i:s'),
                    'duration' => 60,
                ],
            ],
            tenantId: $company->id
        );
        
        // Execute through orchestrator
        $response = $this->orchestrator->route($request);
        
        // Assert success
        $this->assertTrue($response->isSuccess());
        $this->assertNotNull($response->getData()['appointment']);
        $this->assertDatabaseHas('appointments', [
            'customer_email' => 'test@example.com',
            'service_id' => $service->id,
            'branch_id' => $branch->id,
        ]);
    }
    
    public function test_circuit_breaker_prevents_cascading_failures()
    {
        // Simulate Cal.com being down
        $this->mockCalcomFailure();
        
        $request = new MCPRequest(
            service: 'integration',
            operation: 'syncCalendarEvent',
            params: ['event' => []],
            tenantId: 1
        );
        
        // First 5 requests should fail normally
        for ($i = 0; $i < 5; $i++) {
            $response = $this->orchestrator->route($request);
            $this->assertFalse($response->isSuccess());
        }
        
        // 6th request should get circuit breaker response
        $response = $this->orchestrator->route($request);
        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('temporarily unavailable', $response->getError());
    }
}
```

### 4.3 Load Testing

```php
<?php

namespace Tests\Load\MCP;

use Tests\TestCase;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCP\MCPRequest;
use Illuminate\Support\Facades\Queue;

class MCPLoadTest extends TestCase
{
    public function test_system_handles_concurrent_bookings()
    {
        // Disable queue for synchronous testing
        Queue::fake();
        
        $orchestrator = app(MCPOrchestrator::class);
        $company = $this->createTestCompany();
        
        // Simulate 100 concurrent booking requests
        $promises = [];
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $request = new MCPRequest(
                service: 'booking',
                operation: 'checkAvailability',
                params: [
                    'service_id' => 1,
                    'date' => now()->addDay()->format('Y-m-d'),
                ],
                tenantId: $company->id
            );
            
            // Simulate async request
            $promises[] = $this->asyncRequest($orchestrator, $request);
        }
        
        // Wait for all requests to complete
        $results = $this->waitAll($promises);
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Assert performance metrics
        $this->assertLessThan(5, $duration, 'All requests should complete within 5 seconds');
        
        $successCount = collect($results)->filter(fn($r) => $r->isSuccess())->count();
        $this->assertGreaterThan(95, $successCount, 'At least 95% success rate required');
        
        // Check for duplicate bookings
        $this->assertNoDuplicateBookings($company->id);
    }
    
    public function test_rate_limiting_prevents_abuse()
    {
        $orchestrator = app(MCPOrchestrator::class);
        $company = $this->createTestCompany(['plan' => 'basic']);
        
        // Basic plan allows 100 requests per hour
        $responses = [];
        
        for ($i = 0; $i < 150; $i++) {
            $request = new MCPRequest(
                service: 'booking',
                operation: 'checkAvailability',
                params: ['service_id' => 1, 'date' => now()->format('Y-m-d')],
                tenantId: $company->id
            );
            
            $responses[] = $orchestrator->route($request);
        }
        
        // First 100 should succeed
        $successful = array_slice($responses, 0, 100);
        foreach ($successful as $response) {
            $this->assertTrue($response->isSuccess());
        }
        
        // Remaining should be rate limited
        $rateLimited = array_slice($responses, 100);
        foreach ($rateLimited as $response) {
            $this->assertFalse($response->isSuccess());
            $this->assertStringContainsString('quota exceeded', $response->getError());
        }
    }
}
```

### 4.4 Chaos Engineering Tests

```php
<?php

namespace Tests\Chaos\MCP;

use Tests\TestCase;
use App\Services\MCP\MCPOrchestrator;

class MCPChaosTest extends TestCase
{
    public function test_system_recovers_from_database_failure()
    {
        $orchestrator = app(MCPOrchestrator::class);
        
        // Simulate database going down mid-transaction
        DB::listen(function ($query) {
            if (str_contains($query->sql, 'INSERT INTO appointments')) {
                throw new \PDOException('MySQL server has gone away');
            }
        });
        
        $request = $this->createBookingRequest();
        $response = $orchestrator->route($request);
        
        // Should handle gracefully
        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('temporarily unavailable', $response->getError());
        
        // Verify no partial data was saved
        $this->assertDatabaseMissing('appointments', [
            'customer_email' => $request->getParam('booking')['customer_email'],
        ]);
    }
    
    public function test_system_handles_memory_pressure()
    {
        $orchestrator = app(MCPOrchestrator::class);
        
        // Allocate significant memory
        $memoryHog = str_repeat('x', 50 * 1024 * 1024); // 50MB
        
        $request = $this->createBookingRequest();
        $response = $orchestrator->route($request);
        
        // Should still function under memory pressure
        $this->assertTrue($response->isSuccess());
        
        // Clean up
        unset($memoryHog);
    }
    
    public function test_system_handles_slow_external_services()
    {
        $orchestrator = app(MCPOrchestrator::class);
        
        // Mock slow Cal.com response
        $this->mockSlowCalcomResponse(5); // 5 second delay
        
        $startTime = microtime(true);
        $request = new MCPRequest(
            service: 'integration',
            operation: 'syncCalendarEvent',
            params: ['event' => []],
            tenantId: 1
        );
        
        $response = $orchestrator->route($request);
        $duration = microtime(true) - $startTime;
        
        // Should timeout before 5 seconds
        $this->assertLessThan(3, $duration);
        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('timeout', $response->getError());
    }
}
```

## 5. Error Handling and Compensation

### 5.1 Standardized Error Response

```php
<?php

namespace App\Services\MCP\Errors;

class MCPErrorResponse
{
    const ERROR_CODES = [
        'VALIDATION_FAILED' => 1001,
        'RESOURCE_NOT_FOUND' => 1002,
        'PERMISSION_DENIED' => 1003,
        'RATE_LIMIT_EXCEEDED' => 1004,
        'SERVICE_UNAVAILABLE' => 1005,
        'TENANT_QUOTA_EXCEEDED' => 1006,
        'TRANSACTION_FAILED' => 1007,
        'INTEGRATION_ERROR' => 1008,
    ];
    
    public static function create(string $code, string $message, array $details = []): array
    {
        return [
            'error' => [
                'code' => self::ERROR_CODES[$code] ?? 9999,
                'type' => $code,
                'message' => $message,
                'details' => $details,
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}
```

### 5.2 Compensation Patterns

```php
<?php

namespace App\Services\MCP\Compensation;

class BookingCompensationHandlers
{
    /**
     * Compensate for failed calendar sync
     */
    public static function compensateCalendarSync(array $appointment): void
    {
        // Mark appointment as pending sync
        Appointment::find($appointment['id'])->update([
            'sync_status' => 'pending',
            'sync_error' => 'Initial sync failed, will retry',
        ]);
        
        // Queue for retry
        ProcessFailedCalendarSync::dispatch($appointment['id'])
            ->delay(now()->addMinutes(5));
            
        // Notify operations team
        Notification::send(
            User::operations()->get(),
            new CalendarSyncFailedNotification($appointment)
        );
    }
    
    /**
     * Compensate for failed notification
     */
    public static function compensateNotificationFailure(array $appointment): void
    {
        // Log failure for manual follow-up
        DB::table('failed_notifications')->insert([
            'appointment_id' => $appointment['id'],
            'type' => 'booking_confirmation',
            'attempted_at' => now(),
            'error' => 'Email service unavailable',
        ]);
        
        // Try SMS as fallback if available
        if ($customer = Customer::find($appointment['customer_id'])) {
            if ($customer->phone && config('services.sms.enabled')) {
                SMS::send($customer->phone, 
                    "Termin bestätigt: {$appointment['date']} um {$appointment['time']}. " .
                    "Bestätigungs-Nr: {$appointment['confirmation_number']}"
                );
            }
        }
    }
}
```

## 6. Monitoring and Observability

### 6.1 Metrics Collection

```php
<?php

namespace App\Services\MCP\Monitoring;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

class MCPMetricsCollector
{
    protected CollectorRegistry $registry;
    
    public function __construct()
    {
        $this->registry = new CollectorRegistry(new Redis([
            'host' => config('database.redis.default.host'),
            'port' => config('database.redis.default.port'),
        ]));
        
        $this->registerMetrics();
    }
    
    protected function registerMetrics(): void
    {
        // Request counter
        $this->registry->registerCounter(
            'mcp',
            'requests_total',
            'Total MCP requests',
            ['service', 'operation', 'status']
        );
        
        // Request duration histogram
        $this->registry->registerHistogram(
            'mcp',
            'request_duration_seconds',
            'MCP request duration',
            ['service', 'operation'],
            [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10]
        );
        
        // Active connections gauge
        $this->registry->registerGauge(
            'mcp',
            'active_connections',
            'Active MCP connections',
            ['service']
        );
        
        // Circuit breaker state
        $this->registry->registerGauge(
            'mcp',
            'circuit_breaker_state',
            'Circuit breaker state (0=closed, 1=open, 2=half-open)',
            ['service']
        );
    }
    
    public function recordRequest(string $service, string $operation, bool $success, float $duration): void
    {
        $counter = $this->registry->getCounter('mcp', 'requests_total');
        $counter->incBy(1, [$service, $operation, $success ? 'success' : 'failure']);
        
        $histogram = $this->registry->getHistogram('mcp', 'request_duration_seconds');
        $histogram->observe($duration, [$service, $operation]);
    }
}
```

### 6.2 Distributed Tracing

```php
<?php

namespace App\Services\MCP\Tracing;

use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\SpanInterface;

class MCPTracer
{
    protected TracerInterface $tracer;
    
    public function __construct(TracerInterface $tracer)
    {
        $this->tracer = $tracer;
    }
    
    public function traceOperation(MCPRequest $request, callable $operation)
    {
        $span = $this->tracer->spanBuilder("{$request->getService()}.{$request->getOperation()}")
            ->setAttribute('mcp.service', $request->getService())
            ->setAttribute('mcp.operation', $request->getOperation())
            ->setAttribute('mcp.tenant_id', $request->getTenantId())
            ->setAttribute('mcp.correlation_id', $request->getCorrelationId())
            ->startSpan();
            
        try {
            $result = $operation();
            
            $span->setAttribute('mcp.success', true);
            
            return $result;
            
        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setAttribute('mcp.success', false);
            $span->setAttribute('mcp.error', $e->getMessage());
            
            throw $e;
            
        } finally {
            $span->end();
        }
    }
}
```

## 7. Security Considerations

### 7.1 Authentication and Authorization

```php
<?php

namespace App\Services\MCP\Security;

use App\Services\MCP\MCPRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class MCPAuthorizationService
{
    /**
     * Check if user can perform MCP operation
     */
    public function authorize(User $user, MCPRequest $request): bool
    {
        // Verify tenant access
        if ($user->company_id !== $request->getTenantId()) {
            return false;
        }
        
        // Check operation-specific permissions
        $permission = $this->getRequiredPermission($request);
        
        return Gate::forUser($user)->allows($permission, [
            'tenant_id' => $request->getTenantId(),
            'params' => $request->getParams(),
        ]);
    }
    
    /**
     * Map MCP operations to permissions
     */
    protected function getRequiredPermission(MCPRequest $request): string
    {
        $permissionMap = [
            'booking.createBooking' => 'create-bookings',
            'booking.cancelBooking' => 'cancel-bookings',
            'tenant.updateTenant' => 'manage-company',
            'staff.updateStaffSchedule' => 'manage-staff',
            'integration.checkIntegrationHealth' => 'view-integrations',
        ];
        
        $key = "{$request->getService()}.{$request->getOperation()}";
        
        return $permissionMap[$key] ?? 'access-mcp';
    }
}
```

### 7.2 Input Validation

```php
<?php

namespace App\Services\MCP\Validation;

use Illuminate\Support\Facades\Validator;

class MCPRequestValidator
{
    protected array $rules = [
        'booking.createBooking' => [
            'booking.customer_phone' => 'required|string|regex:/^\+49/',
            'booking.service_id' => 'required|exists:services,id',
            'booking.branch_id' => 'required|exists:branches,id',
            'booking.start_time' => 'required|date|after:now',
            'booking.duration' => 'required|integer|min:15|max:480',
        ],
        'staff.updateStaffSchedule' => [
            'schedule.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedule.*.start' => 'required|date_format:H:i',
            'schedule.*.end' => 'required|date_format:H:i|after:schedule.*.start',
        ],
    ];
    
    public function validate(MCPRequest $request): array
    {
        $key = "{$request->getService()}.{$request->getOperation()}";
        $rules = $this->rules[$key] ?? [];
        
        if (empty($rules)) {
            return []; // No validation rules defined
        }
        
        $validator = Validator::make($request->getParams(), $rules);
        
        if ($validator->fails()) {
            throw new MCPValidationException($validator->errors()->toArray());
        }
        
        return $validator->validated();
    }
}
```

## 8. Deployment and Configuration

### 8.1 Environment Configuration

```env
# MCP Service Configuration
MCP_ENABLED=true
MCP_DEBUG=false
MCP_TIMEOUT=30
MCP_MAX_RETRIES=3

# Service-specific settings
MCP_BOOKING_LOCK_TIMEOUT=300
MCP_BOOKING_MAX_ADVANCE_DAYS=90
MCP_STAFF_SCHEDULE_CACHE_TTL=3600

# Circuit breaker settings
MCP_CIRCUIT_FAILURE_THRESHOLD=5
MCP_CIRCUIT_RECOVERY_TIMEOUT=60
MCP_CIRCUIT_EXPECTED_EXCEPTIONS="CalcomException,RetellException"

# Rate limiting
MCP_RATE_LIMIT_BASIC=100
MCP_RATE_LIMIT_PRO=1000
MCP_RATE_LIMIT_ENTERPRISE=10000
MCP_RATE_LIMIT_WINDOW=3600

# Monitoring
MCP_METRICS_ENABLED=true
MCP_TRACING_ENABLED=true
MCP_TRACING_SAMPLE_RATE=0.1
```

### 8.2 Service Provider Registration

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCP\Core\TenantMCPService;
use App\Services\MCP\Core\BookingMCPService;
use App\Services\MCP\Core\StaffMCPService;
use App\Services\MCP\Core\RoutingMCPService;
use App\Services\MCP\Core\IntegrationMCPService;

class MCPServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register core MCP services
        $this->app->singleton(TenantMCPService::class);
        $this->app->singleton(BookingMCPService::class);
        $this->app->singleton(StaffMCPService::class);
        $this->app->singleton(RoutingMCPService::class);
        $this->app->singleton(IntegrationMCPService::class);
        
        // Register orchestrator
        $this->app->singleton(MCPOrchestrator::class, function ($app) {
            return new MCPOrchestrator(
                $app->make(ApiRateLimiter::class),
                $app->make(ConnectionPoolManager::class)
            );
        });
        
        // Register middleware
        $this->app['router']->aliasMiddleware('mcp.auth', MCPAuthMiddleware::class);
        $this->app['router']->aliasMiddleware('mcp.rate_limit', MCPRateLimitMiddleware::class);
    }
    
    public function boot(): void
    {
        // Warm up services on application start
        if (!$this->app->runningInConsole()) {
            $orchestrator = $this->app->make(MCPOrchestrator::class);
            $orchestrator->warmup();
        }
        
        // Register health check route
        Route::get('/mcp/health', function () {
            $orchestrator = app(MCPOrchestrator::class);
            return response()->json($orchestrator->healthCheck());
        });
    }
}
```

## 9. Migration Strategy

### 9.1 Gradual Migration Plan

```php
// Phase 1: Parallel run with existing services
class BookingController extends Controller
{
    protected $legacyService;
    protected $mcpOrchestrator;
    
    public function create(Request $request)
    {
        if (config('features.use_mcp_booking')) {
            $mcpRequest = new MCPRequest(
                service: 'booking',
                operation: 'createBooking',
                params: $request->validated()
            );
            
            $response = $this->mcpOrchestrator->route($mcpRequest);
            
            if ($response->isSuccess()) {
                return response()->json($response->getData());
            }
            
            // Fallback to legacy on MCP failure
            Log::warning('MCP booking failed, falling back to legacy', [
                'error' => $response->getError()
            ]);
        }
        
        // Legacy path
        return $this->legacyService->createBooking($request->all());
    }
}

// Phase 2: Feature flag controlled rollout
class FeatureFlags
{
    public static function isMCPEnabled(string $service, int $tenantId): bool
    {
        // Gradual rollout by tenant
        $enabledTenants = Cache::get('mcp_enabled_tenants', []);
        
        if (in_array($tenantId, $enabledTenants)) {
            return true;
        }
        
        // Percentage-based rollout
        $rolloutPercentage = config("features.mcp_{$service}_rollout", 0);
        return random_int(1, 100) <= $rolloutPercentage;
    }
}
```

## 10. Future Enhancements

### 10.1 AI-Powered Optimization
- Predictive booking recommendations
- Automated staff scheduling optimization
- Dynamic pricing based on demand
- Anomaly detection for fraud prevention

### 10.2 Advanced Integration Patterns
- Event sourcing for complete audit trail
- CQRS for read/write separation
- Saga pattern for complex multi-step workflows
- Message queue integration for async processing

### 10.3 Enhanced Monitoring
- Real-time dashboards with Grafana
- Predictive alerting with ML
- Automated remediation actions
- Business KPI tracking

## Conclusion

This MCP service layer architecture provides:

1. **Clear Service Boundaries**: Each service has well-defined responsibilities
2. **Scalability**: Horizontal scaling through service isolation
3. **Reliability**: Circuit breakers, retries, and compensation patterns
4. **Observability**: Comprehensive metrics, tracing, and logging
5. **Security**: Multi-layer authentication and authorization
6. **Testability**: Comprehensive testing strategies at all levels
7. **Maintainability**: Clean architecture with SOLID principles

The architecture is designed to support AskProAI's growth from startup to enterprise scale while maintaining code quality and operational excellence.