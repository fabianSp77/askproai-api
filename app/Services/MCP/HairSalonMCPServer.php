<?php

namespace App\Services\MCP;

use App\Models\Company;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Services\AppointmentBookingService;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * MCP Server for Hair Salon booking integration with Retell.ai
 * 
 * This server handles:
 * - Service listing with consultation requirements
 * - Staff availability across 3 Google Calendars
 * - Complex multi-block appointments with breaks
 * - Callback scheduling for consultation services
 */
class HairSalonMCPServer
{
    protected Company $salonCompany;
    protected GoogleCalendarService $calendarService;
    protected AppointmentBookingService $bookingService;
    
    // Services requiring consultation
    protected array $consultationServices = [
        'Klassisches Strähnen-Paket',
        'Globale Blondierung',
        'Stähnentechnik Balayage',
        'Faceframe'
    ];
    
    // Services with complex time blocks
    protected array $multiBlockServices = [
        'Ansatzfärbung + Waschen, schneiden, föhnen' => [
            'total_duration' => 120, // 2 hours total
            'blocks' => [
                ['duration' => 30, 'type' => 'work', 'description' => 'Färbung auftragen'],
                ['duration' => 30, 'type' => 'break', 'description' => 'Einwirkzeit'],
                ['duration' => 60, 'type' => 'work', 'description' => 'Waschen, schneiden, föhnen']
            ]
        ]
    ];
    
    public function __construct()
    {
        // Make Google Calendar optional - use only if configured
        try {
            if (config('services.google.client_id')) {
                $this->calendarService = new GoogleCalendarService();
            }
        } catch (\Exception $e) {
            \Log::info('Google Calendar not configured for Hair Salon MCP');
        }
        
        // Make booking service optional as well
        try {
            $this->bookingService = app(AppointmentBookingService::class);
        } catch (\Exception $e) {
            \Log::info('AppointmentBookingService not available');
        }
    }
    
    /**
     * Initialize with specific salon company
     */
    public function setSalonCompany(Company $company): self
    {
        $this->salonCompany = $company;
        return $this;
    }
    
    /**
     * Ensure salon company is loaded
     */
    protected function ensureSalonCompany(array $params): bool
    {
        if (!isset($this->salonCompany)) {
            $companyId = $params['company_id'] ?? 1;
            $this->salonCompany = Company::find($companyId);
            
            if (!$this->salonCompany) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get all available services with details
     */
    public function getServices(array $params = []): array
    {
        try {
            if (!$this->ensureSalonCompany($params)) {
                \Log::warning('HairSalonMCP: Company not found in ensureSalonCompany');
                return ['success' => false, 'error' => 'Company not found'];
            }
            
            \Log::info('HairSalonMCP: Fetching services', [
                'company_id' => $this->salonCompany->id ?? null,
                'company_name' => $this->salonCompany->name ?? null
            ]);
            
            // Use raw DB query to bypass all scopes
            $servicesData = \DB::table('services')
                ->where('company_id', $this->salonCompany->id)
                ->where('active', 1)
                ->orderBy('sort_order')
                ->get();
            
            \Log::info('HairSalonMCP: Using raw DB query', [
                'count' => $servicesData->count(),
                'company_id' => $this->salonCompany->id
            ]);
            
            // Convert to collection for compatibility
            $services = collect($servicesData);
            
            $serviceList = [];
            foreach ($services as $service) {
                $serviceData = [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => $service->price,
                    'duration_minutes' => $service->default_duration_minutes ?? 30,
                    'requires_consultation' => $this->requiresConsultation($service->name),
                    'has_breaks' => $this->hasMultipleBlocks($service->name),
                    'available_with' => $this->getAvailableStaff($service->id)
                ];
                
                // Add break pattern if applicable
                if ($serviceData['has_breaks']) {
                    $serviceData['time_blocks'] = $this->multiBlockServices[$service->name] ?? null;
                }
                
                $serviceList[] = $serviceData;
            }
            
            return [
                'success' => true,
                'services' => $serviceList,
                'total' => count($serviceList),
                'consultation_note' => 'Services marked with requires_consultation need a callback or direct consultation'
            ];
            
        } catch (\Exception $e) {
            Log::error('HairSalonMCP::getServices error', [
                'error' => $e->getMessage(),
                'company_id' => $this->salonCompany->id ?? null
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to retrieve services: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get available staff members
     */
    public function getStaff(array $params = []): array
    {
        try {
            $staff = Staff::where('company_id', $this->salonCompany->id)
                ->where('is_bookable', true)
                ->where('is_active', true)
                ->get();
            
            $staffList = [];
            foreach ($staff as $member) {
                $staffList[] = [
                    'id' => $member->id,
                    'name' => $member->name,
                    'calendar_id' => $member->google_calendar_id ?? $member->external_calendar_id,
                    'available_services' => $this->getStaffServices($member->id),
                    'working_hours' => $this->getStaffWorkingHours($member->id)
                ];
            }
            
            return [
                'success' => true,
                'staff' => $staffList,
                'total' => count($staffList)
            ];
            
        } catch (\Exception $e) {
            Log::error('HairSalonMCP::getStaff error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Failed to retrieve staff: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check availability for a specific service and optional staff member
     */
    public function checkAvailability(array $params): array
    {
        try {
            if (!$this->ensureSalonCompany($params)) {
                return ['success' => false, 'error' => 'Company not found'];
            }
            
            $serviceId = $params['service_id'] ?? null;
            $staffId = $params['staff_id'] ?? null;
            $date = $params['date'] ?? Carbon::today()->format('Y-m-d');
            $days = $params['days'] ?? 7;
            
            if (!$serviceId) {
                return ['success' => false, 'error' => 'Service ID required'];
            }
            
            // Use raw DB query to bypass tenant scope
            $service = \DB::table('services')
                ->where('id', $serviceId)
                ->first();
            if (!$service) {
                return ['success' => false, 'error' => 'Service not found'];
            }
            
            // Check if service requires consultation
            if ($this->requiresConsultation($service->name)) {
                return [
                    'success' => true,
                    'requires_consultation' => true,
                    'message' => 'This service requires a consultation. Would you like to schedule a callback?',
                    'callback_available' => true
                ];
            }
            
            $availableSlots = [];
            $startDate = Carbon::parse($date);
            $endDate = $startDate->copy()->addDays($days);
            
            // Get staff members who can perform this service (bypass tenant scope)
            if ($staffId) {
                $staffData = \DB::table('staff')->where('id', $staffId)->get();
            } else {
                $staffData = \DB::table('staff')
                    ->where('company_id', $this->salonCompany->id)
                    ->where('is_bookable', true)
                    ->get();
            }
            $staffMembers = collect($staffData);
            
            foreach ($staffMembers as $staff) {
                $calendarId = $staff->google_calendar_id ?? $staff->external_calendar_id;
                if (!$calendarId) continue;
                
                // Check if service has multiple blocks
                if ($this->hasMultipleBlocks($service->name)) {
                    $slots = $this->checkMultiBlockAvailability(
                        $staff, 
                        $service, 
                        $startDate, 
                        $endDate
                    );
                } else {
                    // Use calendar service if available, otherwise generate mock slots
                    if ($this->calendarService) {
                        $slots = $this->calendarService->getAvailableSlots(
                            $calendarId,
                            $startDate,
                            $endDate,
                            $service->default_duration_minutes
                        );
                    } else {
                        // Generate sample slots for testing
                        $slots = $this->generateMockSlots($startDate, $endDate, $service->default_duration_minutes);
                    }
                }
                
                foreach ($slots as $slot) {
                    $availableSlots[] = [
                        'staff_id' => $staff->id,
                        'staff_name' => $staff->name,
                        'date' => $slot['date'],
                        'time' => $slot['time'],
                        'datetime' => $slot['datetime'] ?? "{$slot['date']} {$slot['time']}"
                    ];
                }
            }
            
            // Sort by datetime
            usort($availableSlots, function($a, $b) {
                return strtotime($a['datetime']) - strtotime($b['datetime']);
            });
            
            // Limit to first 10 slots for voice response
            $availableSlots = array_slice($availableSlots, 0, 10);
            
            return [
                'success' => true,
                'service' => $service->name,
                'duration_minutes' => $service->default_duration_minutes,
                'available_slots' => $availableSlots,
                'total_slots' => count($availableSlots),
                'date_range' => "$date to " . $endDate->format('Y-m-d')
            ];
            
        } catch (\Exception $e) {
            Log::error('HairSalonMCP::checkAvailability error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to check availability: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Book an appointment
     */
    public function bookAppointment(array $params): array
    {
        try {
            if (!$this->ensureSalonCompany($params)) {
                return ['success' => false, 'error' => 'Company not found'];
            }
            
            DB::beginTransaction();
            
            // Extract parameters
            $customerName = $params['customer_name'] ?? null;
            $customerPhone = $params['customer_phone'] ?? null;
            $serviceId = $params['service_id'] ?? null;
            $staffId = $params['staff_id'] ?? null;
            $datetime = $params['datetime'] ?? null;
            $notes = $params['notes'] ?? '';
            $callId = $params['call_id'] ?? null;
            
            // Validate required fields
            if (!$customerName || !$customerPhone || !$serviceId || !$staffId || !$datetime) {
                return [
                    'success' => false,
                    'error' => 'Missing required fields: customer_name, customer_phone, service_id, staff_id, datetime'
                ];
            }
            
            // Get or create customer
            $customer = Customer::firstOrCreate(
                ['phone' => $customerPhone],
                [
                    'name' => $customerName,
                    'company_id' => $this->salonCompany->id,
                    'source' => 'phone'
                ]
            );
            
            // Get service and staff
            $service = Service::find($serviceId);
            $staff = Staff::find($staffId);
            
            if (!$service || !$staff) {
                DB::rollback();
                return [
                    'success' => false,
                    'error' => 'Invalid service or staff ID'
                ];
            }
            
            // Check if consultation required
            if ($this->requiresConsultation($service->name)) {
                // Create callback request instead
                return $this->scheduleCallback([
                    'customer_id' => $customer->id,
                    'service_id' => $serviceId,
                    'customer_phone' => $customerPhone,
                    'notes' => "Consultation required for: {$service->name}"
                ]);
            }
            
            $appointmentStart = Carbon::parse($datetime);
            
            // Handle multi-block appointments
            if ($this->hasMultipleBlocks($service->name)) {
                $result = $this->bookMultiBlockAppointment(
                    $customer,
                    $service,
                    $staff,
                    $appointmentStart,
                    $notes,
                    $callId
                );
            } else {
                // Standard single-block appointment
                $appointmentEnd = $appointmentStart->copy()->addMinutes($service->default_duration_minutes);
                
                // Create appointment in database
                $appointment = Appointment::create([
                    'customer_id' => $customer->id,
                    'company_id' => $this->salonCompany->id,
                    'branch_id' => $staff->branch_id,
                    'staff_id' => $staff->id,
                    'service_id' => $service->id,
                    'starts_at' => $appointmentStart,
                    'ends_at' => $appointmentEnd,
                    'status' => 'confirmed',
                    'price' => $service->price,
                    'notes' => $notes,
                    'source' => 'phone',
                    'call_id' => $callId
                ]);
                
                // Create Google Calendar event
                $calendarId = $staff->google_calendar_id ?? $staff->external_calendar_id;
                if ($calendarId) {
                    $eventId = $this->calendarService->createEvent($calendarId, [
                        'summary' => "{$service->name} - {$customer->name}",
                        'description' => "Kunde: {$customer->name}\nTelefon: {$customer->phone}\n{$notes}",
                        'start' => $appointmentStart->toIso8601String(),
                        'end' => $appointmentEnd->toIso8601String()
                    ]);
                    
                    $appointment->update(['external_id' => $eventId]);
                }
                
                $result = [
                    'success' => true,
                    'appointment_id' => $appointment->id,
                    'message' => "Appointment booked successfully"
                ];
            }
            
            DB::commit();
            
            // Cache clear for availability
            Cache::tags(['appointments', "staff_{$staffId}"])->flush();
            
            return array_merge($result, [
                'customer_name' => $customer->name,
                'service_name' => $service->name,
                'staff_name' => $staff->name,
                'datetime' => $appointmentStart->format('d.m.Y H:i'),
                'duration' => $service->default_duration_minutes . ' minutes',
                'price' => $service->price . '€'
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('HairSalonMCP::bookAppointment error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to book appointment: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Schedule a callback for consultation services
     */
    public function scheduleCallback(array $params): array
    {
        try {
            if (!$this->ensureSalonCompany($params)) {
                return ['success' => false, 'error' => 'Company not found'];
            }
            
            $customerId = $params['customer_id'] ?? null;
            $customerPhone = $params['customer_phone'] ?? null;
            $serviceId = $params['service_id'] ?? null;
            $preferredTime = $params['preferred_time'] ?? null;
            $notes = $params['notes'] ?? '';
            
            // Create callback request (stored as a special appointment)
            $callback = Appointment::create([
                'customer_id' => $customerId,
                'company_id' => $this->salonCompany->id,
                'service_id' => $serviceId,
                'starts_at' => $preferredTime ? Carbon::parse($preferredTime) : Carbon::now()->addHours(2),
                'ends_at' => $preferredTime ? Carbon::parse($preferredTime)->addMinutes(30) : Carbon::now()->addHours(2)->addMinutes(30),
                'status' => 'callback_required',
                'notes' => "CALLBACK REQUIRED: $notes",
                'source' => 'phone',
                'metadata' => [
                    'callback_phone' => $customerPhone,
                    'consultation_required' => true
                ]
            ]);
            
            // Log for salon staff
            Log::info('Callback scheduled for consultation', [
                'customer_phone' => $customerPhone,
                'service_id' => $serviceId,
                'callback_id' => $callback->id
            ]);
            
            return [
                'success' => true,
                'callback_scheduled' => true,
                'callback_id' => $callback->id,
                'message' => 'A callback has been scheduled. Our specialist will contact you for consultation.',
                'expected_callback' => $callback->starts_at->format('d.m.Y H:i')
            ];
            
        } catch (\Exception $e) {
            Log::error('HairSalonMCP::scheduleCallback error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to schedule callback: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Book multi-block appointment with breaks
     */
    protected function bookMultiBlockAppointment($customer, $service, $staff, $startTime, $notes, $callId): array
    {
        $blocks = $this->multiBlockServices[$service->name]['blocks'] ?? [];
        $appointments = [];
        $currentTime = $startTime->copy();
        $calendarId = $staff->google_calendar_id ?? $staff->external_calendar_id;
        
        // Generate a unique series ID for all blocks
        $seriesId = uniqid('series_');
        
        foreach ($blocks as $index => $block) {
            if ($block['type'] === 'work') {
                $blockEnd = $currentTime->copy()->addMinutes($block['duration']);
                
                // Create appointment for work block
                $appointment = Appointment::create([
                    'customer_id' => $customer->id,
                    'company_id' => $this->salonCompany->id,
                    'branch_id' => $staff->branch_id,
                    'staff_id' => $staff->id,
                    'service_id' => $service->id,
                    'starts_at' => $currentTime,
                    'ends_at' => $blockEnd,
                    'status' => 'confirmed',
                    'price' => $index === 0 ? $service->price : 0, // Charge only on first block
                    'notes' => "{$block['description']} - Teil " . ($index + 1),
                    'source' => 'phone',
                    'call_id' => $callId,
                    'series_id' => $seriesId,
                    'metadata' => [
                        'block_index' => $index,
                        'block_type' => 'work',
                        'total_blocks' => count($blocks)
                    ]
                ]);
                
                // Create calendar event for work block
                if ($calendarId) {
                    $eventId = $this->calendarService->createEvent($calendarId, [
                        'summary' => "{$service->name} - {$customer->name} (Teil " . ($index + 1) . ")",
                        'description' => "{$block['description']}\nKunde: {$customer->name}\nTelefon: {$customer->phone}",
                        'start' => $currentTime->toIso8601String(),
                        'end' => $blockEnd->toIso8601String()
                    ]);
                    
                    $appointment->update(['external_id' => $eventId]);
                }
                
                $appointments[] = $appointment;
            }
            
            // Move time forward (including breaks)
            $currentTime->addMinutes($block['duration']);
        }
        
        return [
            'success' => true,
            'appointments' => array_map(fn($a) => $a->id, $appointments),
            'series_id' => $seriesId,
            'message' => "Multi-block appointment booked successfully",
            'total_duration' => $this->multiBlockServices[$service->name]['total_duration'] . ' minutes'
        ];
    }
    
    /**
     * Check if service requires consultation
     */
    protected function requiresConsultation(string $serviceName): bool
    {
        return in_array($serviceName, $this->consultationServices);
    }
    
    /**
     * Check if service has multiple time blocks
     */
    protected function hasMultipleBlocks(string $serviceName): bool
    {
        return isset($this->multiBlockServices[$serviceName]);
    }
    
    /**
     * Get staff members available for a service
     */
    protected function getAvailableStaff(int $serviceId): array
    {
        // For hair salon, all staff can perform all services
        // This can be customized based on staff skills
        // Use raw DB to bypass tenant scope
        return \DB::table('staff')
            ->where('company_id', $this->salonCompany->id)
            ->where('is_bookable', true)
            ->pluck('name')
            ->toArray();
    }
    
    /**
     * Get services a staff member can perform
     */
    protected function getStaffServices(string $staffId): array
    {
        // For hair salon, all staff can perform all services
        // This can be customized based on staff skills
        return Service::where('company_id', $this->salonCompany->id)
            ->where('active', true)
            ->pluck('name')
            ->toArray();
    }
    
    /**
     * Get staff working hours
     */
    protected function getStaffWorkingHours(string $staffId): array
    {
        // Default hair salon hours
        return [
            'monday' => '09:00-18:00',
            'tuesday' => '09:00-18:00',
            'wednesday' => '09:00-18:00',
            'thursday' => '09:00-20:00',
            'friday' => '09:00-20:00',
            'saturday' => '09:00-16:00',
            'sunday' => 'closed'
        ];
    }
    
    /**
     * Check availability for multi-block appointments
     */
    protected function checkMultiBlockAvailability($staff, $service, $startDate, $endDate): array
    {
        $availableSlots = [];
        $blocks = $this->multiBlockServices[$service->name]['blocks'] ?? [];
        $totalDuration = $this->multiBlockServices[$service->name]['total_duration'] ?? 120;
        
        $calendarId = $staff->google_calendar_id ?? $staff->external_calendar_id;
        if (!$calendarId) return [];
        
        // If no calendar service, return mock slots
        if (!$this->calendarService) {
            return $this->generateMockSlots($startDate, $endDate, $totalDuration);
        }
        
        // Get all potential start times
        $potentialSlots = $this->calendarService->getAvailableSlots(
            $calendarId,
            $startDate,
            $endDate,
            30 // Check in 30-minute increments
        );
        
        foreach ($potentialSlots as $slot) {
            $slotStart = Carbon::parse("{$slot['date']} {$slot['time']}");
            $isAvailable = true;
            $currentTime = $slotStart->copy();
            
            // Check if all blocks fit
            foreach ($blocks as $block) {
                if ($block['type'] === 'work') {
                    // Check if this work block time is available
                    if (!$this->calendarService->isTimeAvailable(
                        $calendarId,
                        $currentTime,
                        $block['duration']
                    )) {
                        $isAvailable = false;
                        break;
                    }
                }
                $currentTime->addMinutes($block['duration']);
            }
            
            if ($isAvailable) {
                $availableSlots[] = [
                    'date' => $slot['date'],
                    'time' => $slot['time'],
                    'total_duration' => $totalDuration
                ];
            }
        }
        
        return $availableSlots;
    }
    
    /**
     * Get customer by phone number
     */
    public function getCustomerByPhone(array $params): array
    {
        try {
            $phone = $params['phone'] ?? null;
            if (!$phone) {
                return ['success' => false, 'error' => 'Phone number required'];
            }
            
            $customer = Customer::where('phone', $phone)
                ->where('company_id', $this->salonCompany->id)
                ->first();
            
            if ($customer) {
                // Get customer's appointment history
                $appointments = Appointment::where('customer_id', $customer->id)
                    ->where('company_id', $this->salonCompany->id)
                    ->orderBy('starts_at', 'desc')
                    ->limit(5)
                    ->get();
                
                return [
                    'success' => true,
                    'customer_found' => true,
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'email' => $customer->email,
                        'appointment_count' => $appointments->count(),
                        'last_appointment' => $appointments->first() ? [
                            'date' => $appointments->first()->starts_at->format('d.m.Y'),
                            'service' => $appointments->first()->service->name ?? 'Unknown'
                        ] : null
                    ]
                ];
            }
            
            return [
                'success' => true,
                'customer_found' => false,
                'message' => 'No customer found with this phone number'
            ];
            
        } catch (\Exception $e) {
            Log::error('HairSalonMCP::getCustomerByPhone error', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to lookup customer: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate mock slots for testing when calendar service not available
     */
    private function generateMockSlots($startDate, $endDate, $duration): array
    {
        $slots = [];
        $current = $startDate->copy();
        
        while ($current <= $endDate) {
            // Skip Sundays
            if ($current->dayOfWeek !== 0) {
                // Morning slots: 9:00, 10:00, 11:00
                $slots[] = ['start' => $current->copy()->setTime(9, 0), 'end' => $current->copy()->setTime(9, 0)->addMinutes($duration)];
                $slots[] = ['start' => $current->copy()->setTime(10, 0), 'end' => $current->copy()->setTime(10, 0)->addMinutes($duration)];
                $slots[] = ['start' => $current->copy()->setTime(11, 0), 'end' => $current->copy()->setTime(11, 0)->addMinutes($duration)];
                
                // Afternoon slots: 14:00, 15:00, 16:00, 17:00
                $slots[] = ['start' => $current->copy()->setTime(14, 0), 'end' => $current->copy()->setTime(14, 0)->addMinutes($duration)];
                $slots[] = ['start' => $current->copy()->setTime(15, 0), 'end' => $current->copy()->setTime(15, 0)->addMinutes($duration)];
                $slots[] = ['start' => $current->copy()->setTime(16, 0), 'end' => $current->copy()->setTime(16, 0)->addMinutes($duration)];
                $slots[] = ['start' => $current->copy()->setTime(17, 0), 'end' => $current->copy()->setTime(17, 0)->addMinutes($duration)];
            }
            
            $current->addDay();
        }
        
        return $slots;
    }
}