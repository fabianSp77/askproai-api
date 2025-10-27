<?php

namespace App\Services\Testing;

use App\Models\RetellFunctionTrace;
use App\Models\Service;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mock Function Executor
 *
 * Simulates Retell AI function executions WITHOUT making real API calls.
 * Returns realistic mock data based on historical function traces.
 *
 * Purpose: Test function calling logic without external dependencies.
 */
class MockFunctionExecutor
{
    private array $historicalData = [];

    public function __construct()
    {
        $this->loadHistoricalData();
    }

    /**
     * Execute a mock function call.
     *
     * @param string $functionName Function to execute
     * @param array $context Call context/variables
     * @return array Function result
     */
    public function execute(string $functionName, array $context = []): array
    {
        return match ($functionName) {
            'initialize_call' => $this->mockInitializeCall($context),
            'check_availability', 'check_availability_v2', 'check_availability_v17' => $this->mockCheckAvailability($context),
            'book_appointment', 'book_appointment_v17' => $this->mockBookAppointment($context),
            'check_customer' => $this->mockCheckCustomer($context),
            'collect_appointment_info' => $this->mockCollectAppointmentInfo($context),
            default => $this->mockGenericFunction($functionName, $context),
        };
    }

    /**
     * Mock initialize_call function.
     *
     * Returns customer data or anonymous greeting based on call context.
     *
     * @param array $context Call context
     * @return array Function result
     */
    private function mockInitializeCall(array $context): array
    {
        // Simulate finding customer by phone number
        $phoneNumber = $context['phone_number'] ?? null;

        if ($phoneNumber) {
            // Try to find existing customer
            $customer = Customer::where('phone', $phoneNumber)->first();

            if ($customer) {
                return [
                    'success' => true,
                    'customer' => [
                        'status' => 'found',
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                    ],
                    'variables' => [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'customer_status' => 'known',
                    ],
                ];
            }
        }

        // Anonymous caller (no customer found)
        return [
            'success' => true,
            'customer' => [
                'status' => 'anonymous',
                'id' => null,
                'message' => 'Neuer Anruf. Bitte fragen Sie nach dem Namen.',
            ],
            'variables' => [
                'customer_status' => 'anonymous',
            ],
        ];
    }

    /**
     * Mock check_availability function.
     *
     * Returns realistic availability data based on service and datetime.
     *
     * @param array $context Call context
     * @return array Function result
     */
    private function mockCheckAvailability(array $context): array
    {
        $service = $context['service'] ?? $context['dienstleistung'] ?? null;
        $date = $context['date'] ?? $context['datum'] ?? null;
        $time = $context['time'] ?? $context['uhrzeit'] ?? null;

        if (!$service || !$date || !$time) {
            return [
                'success' => false,
                'error' => 'Missing required parameters (service, date, time)',
            ];
        }

        // Simulate checking availability
        // For testing: Make some time slots available, some not
        $requestedDateTime = Carbon::parse("{$date} {$time}");
        $hour = (int) $requestedDateTime->format('H');

        // Simulated business hours: 9:00 - 18:00
        if ($hour < 9 || $hour >= 18) {
            return [
                'success' => true,
                'available' => false,
                'message' => 'Außerhalb der Geschäftszeiten',
                'alternatives' => $this->generateAlternativeSlots($requestedDateTime),
            ];
        }

        // Simulate 70% availability rate
        $isAvailable = (rand(1, 100) <= 70);

        if ($isAvailable) {
            return [
                'success' => true,
                'available' => true,
                'datetime' => $requestedDateTime->format('Y-m-d H:i'),
                'service' => $service,
                'duration' => $this->getServiceDuration($service),
                'variables' => [
                    'slot_available' => true,
                    'confirmed_datetime' => $requestedDateTime->format('Y-m-d H:i'),
                ],
            ];
        }

        // Not available - offer alternatives
        return [
            'success' => true,
            'available' => false,
            'message' => 'Leider ist um ' . $requestedDateTime->format('H:i') . ' Uhr kein Termin mehr verfügbar.',
            'alternatives' => $this->generateAlternativeSlots($requestedDateTime),
            'variables' => [
                'slot_available' => false,
            ],
        ];
    }

    /**
     * Mock book_appointment function.
     *
     * Simulates appointment booking.
     *
     * @param array $context Call context
     * @return array Function result
     */
    private function mockBookAppointment(array $context): array
    {
        $customerId = $context['customer_id'] ?? null;
        $datetime = $context['datetime'] ?? $context['confirmed_datetime'] ?? null;
        $service = $context['service'] ?? $context['dienstleistung'] ?? null;

        if (!$datetime || !$service) {
            return [
                'success' => false,
                'error' => 'Missing required parameters (datetime, service)',
            ];
        }

        // Simulate successful booking (90% success rate)
        $bookingSuccess = (rand(1, 100) <= 90);

        if ($bookingSuccess) {
            $appointmentId = 'mock_' . uniqid();

            return [
                'success' => true,
                'appointment_id' => $appointmentId,
                'datetime' => $datetime,
                'service' => $service,
                'customer_id' => $customerId,
                'message' => 'Termin erfolgreich gebucht',
                'variables' => [
                    'appointment_booked' => true,
                    'appointment_id' => $appointmentId,
                ],
            ];
        }

        // Booking failed (rare)
        return [
            'success' => false,
            'error' => 'Booking failed - slot taken by another customer',
            'variables' => [
                'appointment_booked' => false,
            ],
        ];
    }

    /**
     * Mock check_customer function.
     *
     * Checks if customer exists by phone or name.
     *
     * @param array $context Call context
     * @return array Function result
     */
    private function mockCheckCustomer(array $context): array
    {
        $phone = $context['phone'] ?? null;
        $name = $context['name'] ?? null;

        if ($phone) {
            $customer = Customer::where('phone', $phone)->first();
            if ($customer) {
                return [
                    'success' => true,
                    'found' => true,
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                    ],
                ];
            }
        }

        if ($name) {
            $customer = Customer::where('name', 'like', "%{$name}%")->first();
            if ($customer) {
                return [
                    'success' => true,
                    'found' => true,
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                    ],
                ];
            }
        }

        return [
            'success' => true,
            'found' => false,
            'message' => 'Customer not found',
        ];
    }

    /**
     * Mock collect_appointment_info function.
     *
     * Collects and validates appointment information.
     *
     * @param array $context Call context
     * @return array Function result
     */
    private function mockCollectAppointmentInfo(array $context): array
    {
        return [
            'success' => true,
            'collected' => true,
            'info' => [
                'service' => $context['service'] ?? null,
                'date' => $context['date'] ?? null,
                'time' => $context['time'] ?? null,
                'customer_name' => $context['customer_name'] ?? null,
            ],
        ];
    }

    /**
     * Mock generic function (fallback).
     *
     * @param string $functionName Function name
     * @param array $context Call context
     * @return array Function result
     */
    private function mockGenericFunction(string $functionName, array $context): array
    {
        return [
            'success' => true,
            'function' => $functionName,
            'message' => 'Mock function executed',
            'context' => $context,
        ];
    }

    /**
     * Generate alternative time slots.
     *
     * @param Carbon $requestedDateTime Requested datetime
     * @return array Alternative slots
     */
    private function generateAlternativeSlots(Carbon $requestedDateTime): array
    {
        $alternatives = [];

        // Offer 3 alternative slots
        for ($i = 1; $i <= 3; $i++) {
            $alternativeDateTime = $requestedDateTime->copy()->addHours($i);

            // Skip if outside business hours
            $hour = (int) $alternativeDateTime->format('H');
            if ($hour >= 9 && $hour < 18) {
                $alternatives[] = [
                    'datetime' => $alternativeDateTime->format('Y-m-d H:i'),
                    'formatted' => $alternativeDateTime->format('d.m.Y \u\m H:i \U\h\r'),
                ];
            }
        }

        return $alternatives;
    }

    /**
     * Get service duration in minutes.
     *
     * @param string $serviceName Service name
     * @return int Duration in minutes
     */
    private function getServiceDuration(string $serviceName): int
    {
        // Try to find real service
        $service = Service::where('name', 'like', "%{$serviceName}%")->first();

        if ($service && $service->duration) {
            return $service->duration;
        }

        // Default durations based on common services
        $durations = [
            'herrenhaarschnitt' => 30,
            'damenhaarschnitt' => 45,
            'färben' => 90,
            'waschen' => 15,
            'bart' => 20,
        ];

        foreach ($durations as $keyword => $duration) {
            if (stripos($serviceName, $keyword) !== false) {
                return $duration;
            }
        }

        // Default: 30 minutes
        return 30;
    }

    /**
     * Load historical function trace data for realistic mocking.
     *
     * @return void
     */
    private function loadHistoricalData(): void
    {
        // Load sample of recent function traces
        $traces = RetellFunctionTrace::whereIn('function_name', [
            'initialize_call',
            'check_availability',
            'book_appointment',
        ])
            ->whereNotNull('output_result')
            ->limit(100)
            ->get();

        foreach ($traces as $trace) {
            $functionName = $trace->function_name;

            if (!isset($this->historicalData[$functionName])) {
                $this->historicalData[$functionName] = [];
            }

            $this->historicalData[$functionName][] = [
                'input' => $trace->input_params,
                'output' => $trace->output_result,
                'duration_ms' => $trace->duration_ms,
                'status' => $trace->status,
            ];
        }
    }

    /**
     * Get historical data for a function.
     *
     * @param string $functionName Function name
     * @return array Historical data
     */
    public function getHistoricalData(string $functionName): array
    {
        return $this->historicalData[$functionName] ?? [];
    }

    /**
     * Get random historical response for a function.
     *
     * @param string $functionName Function name
     * @return array|null Historical response
     */
    public function getRandomHistoricalResponse(string $functionName): ?array
    {
        $data = $this->getHistoricalData($functionName);

        if (empty($data)) {
            return null;
        }

        return $data[array_rand($data)];
    }
}
