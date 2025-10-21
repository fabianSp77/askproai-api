<?php

namespace App\Services\Retell;

use App\Models\Appointment;

/**
 * Query Appointment by Customer Name (For Anonymous/Hidden Number Calls)
 *
 * This is a new Retell Function Call designed specifically for handling
 * anonymous callers (hidden phone numbers 00000000) who cannot be looked up
 * via phone number using query_appointment().
 *
 * When a customer with a suppressed phone number needs to:
 * - Check their existing appointment
 * - Reschedule
 * - Cancel
 *
 * This function allows lookup by customer name instead of phone number.
 *
 * @author AI Gateway
 * @version 1.0
 */
class QueryAppointmentByNameFunction
{
    /**
     * Function name as registered in Retell AI
     */
    public const FUNCTION_NAME = 'query_appointment_by_name';

    /**
     * Query appointments by customer name (for anonymous callers)
     *
     * @param string $customer_name Customer name to search for
     * @param string|null $appointment_date Optional: filter by specific date (d.m.Y format)
     * @param string|null $call_id Retell call_id for tracking/logging
     * @return array Response with appointment details or error
     *
     * @example
     * // Response for existing appointment
     * {
     *   "success": true,
     *   "appointments": [
     *     {
     *       "id": 12345,
     *       "customer_name": "Maria Schmidt",
     *       "appointment_date": "2025-10-20",
     *       "appointment_date_display": "20.10.2025",
     *       "appointment_time": "14:00",
     *       "service_name": "Frisur",
     *       "status": "confirmed",
     *       "notes": "Highlight and cut"
     *     }
     *   ]
     * }
     */
    public function execute(array $params): array
    {
        // Extract parameters
        $customerName = $params['customer_name'] ?? null;
        $appointmentDate = $params['appointment_date'] ?? null; // Optional filter
        $callId = $params['call_id'] ?? null;

        // Validation
        if (empty($customerName)) {
            return [
                'success' => false,
                'error' => 'customer_name is required',
                'error_code' => 'INVALID_PARAMS'
            ];
        }

        try {
            // Query appointments by customer name
            $query = Appointment::where('customer_name', $customerName)
                ->where('status', '!=', 'cancelled')
                ->orderBy('appointment_date', 'desc')
                ->orderBy('appointment_time', 'desc');

            // Optional: filter by date
            if (!empty($appointmentDate)) {
                // Convert d.m.Y to Y-m-d if needed
                $date = $this->parseDate($appointmentDate);
                $query->whereDate('appointment_date', $date);
            }

            $appointments = $query->get();

            // No appointments found
            if ($appointments->isEmpty()) {
                return [
                    'success' => true,
                    'appointments' => [],
                    'message' => 'Keine Termine unter diesem Namen gefunden'
                ];
            }

            // Format appointments for response
            $formattedAppointments = $appointments->map(function ($appt) {
                return [
                    'id' => $appt->id,
                    'customer_name' => $appt->customer_name,
                    'appointment_date' => $appt->appointment_date->format('Y-m-d'),
                    'appointment_date_display' => $appt->appointment_date->format('d.m.Y'),
                    'appointment_time' => $appt->appointment_time,
                    'service_name' => $appt->service?->name ?? 'Unbekannt',
                    'status' => $appt->status,
                    'notes' => $appt->notes ?? ''
                ];
            })->toArray();

            return [
                'success' => true,
                'appointments' => $formattedAppointments,
                'count' => count($formattedAppointments)
            ];

        } catch (\Exception $e) {
            \Log::error('query_appointment_by_name error', [
                'customer_name' => $customerName,
                'appointment_date' => $appointmentDate,
                'call_id' => $callId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Fehler bei der Terminabfrage',
                'error_code' => 'QUERY_ERROR'
            ];
        }
    }

    /**
     * Parse date string (d.m.Y format) to Carbon
     */
    private function parseDate(string $dateString)
    {
        try {
            return \Carbon\Carbon::createFromFormat('d.m.Y', $dateString)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Retell Function Schema (JSON for Retell API)
     *
     * This is the schema definition that needs to be registered in Retell AI
     * when creating the function call.
     */
    public static function getRetellSchema(): array
    {
        return [
            'function_name' => self::FUNCTION_NAME,
            'description' => 'Query appointment by customer name - for anonymous callers with hidden numbers who cannot be looked up by phone',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'customer_name' => [
                        'type' => 'string',
                        'description' => 'Full name of the customer (e.g., "Maria Schmidt")',
                        'example' => 'Maria Schmidt'
                    ],
                    'appointment_date' => [
                        'type' => 'string',
                        'description' => 'Optional: filter by appointment date (format: d.m.Y, e.g., 20.10.2025)',
                        'example' => '20.10.2025'
                    ],
                    'call_id' => [
                        'type' => 'string',
                        'description' => 'Retell call_id for logging and tracking',
                        'example' => 'call_12345'
                    ]
                ],
                'required' => ['customer_name']
            ],
            'response' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'appointments' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'customer_name' => ['type' => 'string'],
                                'appointment_date' => ['type' => 'string'],
                                'appointment_date_display' => ['type' => 'string'],
                                'appointment_time' => ['type' => 'string'],
                                'service_name' => ['type' => 'string'],
                                'status' => ['type' => 'string'],
                                'notes' => ['type' => 'string']
                            ]
                        ]
                    ],
                    'message' => ['type' => 'string'],
                    'error' => ['type' => 'string'],
                    'error_code' => ['type' => 'string']
                ]
            ]
        ];
    }

    /**
     * Documentation for Retell Agent Prompt
     */
    public static function getPromptDocumentation(): string
    {
        return <<<'DOC'
## query_appointment_by_name() - ANONYMOUS CALLER LOOKUP

**When to use**: When customer has hidden phone number (00000000) and needs to query existing appointment

**Parameters**:
- customer_name (REQUIRED): Full customer name (e.g., "Maria Schmidt")
- appointment_date (OPTIONAL): Specific date to filter (d.m.Y format, e.g., "20.10.2025")
- call_id: Retell call ID for logging

**Usage in Agent**:
```
User: "Ich möchte meine Termin wissen"
Agent: "Gerne! Unter welchem Namen ist der Termin gebucht?"
User: "Maria Schmidt"
Agent: [call query_appointment_by_name(customer_name="Maria Schmidt")]
Backend: Returns 1 appointment
Agent: "Sie haben einen Termin am Montag, 20. Oktober um 14 Uhr"
```

**Return Values**:
- ✅ success=true, appointments=[...] - Appointments found
- ✅ success=true, appointments=[] - No appointments found
- ❌ success=false, error=... - Error occurred

**Error Codes**:
- INVALID_PARAMS: customer_name not provided
- QUERY_ERROR: Database error during lookup
DOC;
    }
}
