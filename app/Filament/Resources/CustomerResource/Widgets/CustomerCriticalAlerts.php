<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Models\Customer;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Reactive;

/**
 * Customer Critical Alerts Widget
 *
 * Prominent alerts for urgent actions:
 * - Failed bookings
 * - No conversion (calls without appointments)
 * - Missing email
 * - Duplicates
 */
class CustomerCriticalAlerts extends Widget
{
    protected static string $view = 'filament.widgets.customer-critical-alerts';

    #[Reactive]
    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    // Show ABOVE all other widgets
    protected static ?int $sort = -100;

    /**
     * Get widget data for view
     * Override getViewData() (not getData()) for Filament widgets
     */
    protected function getViewData(): array
    {
        return $this->getAlerts();
    }

    protected function getAlerts(): array
    {
        if (!$this->record) {
            return ['alerts' => []];
        }

        /** @var Customer $customer */
        $customer = $this->record;

        $alerts = [];

        // CRITICAL: Failed Bookings
        $failedBookings = $customer->calls()
            ->where('appointment_made', 1)
            ->whereNull('converted_appointment_id')
            ->count();

        if ($failedBookings > 0) {
            $failedCalls = $customer->calls()
                ->where('appointment_made', 1)
                ->whereNull('converted_appointment_id')
                ->latest('created_at')
                ->get();

            $alerts[] = [
                'type' => 'critical',
                'priority' => 1,
                'icon' => '🚨',
                'title' => "{$failedBookings} Fehlgeschlagene Terminbuchung" . ($failedBookings > 1 ? 'en' : ''),
                'message' => "Der AI-Agent versuchte {$failedBookings} Termin" . ($failedBookings > 1 ? 'e' : '') . " zu buchen, aber die Buchung" . ($failedBookings > 1 ? 'en' : '') . " schlug" . ($failedBookings > 1 ? 'en' : '') . " fehl. Manuelle Nachbearbeitung dringend erforderlich!",
                'actions' => [
                    [
                        'label' => 'Jetzt nachbuchen',
                        'url' => route('filament.admin.resources.appointments.create', [
                            'customer_id' => $customer->id,
                            'call_id' => $failedCalls->first()->id ?? null,
                        ]),
                        'color' => 'danger',
                    ],
                ],
                'details' => $failedCalls->map(fn($call) => [
                    'text' => 'Call #' . $call->id . ' am ' . $call->created_at->format('d.m.Y H:i'),
                    'url' => route('filament.admin.resources.calls.edit', $call->id),
                ])->toArray(),
            ];
        }

        // HIGH: No Conversion (Calls but no Appointments)
        $callCount = $customer->calls()->count();
        $appointmentCount = $customer->appointments()->count();

        if ($callCount > 0 && $appointmentCount === 0) {
            $alerts[] = [
                'type' => 'high',
                'priority' => 2,
                'icon' => '🎯',
                'title' => '0% Conversion Rate',
                'message' => "Kunde hat {$callCount} Anruf" . ($callCount > 1 ? 'e' : '') . " getätigt, aber noch keinen einzigen Termin gebucht. Dringend nachfassen!",
                'actions' => [
                    [
                        'label' => 'Ersten Termin buchen',
                        'url' => route('filament.admin.resources.appointments.create', [
                            'customer_id' => $customer->id,
                        ]),
                        'color' => 'warning',
                    ],
                    [
                        'label' => 'Anrufen',
                        'url' => 'tel:' . $customer->phone,
                        'color' => 'success',
                    ],
                ],
            ];
        }

        // MEDIUM: Missing Email
        if (empty($customer->email)) {
            $alerts[] = [
                'type' => 'medium',
                'priority' => 3,
                'icon' => '✉️',
                'title' => 'E-Mail-Adresse fehlt',
                'message' => 'Ohne E-Mail-Adresse sind Marketing-Kampagnen, Erinnerungen und Follow-ups nicht möglich. Nutzen Sie den "E-Mail hinzufügen" Button im Seiten-Header.',
                'actions' => [
                    [
                        'label' => 'Kunde bearbeiten',
                        'url' => route('filament.admin.resources.customers.edit', $customer->id),
                        'color' => 'info',
                    ],
                ],
            ];
        }

        // MEDIUM: Duplicates
        $duplicates = \App\Models\Customer::where(function($query) use ($customer) {
                $query->where('phone', $customer->phone);
                if ($customer->email) {
                    $query->orWhere('email', $customer->email);
                }
            })
            ->where('id', '!=', $customer->id)
            ->where('company_id', $customer->company_id)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $alerts[] = [
                'type' => 'medium',
                'priority' => 4,
                'icon' => '👥',
                'title' => $duplicates->count() . ' Duplikat' . ($duplicates->count() > 1 ? 'e' : '') . ' gefunden',
                'message' => 'Es wurden ' . $duplicates->count() . ' weitere Kunden mit gleicher Telefonnummer' . ($customer->email ? ' oder E-Mail' : '') . ' gefunden. Nutzen Sie die "Duplikate" und "Duplikat #X zusammenführen" Buttons im Seiten-Header.',
                'actions' => [],
                'details' => $duplicates->map(fn($dup) => [
                    'text' => 'Kunde #' . $dup->id . ': ' . $dup->name . ' (' . $dup->calls()->count() . ' Calls, ' . $dup->appointments()->count() . ' Termine)',
                    'url' => route('filament.admin.resources.customers.view', $dup->id),
                ])->toArray(),
            ];
        }

        // Sort by priority
        usort($alerts, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return [
            'alerts' => $alerts,
            'customer' => $customer,
        ];
    }
}
