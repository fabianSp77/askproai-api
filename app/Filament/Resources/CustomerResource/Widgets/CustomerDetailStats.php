<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Reactive;

/**
 * Customer Detail Stats Widget
 *
 * Shows individual customer metrics on the View page.
 * Unlike CustomerOverview (which shows ALL customers), this shows stats for ONE customer.
 */
class CustomerDetailStats extends BaseWidget
{
    #[Reactive]
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        /** @var Customer $customer */
        $customer = $this->record;

        // Calculate conversion rate (Calls → Appointments)
        $callCount = $customer->calls()->count();
        $appointmentCount = $customer->appointments()->count();
        $conversionRate = $callCount > 0
            ? round(($appointmentCount / $callCount) * 100, 1)
            : 0;

        // Calculate revenue metrics
        $totalRevenue = $customer->total_revenue ?? 0;
        $avgRevenue = $appointmentCount > 0
            ? round($totalRevenue / $appointmentCount, 2)
            : 0;

        // Last contact time - get from actual relations, not from cached fields
        $lastCall = $customer->calls()->latest('created_at')->first();
        $lastAppointment = $customer->appointments()->latest('created_at')->first();

        $lastContactAt = null;
        $contactSource = 'created';

        if ($lastCall && $lastAppointment) {
            // Use whichever is more recent
            $lastContactAt = $lastCall->created_at > $lastAppointment->created_at
                ? $lastCall->created_at
                : $lastAppointment->created_at;
            $contactSource = $lastCall->created_at > $lastAppointment->created_at ? 'call' : 'appointment';
        } elseif ($lastCall) {
            $lastContactAt = $lastCall->created_at;
            $contactSource = 'call';
        } elseif ($lastAppointment) {
            $lastContactAt = $lastAppointment->created_at;
            $contactSource = 'appointment';
        } else {
            $lastContactAt = $customer->created_at;
            $contactSource = 'created';
        }

        $daysSinceContact = now()->diffInDays($lastContactAt);
        $hoursSinceContact = now()->diffInHours($lastContactAt);

        // Failed bookings (calls with appointment_made=1 but no converted_appointment_id)
        $failedBookings = $customer->calls()
            ->where('appointment_made', 1)
            ->whereNull('converted_appointment_id')
            ->count();

        return [
            Stat::make('Anrufe', $callCount)
                ->description($failedBookings > 0
                    ? "⚠️ {$failedBookings} Buchung(en) fehlgeschlagen"
                    : 'Gesamt eingehende/ausgehende Anrufe'
                )
                ->descriptionIcon($failedBookings > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-phone')
                ->color($failedBookings > 0 ? 'warning' : 'primary'),

            Stat::make('Termine', $appointmentCount)
                ->description($appointmentCount === 0 && $callCount > 0
                    ? '⚠️ Noch keine Termine trotz Anrufen'
                    : "{$appointmentCount} von {$callCount} Anrufen konvertiert"
                )
                ->descriptionIcon($appointmentCount === 0 ? 'heroicon-m-calendar-x-mark' : 'heroicon-m-calendar')
                ->color($appointmentCount === 0 && $callCount > 0 ? 'danger' : 'success'),

            Stat::make('Conversion', $conversionRate . '%')
                ->description('Calls → Termine')
                ->descriptionIcon($conversionRate > 50 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($conversionRate > 50 ? 'success' : ($conversionRate > 25 ? 'warning' : 'danger')),

            Stat::make('Umsatz', '€' . number_format($totalRevenue, 2))
                ->description('Ø €' . number_format($avgRevenue, 2) . ' pro Termin')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color($totalRevenue > 0 ? 'success' : 'gray'),

            Stat::make('Letzter Kontakt', $this->formatLastContact($daysSinceContact, $hoursSinceContact))
                ->description(($lastContactAt ? $lastContactAt->format('d.m.Y H:i') : 'N/A') . ' (' . $this->getContactSourceLabel($contactSource) . ')')
                ->descriptionIcon('heroicon-m-clock')
                ->color($daysSinceContact > 90 ? 'danger' : ($daysSinceContact > 30 ? 'warning' : 'success')),

            Stat::make('Journey', $this->getJourneyLabel($customer->journey_status))
                ->description($this->getJourneyNextStep($customer))
                ->descriptionIcon($this->getJourneyIcon($customer->journey_status))
                ->color($this->getJourneyColor($customer->journey_status)),
        ];
    }

    private function getCallsChart(Customer $customer): array
    {
        // Last 7 days call activity
        $calls = $customer->calls()
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();

        return $calls ?: [0];
    }

    private function getAppointmentsChart(Customer $customer): array
    {
        // Last 7 days appointment activity
        $appointments = $customer->appointments()
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();

        return $appointments ?: [0];
    }

    private function getJourneyLabel(string $status): string
    {
        return match($status) {
            'initial_contact' => '📱 Erstkontakt',
            'lead' => '🌱 Lead',
            'prospect' => '🔍 Interessent',
            'customer' => '⭐ Kunde',
            'regular' => '💎 Stammkunde',
            'vip' => '👑 VIP',
            'at_risk' => '⚠️ Gefährdet',
            'churned' => '❌ Verloren',
            default => $status,
        };
    }

    private function getJourneyNextStep(Customer $customer): string
    {
        $appointmentCount = $customer->appointments()->count();
        $callCount = $customer->calls()->count();

        return match($customer->journey_status) {
            'initial_contact' => $callCount > 0 && $appointmentCount === 0
                ? '→ Ersten Termin buchen!'
                : '→ Kontakt aufnehmen',
            'lead' => '→ Follow-up & Interesse validieren',
            'prospect' => '→ Termin vereinbaren',
            'customer' => '→ Stammkunde entwickeln',
            'regular' => '→ Upselling & Retention',
            'vip' => '→ Premium-Service pflegen',
            'at_risk' => '⚠️ Dringend kontaktieren!',
            'churned' => '→ Win-back Kampagne',
            default => '→ Status aktualisieren',
        };
    }

    private function getJourneyIcon(string $status): string
    {
        return match($status) {
            'initial_contact' => 'heroicon-m-user-plus',
            'lead', 'prospect' => 'heroicon-m-eye',
            'customer', 'regular' => 'heroicon-m-star',
            'vip' => 'heroicon-m-sparkles',
            'at_risk' => 'heroicon-m-exclamation-triangle',
            'churned' => 'heroicon-m-x-circle',
            default => 'heroicon-m-user',
        };
    }

    private function getJourneyColor(string $status): string
    {
        return match($status) {
            'initial_contact' => 'info',
            'lead' => 'gray',
            'prospect' => 'warning',
            'customer' => 'success',
            'regular' => 'primary',
            'vip' => 'warning',
            'at_risk' => 'danger',
            'churned' => 'gray',
            default => 'gray',
        };
    }

    private function formatLastContact(int $days, int $hours): string
    {
        if ($hours < 1) {
            return 'Gerade eben';
        } elseif ($hours < 24) {
            return $hours === 1 ? 'vor 1 Stunde' : "vor {$hours} Stunden";
        } elseif ($days === 1) {
            return 'Gestern';
        } elseif ($days < 7) {
            return "vor {$days} Tagen";
        } elseif ($days < 30) {
            $weeks = floor($days / 7);
            return $weeks === 1 ? 'vor 1 Woche' : "vor {$weeks} Wochen";
        } elseif ($days < 365) {
            $months = floor($days / 30);
            return $months === 1 ? 'vor 1 Monat' : "vor {$months} Monaten";
        } else {
            $years = floor($days / 365);
            return $years === 1 ? 'vor 1 Jahr' : "vor {$years} Jahren";
        }
    }

    private function getContactSourceLabel(string $source): string
    {
        return match($source) {
            'call' => 'Anruf',
            'appointment' => 'Termin',
            'created' => 'Kunde erstellt',
            default => $source,
        };
    }
}
