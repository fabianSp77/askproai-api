<?php

namespace App\Filament\Widgets;

use App\Models\Integration;
use App\Services\IntegrationService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Traits\DisablesPollingOnMobile;

class IntegrationMonitorWidget extends BaseWidget
{
    use DisablesPollingOnMobile;
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Integration Monitoring';

    public function table(Table $table): Table
    {
        try {
        return $table
            ->query(
                Integration::query()
                    ->select('id', 'company_id', 'name', 'type', 'provider', 'status', 'health_status', 'health_score', 'error_count', 'last_sync_at', 'last_error', 'next_sync_at')
                    ->with(['company:id,name'])
                    ->where(function (Builder $query) {
                        $query->where('health_status', '!=', Integration::HEALTH_HEALTHY)
                            ->orWhere('status', Integration::STATUS_ERROR)
                            ->orWhere('error_count', '>', 0)
                            ->orWhereNull('last_sync_at')
                            ->orWhere('last_sync_at', '<', now()->subHours(24));
                    })
                    ->limit(50)
            )
            ->heading('Integration Überwachung')
            ->description('Integrationen die Aufmerksamkeit benötigen')
            ->columns([
                TextColumn::make('name')
                    ->label('Integration')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->provider_name ?? ''),

                TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->placeholder('Global')
                    ->color('primary'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn ($record) => $record->status_color ?? 'gray'),

                TextColumn::make('health_status')
                    ->label('Gesundheit')
                    ->formatStateUsing(fn ($state, $record) => ($record->health_icon ?? '❓') . ' ' . ($record->health_score ?? 0) . '%')
                    ->color(fn ($record) => $record->health_color ?? 'gray'),

                TextColumn::make('error_count')
                    ->label('Fehler')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "❌ {$state}" : "✅ 0")
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->alignCenter(),

                TextColumn::make('last_sync_at')
                    ->label('Letzte Sync')
                    ->formatStateUsing(fn ($state) => $state ? $state->diffForHumans() : 'Nie')
                    ->color(fn ($state) => !$state || $state < now()->subHours(24) ? 'danger' : 'gray'),

                TextColumn::make('last_error')
                    ->label('Letzter Fehler')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->last_error ?? '')
                    ->placeholder('Kein Fehler'),

                TextColumn::make('next_sync_at')
                    ->label('Nächste Sync')
                    ->formatStateUsing(fn ($state) => $state ? $state->diffForHumans() : 'Nicht geplant')
                    ->color(fn ($state) => $state && $state->isPast() ? 'warning' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Integration::STATUS_ERROR => 'Fehler',
                        Integration::STATUS_INACTIVE => 'Inaktiv',
                        Integration::STATUS_SUSPENDED => 'Gesperrt',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('health_status')
                    ->label('Gesundheit')
                    ->options([
                        Integration::HEALTH_UNHEALTHY => '❌ Ungesund',
                        Integration::HEALTH_DEGRADED => '⚠️ Beeinträchtigt',
                        Integration::HEALTH_UNKNOWN => '❓ Unbekannt',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('has_errors')
                    ->label('Hat Fehler')
                    ->queries(
                        true: fn (Builder $query) => $query->where('error_count', '>', 0),
                        false: fn (Builder $query) => $query->where('error_count', 0),
                    ),

                Tables\Filters\TernaryFilter::make('overdue_sync')
                    ->label('Sync überfällig')
                    ->queries(
                        true: fn (Builder $query) => $query->where(function ($q) {
                            $q->whereNull('last_sync_at')
                                ->orWhere('last_sync_at', '<', now()->subHours(24));
                        }),
                        false: fn (Builder $query) => $query->where('last_sync_at', '>=', now()->subHours(24)),
                    ),
            ])
            ->actions([
                Action::make('test')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (Integration $record) {
                        try {
                            $service = new IntegrationService();
                            $success = $service->testConnection($record);

                            if ($success) {
                                $record->update([
                                    'status' => Integration::STATUS_ACTIVE,
                                    'last_success_at' => now(),
                                ]);
                                $record->updateHealthStatus();

                                Notification::make()
                                    ->title('Verbindung erfolgreich')
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            $record->markSyncError($e->getMessage());

                            Notification::make()
                                ->title('Verbindungsfehler')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('sync')
                    ->label('Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => $record->canSync())
                    ->action(function (Integration $record) {
                        try {
                            $service = new IntegrationService();
                            $result = $service->sync($record);

                            Notification::make()
                                ->title('Synchronisation erfolgreich')
                                ->body('Synchronisiert: ' . json_encode($result))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Synchronisationsfehler')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('reset_errors')
                    ->label('Fehler zurücksetzen')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->error_count > 0)
                    ->action(function (Integration $record) {
                        $record->update([
                            'error_count' => 0,
                            'last_error' => null,
                            'status' => Integration::STATUS_ACTIVE,
                        ]);
                        $record->updateHealthStatus();

                        Notification::make()
                            ->title('Fehler zurückgesetzt')
                            ->success()
                            ->send();
                    }),

                Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => "/admin/integrations/{$record->id}")
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_test')
                    ->label('Alle testen')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function ($records) {
                        $service = new IntegrationService();
                        $success = 0;
                        $failed = 0;

                        foreach ($records as $record) {
                            try {
                                if ($service->testConnection($record)) {
                                    $record->update(['status' => Integration::STATUS_ACTIVE]);
                                    $record->updateHealthStatus();
                                    $success++;
                                } else {
                                    $failed++;
                                }
                            } catch (\Exception $e) {
                                $record->markSyncError($e->getMessage());
                                $failed++;
                            }
                        }

                        Notification::make()
                            ->title('Tests abgeschlossen')
                            ->body("Erfolgreich: {$success}, Fehlgeschlagen: {$failed}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\BulkAction::make('reset_all_errors')
                    ->label('Alle Fehler zurücksetzen')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update([
                                'error_count' => 0,
                                'last_error' => null,
                                'status' => Integration::STATUS_ACTIVE,
                            ]);
                            $record->updateHealthStatus();
                        }

                        Notification::make()
                            ->title('Alle Fehler zurückgesetzt')
                            ->success()
                            ->send();
                    }),
            ])
            ->striped();

        // Apply conditional polling based on device type
        return $this->configureTablePolling($table);
        } catch (\Exception $e) {
            \Log::error('IntegrationMonitorWidget Error: ' . $e->getMessage());
            return $table
                ->query(Integration::query()->whereRaw('0=1')) // Empty query on error
                ->columns([]);
        }
    }
}