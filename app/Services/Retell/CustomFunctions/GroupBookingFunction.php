<?php

namespace App\Services\Retell\CustomFunctions;

use App\Services\Booking\GroupBookingService;
use App\Services\Customer\EnhancedCustomerService;
use App\Models\Branch;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Retell.ai custom function for creating group bookings
 */
class GroupBookingFunction
{
    protected GroupBookingService $groupService;
    protected EnhancedCustomerService $customerService;

    public function __construct(
        GroupBookingService $groupService,
        EnhancedCustomerService $customerService
    ) {
        $this->groupService = $groupService;
        $this->customerService = $customerService;
    }

    /**
     * Function definition for Retell.ai
     */
    public static function getDefinition(): array
    {
        return [
            'name' => 'book_group_appointment',
            'description' => 'Create a group appointment for multiple people at the same time',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'participants' => [
                        'type' => 'array',
                        'description' => 'List of participants',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'phone' => ['type' => 'string'],
                                'email' => ['type' => 'string']
                            ],
                            'required' => ['name', 'phone']
                        ]
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
                    'appointment_date' => [
                        'type' => 'string',
                        'description' => 'Date (YYYY-MM-DD)'
                    ],
                    'appointment_time' => [
                        'type' => 'string',
                        'description' => 'Time (HH:MM)'
                    ],
                    'duration_minutes' => [
                        'type' => 'integer',
                        'description' => 'Duration in minutes'
                    ],
                    'notes' => [
                        'type' => 'string',
                        'description' => 'Additional notes'
                    ]
                ],
                'required' => ['participants', 'service_id', 'branch_id', 
                             'appointment_date', 'appointment_time']
            ]
        ];
    }

    /**
     * Execute the function
     */
    public function execute(array $parameters): array
    {
        try {
            // Validate minimum participants
            if (count($parameters['participants']) < 2) {
                return [
                    'success' => false,
                    'error' => 'Mindestens 2 Teilnehmer erforderlich für eine Gruppenbuchung'
                ];
            }

            // Get service and branch details
            $service = Service::find($parameters['service_id']);
            $branch = Branch::find($parameters['branch_id']);

            if (!$service || !$branch) {
                return [
                    'success' => false,
                    'error' => 'Service oder Filiale nicht gefunden'
                ];
            }

            // Check if service allows group bookings
            if ($service->max_participants && count($parameters['participants']) > $service->max_participants) {
                return [
                    'success' => false,
                    'error' => "Dieser Service erlaubt maximal {$service->max_participants} Teilnehmer"
                ];
            }

            // Prepare customer data
            $customers = [];
            foreach ($parameters['participants'] as $participant) {
                // Check if customer exists
                $identification = $this->customerService->identifyByPhone($participant['phone']);
                
                if ($identification['found']) {
                    $customers[] = ['id' => $identification['customer']->id];
                } else {
                    $customers[] = [
                        'name' => $participant['name'],
                        'phone' => $participant['phone'],
                        'email' => $participant['email'] ?? null
                    ];
                }
            }

            // Create group booking
            $starts_at = Carbon::parse($parameters['appointment_date'] . ' ' . $parameters['appointment_time']);
            
            $result = $this->groupService->createGroupBooking([
                'customers' => $customers,
                'company_id' => $branch->company_id,
                'branch_id' => $parameters['branch_id'],
                'staff_id' => $parameters['staff_id'] ?? null,
                'service_id' => $parameters['service_id'],
                'starts_at' => $starts_at->toDateTimeString(),
                'duration_minutes' => $parameters['duration_minutes'] ?? $service->duration,
                'notes' => $parameters['notes'] ?? null,
                'source' => 'phone_ai',
                'auto_confirm' => true
            ]);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => 'Gruppenbuchung konnte nicht erstellt werden',
                    'details' => $result['errors'] ?? []
                ];
            }

            // Generate confirmation
            $confirmationMessage = $this->generateConfirmationMessage(
                $result['appointments'],
                $result['pricing'],
                $service
            );

            return [
                'success' => true,
                'group_booking_id' => $result['group_booking_id'],
                'participants_count' => count($result['appointments']),
                'appointment_details' => [
                    'date' => $starts_at->format('d.m.Y'),
                    'time' => $starts_at->format('H:i'),
                    'duration' => $parameters['duration_minutes'] ?? $service->duration,
                    'service' => $service->name
                ],
                'pricing' => $result['pricing'],
                'confirmation_message' => $confirmationMessage,
                'appointment_ids' => collect($result['appointments'])->pluck('id')->toArray()
            ];

        } catch (\Exception $e) {
            Log::error('GroupBookingFunction error', [
                'parameters' => $parameters,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Fehler bei der Gruppenbuchung',
                'message' => 'Entschuldigung, ich konnte die Gruppenbuchung nicht erstellen.'
            ];
        }
    }

    /**
     * Generate confirmation message
     */
    protected function generateConfirmationMessage($appointments, $pricing, $service): string
    {
        $count = count($appointments);
        $date = $appointments[0]->starts_at->format('d.m.Y');
        $time = $appointments[0]->starts_at->format('H:i');

        $message = "Perfekt! Ich habe eine Gruppenbuchung für {$count} Personen erstellt. ";
        $message .= "Der Termin ist am {$date} um {$time} Uhr für {$service->name}. ";

        if ($pricing['discount_percentage'] > 0) {
            $message .= "Sie erhalten einen Gruppenrabatt von {$pricing['discount_percentage']}%. ";
        }

        $message .= "Der Gesamtpreis beträgt " . number_format($pricing['total_after_discount'], 2, ',', '.') . " Euro ";
        $message .= "(" . number_format($pricing['price_per_person'], 2, ',', '.') . " Euro pro Person). ";
        
        $message .= "Alle Teilnehmer erhalten eine Bestätigung per E-Mail.";

        return $message;
    }
}