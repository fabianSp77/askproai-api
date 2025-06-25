<?php

namespace App\Services\Retell\CustomFunctions;

use App\Services\Booking\RecurringAppointmentService;
use App\Services\Customer\EnhancedCustomerService;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Retell.ai custom function for creating recurring appointments
 */
class RecurringBookingFunction
{
    protected RecurringAppointmentService $recurringService;
    protected EnhancedCustomerService $customerService;

    public function __construct(
        RecurringAppointmentService $recurringService,
        EnhancedCustomerService $customerService
    ) {
        $this->recurringService = $recurringService;
        $this->customerService = $customerService;
    }

    /**
     * Function definition for Retell.ai
     */
    public static function getDefinition(): array
    {
        return [
            'name' => 'book_recurring_appointments',
            'description' => 'Create a series of recurring appointments (daily, weekly, monthly)',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'customer_phone' => [
                        'type' => 'string',
                        'description' => 'Customer phone number'
                    ],
                    'customer_name' => [
                        'type' => 'string',
                        'description' => 'Customer name (if new customer)'
                    ],
                    'service_id' => [
                        'type' => 'integer',
                        'description' => 'ID of the service'
                    ],
                    'branch_id' => [
                        'type' => 'integer',
                        'description' => 'ID of the branch'
                    ],
                    'staff_id' => [
                        'type' => 'integer',
                        'description' => 'ID of the staff member (optional)'
                    ],
                    'recurrence_type' => [
                        'type' => 'string',
                        'enum' => ['daily', 'weekly', 'biweekly', 'monthly'],
                        'description' => 'How often to repeat'
                    ],
                    'days_of_week' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer'],
                        'description' => 'For weekly: which days (0=Sunday, 1=Monday, etc.)'
                    ],
                    'start_date' => [
                        'type' => 'string',
                        'description' => 'First appointment date (YYYY-MM-DD)'
                    ],
                    'appointment_time' => [
                        'type' => 'string',
                        'description' => 'Time for appointments (HH:MM)'
                    ],
                    'duration_minutes' => [
                        'type' => 'integer',
                        'description' => 'Duration in minutes'
                    ],
                    'end_date' => [
                        'type' => 'string',
                        'description' => 'Last date for series (YYYY-MM-DD)'
                    ],
                    'occurrences_count' => [
                        'type' => 'integer',
                        'description' => 'Number of appointments (alternative to end_date)'
                    ],
                    'notes' => [
                        'type' => 'string',
                        'description' => 'Additional notes'
                    ]
                ],
                'required' => ['customer_phone', 'service_id', 'branch_id', 'recurrence_type', 
                             'start_date', 'appointment_time', 'duration_minutes']
            ]
        ];
    }

    /**
     * Execute the function
     */
    public function execute(array $parameters): array
    {
        try {
            // Identify or create customer
            $customerResult = $this->identifyOrCreateCustomer($parameters);
            if (!$customerResult['success']) {
                return $customerResult;
            }

            $customer = $customerResult['customer'];
            $companyId = Branch::find($parameters['branch_id'])->company_id;

            // Get service details
            $service = Service::find($parameters['service_id']);
            if (!$service) {
                return [
                    'success' => false,
                    'error' => 'Service nicht gefunden'
                ];
            }

            // Prepare recurrence pattern
            $recurrencePattern = [];
            if ($parameters['recurrence_type'] === 'weekly' && isset($parameters['days_of_week'])) {
                $recurrencePattern['days_of_week'] = $parameters['days_of_week'];
            }

            // Create recurring series
            $result = $this->recurringService->createRecurringSeries([
                'customer_id' => $customer->id,
                'company_id' => $companyId,
                'branch_id' => $parameters['branch_id'],
                'staff_id' => $parameters['staff_id'] ?? null,
                'service_id' => $parameters['service_id'],
                'recurrence_type' => $parameters['recurrence_type'],
                'recurrence_pattern' => $recurrencePattern,
                'recurrence_interval' => 1,
                'start_date' => $parameters['start_date'],
                'end_date' => $parameters['end_date'] ?? null,
                'occurrences_count' => $parameters['occurrences_count'] ?? null,
                'appointment_time' => $parameters['appointment_time'],
                'duration_minutes' => $parameters['duration_minutes'] ?? $service->duration,
                'notes' => $parameters['notes'] ?? null,
                'price_per_session' => $service->price,
                'auto_confirm' => true,
                'source' => 'phone_ai'
            ]);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => 'Terminserie konnte nicht erstellt werden',
                    'details' => $result['errors'] ?? []
                ];
            }

            $series = $result['series'];
            $appointments = $result['appointments'];

            // Generate confirmation message
            $confirmationMessage = $this->generateConfirmationMessage($series, $appointments);

            return [
                'success' => true,
                'series_id' => $series->series_id,
                'total_appointments' => count($appointments),
                'first_appointment' => [
                    'date' => $appointments[0]->starts_at->format('d.m.Y'),
                    'time' => $appointments[0]->starts_at->format('H:i'),
                    'day_name' => $this->getGermanDayName($appointments[0]->starts_at->dayOfWeek)
                ],
                'last_appointment' => [
                    'date' => end($appointments)->starts_at->format('d.m.Y'),
                    'time' => end($appointments)->starts_at->format('H:i')
                ],
                'confirmation_message' => $confirmationMessage,
                'total_price' => $series->total_price,
                'appointment_ids' => collect($appointments)->pluck('id')->toArray()
            ];

        } catch (\Exception $e) {
            Log::error('RecurringBookingFunction error', [
                'parameters' => $parameters,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Fehler bei der Terminbuchung',
                'message' => 'Entschuldigung, ich konnte die Terminserie nicht erstellen. Bitte versuchen Sie es erneut.'
            ];
        }
    }

    /**
     * Identify existing customer or create new
     */
    protected function identifyOrCreateCustomer(array $parameters): array
    {
        $identification = $this->customerService->identifyByPhone($parameters['customer_phone']);
        
        if ($identification['found']) {
            return [
                'success' => true,
                'customer' => $identification['customer']
            ];
        }

        // Create new customer
        if (empty($parameters['customer_name'])) {
            return [
                'success' => false,
                'error' => 'Kundenname erforderlich für neue Kunden'
            ];
        }

        try {
            $branch = Branch::find($parameters['branch_id']);
            $customer = $this->customerService->createOrUpdate([
                'name' => $parameters['customer_name'],
                'phone' => $parameters['customer_phone'],
                'company_id' => $branch->company_id,
                'branch_id' => $parameters['branch_id'],
                'source' => 'phone_booking'
            ]);

            return [
                'success' => true,
                'customer' => $customer
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Kunde konnte nicht angelegt werden: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate confirmation message for the series
     */
    protected function generateConfirmationMessage($series, $appointments): string
    {
        $recurrenceDesc = $series->getRecurrenceDescription();
        $firstDate = $appointments[0]->starts_at->format('d.m.Y');
        $time = $appointments[0]->starts_at->format('H:i');
        $count = count($appointments);

        $message = "Perfekt! Ich habe {$count} Termine für Sie gebucht. ";
        $message .= "Die Termine finden {$recurrenceDesc} statt, ";
        $message .= "beginnend am {$firstDate} um {$time} Uhr. ";

        if ($series->total_price > 0) {
            $message .= "Der Gesamtpreis beträgt " . number_format($series->total_price, 2, ',', '.') . " Euro. ";
        }

        $message .= "Sie erhalten eine Bestätigung per E-Mail mit allen Terminen.";

        return $message;
    }

    /**
     * Get German day name
     */
    protected function getGermanDayName(int $dayOfWeek): string
    {
        $days = [
            0 => 'Sonntag',
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
            6 => 'Samstag'
        ];

        return $days[$dayOfWeek] ?? '';
    }
}