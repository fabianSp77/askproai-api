<?php

namespace App\Services\Retell;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;

/**
 * Appointment Query Service
 *
 * Handles secure appointment lookup for Retell AI calls.
 * Security: Requires phone number verification (no anonymous queries allowed)
 */
class AppointmentQueryService
{
    public function __construct(
        private CallLifecycleService $callLifecycle
    ) {}

    /**
     * Check if caller is anonymous (no phone number)
     *
     * @param Call $call
     * @return bool True if anonymous
     */
    public function isAnonymousCaller(Call $call): bool
    {
        $phoneNumber = $call->from_number ?? '';

        // Check for anonymous indicators
        $anonymousPatterns = [
            'anonymous',
            'unknown',
            'withheld',
            'restricted',
            ''
        ];

        $lowerPhone = strtolower($phoneNumber);

        return in_array($lowerPhone, $anonymousPatterns)
            || str_starts_with($phoneNumber, 'anonymous_');
    }

    /**
     * Find appointments for caller with phone verification
     *
     * Security: Only works with verified phone numbers
     *
     * @param Call $call Current call context
     * @param array $criteria Search criteria (date, service, etc.)
     * @return array Result with appointments and metadata
     */
    public function findAppointments(Call $call, array $criteria): array
    {
        // 🔒 Security Check: Phone number required
        if ($this->isAnonymousCaller($call)) {
            Log::warning('🚫 Anonymous caller attempted appointment query', [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id
            ]);

            return [
                'success' => false,
                'error' => 'anonymous_caller',
                'message' => 'Aus Sicherheitsgründen kann ich Termininformationen nur geben, wenn Ihre Telefonnummer übertragen wird. Bitte rufen Sie erneut an ohne Rufnummernunterdrückung.',
                'requires_phone_number' => true
            ];
        }

        // Find customer by phone (100% verified)
        $customer = $this->findCustomerByPhone($call);

        if (!$customer) {
            Log::info('📞 Customer not found by phone', [
                'phone' => $call->from_number,
                'company_id' => $call->company_id
            ]);

            return [
                'success' => false,
                'error' => 'customer_not_found',
                'message' => 'Ich konnte Sie in unserem System nicht finden. Möchten Sie einen neuen Termin buchen?'
            ];
        }

        // Find appointments for verified customer
        $appointments = $this->findCustomerAppointments($customer, $criteria);

        if ($appointments->isEmpty()) {
            Log::info('📅 No appointments found', [
                'customer_id' => $customer->id,
                'criteria' => $criteria
            ]);

            return [
                'success' => false,
                'error' => 'no_appointments',
                'message' => 'Ich konnte keinen Termin für Sie finden. Möchten Sie einen Termin buchen?'
            ];
        }

        Log::info('✅ Appointments found for verified customer', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'appointment_count' => $appointments->count(),
            'phone_verified' => true
        ]);

        return $this->formatAppointmentsResponse($appointments, $customer);
    }

    /**
     * Find customer by phone number (100% secure verification)
     *
     * @param Call $call
     * @return Customer|null
     */
    private function findCustomerByPhone(Call $call): ?Customer
    {
        return Customer::where('phone', $call->from_number)
            ->where('company_id', $call->company_id)
            ->first();
    }

    /**
     * Find appointments for customer with optional filters
     *
     * @param Customer $customer
     * @param array $criteria
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findCustomerAppointments(Customer $customer, array $criteria)
    {
        $query = Appointment::where('customer_id', $customer->id)
            ->where('company_id', $customer->company_id)  // Extra security check
            ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
            ->where('starts_at', '>=', now());  // Only future appointments

        // Filter by date if provided
        if (!empty($criteria['appointment_date'])) {
            $date = $this->parseDate($criteria['appointment_date']);
            if ($date) {
                $query->whereDate('starts_at', $date);
            }
        }

        // Filter by service if provided
        if (!empty($criteria['service_name'])) {
            $query->whereHas('service', function($q) use ($criteria) {
                $q->where('name', 'LIKE', '%' . $criteria['service_name'] . '%');
            });
        }

        // Phase 4: Eager load relationships to prevent N+1 queries
        $query->with(['service:id,name', 'staff:id,name', 'customer:id,name,email,phone']);

        return $query->orderBy('starts_at', 'asc')->get();
    }

    /**
     * Parse date string to Carbon date
     *
     * @param string $dateString
     * @return \Carbon\Carbon|null
     */
    private function parseDate(string $dateString): ?\Carbon\Carbon
    {
        try {
            // Try to parse using existing DateTimeParser if available
            if (class_exists('\App\Services\DateTimeParser')) {
                $parser = app(\App\Services\DateTimeParser::class);
                return $parser->parse($dateString);
            }

            // Fallback to Carbon parsing
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            Log::warning('Failed to parse date', [
                'date_string' => $dateString,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Format appointments for conversational response
     *
     * Intelligente Logik:
     * - Wenn nur 1 Termin: Detaillierte Info
     * - Wenn 2-3 Termine am gleichen Tag: Alle auflisten + Rückfrage
     * - Wenn >3 Termine oder verschiedene Tage: Nur nächsten nennen + Hinweis
     *
     * @param \Illuminate\Database\Eloquent\Collection $appointments
     * @param Customer $customer
     * @return array
     */
    private function formatAppointmentsResponse($appointments, Customer $customer): array
    {
        if ($appointments->count() === 1) {
            // Single appointment - detailed response
            $apt = $appointments->first();

            return [
                'success' => true,
                'appointment_count' => 1,
                'message' => sprintf(
                    'Ihr Termin ist am %s um %s Uhr für %s.',
                    $apt->starts_at->format('d.m.Y'),
                    $apt->starts_at->format('H:i'),
                    $apt->service->name ?? 'Ihre Behandlung'
                ),
                'appointment' => [
                    'id' => $apt->id,
                    'date' => $apt->starts_at->format('d.m.Y'),
                    'time' => $apt->starts_at->format('H:i'),
                    'datetime' => $apt->starts_at->toIso8601String(),
                    'service' => $apt->service->name ?? null,
                    'staff' => $apt->staff->name ?? null,
                    'status' => $apt->status
                ]
            ];
        }

        // Multiple appointments - check if same day
        $firstDate = $appointments->first()->starts_at->format('Y-m-d');
        $allSameDay = $appointments->every(function($apt) use ($firstDate) {
            return $apt->starts_at->format('Y-m-d') === $firstDate;
        });

        // Case 1: Multiple appointments on SAME day (2-3 appointments)
        if ($allSameDay && $appointments->count() <= 3) {
            $appointmentList = [];
            $messageLines = [
                sprintf(
                    "Sie haben %d Termine am %s:",
                    $appointments->count(),
                    $appointments->first()->starts_at->format('d.m.Y')
                )
            ];

            foreach ($appointments as $apt) {
                $appointmentList[] = [
                    'id' => $apt->id,
                    'date' => $apt->starts_at->format('d.m.Y'),
                    'time' => $apt->starts_at->format('H:i'),
                    'datetime' => $apt->starts_at->toIso8601String(),
                    'service' => $apt->service->name ?? null,
                    'staff' => $apt->staff->name ?? null,
                    'status' => $apt->status
                ];

                $messageLines[] = sprintf(
                    '- %s Uhr für %s',
                    $apt->starts_at->format('H:i'),
                    $apt->service->name ?? 'Behandlung'
                );
            }

            $messageLines[] = 'Welchen Termin möchten Sie wissen?';

            return [
                'success' => true,
                'appointment_count' => $appointments->count(),
                'same_day' => true,
                'message' => implode("\n", $messageLines),
                'appointments' => $appointmentList
            ];
        }

        // Case 2: Multiple appointments on DIFFERENT days OR too many same day
        // → Only return NEXT appointment + hint about others
        $nextApt = $appointments->first(); // Already sorted by starts_at ASC
        $remainingCount = $appointments->count() - 1;

        $message = sprintf(
            'Ihr nächster Termin ist am %s um %s Uhr für %s.',
            $nextApt->starts_at->format('d.m.Y'),
            $nextApt->starts_at->format('H:i'),
            $nextApt->service->name ?? 'Ihre Behandlung'
        );

        if ($remainingCount > 0) {
            $message .= sprintf(
                ' Sie haben insgesamt noch %d weitere %s. Möchten Sie alle Termine hören?',
                $remainingCount,
                $remainingCount === 1 ? 'Termin' : 'Termine'
            );
        }

        return [
            'success' => true,
            'appointment_count' => $appointments->count(),
            'showing' => 'next_only',
            'message' => $message,
            'next_appointment' => [
                'id' => $nextApt->id,
                'date' => $nextApt->starts_at->format('d.m.Y'),
                'time' => $nextApt->starts_at->format('H:i'),
                'datetime' => $nextApt->starts_at->toIso8601String(),
                'service' => $nextApt->service->name ?? null,
                'staff' => $nextApt->staff->name ?? null,
                'status' => $nextApt->status
            ],
            'remaining_count' => $remainingCount
        ];
    }
}
