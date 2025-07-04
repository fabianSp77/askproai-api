<?php

namespace App\Services\Retell\CustomFunctions;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Appointment;
use App\Services\AppointmentBookingService;
use App\Services\CalcomV2Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Retell.ai Custom Function für Standard-Terminbuchungen
 * Diese Function wird vom AI-Agent aufgerufen, um Termine zu buchen
 */
class AppointmentBookingFunction
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
     * Function Definition für Retell.ai
     */
    public static function getDefinition(): array
    {
        return [
            'name' => 'book_appointment',
            'description' => 'Bucht einen Termin für einen Kunden',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'customer_name' => [
                        'type' => 'string',
                        'description' => 'Vollständiger Name des Kunden'
                    ],
                    'customer_phone' => [
                        'type' => 'string',
                        'description' => 'Telefonnummer des Kunden im Format +49...'
                    ],
                    'customer_email' => [
                        'type' => 'string',
                        'description' => 'E-Mail Adresse des Kunden (optional)'
                    ],
                    'service_name' => [
                        'type' => 'string',
                        'description' => 'Name der gewünschten Dienstleistung'
                    ],
                    'service_id' => [
                        'type' => 'integer',
                        'description' => 'ID der Dienstleistung (wenn bekannt)'
                    ],
                    'staff_name' => [
                        'type' => 'string',
                        'description' => 'Name des gewünschten Mitarbeiters (optional)'
                    ],
                    'staff_id' => [
                        'type' => 'integer',
                        'description' => 'ID des Mitarbeiters (wenn bekannt)'
                    ],
                    'appointment_date' => [
                        'type' => 'string',
                        'description' => 'Datum des Termins (YYYY-MM-DD)'
                    ],
                    'appointment_time' => [
                        'type' => 'string',
                        'description' => 'Uhrzeit des Termins (HH:MM)'
                    ],
                    'notes' => [
                        'type' => 'string',
                        'description' => 'Zusätzliche Notizen oder Wünsche'
                    ],
                    'branch_id' => [
                        'type' => 'string',
                        'description' => 'ID der Filiale (UUID)'
                    ]
                ],
                'required' => ['customer_name', 'customer_phone', 'appointment_date', 'appointment_time']
            ]
        ];
    }

    /**
     * Führt die Terminbuchung aus
     */
    public function execute(array $parameters, ?string $callId = null): array
    {
        Log::info('AppointmentBookingFunction::execute', [
            'parameters' => $parameters,
            'call_id' => $callId
        ]);

        DB::beginTransaction();
        
        try {
            // 1. Branch validieren
            $branchId = $parameters['branch_id'] ?? null;
            if (!$branchId) {
                throw new \Exception('Filiale nicht angegeben');
            }
            
            $branch = Branch::find($branchId);
            if (!$branch) {
                throw new \Exception('Filiale nicht gefunden');
            }

            // 2. Service finden oder erraten
            $service = $this->resolveService($parameters, $branch);
            if (!$service) {
                throw new \Exception('Service konnte nicht gefunden werden');
            }

            // 3. Customer finden oder erstellen
            $customer = $this->findOrCreateCustomer($parameters, $branch->company_id);

            // 4. Staff finden (optional)
            $staff = $this->resolveStaff($parameters, $branch);

            // 5. Datum und Zeit vorbereiten
            $startsAt = Carbon::parse($parameters['appointment_date'] . ' ' . $parameters['appointment_time']);
            
            // Zeitzone korrigieren (Berlin Zeit)
            if ($startsAt->timezone !== 'Europe/Berlin') {
                $startsAt->setTimezone('Europe/Berlin');
            }

            // 6. Verfügbarkeit prüfen
            $isAvailable = $this->bookingService->checkAvailability(
                $branch->id,
                $service->id,
                $startsAt,
                $staff?->id
            );

            if (!$isAvailable) {
                return [
                    'success' => false,
                    'error' => 'Der gewünschte Termin ist leider nicht verfügbar',
                    'message' => 'Bitte wählen Sie einen anderen Termin'
                ];
            }

            // 7. Termin erstellen
            $appointment = Appointment::create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'staff_id' => $staff?->id,
                'service_id' => $service->id,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMinutes($service->duration ?? 30),
                'status' => 'scheduled',
                'source' => 'phone_ai',
                'notes' => $parameters['notes'] ?? null,
                'price' => $service->price ?? 0,
                'call_id' => $callId,
                'confirmation_sent' => false,
            ]);

            // 8. Cal.com Integration (wenn aktiviert)
            $calcomBooking = null;
            if ($branch->calcom_integration_enabled && $staff?->calcom_user_id) {
                try {
                    $calcomBooking = $this->calcomService->createBooking([
                        'eventTypeId' => $service->calcom_event_type_id,
                        'start' => $startsAt->toIso8601String(),
                        'attendee' => [
                            'name' => $customer->name,
                            'email' => $customer->email ?? 'noemail@askproai.de',
                            'phone' => $customer->phone,
                        ],
                        'userId' => $staff->calcom_user_id,
                        'metadata' => [
                            'appointment_id' => $appointment->id,
                            'source' => 'phone_ai',
                            'call_id' => $callId,
                        ]
                    ]);

                    if ($calcomBooking && isset($calcomBooking['id'])) {
                        $appointment->update([
                            'calcom_booking_id' => $calcomBooking['id'],
                            'calcom_booking_uid' => $calcomBooking['uid'] ?? null,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Cal.com booking failed', [
                        'error' => $e->getMessage(),
                        'appointment_id' => $appointment->id
                    ]);
                    // Fahre trotzdem fort - Cal.com ist optional
                }
            }

            DB::commit();

            // 9. Bestätigungsnachricht generieren
            $confirmationMessage = $this->generateConfirmationMessage($appointment, $service, $branch);

            // 10. Erfolgreiche Antwort
            return [
                'success' => true,
                'appointment_id' => $appointment->id,
                'appointment_details' => [
                    'date' => $startsAt->format('d.m.Y'),
                    'time' => $startsAt->format('H:i'),
                    'service' => $service->name,
                    'staff' => $staff?->name ?? 'Nächster verfügbarer Mitarbeiter',
                    'duration' => $service->duration . ' Minuten',
                    'price' => number_format($service->price ?? 0, 2, ',', '.') . ' €',
                ],
                'customer' => [
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ],
                'confirmation_message' => $confirmationMessage,
                'calcom_booking_id' => $calcomBooking['id'] ?? null,
            ];

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('AppointmentBookingFunction error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'parameters' => $parameters,
                'call_id' => $callId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Es tut mir leid, ich konnte den Termin nicht buchen. ' . $e->getMessage()
            ];
        }
    }

    /**
     * Service anhand von Name oder ID finden
     */
    protected function resolveService(array $parameters, Branch $branch): ?Service
    {
        // 1. Versuche über service_id
        if (!empty($parameters['service_id'])) {
            $service = Service::where('id', $parameters['service_id'])
                ->where('company_id', $branch->company_id)
                ->where('is_active', true)
                ->first();
            
            if ($service) {
                return $service;
            }
        }

        // 2. Versuche über service_name
        if (!empty($parameters['service_name'])) {
            // Exakte Übereinstimmung
            $service = Service::where('company_id', $branch->company_id)
                ->where('is_active', true)
                ->where('name', $parameters['service_name'])
                ->first();
            
            if ($service) {
                return $service;
            }

            // Fuzzy Search
            $service = Service::where('company_id', $branch->company_id)
                ->where('is_active', true)
                ->where('name', 'LIKE', '%' . $parameters['service_name'] . '%')
                ->first();
            
            if ($service) {
                return $service;
            }
        }

        // 3. Fallback: Ersten aktiven Service nehmen
        return Service::where('company_id', $branch->company_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Mitarbeiter anhand von Name oder ID finden
     */
    protected function resolveStaff(array $parameters, Branch $branch): ?Staff
    {
        // Staff ist optional
        if (empty($parameters['staff_id']) && empty($parameters['staff_name'])) {
            return null;
        }

        // 1. Versuche über staff_id
        if (!empty($parameters['staff_id'])) {
            $staff = Staff::where('id', $parameters['staff_id'])
                ->where('branch_id', $branch->id)
                ->where('is_active', true)
                ->first();
            
            if ($staff) {
                return $staff;
            }
        }

        // 2. Versuche über staff_name
        if (!empty($parameters['staff_name'])) {
            // Exakte Übereinstimmung
            $staff = Staff::where('branch_id', $branch->id)
                ->where('is_active', true)
                ->where('name', $parameters['staff_name'])
                ->first();
            
            if ($staff) {
                return $staff;
            }

            // Fuzzy Search
            $staff = Staff::where('branch_id', $branch->id)
                ->where('is_active', true)
                ->where('name', 'LIKE', '%' . $parameters['staff_name'] . '%')
                ->first();
            
            if ($staff) {
                return $staff;
            }
        }

        return null;
    }

    /**
     * Kunde finden oder neu erstellen
     */
    protected function findOrCreateCustomer(array $parameters, string $companyId): Customer
    {
        // Telefonnummer normalisieren
        $phone = $this->normalizePhoneNumber($parameters['customer_phone']);

        // Existierenden Kunden suchen
        $customer = Customer::where('company_id', $companyId)
            ->where('phone', $phone)
            ->first();

        if ($customer) {
            // Aktualisiere fehlende Daten
            $updates = [];
            
            if (empty($customer->email) && !empty($parameters['customer_email'])) {
                $updates['email'] = $parameters['customer_email'];
            }
            
            if (!empty($updates)) {
                $customer->update($updates);
            }
            
            return $customer;
        }

        // Neuen Kunden erstellen
        return Customer::create([
            'company_id' => $companyId,
            'name' => $parameters['customer_name'],
            'phone' => $phone,
            'email' => $parameters['customer_email'] ?? null,
            'source' => 'phone_ai',
            'tags' => ['phone_booking'],
        ]);
    }

    /**
     * Telefonnummer normalisieren
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

    /**
     * Bestätigungsnachricht generieren
     */
    protected function generateConfirmationMessage(Appointment $appointment, Service $service, Branch $branch): string
    {
        $date = $appointment->starts_at->format('d.m.Y');
        $time = $appointment->starts_at->format('H:i');
        $staffName = $appointment->staff?->name ?? 'dem nächsten verfügbaren Mitarbeiter';
        
        $message = "Perfekt! Ich habe Ihren Termin gebucht:\n\n";
        $message .= "📅 Datum: {$date}\n";
        $message .= "🕐 Uhrzeit: {$time} Uhr\n";
        $message .= "💈 Service: {$service->name}\n";
        $message .= "👤 Mit: {$staffName}\n";
        $message .= "⏱️ Dauer: {$service->duration} Minuten\n";
        
        if ($service->price > 0) {
            $price = number_format($service->price, 2, ',', '.');
            $message .= "💰 Preis: {$price} €\n";
        }
        
        $message .= "\n📍 Adresse: {$branch->name}, {$branch->address}, {$branch->city}\n";
        
        if ($branch->phone_number) {
            $message .= "📞 Bei Fragen: {$branch->phone_number}\n";
        }
        
        $message .= "\nSie erhalten eine Bestätigung per E-Mail. ";
        $message .= "Wir freuen uns auf Ihren Besuch!";
        
        return $message;
    }
}