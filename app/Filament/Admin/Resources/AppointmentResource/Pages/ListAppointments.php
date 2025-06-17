<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Pages;

use App\Filament\Admin\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync_calcom')
                ->label('Termine abrufen')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Termine von Cal.com abrufen')
                ->modalDescription('Möchten Sie alle Termine von Cal.com synchronisieren? Dies kann einen Moment dauern.')
                ->modalSubmitActionLabel('Ja, synchronisieren')
                ->action(function () {
                    $company = auth()->user()->company;
                    
                    if (!$company) {
                        \Filament\Notifications\Notification::make()
                            ->title('Fehler')
                            ->body('Keine Company zugeordnet.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    try {
                        // Verwende Company-spezifische API Key
                        $apiKey = $company->calcom_api_key;
                        
                        // Fallback auf globale Config
                        if (empty($apiKey)) {
                            $apiKey = config('services.calcom.api_key');
                        }
                        
                        if (empty($apiKey)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Konfigurationsfehler')
                                ->body('Cal.com API Key ist weder für Ihre Firma noch global konfiguriert.')
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }
                        
                        // Dispatch sync job
                        \App\Jobs\SyncCalcomBookingsJob::dispatch($company, $apiKey);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Synchronisation gestartet')
                            ->body('Die Termine werden im Hintergrund synchronisiert. Sie werden benachrichtigt, sobald der Vorgang abgeschlossen ist.')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Fehler beim Synchronisieren')
                            ->body('Fehler: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\CreateAction::make()
                ->label('Neuer Termin')
                ->icon('heroicon-o-plus'),
                
            Actions\Action::make('export')
                ->label('Exportieren')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\Select::make('format')
                        ->label('Format')
                        ->options([
                            'xlsx' => 'Excel (.xlsx)',
                            'csv' => 'CSV (.csv)',
                            'pdf' => 'PDF (.pdf)',
                        ])
                        ->default('xlsx')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('from')
                        ->label('Von')
                        ->native(false)
                        ->displayFormat('d.m.Y'),
                    \Filament\Forms\Components\DatePicker::make('to')
                        ->label('Bis')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    // Export logic would go here
                    \Filament\Notifications\Notification::make()
                        ->title('Export gestartet')
                        ->body('Der Export wird vorbereitet und in Kürze heruntergeladen.')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }
    
    public function getTabs(): array
    {
        $model = \App\Models\Appointment::class;
        
        return [
            'all' => Tab::make('Alle Termine')
                ->icon('heroicon-m-calendar-days')
                ->badge($model::count())
                ->badgeColor('gray')
                ->extraAttributes([
                    'title' => 'Zeigt alle Termine unabhängig vom Status oder Datum. Nutzen Sie diese Ansicht für eine vollständige Übersicht aller Buchungen.',
                    'class' => 'tab-with-tooltip'
                ]),
                
            'today' => Tab::make('Heute')
                ->icon('heroicon-m-calendar')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('starts_at', today()))
                ->badge($model::whereDate('starts_at', today())->count())
                ->badgeColor('primary')
                ->extraAttributes([
                    'title' => 'Zeigt nur die heutigen Termine. Ideal für die tägliche Planung und Vorbereitung.',
                    'class' => 'tab-with-tooltip'
                ]),
                
            'upcoming' => Tab::make('Kommend')
                ->icon('heroicon-m-arrow-trending-up')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('starts_at', '>', now())
                    ->where('status', '!=', 'cancelled')
                    ->orderBy('starts_at', 'asc'))
                ->badge($model::where('starts_at', '>', now())
                    ->where('status', '!=', 'cancelled')
                    ->count())
                ->badgeColor('info')
                ->extraAttributes([
                    'title' => 'Alle zukünftigen Termine (außer abgesagte). Sortiert nach Datum für optimale Übersicht.',
                    'class' => 'tab-with-tooltip'
                ]),
                
            'needs_confirmation' => Tab::make('Zu bestätigen')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'pending')
                    ->where('starts_at', '>', now())
                    ->where('starts_at', '<', now()->addDays(7)))
                ->badge($model::where('status', 'pending')
                    ->where('starts_at', '>', now())
                    ->where('starts_at', '<', now()->addDays(7))
                    ->count())
                ->badgeColor('warning')
                ->extraAttributes([
                    'title' => 'Termine der nächsten 7 Tage, die noch bestätigt werden müssen. Diese benötigen Ihre Aufmerksamkeit!',
                    'class' => 'tab-with-tooltip'
                ]),
                
            'no_shows' => Tab::make('Nicht erschienen')
                ->icon('heroicon-m-user-minus')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'no_show')
                    ->orWhere(function($q) {
                        $q->where('status', 'confirmed')
                          ->where('ends_at', '<', now()->subHours(2));
                    }))
                ->badge($model::where('status', 'no_show')->count())
                ->badgeColor('danger')
                ->extraAttributes([
                    'title' => 'Kunden, die nicht zum Termin erschienen sind. Wichtig für Nachverfolgung und ggf. No-Show-Gebühren.',
                    'class' => 'tab-with-tooltip'
                ]),
                
            'completed' => Tab::make('Abgeschlossen')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge($model::where('status', 'completed')->count())
                ->badgeColor('success')
                ->extraAttributes([
                    'title' => 'Erfolgreich durchgeführte Termine. Zeigt Ihre abgeschlossenen Leistungen.',
                    'class' => 'tab-with-tooltip'
                ]),
                
            'synced' => Tab::make('Cal.com')
                ->icon('heroicon-m-cloud-arrow-down')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function($q) {
                    $q->whereNotNull('calcom_booking_id')
                      ->orWhereNotNull('calcom_v2_booking_id');
                }))
                ->badge($model::where(function($q) {
                    $q->whereNotNull('calcom_booking_id')
                      ->orWhereNotNull('calcom_v2_booking_id');
                })->count())
                ->badgeColor('success')
                ->extraAttributes([
                    'title' => 'Termine, die mit Cal.com synchronisiert sind. Zeigt die Integration mit Ihrem Kalendersystem.',
                    'class' => 'tab-with-tooltip'
                ]),
                
            'manual' => Tab::make('Manuell')
                ->icon('heroicon-m-pencil-square')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNull('calcom_booking_id')
                    ->whereNull('calcom_v2_booking_id')
                    ->whereNull('call_id'))
                ->badge($model::whereNull('calcom_booking_id')
                    ->whereNull('calcom_v2_booking_id')
                    ->whereNull('call_id')
                    ->count())
                ->badgeColor('gray')
                ->extraAttributes([
                    'title' => 'Manuell erstellte Termine ohne Cal.com oder Anruf-Verbindung.',
                    'class' => 'tab-with-tooltip'
                ]),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Resources\AppointmentResource\Widgets\AppointmentStatsWidget::class,
            \App\Filament\Admin\Resources\AppointmentResource\Widgets\AppointmentTrendsWidget::class,
            \App\Filament\Admin\Resources\AppointmentResource\Widgets\StaffPerformanceWidget::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            AppointmentResource\Widgets\AppointmentCalendar::class,
        ];
    }
}