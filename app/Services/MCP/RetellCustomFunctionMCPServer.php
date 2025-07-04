<?php

namespace App\Services\MCP;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\RetellConfiguration;
use App\Models\Branch;
use App\Services\PhoneNumberResolver;
use App\Services\AppointmentBookingService;
use App\Services\CalcomV2Service;
use App\Services\Retell\CustomFunctionHandler;
use App\Exceptions\MCPException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * MCP Server for handling Retell.ai custom function calls
 * 
 * This server processes custom function requests from Retell agents
 * during active phone calls.
 */
class RetellCustomFunctionMCPServer
{
    protected PhoneNumberResolver $phoneResolver;
    protected AppointmentBookingService $bookingService;
    protected CalcomV2Service $calcomService;
    protected CustomFunctionHandler $customFunctionHandler;
    
    public function __construct(
        PhoneNumberResolver $phoneResolver,
        AppointmentBookingService $bookingService,
        CalcomV2Service $calcomService
    ) {
        $this->phoneResolver = $phoneResolver;
        $this->bookingService = $bookingService;
        $this->calcomService = $calcomService;
        $this->customFunctionHandler = new CustomFunctionHandler($bookingService, $calcomService);
    }
    
    /**
     * Handle collect_appointment function
     * 
     * @param array $params
     * @return array
     */
    public function collect_appointment(array $params): array
    {
        Log::info('MCP: collect_appointment called', ['params' => $params]);
        
        try {
            // Validate required fields
            $this->validateAppointmentData($params);
            
            // Extract call context
            $callId = $params['call_id'] ?? null;
            $callerNumber = $params['caller_number'] ?? $params['telefonnummer'] ?? null;
            
            if (!$callId) {
                throw new MCPException('Call ID is required', MCPException::INVALID_PARAMS);
            }
            
            // Resolve company context from phone number
            $context = $this->resolveContext($callerNumber, $params['to_number'] ?? null);
            
            if (!$context['company_id']) {
                throw new MCPException('Unable to determine company', MCPException::INVALID_PARAMS);
            }
            
            // Parse and validate appointment data
            $appointmentData = [
                'company_id' => $context['company_id'],
                'branch_id' => $context['branch_id'],
                'call_id' => $callId,
                'customer_name' => $params['name'],
                'customer_phone' => $this->normalizePhoneNumber($callerNumber),
                'service_name' => $params['dienstleistung'],
                'requested_date' => $this->parseDate($params['datum']),
                'requested_time' => $this->parseTime($params['uhrzeit']),
                'notes' => $params['notizen'] ?? null,
            ];
            
            // Store in cache for webhook processing
            $cacheKey = "retell:appointment:{$callId}";
            Cache::put($cacheKey, $appointmentData, 3600); // 1 hour TTL
            
            Log::info('Appointment data collected and cached', [
                'call_id' => $callId,
                'cache_key' => $cacheKey,
                'data' => $appointmentData,
            ]);
            
            return [
                'success' => true,
                'reference_id' => Str::uuid()->toString(),
                'message' => 'Termindaten erfolgreich erfasst',
                'appointment_summary' => $this->generateSummary($appointmentData),
            ];
            
        } catch (\Exception $e) {
            Log::error('collect_appointment failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e instanceof MCPException ? $e->getCode() : -32603,
            ];
        }
    }
    
    /**
     * Handle change_appointment function
     * 
     * @param array $params
     * @return array
     */
    public function change_appointment(array $params): array
    {
        Log::info('MCP: change_appointment called', ['params' => $params]);
        
        try {
            $callerNumber = $params['caller_number'] ?? null;
            
            if (!$callerNumber) {
                throw new MCPException('Caller number is required', MCPException::INVALID_PARAMS);
            }
            
            // Find customer by phone number
            $customer = Customer::where('phone', $this->normalizePhoneNumber($callerNumber))
                ->orWhere('phone', $callerNumber)
                ->first();
            
            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Keine Termine unter dieser Nummer gefunden',
                ];
            }
            
            // Find upcoming appointments
            $appointment = Appointment::where('customer_id', $customer->id)
                ->where('starts_at', '>', now())
                ->where('status', 'scheduled')
                ->orderBy('starts_at')
                ->first();
            
            if (!$appointment) {
                return [
                    'success' => false,
                    'message' => 'Kein anstehender Termin gefunden',
                ];
            }
            
            // Parse new date and time
            $newDate = $this->parseDate($params['neues_datum']);
            $newTime = $this->parseTime($params['neue_uhrzeit']);
            $newDateTime = Carbon::parse("{$newDate} {$newTime}");
            
            // Check availability
            $isAvailable = $this->checkAvailability(
                $appointment->branch_id,
                $appointment->service_id,
                $newDateTime
            );
            
            if (!$isAvailable) {
                return [
                    'success' => false,
                    'message' => 'Der gewünschte Termin ist nicht verfügbar',
                    'current_appointment' => [
                        'date' => $appointment->starts_at->format('d.m.Y'),
                        'time' => $appointment->starts_at->format('H:i'),
                    ],
                ];
            }
            
            // Update appointment
            $oldDateTime = $appointment->starts_at->copy();
            
            $appointment->update([
                'starts_at' => $newDateTime,
                'ends_at' => $newDateTime->copy()->addMinutes($appointment->duration_minutes),
                'rescheduled_at' => now(),
                'rescheduled_from' => $oldDateTime,
            ]);
            
            // Log change
            activity()
                ->performedOn($appointment)
                ->causedBy($customer)
                ->withProperties([
                    'old_datetime' => $oldDateTime->toIso8601String(),
                    'new_datetime' => $newDateTime->toIso8601String(),
                    'source' => 'phone_call',
                ])
                ->log('appointment_rescheduled');
            
            return [
                'success' => true,
                'message' => 'Termin erfolgreich verschoben',
                'appointment_id' => $appointment->id,
                'old_datetime' => $oldDateTime->format('d.m.Y H:i'),
                'new_datetime' => $newDateTime->format('d.m.Y H:i'),
            ];
            
        } catch (\Exception $e) {
            Log::error('change_appointment failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Handle cancel_appointment function
     * 
     * @param array $params
     * @return array
     */
    public function cancel_appointment(array $params): array
    {
        Log::info('MCP: cancel_appointment called', ['params' => $params]);
        
        try {
            $callerNumber = $params['caller_number'] ?? null;
            
            if (!$callerNumber) {
                throw new MCPException('Caller number is required', MCPException::INVALID_PARAMS);
            }
            
            // Find customer
            $customer = Customer::where('phone', $this->normalizePhoneNumber($callerNumber))
                ->orWhere('phone', $callerNumber)
                ->first();
            
            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Keine Termine unter dieser Nummer gefunden',
                ];
            }
            
            // Find upcoming appointment
            $appointment = Appointment::where('customer_id', $customer->id)
                ->where('starts_at', '>', now())
                ->where('status', 'scheduled')
                ->orderBy('starts_at')
                ->first();
            
            if (!$appointment) {
                return [
                    'success' => false,
                    'message' => 'Kein anstehender Termin gefunden',
                ];
            }
            
            // Cancel appointment
            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $params['grund'] ?? 'Telefonisch storniert',
            ]);
            
            // Log cancellation
            activity()
                ->performedOn($appointment)
                ->causedBy($customer)
                ->withProperties([
                    'reason' => $params['grund'] ?? 'No reason provided',
                    'source' => 'phone_call',
                ])
                ->log('appointment_cancelled');
            
            // TODO: Send cancellation email
            // TODO: Cancel in Cal.com if integrated
            
            return [
                'success' => true,
                'message' => 'Termin erfolgreich storniert',
                'appointment_id' => $appointment->id,
                'appointment_datetime' => $appointment->starts_at->format('d.m.Y H:i'),
                'service' => $appointment->service->name,
            ];
            
        } catch (\Exception $e) {
            Log::error('cancel_appointment failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Handle check_availability function
     * 
     * @param array $params
     * @return array
     */
    public function check_availability(array $params): array
    {
        Log::info('MCP: check_availability called', ['params' => $params]);
        
        try {
            // Resolve context
            $context = $this->resolveContext(
                $params['caller_number'] ?? null,
                $params['to_number'] ?? null
            );
            
            if (!$context['branch_id']) {
                throw new MCPException('Unable to determine branch', MCPException::INVALID_PARAMS);
            }
            
            $date = $this->parseDate($params['datum']);
            $serviceName = $params['dienstleistung'] ?? null;
            
            // Get available slots
            $slots = $this->getAvailableSlots(
                $context['branch_id'],
                $date,
                $serviceName
            );
            
            if (empty($slots)) {
                return [
                    'success' => true,
                    'available' => false,
                    'message' => 'Keine freien Termine an diesem Tag',
                    'next_available_date' => $this->findNextAvailableDate(
                        $context['branch_id'],
                        $date,
                        $serviceName
                    ),
                ];
            }
            
            // Format slots for voice response
            $formattedSlots = collect($slots)
                ->take(5) // Limit to 5 slots for voice
                ->map(fn($slot) => Carbon::parse($slot)->format('H:i'))
                ->join(', ');
            
            return [
                'success' => true,
                'available' => true,
                'date' => Carbon::parse($date)->format('d.m.Y'),
                'slots_count' => count($slots),
                'available_times' => $formattedSlots,
                'message' => sprintf(
                    'Es sind %d Termine verfügbar: %s Uhr',
                    count($slots),
                    $formattedSlots
                ),
            ];
            
        } catch (\Exception $e) {
            Log::error('check_availability failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Resolve company and branch context
     */
    protected function resolveContext(?string $callerNumber, ?string $toNumber): array
    {
        // Try to resolve from to_number (the number that was called)
        if ($toNumber) {
            $context = $this->phoneResolver->resolveFromPhone($toNumber);
            if ($context['company_id']) {
                return $context;
            }
        }
        
        // Fallback to first company/branch
        $company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->first();
        $branch = null;
        if ($company) {
            // Get the branch with staff (Hauptfiliale)
            $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $company->id)
                ->where('id', '35a66176-5376-11f0-b773-0ad77e7a9793')
                ->first();
            
            // If not found, get any active branch
            if (!$branch) {
                $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('company_id', $company->id)
                    ->where('is_active', true)
                    ->first();
            }
        }
        
        return [
            'company_id' => $company?->id,
            'branch_id' => $branch?->id,
            'company' => $company,
            'branch' => $branch,
        ];
    }
    
    /**
     * Validate appointment data
     */
    protected function validateAppointmentData(array $data): void
    {
        $required = ['datum', 'uhrzeit', 'dienstleistung', 'name'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new MCPException(
                    "Feld '{$field}' ist erforderlich",
                    MCPException::INVALID_PARAMS
                );
            }
        }
    }
    
    /**
     * Parse German date format
     */
    protected function parseDate(string $date): string
    {
        // Handle various German date formats
        $date = trim($date);
        
        // Try different formats
        $formats = [
            'd.m.Y',
            'd.m.y',
            'd-m-Y',
            'd/m/Y',
            'Y-m-d',
        ];
        
        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $date);
                if ($parsed) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Try relative dates
        $relativeDates = [
            'heute' => 'today',
            'morgen' => 'tomorrow',
            'übermorgen' => '+2 days',
            'montag' => 'next monday',
            'dienstag' => 'next tuesday',
            'mittwoch' => 'next wednesday',
            'donnerstag' => 'next thursday',
            'freitag' => 'next friday',
            'samstag' => 'next saturday',
            'sonntag' => 'next sunday',
        ];
        
        $lowerDate = strtolower($date);
        if (isset($relativeDates[$lowerDate])) {
            return Carbon::parse($relativeDates[$lowerDate])->format('Y-m-d');
        }
        
        throw new MCPException("Ungültiges Datumsformat: {$date}", MCPException::INVALID_PARAMS);
    }
    
    /**
     * Parse time format
     */
    protected function parseTime(string $time): string
    {
        $time = trim(str_replace(['Uhr', 'uhr'], '', $time));
        
        // Handle formats like "14:30", "14.30", "14 30", "1430"
        $time = preg_replace('/[^\d]/', ':', $time);
        $time = preg_replace('/:{2,}/', ':', $time);
        $time = trim($time, ':');
        
        // Add :00 if only hour is provided
        if (!str_contains($time, ':')) {
            if (strlen($time) <= 2) {
                $time .= ':00';
            } elseif (strlen($time) == 4) {
                // Handle "1430" format
                $time = substr($time, 0, 2) . ':' . substr($time, 2);
            }
        }
        
        try {
            return Carbon::createFromFormat('H:i', $time)->format('H:i');
        } catch (\Exception $e) {
            throw new MCPException("Ungültiges Zeitformat: {$time}", MCPException::INVALID_PARAMS);
        }
    }
    
    /**
     * Normalize phone number
     */
    protected function normalizePhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }
        
        // Remove all non-numeric characters
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Handle German numbers
        if (str_starts_with($phone, '0')) {
            $phone = '+49' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '+')) {
            $phone = '+49' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Generate appointment summary
     */
    protected function generateSummary(array $data): string
    {
        $date = Carbon::parse($data['requested_date'])->format('d.m.Y');
        $time = $data['requested_time'];
        
        return sprintf(
            '%s am %s um %s Uhr für %s',
            $data['service_name'],
            $date,
            $time,
            $data['customer_name']
        );
    }
    
    /**
     * Check availability for a specific time slot
     */
    protected function checkAvailability(string $branchId, ?int $serviceId, Carbon $dateTime): bool
    {
        // TODO: Implement actual availability check
        // For now, return true
        return true;
    }
    
    /**
     * Get available slots for a date
     */
    protected function getAvailableSlots(string $branchId, string $date, ?string $serviceName): array
    {
        try {
            // Parse the date using our German date parser
            $parsedDate = $this->parseDate($date);
            $dateObj = Carbon::parse($parsedDate);
            
            // Get staff members for this branch with the service
            $staffMembers = \DB::table('staff')
                ->where('branch_id', $branchId)
                ->where('deleted_at', null)
                ->pluck('id');
            
            if ($staffMembers->isEmpty()) {
                Log::warning('No staff members found for branch', ['branch_id' => $branchId]);
                return [];
            }
            
            // Get working hours for the requested day
            $dayOfWeek = $dateObj->dayOfWeek;
            // Sunday is 0 in Carbon but 7 in our database
            if ($dayOfWeek === 0) {
                $dayOfWeek = 7;
            }
            
            $workingHours = \DB::table('working_hours')
                ->whereIn('staff_id', $staffMembers)
                ->where('day_of_week', $dayOfWeek)
                ->get();
            
            if ($workingHours->isEmpty()) {
                Log::warning('No working hours for this day', [
                    'branch_id' => $branchId,
                    'day_of_week' => $dayOfWeek,
                    'date' => $date
                ]);
                return [];
            }
            
            $slots = [];
            
            foreach ($workingHours as $wh) {
                // Get existing appointments for this staff member on this date
                $existingAppointments = \DB::table('appointments')
                    ->where('staff_id', $wh->staff_id)
                    ->where('branch_id', $branchId)
                    ->whereDate('starts_at', $dateObj->format('Y-m-d'))
                    ->whereIn('status', ['scheduled', 'confirmed'])
                    ->orderBy('starts_at')
                    ->get(['starts_at', 'ends_at']);
                
                // Generate time slots
                $start = Carbon::parse($date . ' ' . $wh->start);
                $end = Carbon::parse($date . ' ' . $wh->end);
                $slotDuration = 30; // 30-minute slots
                
                // If it's today, start from next available slot
                if ($dateObj->isToday() && $start->isPast()) {
                    $now = Carbon::now();
                    $nextSlot = $now->copy()->addMinutes(30 - ($now->minute % 30))->second(0);
                    if ($nextSlot->isAfter($start)) {
                        $start = $nextSlot;
                    }
                }
                
                while ($start->copy()->addMinutes($slotDuration)->lte($end)) {
                    $slotEnd = $start->copy()->addMinutes($slotDuration);
                    
                    // Check if this slot conflicts with existing appointments
                    $isAvailable = true;
                    foreach ($existingAppointments as $apt) {
                        $aptStart = Carbon::parse($apt->starts_at);
                        $aptEnd = Carbon::parse($apt->ends_at);
                        
                        // Check for overlap
                        if (!($slotEnd->lte($aptStart) || $start->gte($aptEnd))) {
                            $isAvailable = false;
                            break;
                        }
                    }
                    
                    if ($isAvailable) {
                        $slots[] = $start->format('Y-m-d H:i:s');
                    }
                    
                    $start->addMinutes($slotDuration);
                }
            }
            
            // Remove duplicates and sort
            $slots = array_unique($slots);
            sort($slots);
            
            return array_values($slots);
            
        } catch (\Exception $e) {
            Log::error('Failed to get available slots', [
                'branch_id' => $branchId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to some default slots if there's an error
            $fallbackSlots = [];
            $start = Carbon::parse($date)->setTime(9, 0);
            $end = Carbon::parse($date)->setTime(17, 0);
            
            while ($start < $end) {
                $fallbackSlots[] = $start->format('Y-m-d H:i:s');
                $start->addMinutes(30);
            }
            
            return $fallbackSlots;
        }
    }
    
    /**
     * Find next available date
     */
    protected function findNextAvailableDate(string $branchId, string $startDate, ?string $serviceName): ?string
    {
        // TODO: Implement actual search
        // For now, return next business day
        $date = Carbon::parse($startDate);
        
        do {
            $date->addDay();
        } while ($date->isWeekend());
        
        return $date->format('d.m.Y');
    }
    
    /**
     * Handle generic function call from Retell.ai
     * This is the main entry point for all custom functions
     */
    public function handleFunctionCall(string $functionName, array $parameters, ?string $callId = null): array
    {
        Log::info('MCP: Handling Retell custom function', [
            'function' => $functionName,
            'call_id' => $callId,
            'has_parameters' => !empty($parameters)
        ]);

        // Add context information if available
        if ($callId && !isset($parameters['call_id'])) {
            $parameters['call_id'] = $callId;
        }

        // Resolve company/branch context from phone numbers if available
        if (isset($parameters['to_number']) || isset($parameters['caller_number'])) {
            $context = $this->resolveContext(
                $parameters['caller_number'] ?? null,
                $parameters['to_number'] ?? null
            );

            if ($context['company_id'] && !isset($parameters['company_id'])) {
                $parameters['company_id'] = $context['company_id'];
            }
            if ($context['branch_id'] && !isset($parameters['branch_id'])) {
                $parameters['branch_id'] = $context['branch_id'];
            }
        }

        // Delegate to the custom function handler
        return $this->customFunctionHandler->handleFunctionCall($functionName, $parameters, $callId);
    }

    /**
     * Health check
     */
    public function health(): array
    {
        return [
            'status' => 'healthy',
            'service' => 'RetellCustomFunctionMCPServer',
            'handler' => 'CustomFunctionHandler',
            'supported_functions' => [
                'extract_appointment_details',
                'identify_customer',
                'determine_service',
                'book_appointment',
                'book_group_appointment',
                'check_availability',
                'get_business_hours',
                'list_services',
                'cancel_appointment',
                'reschedule_appointment',
                // Legacy functions still supported
                'collect_appointment',
                'change_appointment'
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}