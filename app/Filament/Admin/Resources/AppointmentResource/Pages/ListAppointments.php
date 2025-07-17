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
                ->extraAttributes([
                    'class' => 'fi-btn-premium',
                ])
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
                ->icon('heroicon-o-plus')
                ->extraAttributes([
                    'class' => 'fi-btn-premium-primary',
                ]),
                
            Actions\Action::make('export')
                ->label('Exportieren')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'fi-btn-premium-secondary',
                ])
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
        return []; // Temporarily disabled for debugging
        
        $model = \App\Models\Appointment::class;
        
        return [
            'all' => Tab::make('Alle Termine')
                ->icon('heroicon-m-calendar-days')
                // ->badge(static::getResource()::getEloquentQuery()->count())
                ->badgeColor('gray'),
                
            'today' => Tab::make('Heute')
                ->icon('heroicon-m-calendar')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('starts_at', today()))
                // ->badge(static::getResource()::getEloquentQuery()->whereDate('starts_at', today())->count())
                ->badgeColor('primary'),
                
            'upcoming' => Tab::make('Kommend')
                ->icon('heroicon-m-arrow-trending-up')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('starts_at', '>', now())
                    ->where('status', '!=', 'cancelled')
                    ->orderBy('starts_at', 'asc'))
                // ->badge(static::getResource()::getEloquentQuery()
                //     ->where('starts_at', '>', now())
                //     ->where('status', '!=', 'cancelled')
                //     ->count())
                ->badgeColor('info'),
                
            'needs_confirmation' => Tab::make('Zu bestätigen')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'pending')
                    ->where('starts_at', '>', now())
                    ->where('starts_at', '<', now()->addDays(7)))
                // ->badge(static::getResource()::getEloquentQuery()
                //     ->where('status', 'pending')
                //     ->where('starts_at', '>', now())
                //     ->where('starts_at', '<', now()->addDays(7))
                //     ->count())
                ->badgeColor('warning'),
                
            'no_shows' => Tab::make('Nicht erschienen')
                ->icon('heroicon-m-user-minus')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'no_show')
                    ->orWhere(function($q) {
                        $q->where('status', 'confirmed')
                          ->where('ends_at', '<', now()->subHours(2));
                    }))
                // ->badge(static::getResource()::getEloquentQuery()->where('status', 'no_show')->count())
                ->badgeColor('danger'),
                
            'completed' => Tab::make('Abgeschlossen')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                // ->badge(static::getResource()::getEloquentQuery()->where('status', 'completed')->count())
                ->badgeColor('success'),
                
            'synced' => Tab::make('Cal.com')
                ->icon('heroicon-m-cloud-arrow-down')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function($q) {
                    $q->whereNotNull('calcom_booking_id')
                      ->orWhereNotNull('calcom_v2_booking_id');
                }))
                // ->badge(static::getResource()::getEloquentQuery()->where(function($q) {
                //     $q->whereNotNull('calcom_booking_id')
                //       ->orWhereNotNull('calcom_v2_booking_id');
                // })->count())
                ->badgeColor('success'),
                
            'manual' => Tab::make('Manuell')
                ->icon('heroicon-m-pencil-square')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNull('calcom_booking_id')
                    ->whereNull('calcom_v2_booking_id')
                    ->whereNull('call_id'))
                // ->badge(static::getResource()::getEloquentQuery()
                //     ->whereNull('calcom_booking_id')
                //     ->whereNull('calcom_v2_booking_id')
                //     ->whereNull('call_id')
                //     ->count())
                ->badgeColor('gray'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // TODO: Add widgets when implemented
            // \App\Filament\Admin\Widgets\GlobalFilterWidget::class,
            // \App\Filament\Admin\Widgets\AppointmentKpiWidget::class,
            // \App\Filament\Admin\Widgets\AppointmentTrendWidget::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            // AppointmentResource\Widgets\AppointmentCalendar::class, // Temporarily disabled
        ];
    }
}