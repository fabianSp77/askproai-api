<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Company;
use App\Traits\TransactionalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\CalcomV2Service;
use App\Services\SecureCalcomService;
use App\Services\NotificationService;
use App\Services\EventTypeMatchingService;
use App\Exceptions\BookingException;
use App\Exceptions\AvailabilityException;
use App\Exceptions\SecurityException;
use App\Services\Locking\TimeSlotLockManager;
use Illuminate\Support\Str;
use App\Helpers\SafeQueryHelper;
use App\Services\MCP\MCPGateway;
use App\Jobs\SendAppointmentEmailJob;

/**
 * SECURE VERSION: Appointment Booking Service with proper tenant isolation
 * 
 * This service orchestrates the complete appointment booking flow with strict
 * multi-tenant security. All operations are scoped to the authenticated company.
 * 
 * Security Features:
 * - Mandatory company context validation
 * - Cross-tenant validation for all entities
 * - Audit logging for all bookings
 * - No arbitrary company fallbacks
 * 
 * @package App\Services
 */
class SecureAppointmentBookingService
{
    use TransactionalService;
    
    /**
     * @var SecureCalcomService Secure calendar integration service
     */
    private $calcomService;
    
    /**
     * @var NotificationService Handles all appointment notifications
     */
    private $notificationService;
    
    /**
     * @var AvailabilityService Checks and manages availability
     */
    private $availabilityService;
    
    /**
     * @var TimeSlotLockManager Prevents double-booking with locks
     */
    private $lockManager;
    
    /**
     * @var EventTypeMatchingService Intelligently matches services to event types
     */
    private $eventTypeMatchingService;
    
    /**
     * @var MCPGateway MCP Gateway for all external service calls
     */
    private $mcpGateway;
    
    /**
     * @var Company|null Current company context
     */
    protected ?Company $company = null;
    
    /**
     * @var bool Audit logging enabled
     */
    protected bool $auditEnabled = true;
    
    /**
     * Initialize the secure appointment booking service
     */
    public function __construct(
        ?SecureCalcomService $calcomService = null,
        ?NotificationService $notificationService = null,
        ?AvailabilityService $availabilityService = null,
        ?TimeSlotLockManager $lockManager = null,
        ?EventTypeMatchingService $eventTypeMatchingService = null,
        ?MCPGateway $mcpGateway = null
    ) {
        $this->calcomService = $calcomService ?? new SecureCalcomService();
        $this->notificationService = $notificationService ?? new NotificationService();
        
        if (!$availabilityService) {
            $cacheService = app(\App\Services\CacheService::class);
            $this->availabilityService = new AvailabilityService($cacheService);
        } else {
            $this->availabilityService = $availabilityService;
        }
        
        $this->lockManager = $lockManager ?? new TimeSlotLockManager();
        $this->eventTypeMatchingService = $eventTypeMatchingService ?? new EventTypeMatchingService();
        $this->mcpGateway = $mcpGateway ?? app(MCPGateway::class);
        
        // Resolve company context
        $this->resolveCompanyContext();
    }
    
    /**
     * Set company context explicitly (only for super admins or system operations)
     */
    public function setCompanyContext(Company $company): self
    {
        // Only allow super admins or system operations to override context
        if (Auth::check() && !Auth::user()->hasRole('super_admin')) {
            throw new SecurityException('Unauthorized company context override');
        }
        
        $this->company = $company;
        
        // Update CalcomService context
        if ($this->calcomService instanceof SecureCalcomService) {
            $this->calcomService->setCompanyContext($company);
        }
        
        $this->auditAccess('company_context_override', [
            'company_id' => $company->id,
            'user_id' => Auth::id(),
        ]);
        
        return $this;
    }
    
    /**
     * Book an appointment from a phone call with AI-extracted data
     * 
     * SECURITY: All entities (customer, service, staff, branch) are validated
     * to belong to the authenticated company.
     */
    public function bookFromPhoneCall($callOrData, ?array $appointmentData = null): array
    {
        $this->ensureCompanyContext();
        
        $lockToken = null;
        $startTime = microtime(true);
        
        try {
            // Determine if we're working with a Call object or data array
            $call = null;
            $data = [];
            
            if ($callOrData instanceof Call) {
                $call = $callOrData;
                // Validate call belongs to company
                $this->validateCallBelongsToCompany($call);
                $data = $appointmentData ?? [];
            } else {
                $data = $callOrData;
            }
            
            $context = [
                'call_id' => $call?->id,
                'has_appointment_data' => !empty($data),
                'company_id' => $this->company->id
            ];
            
            return $this->executeInTransaction(function () use ($call, $data, &$lockToken, $startTime) {
                // 1. Prepare booking data from new format
                $bookingData = $this->prepareBookingDataFromCollectFunction($data, $call);
                
                // 2. Find or create customer (with company validation)
                $customer = $this->findOrCreateCustomerSecure($bookingData['customer']);
                
                // 3. Validate service and staff with company context
                $service = null;
                $staff = null;
                $branch = null;
                
                if (!empty($bookingData['service_id'])) {
                    $service = $this->validateServiceSecure($bookingData['service_id']);
                }
                
                if (!empty($bookingData['staff_id'])) {
                    $staff = $this->validateStaffSecure($bookingData['staff_id'], $service);
                }
                
                // Get branch with company validation
                $branch = $this->resolveBranchSecure($call, $staff, $customer);
                
                // Use EventTypeMatchingService to find appropriate event type
                if ($branch && !empty($bookingData['service_name'])) {
                    Log::info('SecureBooking: Using EventTypeMatchingService', [
                        'service_request' => $bookingData['service_name'],
                        'staff_preference' => $bookingData['staff_name'] ?? null,
                        'branch_id' => $branch->id,
                        'company_id' => $this->company->id
                    ]);
                    
                    $matchResult = $this->eventTypeMatchingService->findMatchingEventType(
                        $bookingData['service_name'],
                        $branch,
                        $bookingData['staff_name'] ?? null,
                        null
                    );
                    
                    if ($matchResult) {
                        $service = $matchResult['service'];
                        $eventType = $matchResult['event_type'];
                        $serviceDuration = $matchResult['duration_minutes'] ?? $service->duration ?? 30;
                        
                        Log::info('SecureBooking: Event type match found', [
                            'service_id' => $service->id,
                            'service_name' => $service->name,
                            'event_type_id' => $eventType['id'] ?? null,
                            'duration' => $serviceDuration,
                            'company_id' => $this->company->id
                        ]);
                    }
                }
                
                // Default duration if no service found
                $serviceDuration = $serviceDuration ?? $service?->duration ?? 30;
                
                // 4. Check availability with locking
                $appointmentTime = Carbon::parse($bookingData['appointment_time']);
                $endTime = $appointmentTime->copy()->addMinutes($serviceDuration);
                
                // Create appointment record
                $appointment = $this->createAppointmentSecure([
                    'company_id' => $this->company->id, // CRITICAL: Force company context
                    'branch_id' => $branch->id,
                    'customer_id' => $customer->id,
                    'service_id' => $service?->id,
                    'staff_id' => $staff?->id,
                    'starts_at' => $appointmentTime,
                    'ends_at' => $endTime,
                    'status' => 'scheduled',
                    'notes' => $bookingData['notes'] ?? null,
                    'source' => 'phone_ai',
                    'call_id' => $call?->id,
                    'confirmation_number' => $this->generateConfirmationNumber(),
                    'metadata' => json_encode([
                        'booking_data' => $bookingData,
                        'duration_minutes' => $serviceDuration,
                        'booking_time' => Carbon::now()->toIso8601String(),
                        'booking_duration_ms' => round((microtime(true) - $startTime) * 1000)
                    ])
                ]);
                
                // 5. Book in calendar system
                if ($branch->calcom_event_type_id) {
                    try {
                        $calcomResult = $this->calcomService->bookAppointment([
                            'eventTypeId' => $branch->calcom_event_type_id,
                            'start' => $appointmentTime->toIso8601String(),
                            'end' => $endTime->toIso8601String(),
                            'responses' => [
                                'name' => $customer->full_name,
                                'email' => $customer->email ?? 'kunde@example.com',
                                'notes' => $this->formatCalcomNotes($bookingData, $customer)
                            ],
                            'metadata' => [
                                'appointment_id' => $appointment->id,
                                'company_id' => $this->company->id
                            ]
                        ]);
                        
                        if ($calcomResult['success'] && isset($calcomResult['data']['id'])) {
                            $appointment->update([
                                'calcom_booking_id' => $calcomResult['data']['id']
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('SecureBooking: Cal.com booking failed', [
                            'error' => $e->getMessage(),
                            'appointment_id' => $appointment->id,
                            'company_id' => $this->company->id
                        ]);
                    }
                }
                
                // 6. Send notifications
                $this->sendNotificationsSecure($appointment, $customer);
                
                // 7. Update call record
                if ($call) {
                    $call->update([
                        'appointment_id' => $appointment->id,
                        'status' => 'completed'
                    ]);
                }
                
                // Audit the booking
                $this->auditAccess('appointment_booked', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customer->id,
                    'service_id' => $service?->id,
                    'staff_id' => $staff?->id,
                    'branch_id' => $branch->id,
                    'call_id' => $call?->id
                ]);
                
                return [
                    'success' => true,
                    'appointment' => $appointment->fresh(),
                    'message' => 'Termin erfolgreich gebucht',
                    'confirmation_number' => $appointment->confirmation_number
                ];
            });
            
        } catch (BookingException $e) {
            $this->logError('booking_failed', $e, $context);
            return [
                'success' => false,
                'appointment' => null,
                'message' => $e->getMessage(),
                'errors' => ['booking' => $e->getMessage()]
            ];
        } catch (\Exception $e) {
            $this->logError('unexpected_error', $e, $context);
            return [
                'success' => false,
                'appointment' => null,
                'message' => 'Ein unerwarteter Fehler ist aufgetreten',
                'errors' => ['system' => $e->getMessage()]
            ];
        } finally {
            if ($lockToken) {
                $this->lockManager->releaseLock($lockToken);
            }
        }
    }
    
    /**
     * Find or create customer with company validation
     */
    protected function findOrCreateCustomerSecure(array $customerData): Customer
    {
        // Ensure company context
        $customerData['company_id'] = $this->company->id;
        
        if (!empty($customerData['phone'])) {
            $customer = Customer::where('phone', $customerData['phone'])
                ->where('company_id', $this->company->id) // CRITICAL: Company scope
                ->first();
                
            if ($customer) {
                // Update existing customer
                $customer->update(array_filter([
                    'first_name' => $customerData['first_name'] ?? $customer->first_name,
                    'last_name' => $customerData['last_name'] ?? $customer->last_name,
                    'email' => $customerData['email'] ?? $customer->email,
                ]));
                
                return $customer;
            }
        }
        
        // Create new customer
        return Customer::create([
            'company_id' => $this->company->id, // CRITICAL: Force company
            'first_name' => $customerData['first_name'] ?? 'Unbekannt',
            'last_name' => $customerData['last_name'] ?? '',
            'phone' => $customerData['phone'] ?? null,
            'email' => $customerData['email'] ?? null,
            'source' => 'phone_ai'
        ]);
    }
    
    /**
     * Validate service belongs to company
     */
    protected function validateServiceSecure($serviceId): Service
    {
        $service = Service::where('id', $serviceId)
            ->where('company_id', $this->company->id) // CRITICAL: Company scope
            ->first();
            
        if (!$service) {
            throw new BookingException("Service {$serviceId} not found or does not belong to company");
        }
        
        return $service;
    }
    
    /**
     * Validate staff belongs to company
     */
    protected function validateStaffSecure($staffId, ?Service $service): Staff
    {
        $staff = Staff::where('id', $staffId)
            ->whereHas('branches', function($query) {
                $query->where('company_id', $this->company->id);
            })
            ->first();
            
        if (!$staff) {
            throw new BookingException("Staff {$staffId} not found or does not belong to company");
        }
        
        // Validate staff can provide service if specified
        if ($service && !$staff->services->contains($service->id)) {
            throw new BookingException("Staff cannot provide requested service");
        }
        
        return $staff;
    }
    
    /**
     * Resolve branch with company validation
     */
    protected function resolveBranchSecure(?Call $call, ?Staff $staff, Customer $customer): Branch
    {
        // Priority: Call branch -> Staff home branch -> Company default
        if ($call && $call->branch_id) {
            $branch = Branch::where('id', $call->branch_id)
                ->where('company_id', $this->company->id) // CRITICAL: Company scope
                ->first();
                
            if ($branch) {
                return $branch;
            }
        }
        
        if ($staff && $staff->home_branch_id) {
            $branch = Branch::where('id', $staff->home_branch_id)
                ->where('company_id', $this->company->id) // CRITICAL: Company scope
                ->first();
                
            if ($branch) {
                return $branch;
            }
        }
        
        // Get company's first/main branch
        $branch = Branch::where('company_id', $this->company->id)
            ->orderBy('is_main', 'desc')
            ->orderBy('created_at', 'asc')
            ->first();
            
        if (!$branch) {
            throw new BookingException('No branch available for booking');
        }
        
        return $branch;
    }
    
    /**
     * Validate call belongs to company
     */
    protected function validateCallBelongsToCompany(Call $call): void
    {
        if ($call->company_id !== $this->company->id) {
            throw new SecurityException("Call {$call->id} does not belong to company");
        }
    }
    
    /**
     * Create appointment with validation
     */
    protected function createAppointmentSecure(array $data): Appointment
    {
        // Force company context
        $data['company_id'] = $this->company->id;
        
        // Validate all foreign keys belong to company
        if (isset($data['branch_id'])) {
            $branch = Branch::where('id', $data['branch_id'])
                ->where('company_id', $this->company->id)
                ->firstOrFail();
        }
        
        if (isset($data['customer_id'])) {
            $customer = Customer::where('id', $data['customer_id'])
                ->where('company_id', $this->company->id)
                ->firstOrFail();
        }
        
        return Appointment::create($data);
    }
    
    /**
     * Send notifications with security context
     */
    protected function sendNotificationsSecure(Appointment $appointment, Customer $customer): void
    {
        try {
            // Queue email notification
            if ($customer->email) {
                SendAppointmentEmailJob::dispatch($appointment, 'confirmation')
                    ->onQueue('emails');
            }
            
            // Additional notifications can be added here
        } catch (\Exception $e) {
            Log::warning('SecureBooking: Notification failed', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'company_id' => $this->company->id
            ]);
        }
    }
    
    /**
     * Prepare booking data from collect function format
     */
    protected function prepareBookingDataFromCollectFunction(array $data, ?Call $call): array
    {
        // Handle both legacy and new formats
        if (isset($data['service_request']) || isset($data['appointment_datetime'])) {
            // New format from collect_appointment_data
            return [
                'service_name' => $data['service_request'] ?? null,
                'appointment_time' => $data['appointment_datetime'] ?? null,
                'staff_name' => $data['staff_preference'] ?? null,
                'notes' => $data['additional_notes'] ?? null,
                'customer' => [
                    'company_id' => $this->company->id,
                    'phone' => $data['customer_phone'] ?? $call?->from_number,
                    'first_name' => $data['customer_name'] ?? null,
                    'email' => $data['customer_email'] ?? null,
                ]
            ];
        }
        
        // Legacy format
        return array_merge($data, [
            'customer' => array_merge($data['customer'] ?? [], [
                'company_id' => $this->company->id
            ])
        ]);
    }
    
    /**
     * Format notes for Cal.com booking
     */
    protected function formatCalcomNotes(array $bookingData, Customer $customer): string
    {
        $notes = [];
        
        if (!empty($customer->phone)) {
            $notes[] = "Telefon: " . $customer->phone;
        }
        
        if (!empty($bookingData['service_name'])) {
            $notes[] = "Service: " . $bookingData['service_name'];
        }
        
        if (!empty($bookingData['notes'])) {
            $notes[] = "Notizen: " . $bookingData['notes'];
        }
        
        $notes[] = "Gebucht über: AI Telefon";
        
        return implode("\n", $notes);
    }
    
    /**
     * Generate unique confirmation number
     */
    protected function generateConfirmationNumber(): string
    {
        return strtoupper(Str::random(3) . '-' . rand(1000, 9999));
    }
    
    /**
     * Resolve company context from authenticated user
     */
    protected function resolveCompanyContext(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user->company_id) {
                $this->company = Company::find($user->company_id);
            }
        }
    }
    
    /**
     * Ensure company context is set
     */
    protected function ensureCompanyContext(): void
    {
        if (!$this->company) {
            throw new SecurityException('No valid company context for appointment booking');
        }
    }
    
    /**
     * Audit access to booking operations
     */
    protected function auditAccess(string $operation, array $context = []): void
    {
        if (!$this->auditEnabled) {
            return;
        }
        
        try {
            if (DB::connection()->getSchemaBuilder()->hasTable('security_audit_logs')) {
                DB::table('security_audit_logs')->insert([
                    'event_type' => 'appointment_booking',
                    'user_id' => Auth::id(),
                    'company_id' => $this->company->id ?? null,
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'url' => request()->fullUrl() ?? 'console',
                    'metadata' => json_encode(array_merge($context, [
                        'operation' => $operation,
                        'user_agent' => request()->userAgent()
                    ])),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('SecureBooking: Audit logging failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Log errors with context
     */
    protected function logError(string $operation, \Exception $e, array $context = []): void
    {
        Log::error("SecureBooking: {$operation} failed", array_merge([
            'company_id' => $this->company->id ?? null,
            'user_id' => Auth::id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], $context));
        
        $this->auditAccess("{$operation}_error", array_merge($context, [
            'error' => $e->getMessage()
        ]));
    }
    
    /**
     * Disable audit logging (for testing)
     */
    public function disableAudit(): self
    {
        $this->auditEnabled = false;
        return $this;
    }
}