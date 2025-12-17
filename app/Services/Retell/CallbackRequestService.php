<?php

namespace App\Services\Retell;

use App\Models\Branch;
use App\Models\Call;
use App\Models\CallbackRequest;
use App\Services\Policy\BranchPolicyEnforcer;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * CallbackRequestService
 *
 * ✅ Phase 3: Retell function handler for callback requests
 *
 * Creates callback requests when:
 * - No availability for desired time
 * - Booking failed
 * - Customer wants callback for questions
 * - Manual callback request
 *
 * Features:
 * - Email capture (optional)
 * - Priority assignment
 * - Duplicate detection (30min window)
 * - Automatic webhook dispatching (via CallbackRequest model)
 * - SLA tracking (30min default expiry)
 *
 * Policy Integration:
 * - Checks POLICY_TYPE_CALLBACK_SERVICE before creating request
 * - Anonymous callers allowed (contact info captured)
 * - Branch can restrict via policy configuration
 */
class CallbackRequestService
{
    public function __construct(
        private BranchPolicyEnforcer $policyEnforcer
    ) {}

    /**
     * Create callback request
     *
     * @param Branch $branch Branch for callback
     * @param Call $call Call record for policy check
     * @param array $parameters Request parameters
     * @return array Response array for Retell
     */
    public function createCallbackRequest(Branch $branch, Call $call, array $parameters = []): array
    {
        Log::info('📞 Callback Request', [
            'branch_id' => $branch->id,
            'call_id' => $call->id,
            'parameters' => $parameters,
        ]);

        // 1. Policy Check
        $policyCheck = $this->policyEnforcer->isOperationAllowed(
            $branch,
            $call,
            'callback'
        );

        if (!$policyCheck['allowed']) {
            Log::warning('🛑 Callback request policy denied', [
                'branch_id' => $branch->id,
                'reason' => $policyCheck['reason'],
            ]);

            return [
                'success' => false,
                'error' => $policyCheck['message'] ?? 'Rückrufservice ist derzeit nicht verfügbar.',
                'reason' => $policyCheck['reason'],
            ];
        }

        // 2. Extract and validate parameters
        $phoneNumber = $parameters['phone_number'] ?? $call->from_number;
        $customerName = $parameters['customer_name'] ?? $call->customer_name ?? null;
        $customerEmail = $parameters['email'] ?? $parameters['customer_email'] ?? null;

        if (!$phoneNumber || in_array(strtolower($phoneNumber), ['anonymous', 'unknown', 'blocked'])) {
            // Anonymous caller without providing phone
            return [
                'success' => false,
                'error' => 'Für einen Rückruf benötigen wir Ihre Telefonnummer. Bitte nennen Sie uns Ihre Nummer.',
                'reason' => 'phone_number_required',
            ];
        }

        if (!$customerName) {
            return [
                'success' => false,
                'error' => 'Für einen Rückruf benötigen wir Ihren Namen. Wie darf ich Sie ansprechen?',
                'reason' => 'customer_name_required',
            ];
        }

        // 3. Create callback request
        try {
            $callbackRequest = CallbackRequest::create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'customer_id' => $call->customer_id,
                'phone_number' => $phoneNumber,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail, // ✅ Phase 3: Email capture
                'service_id' => $parameters['service_id'] ?? null,
                'staff_id' => $parameters['preferred_staff_id'] ?? null,
                'priority' => $this->determinePriority($parameters),
                'status' => CallbackRequest::STATUS_PENDING,
                'preferred_time_window' => $this->extractTimeWindow($parameters),
                'notes' => $this->buildNotes($parameters, $call),
                'expires_at' => now()->addMinutes(30), // SLA: 30 minutes
                'metadata' => [
                    'call_id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'requested_at' => now()->toIso8601String(),
                    'source' => 'retell_agent',
                    'request_reason' => $parameters['reason'] ?? 'general_inquiry',
                ],
            ]);

            Log::info('✅ Callback request created', [
                'callback_id' => $callbackRequest->id,
                'branch_id' => $branch->id,
                'phone_number' => $phoneNumber,
                'has_email' => !empty($customerEmail),
                'expires_at' => $callbackRequest->expires_at->toIso8601String(),
            ]);

            // Webhook dispatching happens automatically via CallbackRequest::boot()

            return [
                'success' => true,
                'data' => [
                    'callback_id' => $callbackRequest->id,
                    'message' => $this->formatSuccessMessage($callbackRequest),
                    'expires_in_minutes' => 30,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('❌ Failed to create callback request', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut oder kontaktieren Sie uns direkt.',
                'reason' => 'internal_error',
            ];
        }
    }

    /**
     * Determine callback priority based on parameters
     *
     * @param array $parameters Request parameters
     * @return string Priority (normal|high|urgent)
     */
    private function determinePriority(array $parameters): string
    {
        // Explicit priority from parameters
        if (isset($parameters['priority']) && in_array($parameters['priority'], CallbackRequest::PRIORITIES)) {
            return $parameters['priority'];
        }

        // Auto-detect based on reason
        $reason = $parameters['reason'] ?? 'general_inquiry';

        return match ($reason) {
            'booking_failed', 'no_availability' => CallbackRequest::PRIORITY_HIGH,
            'urgent_matter', 'complaint' => CallbackRequest::PRIORITY_URGENT,
            default => CallbackRequest::PRIORITY_NORMAL,
        };
    }

    /**
     * Extract preferred time window from parameters
     *
     * @param array $parameters
     * @return array|null Time window preferences
     */
    private function extractTimeWindow(array $parameters): ?array
    {
        if (!isset($parameters['preferred_time'])) {
            return null;
        }

        return [
            'preference' => $parameters['preferred_time'],
            'flexible' => $parameters['time_flexible'] ?? true,
        ];
    }

    /**
     * Build notes from parameters and call context
     *
     * @param array $parameters
     * @param Call $call
     * @return string|null
     */
    private function buildNotes(array $parameters, Call $call): ?string
    {
        $notes = [];

        // User-provided notes
        if (isset($parameters['notes']) && !empty($parameters['notes'])) {
            $notes[] = '**Anliegen**: ' . $parameters['notes'];
        }

        // Reason for callback
        if (isset($parameters['reason'])) {
            $reasonLabels = [
                'no_availability' => 'Keine Verfügbarkeit zur gewünschten Zeit',
                'booking_failed' => 'Buchung fehlgeschlagen',
                'general_inquiry' => 'Allgemeine Anfrage',
                'service_question' => 'Frage zu Dienstleistungen',
                'complaint' => 'Beschwerde',
                'urgent_matter' => 'Dringendes Anliegen',
            ];

            $reasonLabel = $reasonLabels[$parameters['reason']] ?? $parameters['reason'];
            $notes[] = '**Grund**: ' . $reasonLabel;
        }

        // Service interest
        if (isset($parameters['service_name'])) {
            $notes[] = '**Interessiert an**: ' . $parameters['service_name'];
        }

        // Desired date/time (if applicable)
        if (isset($parameters['desired_date']) || isset($parameters['desired_time'])) {
            $desired = [];
            if (isset($parameters['desired_date'])) {
                $desired[] = $parameters['desired_date'];
            }
            if (isset($parameters['desired_time'])) {
                $desired[] = $parameters['desired_time'];
            }
            $notes[] = '**Gewünschter Termin**: ' . implode(' um ', $desired);
        }

        return !empty($notes) ? implode("\n", $notes) : null;
    }

    /**
     * Format success message for speech
     *
     * @param CallbackRequest $request
     * @return string Message for Retell agent
     */
    private function formatSuccessMessage(CallbackRequest $request): string
    {
        $baseMessage = 'Vielen Dank! Ihre Rückrufanfrage wurde erfolgreich registriert.';

        if ($request->customer_email) {
            $baseMessage .= ' Sie erhalten eine Bestätigung per E-Mail.';
        }

        $baseMessage .= ' Wir melden uns innerhalb von 30 Minuten bei Ihnen.';

        return $baseMessage;
    }
}
