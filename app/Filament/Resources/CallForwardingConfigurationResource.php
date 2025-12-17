<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\CallForwardingConfigurationResource\Pages;
use App\Models\CallForwardingConfiguration;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CallForwardingConfigurationResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = CallForwardingConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-up-right';

    protected static ?string $navigationGroup = 'Einstellungen';

    protected static ?string $navigationLabel = 'Anrufweiterleitung';

    protected static ?string $modelLabel = 'Anrufweiterleitungs-Konfiguration';

    protected static ?string $pluralModelLabel = 'Anrufweiterleitungs-Konfigurationen';

    protected static ?int $navigationSort = 50;

    public static function getNavigationBadge(): ?string
    {
        return static::getCachedBadge(function() {
            return static::getModel()::where('is_active', true)->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ═══════════════════════════════════════════════════════════════
                // Section 1: Basis-Einstellungen
                // ═══════════════════════════════════════════════════════════════
                Forms\Components\Section::make('Basis-Einstellungen')
                    ->icon('heroicon-o-cog')
                    ->description('Grundlegende Konfiguration für die Anrufweiterleitung')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('branch_id')
                                    ->label('Filiale')
                                    ->relationship('branch', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Filiale für die diese Weiterleitung gilt')
                                    ->unique(ignoreRecord: true)
                                    ->validationMessages([
                                        'unique' => 'Für diese Filiale existiert bereits eine Weiterleitungs-Konfiguration.',
                                    ])
                                    ->columnSpan(1),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('✅ Aktiviert')
                                    ->default(true)
                                    ->helperText('Aktivieren um Weiterleitung zu nutzen')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('timezone')
                                    ->label('Zeitzone')
                                    ->options([
                                        'Europe/Berlin' => 'Europe/Berlin (MEZ/MESZ)',
                                        'Europe/Vienna' => 'Europe/Vienna (MEZ/MESZ)',
                                        'Europe/Zurich' => 'Europe/Zurich (MEZ/MESZ)',
                                        'UTC' => 'UTC',
                                    ])
                                    ->default('Europe/Berlin')
                                    ->required()
                                    ->native(false)
                                    ->helperText('Zeitzone für Zeitfenster-Berechnungen')
                                    ->columnSpan(1),
                            ]),
                    ]),

                // ═══════════════════════════════════════════════════════════════
                // Section 2: Weiterleitungsregeln (Repeater)
                // ═══════════════════════════════════════════════════════════════
                Forms\Components\Section::make('Weiterleitungsregeln')
                    ->icon('heroicon-o-arrow-path')
                    ->description('Definieren Sie situationsbasierte Weiterleitungsregeln')
                    ->schema([
                        Forms\Components\Repeater::make('forwarding_rules')
                            ->label('')
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\Select::make('trigger')
                                            ->label('Auslöser')
                                            ->options([
                                                CallForwardingConfiguration::TRIGGER_NO_AVAILABILITY => '📅 Keine Verfügbarkeit',
                                                CallForwardingConfiguration::TRIGGER_AFTER_HOURS => '🕐 Außerhalb Öffnungszeiten',
                                                CallForwardingConfiguration::TRIGGER_BOOKING_FAILED => '❌ Buchung fehlgeschlagen',
                                                CallForwardingConfiguration::TRIGGER_HIGH_CALL_VOLUME => '📞 Hohe Anruflast',
                                                CallForwardingConfiguration::TRIGGER_MANUAL => '✋ Manuell',
                                            ])
                                            ->required()
                                            ->native(false)
                                            ->helperText('Wann soll weitergeleitet werden?')
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('target_number')
                                            ->label('Ziel-Nummer')
                                            ->tel()
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('+49151123456789')
                                            ->helperText('E.164 Format (z.B. +4915112345678)')
                                            ->rule('regex:/^\+[1-9]\d{1,14}$/')
                                            ->validationMessages([
                                                'regex' => 'Bitte geben Sie eine gültige Telefonnummer im E.164 Format ein (z.B. +4915112345678).',
                                            ])
                                            ->prefixIcon('heroicon-o-phone')
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('priority')
                                            ->label('Priorität')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->helperText('Niedrigere Zahl = höhere Priorität')
                                            ->columnSpan(1),
                                    ]),

                                Forms\Components\KeyValue::make('conditions')
                                    ->label('Zusätzliche Bedingungen (Optional)')
                                    ->keyLabel('Bedingung')
                                    ->valueLabel('Wert')
                                    ->addActionLabel('Bedingung hinzufügen')
                                    ->reorderable()
                                    ->helperText('Erweiterte Bedingungen für diese Regel (z.B. day: monday, time_after: 18:00)')
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('➕ Weiterleitungsregel hinzufügen')
                            ->reorderable()
                            ->collapsible()
                            ->collapsed(false)
                            ->itemLabel(fn (array $state): ?string =>
                                isset($state['trigger']) && isset($state['target_number'])
                                    ? match ($state['trigger']) {
                                        CallForwardingConfiguration::TRIGGER_NO_AVAILABILITY => "📅 Keine Verfügbarkeit → {$state['target_number']}",
                                        CallForwardingConfiguration::TRIGGER_AFTER_HOURS => "🕐 Nach Geschäftsschluss → {$state['target_number']}",
                                        CallForwardingConfiguration::TRIGGER_BOOKING_FAILED => "❌ Buchung fehlgeschlagen → {$state['target_number']}",
                                        CallForwardingConfiguration::TRIGGER_HIGH_CALL_VOLUME => "📞 Hohe Anruflast → {$state['target_number']}",
                                        CallForwardingConfiguration::TRIGGER_MANUAL => "✋ Manuell → {$state['target_number']}",
                                        default => "Regel → {$state['target_number']}",
                                    }
                                    : 'Neue Regel'
                            )
                            ->minItems(1)
                            ->maxItems(10)
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),

                // ═══════════════════════════════════════════════════════════════
                // Section 3: Fallback-Nummern
                // ═══════════════════════════════════════════════════════════════
                Forms\Components\Section::make('Fallback-Nummern')
                    ->icon('heroicon-o-phone')
                    ->description('Standard- und Notfall-Weiterleitungsnummern')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('default_forwarding_number')
                                    ->label('Standard-Weiterleitungsnummer')
                                    ->tel()
                                    ->maxLength(50)
                                    ->placeholder('+49151123456789')
                                    ->helperText('Fallback wenn keine Regel greift')
                                    ->rule('nullable|regex:/^\+[1-9]\d{1,14}$/')
                                    ->validationMessages([
                                        'regex' => 'Bitte geben Sie eine gültige Telefonnummer im E.164 Format ein.',
                                    ])
                                    ->prefixIcon('heroicon-o-phone')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('emergency_forwarding_number')
                                    ->label('Notfall-Weiterleitungsnummer')
                                    ->tel()
                                    ->maxLength(50)
                                    ->placeholder('+49151123456789')
                                    ->helperText('Bei kritischen Fehlern')
                                    ->rule('nullable|regex:/^\+[1-9]\d{1,14}$/')
                                    ->validationMessages([
                                        'regex' => 'Bitte geben Sie eine gültige Telefonnummer im E.164 Format ein.',
                                    ])
                                    ->prefixIcon('heroicon-o-phone-x-mark')
                                    ->columnSpan(1),
                            ]),
                    ]),

                // ═══════════════════════════════════════════════════════════════
                // Section 4: Aktive Zeiten (Optional)
                // ═══════════════════════════════════════════════════════════════
                Forms\Components\Section::make('Aktive Zeiten')
                    ->icon('heroicon-o-clock')
                    ->description('Beschränken Sie die Weiterleitung auf bestimmte Zeiten (Optional)')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('active_hours_info')
                            ->label('Zeitfenster-Konfiguration')
                            ->content('**Format**: JSON mit Wochentagen als Keys und Zeitbereichen als Values.

**Beispiel**:
```json
{
  "monday": ["09:00-17:00"],
  "tuesday": ["09:00-17:00"],
  "friday": ["09:00-15:00"]
}
```

**Hinweis**: Wenn leer, ist die Weiterleitung 24/7 aktiv.')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('active_hours')
                            ->label('Aktive Zeiten (JSON)')
                            ->rows(8)
                            ->placeholder('{"monday": ["09:00-17:00"], "tuesday": ["09:00-17:00"]}')
                            ->helperText('JSON-Format: {"weekday": ["HH:MM-HH:MM"]}')
                            ->rule('nullable|json')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->weight('bold')
                    ->icon('heroicon-o-building-office')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('rules_count')
                    ->label('Regeln')
                    ->formatStateUsing(fn (CallForwardingConfiguration $record): string =>
                        count($record->forwarding_rules ?? [])
                    )
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-list-bullet')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Custom sorting by JSON array length
                        return $query->selectRaw('*, json_array_length(forwarding_rules) as rules_count')
                            ->orderBy('rules_count', $direction);
                    }),

                Tables\Columns\TextColumn::make('default_forwarding_number')
                    ->label('Standard-Nummer')
                    ->icon('heroicon-o-phone')
                    ->copyable()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string =>
                        $state ? $state : '—'
                    )
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('timezone')
                    ->label('Zeitzone')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-globe-alt')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle anzeigen')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),

                Tables\Filters\TernaryFilter::make('has_rules')
                    ->label('Regeln')
                    ->queries(
                        true: fn (Builder $query) => $query->whereRaw('json_array_length(forwarding_rules) > 0'),
                        false: fn (Builder $query) => $query->whereRaw('json_array_length(forwarding_rules) = 0'),
                    )
                    ->placeholder('Alle anzeigen')
                    ->trueLabel('Mit Regeln')
                    ->falseLabel('Ohne Regeln'),

                Tables\Filters\TernaryFilter::make('has_fallback')
                    ->label('Fallback')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('default_forwarding_number'),
                        false: fn (Builder $query) => $query->whereNull('default_forwarding_number'),
                    )
                    ->placeholder('Alle anzeigen')
                    ->trueLabel('Mit Fallback-Nummer')
                    ->falseLabel('Ohne Fallback-Nummer'),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (CallForwardingConfiguration $record): string =>
                            $record->is_active ? 'Deaktivieren' : 'Aktivieren'
                        )
                        ->icon(fn (CallForwardingConfiguration $record): string =>
                            $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle'
                        )
                        ->color(fn (CallForwardingConfiguration $record): string =>
                            $record->is_active ? 'danger' : 'success'
                        )
                        ->action(function (CallForwardingConfiguration $record): void {
                            $record->is_active = !$record->is_active;
                            $record->save();
                        })
                        ->successNotificationTitle(fn (CallForwardingConfiguration $record): string =>
                            $record->is_active ? 'Weiterleitung aktiviert' : 'Weiterleitung deaktiviert'
                        )
                        ->requiresConfirmation(),

                    Tables\Actions\Action::make('clone_to_branch')
                        ->label('Zu anderer Filiale kopieren')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('target_branch_id')
                                ->label('Ziel-Filiale')
                                ->options(fn (CallForwardingConfiguration $record) =>
                                    Branch::where('company_id', $record->company_id)
                                        ->where('id', '!=', $record->branch_id)
                                        ->whereNotIn('id', CallForwardingConfiguration::pluck('branch_id'))
                                        ->pluck('name', 'id')
                                )
                                ->required()
                                ->searchable()
                                ->helperText('Wählen Sie die Filiale für die Kopie'),
                        ])
                        ->action(function (CallForwardingConfiguration $record, array $data): void {
                            $clone = $record->replicate();
                            $clone->branch_id = $data['target_branch_id'];
                            $clone->save();
                        })
                        ->successNotificationTitle('Erfolgreich kopiert')
                        ->requiresConfirmation(),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each->update(['is_active' => true]);
                        })
                        ->successNotificationTitle('Weiterleitungen aktiviert')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each->update(['is_active' => false]);
                        })
                        ->successNotificationTitle('Weiterleitungen deaktiviert')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen (Soft Delete)')
                        ->requiresConfirmation(),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('Endgültig löschen')
                        ->requiresConfirmation(),

                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Wiederherstellen'),
                ])
                ->label('Massenaktionen')
                ->icon('heroicon-o-squares-plus')
                ->color('primary'),
            ])
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['branch'])
            )
            ->recordUrl(fn (CallForwardingConfiguration $record): string =>
                CallForwardingConfigurationResource::getUrl('view', ['record' => $record])
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Hauptinformationen')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('ID')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('branch.name')
                                    ->label('Filiale')
                                    ->icon('heroicon-o-building-office')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('is_active')
                                    ->label('Status')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktiv' : 'Inaktiv')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),
                            ]),

                        Infolists\Components\TextEntry::make('timezone')
                            ->label('Zeitzone')
                            ->icon('heroicon-o-globe-alt')
                            ->badge(),
                    ]),

                Infolists\Components\Section::make('Weiterleitungsregeln')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('forwarding_rules')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('trigger')
                                            ->label('Auslöser')
                                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                                CallForwardingConfiguration::TRIGGER_NO_AVAILABILITY => '📅 Keine Verfügbarkeit',
                                                CallForwardingConfiguration::TRIGGER_AFTER_HOURS => '🕐 Nach Geschäftsschluss',
                                                CallForwardingConfiguration::TRIGGER_BOOKING_FAILED => '❌ Buchung fehlgeschlagen',
                                                CallForwardingConfiguration::TRIGGER_HIGH_CALL_VOLUME => '📞 Hohe Anruflast',
                                                CallForwardingConfiguration::TRIGGER_MANUAL => '✋ Manuell',
                                                default => $state,
                                            })
                                            ->badge()
                                            ->color('info'),

                                        Infolists\Components\TextEntry::make('target_number')
                                            ->label('Ziel-Nummer')
                                            ->icon('heroicon-o-phone')
                                            ->copyable(),

                                        Infolists\Components\TextEntry::make('priority')
                                            ->label('Priorität')
                                            ->badge()
                                            ->color('warning'),

                                        Infolists\Components\TextEntry::make('conditions')
                                            ->label('Bedingungen')
                                            ->formatStateUsing(function ($state): string {
                                                if (!$state || !is_array($state) || empty($state)) {
                                                    return '—';
                                                }
                                                return collect($state)
                                                    ->map(fn ($value, $key) => "$key: $value")
                                                    ->join(', ');
                                            })
                                            ->placeholder('Keine'),
                                    ]),
                            ])
                            ->placeholder('Keine Regeln konfiguriert')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Fallback-Nummern')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('default_forwarding_number')
                                    ->label('Standard-Weiterleitungsnummer')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->placeholder('Nicht konfiguriert'),

                                Infolists\Components\TextEntry::make('emergency_forwarding_number')
                                    ->label('Notfall-Weiterleitungsnummer')
                                    ->icon('heroicon-o-phone-x-mark')
                                    ->copyable()
                                    ->placeholder('Nicht konfiguriert'),
                            ]),
                    ])
                    ->visible(fn (CallForwardingConfiguration $record): bool =>
                        $record->default_forwarding_number || $record->emergency_forwarding_number
                    ),

                Infolists\Components\Section::make('Aktive Zeiten')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Infolists\Components\TextEntry::make('active_hours')
                            ->label('')
                            ->formatStateUsing(function ($state): string {
                                if (!$state) {
                                    return '24/7 aktiv';
                                }

                                if (is_string($state)) {
                                    $state = json_decode($state, true);
                                }

                                if (!is_array($state) || empty($state)) {
                                    return '24/7 aktiv';
                                }

                                return collect($state)
                                    ->map(fn ($times, $day) => "**{$day}**: " . (is_array($times) ? implode(', ', $times) : $times))
                                    ->join("\n");
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Zeitstempel')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i')
                                    ->helperText(fn (CallForwardingConfiguration $record): string =>
                                        $record->created_at->diffForHumans()
                                    ),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Aktualisiert am')
                                    ->dateTime('d.m.Y H:i')
                                    ->helperText(fn (CallForwardingConfiguration $record): string =>
                                        $record->updated_at->diffForHumans()
                                    ),

                                Infolists\Components\TextEntry::make('deleted_at')
                                    ->label('Gelöscht am')
                                    ->dateTime('d.m.Y H:i')
                                    ->helperText(fn (CallForwardingConfiguration $record): ?string =>
                                        $record->deleted_at ? $record->deleted_at->diffForHumans() : null
                                    )
                                    ->placeholder('Nicht gelöscht')
                                    ->color('danger')
                                    ->visible(fn (CallForwardingConfiguration $record): bool => $record->deleted_at !== null),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallForwardingConfigurations::route('/'),
            'create' => Pages\CreateCallForwardingConfiguration::route('/create'),
            'view' => Pages\ViewCallForwardingConfiguration::route('/{record}'),
            'edit' => Pages\EditCallForwardingConfiguration::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRecordTitle($record): ?string
    {
        return "Weiterleitung #{$record->id} - {$record->branch->name}";
    }
}
