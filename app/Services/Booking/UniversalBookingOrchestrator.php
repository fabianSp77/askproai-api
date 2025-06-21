<?php

namespace App\Services\Booking;

use App\Models\Branch;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Appointment;
use App\Services\PhoneNumberResolver;
use App\Services\Booking\Strategies\BranchSelectionStrategyInterface;
use App\Services\Booking\Strategies\NearestLocationStrategy;
use App\Services\Booking\StaffServiceMatcher;
use App\Services\Booking\UnifiedAvailabilityService;
use App\Services\CalcomV2Service;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * UniversalBookingOrchestrator - Central orchestrator for multi-tenant, multi-location bookings
 * 
 * This service coordinates the entire booking flow across multiple branches and staff members,
 * finding the optimal combination of location, service, and staff based on various criteria.
 */
class UniversalBookingOrchestrator
{
    private PhoneNumberResolver $phoneResolver;
    private StaffServiceMatcher $staffMatcher;
    private UnifiedAvailabilityService $availabilityService;
    private CalcomV2Service $calcomService;
    private NotificationService $notificationService;
    private BranchSelectionStrategyInterface $branchStrategy;
    
    public function __construct(
        PhoneNumberResolver $phoneResolver,
        StaffServiceMatcher $staffMatcher,
        UnifiedAvailabilityService $availabilityService,
        CalcomV2Service $calcomService,
        NotificationService $notificationService,
        ?BranchSelectionStrategyInterface $branchStrategy = null
    ) {
        $this->phoneResolver = $phoneResolver;
        $this->staffMatcher = $staffMatcher;
        $this->availabilityService = $availabilityService;
        $this->calcomService = $calcomService;
        $this->notificationService = $notificationService;
        $this->branchStrategy = $branchStrategy ?? new NearestLocationStrategy();
    }
    
    /**
     * Main entry point for processing a booking request from any source
     * 
     * @param array $bookingRequest The booking request data
     * @param array $context Additional context (call data, source, etc.)
     * @return array Booking result with appointment details or error
     */
    public function processBookingRequest(array $bookingRequest, array $context = []): array
    {
        Log::info('UniversalBookingOrchestrator: Processing booking request', [
            'request' => $bookingRequest,
            'context' => $context
        ]);
        
        DB::beginTransaction();
        
        try {
            // Step 1: Resolve tenant and initial context
            $tenantContext = $this->resolveTenantContext($bookingRequest, $context);
            
            // Step 2: Find or create customer
            $customer = $this->resolveCustomer($bookingRequest, $tenantContext);
            
            // Step 3: Determine service requirements
            $serviceRequirements = $this->analyzeServiceRequirements($bookingRequest, $tenantContext);
            
            // Step 4: Find suitable branches
            $suitableBranches = $this->findSuitableBranches($serviceRequirements, $customer, $tenantContext);
            
            if (empty($suitableBranches)) {
                throw new Exception('Keine passende Filiale für diese Dienstleistung gefunden');
            }
            
            // Step 5: Find available slots across branches
            $availabilityMatrix = $this->buildAvailabilityMatrix(
                $suitableBranches,
                $serviceRequirements,
                $bookingRequest
            );
            
            if (empty($availabilityMatrix)) {
                throw new Exception('Keine verfügbaren Termine gefunden');
            }
            
            // Step 6: Select optimal booking option
            $optimalBooking = $this->selectOptimalBooking($availabilityMatrix, $bookingRequest, $customer);
            
            // Step 7: Create appointment
            $appointment = $this->createAppointment($optimalBooking, $customer, $bookingRequest);
            
            // Step 8: Sync with external systems
            $this->syncWithExternalSystems($appointment, $optimalBooking);
            
            // Step 9: Send notifications
            $this->sendNotifications($appointment);
            
            DB::commit();
            
            Log::info('UniversalBookingOrchestrator: Booking successful', [
                'appointment_id' => $appointment->id,
                'branch_id' => $appointment->branch_id,
                'staff_id' => $appointment->staff_id
            ]);
            
            return [
                'success' => true,
                'appointment' => $appointment,
                'booking_details' => [
                    'branch' => $appointment->branch->name,
                    'staff' => $appointment->staff?->name,
                    'service' => $appointment->service?->name,
                    'datetime' => $appointment->starts_at->format('d.m.Y H:i'),
                    'confirmation_code' => $appointment->confirmation_code
                ]
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('UniversalBookingOrchestrator: Booking failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'alternatives' => $this->suggestAlternatives($bookingRequest, $tenantContext ?? [])
            ];
        }
    }
    
    /**
     * Resolve tenant context from various sources
     */
    private function resolveTenantContext(array $bookingRequest, array $context): array
    {
        $tenantContext = [
            'company_id' => null,
            'branch_id' => null,
            'source' => $context['source'] ?? 'unknown'
        ];
        
        // Try to resolve from phone number
        if (isset($context['to_number'])) {
            $phoneResolution = $this->phoneResolver->resolveFromWebhook([
                'to' => $context['to_number'],
                'to_number' => $context['to_number'],
                'agent_id' => $context['agent_id'] ?? null
            ]);
            
            $tenantContext['company_id'] = $phoneResolution['company_id'];
            $tenantContext['branch_id'] = $phoneResolution['branch_id'];
        }
        
        // Override with explicit values if provided
        $tenantContext['company_id'] = $bookingRequest['company_id'] ?? $context['company_id'] ?? $tenantContext['company_id'];
        $tenantContext['branch_id'] = $bookingRequest['branch_id'] ?? $context['branch_id'] ?? $tenantContext['branch_id'];
        
        // Validate we have at least a company
        if (!$tenantContext['company_id']) {
            throw new Exception('Konnte Mandanten nicht ermitteln');
        }
        
        return $tenantContext;
    }
    
    /**
     * Find or create customer
     */
    private function resolveCustomer(array $bookingRequest, array $tenantContext): Customer
    {
        $customerData = $bookingRequest['customer'] ?? [];
        
        // Try to find existing customer by phone
        if (!empty($customerData['phone'])) {
            $customer = Customer::where('company_id', $tenantContext['company_id'])
                ->where('phone', $customerData['phone'])
                ->first();
                
            if ($customer) {
                // Update with any new information
                $customer->update(array_filter([
                    'name' => $customerData['name'] ?? $customer->name,
                    'email' => $customerData['email'] ?? $customer->email,
                    'preferred_branch_id' => $customerData['preferred_branch_id'] ?? $customer->preferred_branch_id
                ]));
                
                return $customer;
            }
        }
        
        // Create new customer
        return Customer::create([
            'company_id' => $tenantContext['company_id'],
            'branch_id' => $tenantContext['branch_id'],
            'name' => $customerData['name'] ?? 'Unbekannt',
            'phone' => $customerData['phone'] ?? null,
            'email' => $customerData['email'] ?? null,
            'source' => $tenantContext['source'],
            'metadata' => [
                'first_contact' => now()->toIso8601String(),
                'booking_preferences' => $customerData['preferences'] ?? []
            ]
        ]);
    }
    
    /**
     * Analyze service requirements from booking request
     */
    private function analyzeServiceRequirements(array $bookingRequest, array $tenantContext): array
    {
        $requirements = [
            'service_id' => null,
            'service_name' => null,
            'duration' => null,
            'skills_required' => [],
            'staff_preference' => null,
            'language_preference' => null
        ];
        
        // Extract service information
        if (!empty($bookingRequest['service_id'])) {
            $service = Service::find($bookingRequest['service_id']);
            if ($service) {
                $requirements['service_id'] = $service->id;
                $requirements['service_name'] = $service->name;
                $requirements['duration'] = $service->duration;
                $requirements['skills_required'] = $service->required_skills ?? [];
            }
        } elseif (!empty($bookingRequest['service_name'])) {
            // Try to find service by name
            $service = Service::where('company_id', $tenantContext['company_id'])
                ->where('name', 'LIKE', '%' . $bookingRequest['service_name'] . '%')
                ->first();
                
            if ($service) {
                $requirements['service_id'] = $service->id;
                $requirements['service_name'] = $service->name;
                $requirements['duration'] = $service->duration;
                $requirements['skills_required'] = $service->required_skills ?? [];
            } else {
                $requirements['service_name'] = $bookingRequest['service_name'];
                $requirements['duration'] = $bookingRequest['duration'] ?? 30;
            }
        }
        
        // Extract preferences
        $requirements['staff_preference'] = $bookingRequest['staff_preference'] ?? $bookingRequest['staff_name'] ?? null;
        $requirements['language_preference'] = $bookingRequest['language'] ?? 'de';
        
        return $requirements;
    }
    
    /**
     * Find branches that can provide the required service
     */
    private function findSuitableBranches(array $serviceRequirements, Customer $customer, array $tenantContext): array
    {
        $query = Branch::where('company_id', $tenantContext['company_id'])
            ->where('active', true);
        
        // If a specific branch was requested
        if (!empty($tenantContext['branch_id'])) {
            $branch = $query->find($tenantContext['branch_id']);
            return $branch ? [$branch] : [];
        }
        
        // Get all active branches
        $branches = $query->get();
        
        // Filter branches that offer the service
        if ($serviceRequirements['service_id']) {
            $branches = $branches->filter(function ($branch) use ($serviceRequirements) {
                // Check if branch offers this service (through master services or direct assignment)
                return $branch->activeServices()
                    ->where('master_services.id', $serviceRequirements['service_id'])
                    ->exists() ||
                    $branch->services()
                    ->where('services.id', $serviceRequirements['service_id'])
                    ->exists();
            });
        }
        
        // Apply branch selection strategy
        return $this->branchStrategy->selectBranches(
            $branches->all(),
            $customer,
            $serviceRequirements
        );
    }
    
    /**
     * Build availability matrix across branches and staff
     */
    private function buildAvailabilityMatrix(array $branches, array $serviceRequirements, array $bookingRequest): array
    {
        $matrix = [];
        
        $requestedDate = Carbon::parse($bookingRequest['date'] ?? 'tomorrow');
        $requestedTime = $bookingRequest['time'] ?? null;
        $dateRange = [
            'start' => $requestedDate->copy()->startOfDay(),
            'end' => $requestedDate->copy()->addDays(7)->endOfDay()
        ];
        
        foreach ($branches as $branch) {
            // Find staff members who can provide the service
            $eligibleStaff = $this->staffMatcher->findEligibleStaff(
                $branch,
                $serviceRequirements
            );
            
            foreach ($eligibleStaff as $staff) {
                // Get availability for this staff member
                $availability = $this->availabilityService->getStaffAvailability(
                    $staff,
                    $dateRange,
                    $serviceRequirements['duration'] ?? 30
                );
                
                foreach ($availability as $slot) {
                    // Score each slot based on various factors
                    $score = $this->scoreBookingOption([
                        'branch' => $branch,
                        'staff' => $staff,
                        'slot' => $slot,
                        'requested_time' => $requestedTime,
                        'service_requirements' => $serviceRequirements
                    ]);
                    
                    $matrix[] = [
                        'branch' => $branch,
                        'staff' => $staff,
                        'slot' => $slot,
                        'score' => $score,
                        'details' => [
                            'travel_time' => $this->calculateTravelTime($branch, $bookingRequest),
                            'staff_match_score' => $this->staffMatcher->calculateMatchScore($staff, $serviceRequirements),
                            'time_preference_match' => $this->calculateTimePreferenceMatch($slot, $requestedTime)
                        ]
                    ];
                }
            }
        }
        
        // Sort by score (highest first)
        usort($matrix, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $matrix;
    }
    
    /**
     * Score a booking option based on various factors
     */
    private function scoreBookingOption(array $option): float
    {
        $score = 0;
        
        // Time preference match (40% weight)
        if ($option['requested_time']) {
            $score += $option['slot']['time_match_score'] * 0.4;
        } else {
            $score += 0.4; // No preference, all times equal
        }
        
        // Staff expertise match (30% weight)
        $staffScore = $this->staffMatcher->calculateMatchScore(
            $option['staff'],
            $option['service_requirements']
        );
        $score += $staffScore * 0.3;
        
        // Branch proximity (20% weight)
        // This would use customer location/preference
        $score += 0.2; // Placeholder
        
        // Availability density (10% weight)
        // Prefer times with more availability around them
        $score += 0.1; // Placeholder
        
        return $score;
    }
    
    /**
     * Select the optimal booking from the availability matrix
     */
    private function selectOptimalBooking(array $matrix, array $bookingRequest, Customer $customer): array
    {
        if (empty($matrix)) {
            throw new Exception('Keine verfügbaren Optionen gefunden');
        }
        
        // Return the highest scored option
        return $matrix[0];
    }
    
    /**
     * Create the appointment record
     */
    private function createAppointment(array $bookingOption, Customer $customer, array $bookingRequest): Appointment
    {
        $slot = $bookingOption['slot'];
        $branch = $bookingOption['branch'];
        $staff = $bookingOption['staff'];
        
        $appointment = Appointment::create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'service_id' => $bookingRequest['service_id'] ?? null,
            'starts_at' => $slot['start'],
            'ends_at' => $slot['end'],
            'duration' => $slot['duration'] ?? 30,
            'status' => 'scheduled',
            'confirmation_code' => $this->generateConfirmationCode(),
            'source' => $bookingRequest['source'] ?? 'universal_orchestrator',
            'notes' => $bookingRequest['notes'] ?? null,
            'metadata' => [
                'booking_score' => $bookingOption['score'],
                'booking_details' => $bookingOption['details'],
                'original_request' => $bookingRequest
            ]
        ]);
        
        return $appointment;
    }
    
    /**
     * Sync appointment with external systems (Cal.com, etc.)
     */
    private function syncWithExternalSystems(Appointment $appointment, array $bookingOption): void
    {
        try {
            $branch = $bookingOption['branch'];
            $config = $branch->getEffectiveCalcomConfig();
            
            if (!$config || !$config['event_type_id']) {
                Log::warning('No Cal.com configuration for branch', [
                    'branch_id' => $branch->id
                ]);
                return;
            }
            
            $this->calcomService->setApiKey($config['api_key']);
            
            $calcomBooking = $this->calcomService->createBooking([
                'eventTypeId' => $config['event_type_id'],
                'start' => $appointment->starts_at->toIso8601String(),
                'responses' => [
                    'name' => $appointment->customer->name,
                    'email' => $appointment->customer->email ?? 'noreply@askproai.de',
                    'phone' => $appointment->customer->phone,
                    'notes' => $appointment->notes
                ],
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'branch_id' => $branch->id,
                    'source' => 'universal_orchestrator'
                ]
            ]);
            
            $appointment->update([
                'external_id' => $calcomBooking['id'] ?? null,
                'calcom_booking_id' => $calcomBooking['id'] ?? null
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to sync with Cal.com', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            
            // Queue for retry
            \App\Jobs\RetryCalendarSyncJob::dispatch($appointment->id)
                ->delay(now()->addMinutes(5));
        }
    }
    
    /**
     * Send appointment notifications
     */
    private function sendNotifications(Appointment $appointment): void
    {
        try {
            $this->notificationService->sendAppointmentConfirmation($appointment);
            
            if ($appointment->staff) {
                $this->notificationService->notifyStaffNewAppointment($appointment);
            }
        } catch (Exception $e) {
            Log::error('Failed to send notifications', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Suggest alternative booking options
     */
    private function suggestAlternatives(array $bookingRequest, array $tenantContext): array
    {
        // This would return alternative time slots, branches, or services
        return [];
    }
    
    /**
     * Calculate travel time to branch
     */
    private function calculateTravelTime(Branch $branch, array $bookingRequest): int
    {
        // Placeholder - would use geocoding/distance API
        return 15; // minutes
    }
    
    /**
     * Calculate how well a time slot matches the requested time
     */
    private function calculateTimePreferenceMatch(array $slot, ?string $requestedTime): float
    {
        if (!$requestedTime) {
            return 1.0; // No preference
        }
        
        $requested = Carbon::parse($requestedTime);
        $slotTime = Carbon::parse($slot['start']);
        
        $diffMinutes = abs($requested->diffInMinutes($slotTime));
        
        // Perfect match
        if ($diffMinutes === 0) {
            return 1.0;
        }
        
        // Within 30 minutes
        if ($diffMinutes <= 30) {
            return 0.8;
        }
        
        // Within 1 hour
        if ($diffMinutes <= 60) {
            return 0.6;
        }
        
        // Within 2 hours
        if ($diffMinutes <= 120) {
            return 0.4;
        }
        
        // More than 2 hours difference
        return 0.2;
    }
    
    /**
     * Generate unique confirmation code
     */
    private function generateConfirmationCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 6));
        } while (Appointment::where('confirmation_code', $code)->exists());
        
        return $code;
    }
    
    /**
     * Set branch selection strategy
     */
    public function setBranchSelectionStrategy(BranchSelectionStrategyInterface $strategy): void
    {
        $this->branchStrategy = $strategy;
    }
}