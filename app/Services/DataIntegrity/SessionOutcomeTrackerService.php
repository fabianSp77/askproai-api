<?php

namespace App\Services\DataIntegrity;

use App\Models\Call;
use Illuminate\Support\Facades\Log;

class SessionOutcomeTrackerService
{
    /**
     * Infer session outcome from call data
     *
     * @param Call $call
     * @return string|null
     */
    public function inferOutcome(Call $call): ?string
    {
        // Priority 1: Check appointment-related outcomes
        if ($call->appointment_id) {
            $appointment = $call->appointment;
            if ($appointment) {
                return match ($appointment->status) {
                    'cancelled' => 'appointment_cancelled',
                    'rescheduled' => 'appointment_rescheduled',
                    'scheduled', 'confirmed' => 'appointment_booked',
                    default => null,
                };
            }
        }

        // Priority 2: Check call flags
        if ($call->appointment_made) {
            return 'appointment_booked';
        }

        // Priority 3: Analyze transcript for keywords
        if ($call->transcript) {
            $outcome = $this->inferFromTranscript($call->transcript);
            if ($outcome) {
                return $outcome;
            }
        }

        // Priority 4: Analyze call analysis
        if ($call->analysis) {
            $outcome = $this->inferFromAnalysis($call->analysis);
            if ($outcome) {
                return $outcome;
            }
        }

        // Priority 5: Check call duration and status
        if ($call->call_successful === false || $call->duration_sec < 10) {
            return 'abandoned';
        }

        // Default: information only
        return 'information_only';
    }

    /**
     * Set session outcome for a call
     *
     * @param Call $call
     * @param string $outcome
     * @param string $method How the outcome was determined (auto, manual, inferred, etc.)
     * @return bool
     */
    public function setOutcome(Call $call, string $outcome, string $method = 'auto'): bool
    {
        try {
            $validOutcomes = [
                'appointment_booked',
                'appointment_rescheduled',
                'appointment_cancelled',
                'information_only',
                'callback_requested',
                'transferred',
                'voicemail',
                'abandoned',
                'technical_issue',
                'spam',
                'other',
            ];

            if (!in_array($outcome, $validOutcomes)) {
                Log::warning('Invalid session outcome', [
                    'call_id' => $call->id,
                    'outcome' => $outcome,
                ]);
                return false;
            }

            $call->update([
                'session_outcome' => $outcome,
                'linking_metadata' => array_merge($call->linking_metadata ?? [], [
                    'outcome_set_at' => now()->toIso8601String(),
                    'outcome_method' => $method,
                ]),
            ]);

            Log::info('Session outcome set', [
                'call_id' => $call->id,
                'outcome' => $outcome,
                'method' => $method,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to set session outcome', [
                'call_id' => $call->id,
                'outcome' => $outcome,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Auto-detect and set outcome for calls missing it
     *
     * @param Call $call
     * @return bool
     */
    public function autoDetectAndSet(Call $call): bool
    {
        if ($call->session_outcome) {
            // Already has outcome
            return false;
        }

        $inferredOutcome = $this->inferOutcome($call);
        if ($inferredOutcome) {
            return $this->setOutcome($call, $inferredOutcome, 'auto_inferred');
        }

        return false;
    }

    /**
     * Infer outcome from transcript text
     *
     * @param string|array $transcript
     * @return string|null
     */
    private function inferFromTranscript($transcript): ?string
    {
        $text = is_string($transcript) ? $transcript : json_encode($transcript);
        $text = strtolower($text);

        // German keyword patterns
        $patterns = [
            'appointment_booked' => [
                '/termin.*gebucht/',
                '/termin.*vereinbart/',
                '/termin.*bestätigt/',
                '/appointment.*booked/',
                '/appointment.*confirmed/',
            ],
            'appointment_cancelled' => [
                '/termin.*storniert/',
                '/termin.*abgesagt/',
                '/termin.*gecancelt/',
                '/appointment.*cancelled/',
                '/appointment.*canceled/',
            ],
            'appointment_rescheduled' => [
                '/termin.*verschoben/',
                '/termin.*umgebucht/',
                '/appointment.*rescheduled/',
                '/appointment.*moved/',
            ],
            'callback_requested' => [
                '/rückruf.*gewünscht/',
                '/rufen.*zurück/',
                '/callback.*requested/',
                '/please.*call.*back/',
            ],
            'voicemail' => [
                '/mailbox/',
                '/voicemail/',
                '/anrufbeantworter/',
            ],
            'spam' => [
                '/spam/',
                '/werbung/',
                '/advertisement/',
                '/marketing.*call/',
            ],
        ];

        foreach ($patterns as $outcome => $regexList) {
            foreach ($regexList as $regex) {
                if (preg_match($regex, $text)) {
                    return $outcome;
                }
            }
        }

        return null;
    }

    /**
     * Infer outcome from call analysis
     *
     * @param array|string $analysis
     * @return string|null
     */
    private function inferFromAnalysis($analysis): ?string
    {
        $data = is_string($analysis) ? json_decode($analysis, true) : $analysis;
        if (!$data) {
            return null;
        }

        // Check for appointment-related fields
        if (isset($data['appointment_made']) && $data['appointment_made'] === true) {
            return 'appointment_booked';
        }

        if (isset($data['appointment_cancelled']) && $data['appointment_cancelled'] === true) {
            return 'appointment_cancelled';
        }

        if (isset($data['callback_requested']) && $data['callback_requested'] === true) {
            return 'callback_requested';
        }

        if (isset($data['call_type'])) {
            return match (strtolower($data['call_type'])) {
                'spam', 'marketing' => 'spam',
                'voicemail' => 'voicemail',
                'transfer', 'transferred' => 'transferred',
                default => null,
            };
        }

        return null;
    }

    /**
     * Get outcome statistics for a company
     *
     * @param int $companyId
     * @param \Carbon\Carbon|null $startDate
     * @param \Carbon\Carbon|null $endDate
     * @return array
     */
    public function getOutcomeStats(int $companyId, ?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): array
    {
        $query = Call::where('company_id', $companyId)
            ->whereNotNull('session_outcome');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $stats = $query->selectRaw('session_outcome, COUNT(*) as count')
            ->groupBy('session_outcome')
            ->pluck('count', 'session_outcome')
            ->toArray();

        return $stats;
    }
}
