<?php

namespace App\Services\Retell\CustomFunctions;

use App\Services\Customer\EnhancedCustomerService;
use App\Services\PhoneNumberResolver;
use Illuminate\Support\Facades\Log;

/**
 * Retell.ai custom function for retrieving customer history and preferences
 * This function is called by the AI agent to personalize interactions
 */
class CustomerHistoryFunction
{
    protected EnhancedCustomerService $customerService;
    protected PhoneNumberResolver $phoneResolver;

    public function __construct(
        EnhancedCustomerService $customerService,
        PhoneNumberResolver $phoneResolver
    ) {
        $this->customerService = $customerService;
        $this->phoneResolver = $phoneResolver;
    }

    /**
     * Function definition for Retell.ai
     */
    public static function getDefinition(): array
    {
        return [
            'name' => 'check_customer_history',
            'description' => 'Retrieve customer history, preferences, and personalized information based on phone number',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'phone_number' => [
                        'type' => 'string',
                        'description' => 'The customer\'s phone number'
                    ],
                    'include_preferences' => [
                        'type' => 'boolean',
                        'description' => 'Whether to include detailed preferences',
                        'default' => true
                    ],
                    'include_appointments' => [
                        'type' => 'boolean',
                        'description' => 'Whether to include appointment history',
                        'default' => true
                    ]
                ],
                'required' => ['phone_number']
            ]
        ];
    }

    /**
     * Execute the function
     */
    public function execute(array $parameters): array
    {
        try {
            $phoneNumber = $parameters['phone_number'];
            $includePreferences = $parameters['include_preferences'] ?? true;
            $includeAppointments = $parameters['include_appointments'] ?? true;

            // Identify customer by phone
            $identification = $this->customerService->identifyByPhone($phoneNumber);
            
            if (!$identification['found']) {
                return [
                    'success' => false,
                    'is_new_customer' => true,
                    'message' => 'Kein Kunde mit dieser Telefonnummer gefunden. Dies scheint ein neuer Kunde zu sein.',
                    'greeting_suggestion' => 'Hallo! Willkommen bei uns. Ich sehe, dass Sie zum ersten Mal bei uns anrufen.'
                ];
            }

            $customer = $identification['customer'];
            
            // Get comprehensive customer context
            $context = $this->customerService->getCustomerContext($customer->id);
            
            // Prepare response
            $response = [
                'success' => true,
                'is_new_customer' => false,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'is_vip' => $context['loyalty_info']['is_vip'],
                    'loyalty_tier' => $context['loyalty_info']['tier'],
                    'loyalty_points' => $context['loyalty_info']['points'],
                    'preferred_language' => $customer->preferred_language ?? 'de',
                    'total_appointments' => $context['appointment_stats']['total_appointments'],
                    'reliability_score' => $context['appointment_stats']['reliability_score'],
                    'no_show_count' => $customer->no_show_count
                ],
                'personalized_greeting' => $context['personalized_greeting']
            ];

            // Add preferences if requested
            if ($includePreferences && $context['preferences']) {
                $prefs = $context['preferences'];
                $response['preferences'] = [
                    'preferred_days' => $this->formatDays($prefs->preferred_days_of_week ?? []),
                    'preferred_time_slots' => $this->formatTimeSlots($prefs->preferred_time_slots ?? []),
                    'preferred_services' => $prefs->preferredServices()->pluck('name')->toArray(),
                    'preferred_staff' => $prefs->preferredStaff()->pluck('name')->toArray(),
                    'preferred_branch' => $prefs->preferredBranch->name ?? null,
                    'special_requirements' => $prefs->special_instructions,
                    'communication_preferences' => [
                        'reminder_24h' => $prefs->reminder_24h,
                        'reminder_sms' => $prefs->reminder_sms,
                        'preferred_contact' => $customer->preferred_contact_method ?? 'phone'
                    ]
                ];
            }

            // Add appointment history if requested
            if ($includeAppointments) {
                $stats = $context['appointment_stats'];
                $response['appointment_history'] = [
                    'last_appointment' => $stats['last_appointment'] ? [
                        'date' => $stats['last_appointment']->starts_at->format('d.m.Y'),
                        'service' => $stats['last_appointment']->service->name ?? 'Unbekannt',
                        'staff' => $stats['last_appointment']->staff->name ?? 'Unbekannt'
                    ] : null,
                    'upcoming_appointments' => $stats['upcoming_appointments'],
                    'favorite_service' => $stats['favorite_service'] ? 
                        \App\Models\Service::find($stats['favorite_service'])->name : null,
                    'average_booking_frequency_days' => $stats['average_booking_frequency']
                ];
            }

            // Add behavioral insights
            $insights = $context['behavior_insights'];
            if (!empty($insights['risk_indicators'])) {
                $response['risk_indicators'] = [
                    'no_show_risk' => $insights['risk_indicators']['no_show_risk'],
                    'churn_risk' => $insights['risk_indicators']['churn_risk']
                ];
            }

            // Add conversation hints
            $response['conversation_hints'] = $this->generateConversationHints($context);

            return $response;

        } catch (\Exception $e) {
            Log::error('CustomerHistoryFunction error', [
                'parameters' => $parameters,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Fehler beim Abrufen der Kundenhistorie',
                'message' => 'Entschuldigung, ich konnte Ihre Daten momentan nicht abrufen.'
            ];
        }
    }

    /**
     * Generate conversation hints based on customer context
     */
    protected function generateConversationHints(array $context): array
    {
        $hints = [];

        // VIP treatment
        if ($context['loyalty_info']['is_vip']) {
            $hints[] = [
                'type' => 'vip_treatment',
                'message' => 'Behandeln Sie den Kunden mit höchster Priorität. VIP-Status aktiv.'
            ];
        }

        // No-show risk
        if ($context['behavior_insights']['risk_indicators']['no_show_risk'] > 0.5) {
            $hints[] = [
                'type' => 'no_show_risk',
                'message' => 'Betonen Sie die Wichtigkeit der Termineinhaltung.'
            ];
        }

        // Upcoming appointment
        if ($context['appointment_stats']['upcoming_appointments'] > 0) {
            $hints[] = [
                'type' => 'existing_appointment',
                'message' => 'Kunde hat bereits einen bevorstehenden Termin. Fragen Sie, ob es um diesen Termin geht.'
            ];
        }

        // Price sensitivity
        if ($context['behavior_insights']['preferences']['price_sensitivity'] === 'high') {
            $hints[] = [
                'type' => 'price_sensitive',
                'message' => 'Kunde ist preissensitiv. Erwähnen Sie eventuelle Angebote oder Pakete.'
            ];
        }

        // Long-time customer
        if ($context['appointment_stats']['total_appointments'] > 20) {
            $hints[] = [
                'type' => 'loyal_customer',
                'message' => 'Langjähriger Kunde. Bedanken Sie sich für die Treue.'
            ];
        }

        return $hints;
    }

    /**
     * Format day numbers to German day names
     */
    protected function formatDays(array $dayNumbers): array
    {
        $dayNames = [
            0 => 'Sonntag',
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
            6 => 'Samstag'
        ];

        return array_map(function($day) use ($dayNames) {
            return $dayNames[$day] ?? $day;
        }, $dayNumbers);
    }

    /**
     * Format time slots to German
     */
    protected function formatTimeSlots(array $slots): array
    {
        $slotNames = [
            'morning' => 'Vormittag',
            'afternoon' => 'Nachmittag',
            'evening' => 'Abend'
        ];

        return array_map(function($slot) use ($slotNames) {
            return $slotNames[$slot] ?? $slot;
        }, $slots);
    }
}