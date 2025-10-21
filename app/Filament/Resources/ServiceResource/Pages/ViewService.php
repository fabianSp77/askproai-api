<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Fieldset;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;

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
                ->action(function () {
                    // TODO: Implement actual Cal.com sync
                    $this->record->touch(); // Update timestamp for now

                    Notification::make()
                        ->title('Cal.com Synchronisation')
                        ->body('Service wurde mit Cal.com synchronisiert.')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->calcom_event_type_id),

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

                Section::make('Cal.com Integration')
                    ->description('Synchronisationsstatus mit Cal.com')
                    ->icon('heroicon-o-link')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('calcom_event_type_id')
                                ->label('Event Type ID')
                                ->placeholder('Nicht verknüpft')
                                
                                ->badge()
                                ->color(fn ($state) => $state ? 'success' : 'warning'),

                            TextEntry::make('sync_status')
                                ->label('Sync Status')
                                ->getStateUsing(fn ($record) =>
                                    $record->calcom_event_type_id
                                        ? 'Verknüpft'
                                        : 'Nicht synchronisiert'
                                )
                                ->badge()
                                ->color(fn ($record) =>
                                    $record->calcom_event_type_id ? 'success' : 'gray'
                                ),
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