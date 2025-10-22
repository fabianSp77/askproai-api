<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Models\Customer;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Reactive;

/**
 * Customer Activity Timeline Widget
 *
 * Unified chronological timeline of all customer interactions:
 * - Calls (with transcript, recording, booking status)
 * - Appointments (past and upcoming)
 * - Notes
 * - Journey changes
 * - System events
 */
class CustomerActivityTimeline extends Widget
{
    protected static string $view = 'filament.widgets.customer-activity-timeline';

    #[Reactive]
    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        if (!$this->record) {
            return [];
        }

        /** @var Customer $customer */
        $customer = $this->record;

        $activities = $this->buildActivityTimeline($customer);

        return [
            'customer_id' => $customer->id,
            'activities' => $activities,
            'stats' => $this->calculateStats($activities),
        ];
    }

    private function buildActivityTimeline(Customer $customer): array
    {
        $timeline = [];

        // Add calls
        foreach ($customer->calls()->orderBy('created_at', 'desc')->get() as $call) {
            $timeline[] = [
                'type' => 'call',
                'timestamp' => $call->created_at->toIso8601String(),
                'data' => (object)[
                    'id' => $call->id,
                    'recording_url' => $call->recording_url,
                ],
                'icon' => $call->direction === 'inbound' ? '📞' : '📱',
                'color' => $call->status === 'answered' ? 'success' : 'warning',
                'title' => $call->direction === 'inbound' ? 'Eingehender Anruf' : 'Ausgehender Anruf',
                'description' => $this->getCallDescription($call),
                'has_transcript' => !empty($call->transcript),
                'has_recording' => !empty($call->recording_url),
                'is_failed_booking' => $call->appointment_made && !$call->converted_appointment_id,
            ];
        }

        // Add appointments
        foreach ($customer->appointments()->orderBy('starts_at', 'desc')->get() as $appointment) {
            $isPast = $appointment->starts_at < now();
            $timeline[] = [
                'type' => 'appointment',
                'timestamp' => $appointment->starts_at->toIso8601String(),
                'data' => (object)[
                    'id' => $appointment->id,
                ],
                'icon' => '📅',
                'color' => match($appointment->status) {
                    'confirmed' => 'success',
                    'completed' => 'info',
                    'cancelled' => 'danger',
                    'no_show' => 'warning',
                    default => 'primary',
                },
                'title' => $isPast ? 'Termin' : 'Anstehender Termin',
                'description' => $this->getAppointmentDescription($appointment),
                'is_upcoming' => !$isPast,
            ];
        }

        // Add notes if they exist
        if (method_exists($customer, 'notes')) {
            foreach ($customer->notes()->orderBy('created_at', 'desc')->get() as $note) {
                $timeline[] = [
                    'type' => 'note',
                    'timestamp' => $note->created_at->toIso8601String(),
                    'data' => (object)[
                        'id' => $note->id,
                    ],
                    'icon' => '📝',
                    'color' => 'gray',
                    'title' => 'Notiz: ' . $note->subject,
                    'description' => $note->content,
                ];
            }
        }

        // Add customer creation event
        $timeline[] = [
            'type' => 'system',
            'timestamp' => $customer->created_at->toIso8601String(),
            'icon' => '👤',
            'color' => 'info',
            'title' => 'Kunde angelegt',
            'description' => 'Erstellt über ' . ($customer->source ?? 'Admin'),
        ];

        // Add journey status changes if history exists
        if (!empty($customer->journey_history)) {
            try {
                $history = json_decode($customer->journey_history, true);
                if (is_array($history)) {
                    foreach ($history as $change) {
                        if (isset($change['changed_at'])) {
                            $timeline[] = [
                                'type' => 'journey',
                                'timestamp' => $change['changed_at'],
                                'icon' => '🎯',
                                'color' => 'warning',
                                'title' => 'Status-Änderung',
                                'description' => ($change['from'] ?? 'Neu') . ' → ' . ($change['to'] ?? 'Unbekannt'),
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore parsing errors
            }
        }

        // Sort by timestamp descending (newest first)
        usort($timeline, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return $timeline;
    }

    private function getCallDescription($call): string
    {
        $parts = [];

        if ($call->duration_sec) {
            $minutes = floor($call->duration_sec / 60);
            $seconds = $call->duration_sec % 60;
            $duration = $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
            $parts[] = "Dauer: {$duration}";
        }

        $parts[] = match($call->status) {
            'answered' => '✓ Beantwortet',
            'missed' => '✗ Verpasst',
            'busy' => 'Besetzt',
            'failed' => 'Fehlgeschlagen',
            'voicemail' => 'Voicemail',
            default => ucfirst($call->status),
        };

        if ($call->from_number) {
            $parts[] = "von {$call->from_number}";
        }

        if ($call->appointment_made && !$call->converted_appointment_id) {
            $parts[] = "⚠️ Buchung fehlgeschlagen";
        } elseif ($call->appointment_made && $call->converted_appointment_id) {
            $parts[] = "✓ Termin gebucht";
        }

        return implode(' | ', $parts);
    }

    private function getAppointmentDescription($appointment): string
    {
        $parts = [];

        if ($appointment->service) {
            $parts[] = $appointment->service->name;
        }

        if ($appointment->staff) {
            $parts[] = "mit {$appointment->staff->name}";
        }

        if ($appointment->branch) {
            $parts[] = "in {$appointment->branch->name}";
        }

        $status = match($appointment->status) {
            'scheduled' => 'Geplant',
            'confirmed' => '✓ Bestätigt',
            'completed' => '✓ Abgeschlossen',
            'cancelled' => '✗ Abgesagt',
            'no_show' => '✗ Nicht erschienen',
            default => ucfirst($appointment->status),
        };
        $parts[] = $status;

        if ($appointment->price > 0) {
            $parts[] = '€' . number_format($appointment->price, 2);
        }

        return implode(' | ', $parts);
    }

    private function calculateStats(array $activities): array
    {
        $stats = [
            'total' => count($activities),
            'calls' => 0,
            'appointments' => 0,
            'notes' => 0,
            'last_7_days' => 0,
            'last_30_days' => 0,
        ];

        $now = now();

        foreach ($activities as $activity) {
            if ($activity['type'] === 'call') {
                $stats['calls']++;
            } elseif ($activity['type'] === 'appointment') {
                $stats['appointments']++;
            } elseif ($activity['type'] === 'note') {
                $stats['notes']++;
            }

            // Convert ISO8601 string timestamp back to Carbon for comparison
            $timestamp = \Carbon\Carbon::parse($activity['timestamp']);
            $daysDiff = $now->diffInDays($timestamp);
            if ($daysDiff <= 7) {
                $stats['last_7_days']++;
            }
            if ($daysDiff <= 30) {
                $stats['last_30_days']++;
            }
        }

        return $stats;
    }
}
