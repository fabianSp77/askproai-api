<?php

namespace App\Services\Retell;

use App\Services\Retell\CustomFunctions\AppointmentBookingFunction;
use App\Services\Retell\CustomFunctions\ExtractAppointmentDetailsFunction;
use App\Services\Retell\CustomFunctions\IdentifyCustomerFunction;
use App\Services\Retell\CustomFunctions\DetermineServiceFunction;
use App\Services\Retell\CustomFunctions\GroupBookingFunction;
use App\Services\AppointmentBookingService;
use App\Services\CalcomV2Service;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Verarbeitet Custom Function Calls von Retell.ai
 */
class CustomFunctionHandler
{
    protected AppointmentBookingService $bookingService;
    protected CalcomV2Service $calcomService;

    public function __construct(
        AppointmentBookingService $bookingService,
        CalcomV2Service $calcomService
    ) {
        $this->bookingService = $bookingService;
        $this->calcomService = $calcomService;
    }

    /**
     * Verarbeitet einen Function Call von Retell.ai
     */
    public function handleFunctionCall(string $functionName, array $parameters, ?string $callId = null): array
    {
        Log::info('CustomFunctionHandler::handleFunctionCall', [
            'function' => $functionName,
            'parameters' => $parameters,
            'call_id' => $callId
        ]);

        try {
            switch ($functionName) {
                case 'extract_appointment_details':
                    $function = new ExtractAppointmentDetailsFunction();
                    return $function->execute($parameters);

                case 'identify_customer':
                    $function = new IdentifyCustomerFunction();
                    return $function->execute($parameters);

                case 'determine_service':
                    $function = new DetermineServiceFunction();
                    return $function->execute($parameters);

                case 'book_appointment':
                    $function = new AppointmentBookingFunction($this->bookingService, $this->calcomService);
                    return $function->execute($parameters, $callId);

                case 'book_group_appointment':
                    $function = new GroupBookingFunction(
                        app(\App\Services\Booking\GroupBookingService::class),
                        app(\App\Services\Customer\EnhancedCustomerService::class)
                    );
                    return $function->execute($parameters);

                case 'check_availability':
                    return $this->checkAvailability($parameters);

                case 'get_business_hours':
                    return $this->getBusinessHours($parameters);

                case 'list_services':
                    return $this->listServices($parameters);

                case 'cancel_appointment':
                    return $this->cancelAppointment($parameters);

                case 'reschedule_appointment':
                    return $this->rescheduleAppointment($parameters);

                default:
                    Log::warning('Unknown custom function called', [
                        'function' => $functionName,
                        'parameters' => $parameters
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Unbekannte Function: ' . $functionName
                    ];
            }
        } catch (\Exception $e) {
            Log::error('CustomFunctionHandler error', [
                'function' => $functionName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Fehler bei der Ausführung',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Prüft Verfügbarkeit
     */
    protected function checkAvailability(array $parameters): array
    {
        $branchId = $parameters['branch_id'] ?? null;
        $date = $parameters['date'] ?? null;
        $serviceId = $parameters['service_id'] ?? null;
        $serviceName = $parameters['service_name'] ?? null;
        $staffId = $parameters['staff_id'] ?? null;
        $timePreference = $parameters['time_preference'] ?? null;

        if (!$branchId || !$date) {
            return [
                'success' => false,
                'error' => 'Branch ID und Datum sind erforderlich'
            ];
        }

        // Service ermitteln
        if (!$serviceId && $serviceName) {
            $service = Service::where('company_id', function($query) use ($branchId) {
                    $query->select('company_id')
                        ->from('branches')
                        ->where('id', $branchId)
                        ->limit(1);
                })
                ->where('name', 'LIKE', '%' . $serviceName . '%')
                ->where('is_active', true)
                ->first();
            
            if ($service) {
                $serviceId = $service->id;
            }
        }

        // Verfügbare Slots abrufen
        $availableSlots = $this->bookingService->getAvailableSlots(
            $branchId,
            $serviceId,
            Carbon::parse($date),
            $staffId
        );

        // Nach Präferenz filtern
        if ($timePreference) {
            $availableSlots = $this->filterSlotsByPreference($availableSlots, $timePreference);
        }

        // Formatiere Response
        $formattedSlots = [];
        foreach ($availableSlots as $slot) {
            $formattedSlots[] = [
                'time' => $slot['start']->format('H:i'),
                'duration' => $slot['duration'] ?? 30,
                'staff' => $slot['staff_name'] ?? 'Nächster verfügbarer Mitarbeiter',
                'staff_id' => $slot['staff_id'] ?? null
            ];
        }

        return [
            'success' => true,
            'date' => $date,
            'available_slots' => $formattedSlots,
            'total_slots' => count($formattedSlots),
            'message' => count($formattedSlots) > 0 
                ? "Es sind " . count($formattedSlots) . " Termine verfügbar"
                : "Leider sind keine Termine an diesem Tag verfügbar"
        ];
    }

    /**
     * Gibt Öffnungszeiten zurück
     */
    protected function getBusinessHours(array $parameters): array
    {
        $branchId = $parameters['branch_id'] ?? null;
        $date = $parameters['date'] ?? null;

        if (!$branchId) {
            return [
                'success' => false,
                'error' => 'Branch ID ist erforderlich'
            ];
        }

        $branch = Branch::find($branchId);
        if (!$branch) {
            return [
                'success' => false,
                'error' => 'Filiale nicht gefunden'
            ];
        }

        $businessHours = $branch->business_hours ?? $branch->company->business_hours ?? [];

        // Wenn spezifisches Datum angegeben, nur diesen Tag
        if ($date) {
            $dayOfWeek = Carbon::parse($date)->format('l');
            $dayKey = strtolower($dayOfWeek);
            
            if (isset($businessHours[$dayKey])) {
                $hours = $businessHours[$dayKey];
                return [
                    'success' => true,
                    'date' => $date,
                    'day' => $this->translateDay($dayOfWeek),
                    'is_open' => !($hours['closed'] ?? false),
                    'open_time' => $hours['open'] ?? null,
                    'close_time' => $hours['close'] ?? null,
                    'message' => $this->formatHoursMessage($hours, $this->translateDay($dayOfWeek))
                ];
            }
        }

        // Alle Öffnungszeiten formatieren
        $formatted = [];
        $message = "Unsere Öffnungszeiten sind: ";
        
        foreach ($businessHours as $day => $hours) {
            $dayName = $this->translateDay($day);
            $isOpen = !($hours['closed'] ?? false);
            
            $formatted[$day] = [
                'day' => $dayName,
                'is_open' => $isOpen,
                'open_time' => $isOpen ? ($hours['open'] ?? '09:00') : null,
                'close_time' => $isOpen ? ($hours['close'] ?? '18:00') : null
            ];
            
            if ($isOpen) {
                $message .= $dayName . " " . $hours['open'] . "-" . $hours['close'] . ", ";
            }
        }

        return [
            'success' => true,
            'business_hours' => $formatted,
            'message' => rtrim($message, ', ')
        ];
    }

    /**
     * Listet Services auf
     */
    protected function listServices(array $parameters): array
    {
        $branchId = $parameters['branch_id'] ?? null;
        $category = $parameters['category'] ?? null;
        $priceRange = $parameters['price_range'] ?? null;

        if (!$branchId) {
            return [
                'success' => false,
                'error' => 'Branch ID ist erforderlich'
            ];
        }

        $branch = Branch::find($branchId);
        if (!$branch) {
            return [
                'success' => false,
                'error' => 'Filiale nicht gefunden'
            ];
        }

        $query = Service::where('company_id', $branch->company_id)
            ->where('is_active', true);

        // Nach Kategorie filtern
        if ($category) {
            $query->whereHas('category', function($q) use ($category) {
                $q->where('name', 'LIKE', '%' . $category . '%')
                  ->orWhere('slug', 'LIKE', '%' . $category . '%');
            });
        }

        // Nach Preisbereich filtern
        if ($priceRange) {
            switch ($priceRange) {
                case 'low':
                    $query->where('price', '<=', 30);
                    break;
                case 'medium':
                    $query->whereBetween('price', [30, 80]);
                    break;
                case 'high':
                    $query->where('price', '>=', 80);
                    break;
            }
        }

        $services = $query->orderBy('sort_order')->get();

        $formattedServices = [];
        foreach ($services as $service) {
            $formattedServices[] = [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration' => $service->duration . ' Minuten',
                'price' => number_format($service->price ?? 0, 2, ',', '.') . ' €',
                'category' => $service->category?->name
            ];
        }

        $message = "Wir bieten " . count($formattedServices) . " Services an";
        if ($category) {
            $message .= " in der Kategorie " . $category;
        }

        return [
            'success' => true,
            'services' => $formattedServices,
            'total' => count($formattedServices),
            'message' => $message
        ];
    }

    /**
     * Storniert einen Termin
     */
    protected function cancelAppointment(array $parameters): array
    {
        $appointmentId = $parameters['appointment_id'] ?? null;
        $customerPhone = $parameters['customer_phone'] ?? null;
        $reason = $parameters['reason'] ?? 'Vom Kunden storniert';

        if (!$appointmentId || !$customerPhone) {
            return [
                'success' => false,
                'error' => 'Termin-ID und Telefonnummer sind erforderlich'
            ];
        }

        // Termin finden und verifizieren
        $appointment = \App\Models\Appointment::with(['customer', 'service', 'staff'])
            ->where('id', $appointmentId)
            ->first();

        if (!$appointment) {
            return [
                'success' => false,
                'error' => 'Termin nicht gefunden'
            ];
        }

        // Telefonnummer verifizieren
        $normalizedPhone = $this->normalizePhoneNumber($customerPhone);
        $customerPhone = $this->normalizePhoneNumber($appointment->customer->phone);
        
        if ($normalizedPhone !== $customerPhone) {
            return [
                'success' => false,
                'error' => 'Telefonnummer stimmt nicht überein'
            ];
        }

        // Prüfe ob bereits storniert
        if ($appointment->status === 'cancelled') {
            return [
                'success' => true,
                'message' => 'Der Termin wurde bereits storniert'
            ];
        }

        // Storniere den Termin
        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now()
        ]);

        // Cal.com Stornierung (falls vorhanden)
        if ($appointment->calcom_booking_id) {
            try {
                $this->calcomService->cancelBooking($appointment->calcom_booking_id, $reason);
            } catch (\Exception $e) {
                Log::error('Cal.com cancellation failed', [
                    'appointment_id' => $appointmentId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'success' => true,
            'message' => 'Ihr Termin am ' . $appointment->starts_at->format('d.m.Y') . 
                        ' um ' . $appointment->starts_at->format('H:i') . ' Uhr wurde erfolgreich storniert',
            'appointment' => [
                'id' => $appointment->id,
                'date' => $appointment->starts_at->format('d.m.Y'),
                'time' => $appointment->starts_at->format('H:i'),
                'service' => $appointment->service?->name,
                'staff' => $appointment->staff?->name
            ]
        ];
    }

    /**
     * Verschiebt einen Termin
     */
    protected function rescheduleAppointment(array $parameters): array
    {
        // Implementation ähnlich wie cancelAppointment
        // Würde den Termin auf neue Zeit verschieben
        // Hier nur Placeholder für Kürze
        
        return [
            'success' => false,
            'error' => 'Terminverschiebung noch nicht implementiert',
            'message' => 'Bitte rufen Sie uns direkt an, um Ihren Termin zu verschieben'
        ];
    }

    /**
     * Filtert Slots nach Zeitpräferenz
     */
    protected function filterSlotsByPreference(array $slots, string $preference): array
    {
        return array_filter($slots, function($slot) use ($preference) {
            $hour = $slot['start']->hour;
            
            switch ($preference) {
                case 'morning':
                    return $hour >= 8 && $hour < 12;
                case 'afternoon':
                    return $hour >= 12 && $hour < 17;
                case 'evening':
                    return $hour >= 17 && $hour < 21;
                default:
                    return true;
            }
        });
    }

    /**
     * Übersetzt Wochentage
     */
    protected function translateDay(string $day): string
    {
        $translations = [
            'monday' => 'Montag',
            'tuesday' => 'Dienstag',
            'wednesday' => 'Mittwoch',
            'thursday' => 'Donnerstag',
            'friday' => 'Freitag',
            'saturday' => 'Samstag',
            'sunday' => 'Sonntag',
            'Monday' => 'Montag',
            'Tuesday' => 'Dienstag',
            'Wednesday' => 'Mittwoch',
            'Thursday' => 'Donnerstag',
            'Friday' => 'Freitag',
            'Saturday' => 'Samstag',
            'Sunday' => 'Sonntag'
        ];
        
        return $translations[$day] ?? $day;
    }

    /**
     * Formatiert Öffnungszeiten-Nachricht
     */
    protected function formatHoursMessage(array $hours, string $dayName): string
    {
        if ($hours['closed'] ?? false) {
            return "Am {$dayName} haben wir geschlossen";
        }
        
        $open = $hours['open'] ?? '09:00';
        $close = $hours['close'] ?? '18:00';
        
        return "Am {$dayName} haben wir von {$open} bis {$close} Uhr geöffnet";
    }

    /**
     * Normalisiert Telefonnummer
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Entferne alle nicht-numerischen Zeichen
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Füge +49 hinzu wenn keine Ländervorwahl
        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '0')) {
                $phone = '+49' . substr($phone, 1);
            } else {
                $phone = '+49' . $phone;
            }
        }
        
        return $phone;
    }
}