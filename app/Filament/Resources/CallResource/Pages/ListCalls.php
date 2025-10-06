<?php

namespace App\Filament\Resources\CallResource\Pages;

use App\Filament\Resources\CallResource;
use App\Filament\Widgets\OngoingCallsWidget;
use App\Filament\Resources\CallResource\Widgets\CallStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Components\Tab;
use App\Models\Call;

class ListCalls extends ListRecords
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neuer Anruf'),
            Actions\Action::make('export')
                ->label('CSV exportieren')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->exportCalls()),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()
            ->with([
                'customer',
                'company',
                'phoneNumber',
                'agent'
            ]); // Eager load relationships for performance
    }

    public function getTabs(): array
    {
        // Cache tab counts for 5 minutes for better performance
        // Use different cache keys for different time periods
        $user = auth()->user();
        $companyId = $user ? $user->company_id : 'global';
        $cacheKey = 'call-tabs-count-' . $companyId . '-' . now()->format('Y-m-d-H');
        $cacheMinutes = now()->format('i');
        $cacheSegment = floor($cacheMinutes / 5); // Changes every 5 minutes

        $counts = \Illuminate\Support\Facades\Cache::remember($cacheKey . '-' . $cacheSegment, 300, function () {
            return [
                'all' => Call::count(),
                'successful' => Call::where('call_successful', true)->count(),
                'failed' => Call::where('call_successful', false)->count(),
                'today' => Call::whereDate('created_at', today())->count(),
                'with_appointments' => Call::where('appointment_made', true)->count(),
            ];
        });

        return [
            'all' => Tab::make('Alle Anrufe')
                ->badge($counts['all']),
            'successful' => Tab::make('Erfolgreich')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('call_successful', true))
                ->badge($counts['successful']),
            'with_appointments' => Tab::make('Mit Termin')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('appointment_made', true))
                ->badge($counts['with_appointments']),
            'today' => Tab::make('Heute')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge($counts['today']),
            'failed' => Tab::make('Probleme')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('call_successful', false))
                ->badge($counts['failed']),
        ];
    }

    // Export-Funktion für CSV-Export
    protected function exportCalls()
    {
        $filename = 'anrufe_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () {
            // Get filtered calls based on current table filters
            $calls = $this->getFilteredTableQuery()
                ->with(['customer', 'company', 'phoneNumber'])
                ->get();

            // UTF-8 BOM for Excel compatibility
            echo "\xEF\xBB\xBF";

            // CSV Header
            echo "Datum;Uhrzeit;Kunde;Von Nummer;Nach Nummer;Richtung;Dauer;Status;Stimmung;Termin;Ergebnis;Kosten (€);Notizen\n";

            foreach ($calls as $call) {
                $row = [
                    $call->created_at->format('d.m.Y'),
                    $call->created_at->format('H:i:s'),
                    $call->customer_name ?? $call->customer?->name ?? 'Unbekannt',
                    $call->from_number ?? '',
                    $call->to_number ?? '',
                    $call->direction === 'inbound' ? 'Eingehend' : 'Ausgehend',
                    $call->duration_sec ? gmdate("i:s", $call->duration_sec) : '00:00',
                    $this->translateStatus($call->status),
                    $this->translateSentiment($call->sentiment),
                    $call->appointment_made ? 'Ja' : 'Nein',
                    $this->translateOutcome($call->session_outcome),
                    number_format(($call->cost ?? 0) / 100, 2, ',', '.'),
                    str_replace(["\n", "\r", ";"], [" ", " ", ","], $call->notes ?? '')
                ];

                echo implode(';', $row) . "\n";
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function translateStatus(?string $status): string
    {
        return match ($status) {
            'completed' => 'Abgeschlossen',
            'missed' => 'Verpasst',
            'failed' => 'Fehlgeschlagen',
            'busy' => 'Besetzt',
            'no_answer' => 'Keine Antwort',
            default => $status ?? 'Unbekannt',
        };
    }

    public function translateSentiment(?string $sentiment): string
    {
        return match ($sentiment) {
            'positive' => 'Positiv',
            'neutral' => 'Neutral',
            'negative' => 'Negativ',
            default => 'Unbekannt',
        };
    }

    public function translateOutcome(?string $outcome): string
    {
        return match ($outcome) {
            'appointment_scheduled' => 'Termin vereinbart',
            'information_provided' => 'Info gegeben',
            'callback_requested' => 'Rückruf erwünscht',
            'complaint_registered' => 'Beschwerde',
            'no_interest' => 'Kein Interesse',
            'transferred' => 'Weitergeleitet',
            'voicemail' => 'Voicemail',
            default => $outcome ?? '-',
        };
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CallStatsOverview::class,
            OngoingCallsWidget::class,
        ];
    }
}
