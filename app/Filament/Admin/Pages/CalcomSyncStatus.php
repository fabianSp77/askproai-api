<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Company;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Actions;
use Filament\Notifications\Notification;

class CalcomSyncStatus extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Einrichtung & Konfiguration';
    protected static ?string $navigationLabel = 'Cal.com Sync Status';
    protected static ?int $navigationSort = 5;
    
    protected static string $view = 'filament.admin.pages.calcom-sync-status';
    
    public function mount(): void
    {
        // Initialize any necessary data
    }
    
    public function getSyncStatistics(): array
    {
        $stats = Cache::remember('calcom_sync_stats', 300, function () {
            $now = Carbon::now();
            $lastHour = $now->copy()->subHour();
            $last24Hours = $now->copy()->subDay();
            $lastWeek = $now->copy()->subWeek();
            
            return [
                'total_synced' => Appointment::whereNotNull('calcom_booking_id')
                    ->orWhereNotNull('calcom_v2_booking_id')
                    ->count(),
                    
                'synced_last_hour' => Appointment::where(function($q) {
                        $q->whereNotNull('calcom_booking_id')
                          ->orWhereNotNull('calcom_v2_booking_id');
                    })
                    ->where('updated_at', '>=', $lastHour)
                    ->count(),
                    
                'synced_last_24h' => Appointment::where(function($q) {
                        $q->whereNotNull('calcom_booking_id')
                          ->orWhereNotNull('calcom_v2_booking_id');
                    })
                    ->where('updated_at', '>=', $last24Hours)
                    ->count(),
                    
                'synced_last_week' => Appointment::where(function($q) {
                        $q->whereNotNull('calcom_booking_id')
                          ->orWhereNotNull('calcom_v2_booking_id');
                    })
                    ->where('updated_at', '>=', $lastWeek)
                    ->count(),
                    
                'pending_sync' => Appointment::whereNull('calcom_booking_id')
                    ->whereNull('calcom_v2_booking_id')
                    ->where('created_at', '>=', $last24Hours)
                    ->count(),
                    
                'failed_syncs' => DB::table('failed_jobs')
                    ->where('payload', 'LIKE', '%SyncCalcomBookingsJob%')
                    ->count(),
                    
                'last_sync' => Cache::get('last_calcom_sync_' . auth()->user()->company_id),
                'next_sync' => Cache::get('next_calcom_sync_' . auth()->user()->company_id),
            ];
        });
        
        return $stats;
    }
    
    public function getRecentSyncErrors(): array
    {
        return DB::table('failed_jobs')
            ->where('payload', 'LIKE', '%SyncCalcomBookingsJob%')
            ->orWhere('payload', 'LIKE', '%ProcessCalcomWebhookJob%')
            ->orderBy('failed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return [
                    'id' => $job->id,
                    'failed_at' => Carbon::parse($job->failed_at),
                    'exception' => substr($job->exception, 0, 200) . '...',
                    'job_name' => $payload['displayName'] ?? 'Unknown',
                ];
            })
            ->toArray();
    }
    
    public function getWebhookActivity(): array
    {
        return Cache::remember('calcom_webhook_activity', 60, function () {
            $logs = [];
            
            // Hier könnten Sie Webhook-Aktivitäten aus Logs oder einer separaten Tabelle laden
            // Für jetzt zeigen wir Beispieldaten
            
            return $logs;
        });
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manual_sync')
                ->label('Manueller Sync')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Cal.com Synchronisation starten')
                ->modalDescription('Möchten Sie eine manuelle Synchronisation aller Termine starten?')
                ->modalSubmitActionLabel('Ja, synchronisieren')
                ->action(function () {
                    $company = auth()->user()->company;
                    
                    if (!$company) {
                        Notification::make()
                            ->title('Fehler')
                            ->body('Keine Company zugeordnet.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $apiKey = $company->calcom_api_key ?? config('services.calcom.api_key');
                    
                    if (!$apiKey) {
                        Notification::make()
                            ->title('Konfigurationsfehler')
                            ->body('Kein Cal.com API Key konfiguriert.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    \App\Jobs\SyncCalcomBookingsJob::dispatch($company, $apiKey);
                    
                    Cache::put('last_calcom_sync_' . $company->id, now(), 3600);
                    Cache::put('next_calcom_sync_' . $company->id, now()->addHour(), 3600);
                    
                    Notification::make()
                        ->title('Synchronisation gestartet')
                        ->body('Die Cal.com Synchronisation wurde gestartet.')
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('clear_errors')
                ->label('Fehler löschen')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Fehlgeschlagene Jobs löschen')
                ->modalDescription('Möchten Sie alle fehlgeschlagenen Sync-Jobs löschen?')
                ->modalSubmitActionLabel('Ja, löschen')
                ->action(function () {
                    DB::table('failed_jobs')
                        ->where('payload', 'LIKE', '%SyncCalcomBookingsJob%')
                        ->orWhere('payload', 'LIKE', '%ProcessCalcomWebhookJob%')
                        ->delete();
                        
                    Cache::forget('calcom_sync_stats');
                    
                    Notification::make()
                        ->title('Fehler gelöscht')
                        ->body('Alle fehlgeschlagenen Sync-Jobs wurden gelöscht.')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::query()
                    ->where(function($q) {
                        $q->whereNotNull('calcom_booking_id')
                          ->orWhereNotNull('calcom_v2_booking_id');
                    })
                    ->orderBy('updated_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Termin')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('calcom_booking_id')
                    ->label('Cal.com ID')
                    ->badge()
                    ->color('info')
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('meta.calcom_sync.last_synced_at')
                    ->label('Letzte Sync')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('has_webhook')
                    ->label('Webhook')
                    ->state(fn ($record) => isset($record->meta['calcom_webhook']))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'confirmed' => 'Bestätigt',
                        'pending' => 'Ausstehend',
                        'cancelled' => 'Abgesagt',
                        'completed' => 'Abgeschlossen',
                    ]),
                    
                Tables\Filters\Filter::make('synced_today')
                    ->query(fn ($query) => $query->whereDate('updated_at', today()))
                    ->label('Heute synchronisiert'),
                    
                Tables\Filters\Filter::make('has_errors')
                    ->query(fn ($query) => $query->whereJsonContains('meta->sync_errors', true))
                    ->label('Mit Fehlern'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}