<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use App\Jobs\UpdateCalcomEventTypeJob;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ViewService extends ViewRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Bearbeiten')
                ->icon('heroicon-m-pencil-square'),

            Actions\Action::make('syncCalcom')
                ->label('Cal.com Sync')
                ->icon('heroicon-m-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Service mit Cal.com synchronisieren')
                ->modalDescription(function () {
                    $bufferTime = $this->record->buffer_time_minutes ?? 0;
                    $price = number_format($this->record->price, 2);

                    return "Dies synchronisiert den Service \"{$this->record->name}\" mit dem Cal.com Event Type (ID: {$this->record->calcom_event_type_id}).\n\n" .
                        "Folgende Daten werden übertragen:\n" .
                        "• Name und Beschreibung\n" .
                        "• Dauer ({$this->record->duration_minutes} Min.)\n" .
                        "• Pufferzeit ({$bufferTime} Min.)\n" .
                        "• Preis (€{$price})\n\n" .
                        "Die Synchronisation erfolgt asynchron im Hintergrund.";
                })
                ->modalSubmitActionLabel('Jetzt synchronisieren')
                ->modalIcon('heroicon-o-arrow-path')
                ->action(function () {
                    try {
                        // Check if service has Cal.com Event Type ID
                        if (!$this->record->calcom_event_type_id) {
                            Notification::make()
                                ->title('Synchronisation fehlgeschlagen')
                                ->body('Dieser Service hat keine Cal.com Event Type ID.')
                                ->warning()
                                ->duration(5000)
                                ->send();
                            return;
                        }

                        // Check if sync is already pending
                        if ($this->record->sync_status === 'pending') {
                            Notification::make()
                                ->title('Synchronisation läuft bereits')
                                ->body('Eine Synchronisation für diesen Service ist bereits in Bearbeitung. Bitte warten Sie einen Moment.')
                                ->info()
                                ->duration(5000)
                                ->send();
                            return;
                        }

                        // Mark sync as pending
                        $this->record->update([
                            'sync_status' => 'pending',
                            'sync_error' => null
                        ]);

                        // Dispatch the sync job
                        UpdateCalcomEventTypeJob::dispatch($this->record);

                        Notification::make()
                            ->title('Synchronisation gestartet')
                            ->body('Die Synchronisation wurde in die Warteschlange gestellt und wird in Kürze durchgeführt.')
                            ->success()
                            ->duration(5000)
                            ->send();

                    } catch (\Exception $e) {
                        // Handle job dispatch failure
                        $this->record->update([
                            'sync_status' => 'error',
                            'sync_error' => 'Job dispatch failed: ' . $e->getMessage()
                        ]);

                        Notification::make()
                            ->title('Synchronisation fehlgeschlagen')
                            ->body('Fehler beim Starten der Synchronisation: ' . $e->getMessage())
                            ->danger()
                            ->duration(8000)
                            ->send();

                        \Illuminate\Support\Facades\Log::error('[Filament] Failed to dispatch Cal.com sync job', [
                            'service_id' => $this->record->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                })
                ->visible(fn () => (bool) $this->record->calcom_event_type_id),

            Actions\Action::make('duplicate')
                ->label('Duplizieren')
                ->icon('heroicon-m-document-duplicate')
                ->color('info')
                ->action(function () {
                    $newService = $this->record->replicate();
                    $newService->name = $this->record->name . ' (Kopie)';
                    $newService->is_active = false;
                    $newService->calcom_event_type_id = null;
                    $newService->external_id = null;
                    $newService->save();

                    Notification::make()
                        ->title('Service dupliziert')
                        ->body('Der Service wurde erfolgreich kopiert.')
                        ->success()
                        ->send();

                    return redirect()->route('filament.admin.resources.services.edit', $newService);
                }),

            Actions\DeleteAction::make()
                ->label('Löschen')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Split::make([
                    Section::make('Service Details')
                        ->description('Grundlegende Informationen zum Service')
                        ->icon('heroicon-o-briefcase')
                        ->schema([
                            Grid::make(2)->schema([
                                TextEntry::make('name')
                                    ->label('Service Name')
                                    ->icon('heroicon-m-briefcase')
                                    ->weight(FontWeight::Bold)
                                    
                                    ->size('lg'),

                                TextEntry::make('category')
                                    ->label('Kategorie')
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'consulting' => 'info',
                                        'support' => 'warning',
                                        'development' => 'success',
                                        'maintenance' => 'gray',
                                        'training' => 'indigo',
                                        'premium' => 'yellow',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'consulting' => '💼 Beratung',
                                        'support' => '🛠️ Support',
                                        'development' => '💻 Entwicklung',
                                        'maintenance' => '🔧 Wartung',
                                        'training' => '📚 Schulung',
                                        'premium' => '⭐ Premium',
                                        default => $state ?: '📋 Sonstige',
                                    }),
                            ]),

                            TextEntry::make('description')
                                ->label('Beschreibung')
                                ->placeholder('Keine Beschreibung')
                                ->columnSpanFull()
                                ->markdown(),

                            Grid::make(3)->schema([
                                TextEntry::make('company.name')
                                    ->label('Unternehmen')
                                    ->icon('heroicon-m-building-office-2')
                                    ->badge()
                                    ->color('primary'),

                                IconEntry::make('is_active')
                                    ->label('Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                IconEntry::make('is_online')
                                    ->label('Online buchbar')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-globe-alt')
                                    ->falseIcon('heroicon-o-building-storefront')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                            ]),
                        ]),

                    Section::make('Visualisierung')
                        ->description('Farben, Icons und Bilder')
                        ->icon('heroicon-o-paint-brush')
                        ->schema([
                            Grid::make(2)->schema([
                                TextEntry::make('color_code')
                                    ->label('Farbcode')
                                    ->placeholder('Standard')
                                    
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'info' : 'gray'),

                                TextEntry::make('icon')
                                    ->label('Icon')
                                    ->placeholder('Standard Icon')
                                    ,
                            ]),

                            TextEntry::make('image_url')
                                ->label('Bild URL')
                                ->placeholder('Kein Bild')
                                ->url(fn ($state) => $state, true)
                                ,

                            TextEntry::make('external_id')
                                ->label('Externe ID')
                                ->placeholder('Keine externe ID')
                                ,
                        ])
                        ->grow(false),
                ])
                    ->from('md')
                    ->columnSpanFull(),

                Section::make('Preise & Buchungsregeln')
                    ->description('Preisgestaltung und Buchungseinstellungen')
                    ->icon('heroicon-o-currency-euro')
                    ->schema([
                        Grid::make(3)->schema([
                            Fieldset::make('Preis & Zeit')
                                ->schema([
                                    TextEntry::make('price')
                                        ->label('Preis')
                                        ->money('EUR')
                                        ->weight(FontWeight::Bold)
                                        ->size('lg')
                                        ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                                    TextEntry::make('hourly_rate')
                                        ->label('Stundensatz')
                                        ->getStateUsing(fn ($record) =>
                                            $record->price > 0 && $record->duration_minutes > 0
                                                ? number_format($record->price / ($record->duration_minutes / 60), 2) . ' €/h'
                                                : 'N/A'
                                        )
                                        ->badge()
                                        ->color('info'),

                                    TextEntry::make('duration_minutes')
                                        ->label('Dauer')
                                        ->suffix(' Minuten')
                                        ->icon('heroicon-m-clock'),

                                    TextEntry::make('buffer_time_minutes')
                                        ->label('Pufferzeit')
                                        ->suffix(' Minuten')
                                        ->placeholder('Keine')
                                        ->icon('heroicon-m-pause'),
                                ]),

                            Fieldset::make('Anzahlung')
                                ->schema([
                                    IconEntry::make('deposit_required')
                                        ->label('Anzahlung erforderlich')
                                        ->boolean()
                                        ->trueIcon('heroicon-o-check-circle')
                                        ->falseIcon('heroicon-o-x-circle'),

                                    TextEntry::make('deposit_amount')
                                        ->label('Anzahlungsbetrag')
                                        ->money('EUR')
                                        ->visible(fn ($record) => $record->deposit_required)
                                        ->color('warning'),
                                ]),

                            Fieldset::make('Buchungsregeln')
                                ->schema([
                                    TextEntry::make('max_attendees')
                                        ->label('Max. Teilnehmer')
                                        ->suffix(' Personen')
                                        ->icon('heroicon-m-user-group'),

                                    IconEntry::make('requires_confirmation')
                                        ->label('Bestätigung erforderlich')
                                        ->boolean()
                                        ->trueIcon('heroicon-o-shield-check')
                                        ->falseIcon('heroicon-o-check'),

                                    IconEntry::make('allow_cancellation')
                                        ->label('Stornierung erlaubt')
                                        ->boolean()
                                        ->trueIcon('heroicon-o-x-mark')
                                        ->falseIcon('heroicon-o-lock-closed'),

                                    TextEntry::make('cancellation_hours_notice')
                                        ->label('Stornierungsfrist')
                                        ->suffix(' Stunden')
                                        ->visible(fn ($record) => $record->allow_cancellation)
                                        ->icon('heroicon-m-clock'),
                                ]),
                        ]),
                    ]),

                Section::make('Mitarbeiter & Zuweisungen')
                    ->description('Welche Mitarbeiter können diesen Service ausführen')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        TextEntry::make('allowed_staff')
                            ->label('Zugewiesene Mitarbeiter')
                            ->getStateUsing(fn ($record) =>
                                $record->allowedStaff->isEmpty()
                                    ? 'Keine Mitarbeiter zugewiesen'
                                    : $record->allowedStaff->pluck('name')->join(', ')
                            )
                            ->badge()
                            ->color(fn ($record) => $record->allowedStaff->isEmpty() ? 'gray' : 'success')
                            ->columnSpanFull(),

                        TextEntry::make('staff_count')
                            ->label('Anzahl Mitarbeiter')
                            ->getStateUsing(fn ($record) => $record->allowedStaff->count())
                            ->badge()
                            ->color('info'),
                    ]),

                Section::make('Buchungsstatistiken')
                    ->description('Übersicht über Terminhistorie und Performance')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed()
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('stats.total')
                                ->label('Gesamt')
                                ->getStateUsing(fn ($record) => $record->appointments()->count())
                                ->badge()
                                ->color('gray')
                                ->suffix(' Termine'),

                            TextEntry::make('stats.completed')
                                ->label('Abgeschlossen')
                                ->getStateUsing(fn ($record) =>
                                    $record->appointments()->where('status', 'completed')->count()
                                )
                                ->badge()
                                ->color('success')
                                ->suffix(' Termine'),

                            TextEntry::make('stats.cancelled')
                                ->label('Storniert')
                                ->getStateUsing(fn ($record) =>
                                    $record->appointments()->where('status', 'cancelled')->count()
                                )
                                ->badge()
                                ->color('danger')
                                ->suffix(' Termine'),

                            TextEntry::make('stats.revenue')
                                ->label('Umsatz')
                                ->getStateUsing(fn ($record) =>
                                    $record->appointments()
                                        ->where('status', 'completed')
                                        ->sum('price')
                                )
                                ->money('EUR')
                                ->badge()
                                ->color('success'),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('stats.this_month')
                                ->label('Diesen Monat')
                                ->getStateUsing(fn ($record) =>
                                    $record->appointments()
                                        ->whereMonth('starts_at', now()->month)
                                        ->whereYear('starts_at', now()->year)
                                        ->count()
                                )
                                ->badge()
                                ->color('info')
                                ->suffix(' Termine'),

                            TextEntry::make('stats.last_month')
                                ->label('Letzter Monat')
                                ->getStateUsing(fn ($record) =>
                                    $record->appointments()
                                        ->whereMonth('starts_at', now()->subMonth()->month)
                                        ->whereYear('starts_at', now()->subMonth()->year)
                                        ->count()
                                )
                                ->badge()
                                ->color('gray')
                                ->suffix(' Termine'),

                            TextEntry::make('stats.last_booking')
                                ->label('Letzte Buchung')
                                ->getStateUsing(function ($record) {
                                    $last = $record->appointments()
                                        ->latest('created_at')
                                        ->first();

                                    return $last ? $last->created_at->diffForHumans() : 'Keine Buchungen';
                                })
                                ->badge()
                                ->color('info'),
                        ]),
                    ]),

                Section::make('Cal.com Integration')
                    ->description(fn ($record) =>
                        $record->calcom_event_type_id
                            ? '✅ Service ist mit Cal.com synchronisiert'
                            : '⚠️ Service ist NICHT mit Cal.com verknüpft'
                    )
                    ->icon('heroicon-o-link')
                    ->collapsed(fn ($record) => !$record->calcom_event_type_id)
                    ->headerActions([
                        Action::make('verify_integration')
                            ->label('Integration prüfen')
                            ->icon('heroicon-m-shield-check')
                            ->color('info')
                            ->action(function ($record) {
                                $issues = [];

                                if (!$record->calcom_event_type_id) {
                                    $issues[] = 'Keine Event Type ID';
                                }

                                if (!$record->company?->calcom_team_id) {
                                    $issues[] = 'Company hat keine Team ID';
                                }

                                if ($record->calcom_event_type_id) {
                                    $mapping = DB::table('calcom_event_mappings')
                                        ->where('calcom_event_type_id', $record->calcom_event_type_id)
                                        ->first();

                                    if (!$mapping) {
                                        $issues[] = 'Event Mapping fehlt';
                                    } elseif ($record->company && $mapping->calcom_team_id != $record->company->calcom_team_id) {
                                        $issues[] = "Team ID Mismatch: {$mapping->calcom_team_id} ≠ {$record->company->calcom_team_id}";
                                    }
                                }

                                if (empty($issues)) {
                                    Notification::make()
                                        ->title('✅ Integration OK')
                                        ->body('Cal.com Integration ist korrekt konfiguriert.')
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('⚠️ Integration Probleme')
                                        ->body(implode("\n", $issues))
                                        ->warning()
                                        ->send();
                                }
                            })
                    ])
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('calcom_event_type_id')
                                ->label('Event Type ID')
                                ->placeholder('Nicht verknüpft')
                                ->badge()
                                ->color(fn ($state) => $state ? 'success' : 'warning')
                                ->url(function ($record) {
                                    if (!$record->calcom_event_type_id || !$record->company?->calcom_team_id) {
                                        return null;
                                    }
                                    return "https://app.cal.com/event-types/{$record->calcom_event_type_id}";
                                }, shouldOpenInNewTab: true)
                                ->icon(fn ($state) => $state ? 'heroicon-m-link' : null),

                            TextEntry::make('company.calcom_team_id')
                                ->label('Cal.com Team ID')
                                ->placeholder('Nicht konfiguriert')
                                ->badge()
                                ->color(fn ($state) => $state ? 'primary' : 'danger')
                                ->helperText('Multi-Tenant Isolation'),

                            TextEntry::make('mapping_status')
                                ->label('Event Mapping')
                                ->getStateUsing(function ($record) {
                                    if (!$record->calcom_event_type_id) {
                                        return 'Keine Verknüpfung';
                                    }

                                    $mapping = DB::table('calcom_event_mappings')
                                        ->where('calcom_event_type_id', $record->calcom_event_type_id)
                                        ->first();

                                    if (!$mapping) {
                                        return '❌ Mapping fehlt!';
                                    }

                                    if ($record->company && $mapping->calcom_team_id != $record->company->calcom_team_id) {
                                        return "⚠️ Team Mismatch! ({$mapping->calcom_team_id})";
                                    }

                                    return '✅ Korrekt';
                                })
                                ->badge()
                                ->color(function ($record) {
                                    if (!$record->calcom_event_type_id) return 'gray';

                                    $mapping = DB::table('calcom_event_mappings')
                                        ->where('calcom_event_type_id', $record->calcom_event_type_id)
                                        ->first();

                                    if (!$mapping) return 'danger';
                                    if ($record->company && $mapping->calcom_team_id != $record->company->calcom_team_id) return 'warning';
                                    return 'success';
                                }),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('last_calcom_sync')
                                ->label('Letzter Sync')
                                ->dateTime('d.m.Y H:i:s')
                                ->placeholder('Noch nie synchronisiert')
                                ->helperText(fn ($record) =>
                                    $record->last_calcom_sync
                                        ? $record->last_calcom_sync->diffForHumans()
                                        : null
                                )
                                ->icon('heroicon-m-clock'),

                            TextEntry::make('sync_error')
                                ->label('Sync Fehler')
                                ->placeholder('Keine Fehler')
                                ->color('danger')
                                ->visible(fn ($record) => !empty($record->sync_error)),
                        ]),

                        TextEntry::make('metadata')
                            ->label('Zusätzliche Metadaten')
                            ->getStateUsing(fn ($record) =>
                                $record->metadata
                                    ? collect($record->metadata)
                                        ->map(fn ($value, $key) => "$key: $value")
                                        ->join(', ')
                                    : null
                            )
                            ->placeholder('Keine Metadaten')
                            ->columnSpanFull(),
                    ]),

                Section::make('System Information')
                    ->description('Erstellungs- und Änderungsdaten')
                    ->icon('heroicon-o-information-circle')
                    ->collapsed()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('sort_order')
                                ->label('Sortierung')
                                ->badge()
                                ->color('gray'),

                            TextEntry::make('created_at')
                                ->label('Erstellt am')
                                ->dateTime('d.m.Y H:i:s')
                                ->icon('heroicon-m-calendar'),

                            TextEntry::make('updated_at')
                                ->label('Zuletzt geändert')
                                ->dateTime('d.m.Y H:i:s')
                                ->icon('heroicon-m-pencil'),
                        ]),

                        TextEntry::make('appointments_count')
                            ->label('Gesamtbuchungen')
                            ->getStateUsing(fn ($record) => $record->appointments()->count())
                            ->badge()
                            ->color('info')
                            ->suffix(' Termine'),
                    ]),
            ]);
    }
}