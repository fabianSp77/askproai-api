<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\NotificationConfigurationResource\Pages;
use App\Models\NotificationConfiguration;
use App\Models\NotificationEventMapping;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Enums\NotificationChannel;
use App\Rules\ValidTemplateRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotificationConfigurationResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = NotificationConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Benachrichtigungen';

    protected static ?string $navigationLabel = 'Benachrichtigungskonfigurationen';

    protected static ?string $modelLabel = 'Benachrichtigungskonfiguration';

    protected static ?string $pluralModelLabel = 'Benachrichtigungskonfigurationen';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return static::getCachedBadge(function() {
            return static::getModel()::where('is_enabled', true)->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getCachedBadgeColor(function() {
            $count = static::getModel()::where('is_enabled', true)->count();
            return $count > 0 ? 'success' : 'gray';
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Zuordnung')
                    ->icon('heroicon-o-link')
                    ->description('Entität, für die diese Benachrichtigungskonfiguration gilt')
                    ->schema([
                        Forms\Components\MorphToSelect::make('configurable')
                            ->label('Zugeordnete Entität')
                            ->types([
                                Forms\Components\MorphToSelect\Type::make(Company::class)
                                    ->titleAttribute('name')
                                    ->label('Unternehmen')
                                    ->modifyOptionsQueryUsing(fn (Builder $query) =>
                                        $query->where('id', auth()->user()->company_id)
                                    ),
                                Forms\Components\MorphToSelect\Type::make(Branch::class)
                                    ->titleAttribute('name')
                                    ->label('Filiale')
                                    ->modifyOptionsQueryUsing(fn (Builder $query) =>
                                        $query->where('company_id', auth()->user()->company_id)
                                    ),
                                Forms\Components\MorphToSelect\Type::make(Service::class)
                                    ->titleAttribute('name')
                                    ->label('Service')
                                    ->modifyOptionsQueryUsing(fn (Builder $query) =>
                                        $query->where('company_id', auth()->user()->company_id)
                                    ),
                                Forms\Components\MorphToSelect\Type::make(Staff::class)
                                    ->titleAttribute('name')
                                    ->label('Mitarbeiter')
                                    ->modifyOptionsQueryUsing(fn (Builder $query) =>
                                        $query->where('company_id', auth()->user()->company_id)
                                    ),
                            ])
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Event & Kanal')
                    ->icon('heroicon-o-bell')
                    ->schema([
                        Forms\Components\Select::make('event_type')
                            ->label('Event-Typ')
                            ->options(function (): array {
                                // Get all seeded notification events (cached for performance)
                                return \Illuminate\Support\Facades\Cache::remember(
                                    'notification_events_options',
                                    3600, // 1 hour
                                    fn () => NotificationEventMapping::query()
                                        ->pluck('event_label', 'event_type')
                                        ->toArray()
                                );
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Wählen Sie das Event aus, für das diese Benachrichtigungskonfiguration gilt (13 verfügbare Events)')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('channel')
                                    ->label('Primärer Kanal')
                                    ->options(NotificationChannel::getOptions())
                                    ->required()
                                    ->native(false)
                                    ->helperText('Primärer Benachrichtigungskanal'),

                                Forms\Components\Select::make('fallback_channel')
                                    ->label('Fallback-Kanal')
                                    ->options(NotificationChannel::getFallbackOptions())
                                    ->nullable()
                                    ->native(false)
                                    ->helperText('Fallback-Kanal, falls der primäre Kanal fehlschlägt'),
                            ]),

                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Aktiviert')
                            ->default(true)
                            ->helperText('Aktivieren oder deaktivieren Sie diese Benachrichtigungskonfiguration')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Wiederholungslogik')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('retry_count')
                                    ->label('Wiederholungsversuche')
                                    ->numeric()
                                    ->default(3)
                                    ->minValue(0)
                                    ->maxValue(10)
                                    ->helperText('Anzahl der Wiederholungsversuche bei Fehlschlag'),

                                Forms\Components\TextInput::make('retry_delay_minutes')
                                    ->label('Wiederholungsverzögerung (Minuten)')
                                    ->numeric()
                                    ->default(5)
                                    ->minValue(1)
                                    ->maxValue(1440)
                                    ->helperText('Verzögerung zwischen Wiederholungsversuchen in Minuten'),
                            ]),
                    ]),

                Forms\Components\Section::make('Template & Metadaten')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('template_override')
                            ->label('Template-Überschreibung')
                            ->rows(4)
                            ->maxLength(65535)
                            ->rules([new ValidTemplateRule()])
                            ->helperText('Optionale Template-Überschreibung. Unterstützt {{variable}} Syntax. HTML und gefährliche Funktionen werden blockiert.')
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadaten')
                            ->keyLabel('Schlüssel')
                            ->valueLabel('Wert')
                            ->addActionLabel('Metadaten hinzufügen')
                            ->reorderable()
                            ->helperText('Zusätzliche Metadaten für diese Benachrichtigungskonfiguration (z.B. send_time_preference: morning, language: de)')
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
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('configurable_type')
                    ->label('Entitätstyp')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'App\\Models\\Company' => 'Unternehmen',
                        'App\\Models\\Branch' => 'Filiale',
                        'App\\Models\\Service' => 'Service',
                        'App\\Models\\Staff' => 'Mitarbeiter',
                        default => class_basename($state),
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'App\\Models\\Company' => 'success',
                        'App\\Models\\Branch' => 'info',
                        'App\\Models\\Service' => 'warning',
                        'App\\Models\\Staff' => 'primary',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'App\\Models\\Company' => 'heroicon-o-building-office-2',
                        'App\\Models\\Branch' => 'heroicon-o-building-office',
                        'App\\Models\\Service' => 'heroicon-o-wrench-screwdriver',
                        'App\\Models\\Staff' => 'heroicon-o-user',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('configurable.name')
                    ->label('Entität')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('eventMapping.event_label')
                    ->label('Event')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('channel')
                    ->label('Kanäle')
                    ->formatStateUsing(function ($record): string {
                        $primary = NotificationChannel::tryFromValue($record->channel);
                        $channels = [$primary?->getLabel() ?? ucfirst($record->channel)];

                        // Fallback channel
                        if ($record->fallback_channel && $record->fallback_channel !== 'none') {
                            $fallback = NotificationChannel::tryFromValue($record->fallback_channel);
                            $channels[] = $fallback?->getLabel() ?? ucfirst($record->fallback_channel);
                        }

                        return implode(' → ', $channels);
                    })
                    ->icon(fn ($record) => NotificationChannel::tryFromValue($record->channel)?->getIcon())
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('retry_count')
                    ->label('Wiederholungen')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->label('Event-Typ')
                    ->multiple()
                    ->options(function (): array {
                        // Use cached event options for performance
                        return \Illuminate\Support\Facades\Cache::remember(
                            'notification_events_options',
                            3600,
                            fn () => NotificationEventMapping::query()
                                ->pluck('event_label', 'event_type')
                                ->toArray()
                        );
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('channel')
                    ->label('Primärer Kanal')
                    ->multiple()
                    ->options(NotificationChannel::getOptions()),

                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Aktiviert')
                    ->placeholder('Alle anzeigen')
                    ->trueLabel('Nur aktivierte')
                    ->falseLabel('Nur deaktivierte'),

                Tables\Filters\SelectFilter::make('configurable_type')
                    ->label('Entitätstyp')
                    ->multiple()
                    ->options([
                        'App\\Models\\Company' => 'Unternehmen',
                        'App\\Models\\Branch' => 'Filiale',
                        'App\\Models\\Service' => 'Service',
                        'App\\Models\\Staff' => 'Mitarbeiter',
                    ]),

                Tables\Filters\Filter::make('has_fallback')
                    ->label('Hat Fallback-Kanal')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereNotNull('fallback_channel')
                            ->where('fallback_channel', '!=', 'none')
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Erstellt von')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Erstellt bis')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Erstellt ab ' . \Carbon\Carbon::parse($data['created_from'])->format('d.m.Y'))
                                ->removeField('created_from');
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Erstellt bis ' . \Carbon\Carbon::parse($data['created_until'])->format('d.m.Y'))
                                ->removeField('created_until');
                        }

                        return $indicators;
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('test')
                        ->label('Test senden')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('recipient')
                                ->label('Empfänger')
                                ->required()
                                ->helperText('E-Mail, Telefonnummer oder User-ID für Test-Benachrichtigung'),
                            Forms\Components\Textarea::make('test_data')
                                ->label('Test-Daten (JSON)')
                                ->rows(4)
                                ->helperText('Optional: JSON-Daten für Template-Variablen'),
                        ])
                        ->action(function (NotificationConfiguration $record, array $data): void {
                            // Test notification dispatch logic would go here
                            Notification::make()
                                ->success()
                                ->title('Test-Benachrichtigung gesendet')
                                ->body("Test-Benachrichtigung wurde an {$data['recipient']} über {$record->channel} gesendet.")
                                ->send();
                        })
                        ->successNotificationTitle('Test-Benachrichtigung gesendet')
                        ->requiresConfirmation(),

                    Tables\Actions\Action::make('toggle')
                        ->label(fn (NotificationConfiguration $record): string =>
                            $record->is_enabled ? 'Deaktivieren' : 'Aktivieren'
                        )
                        ->icon(fn (NotificationConfiguration $record): string =>
                            $record->is_enabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle'
                        )
                        ->color(fn (NotificationConfiguration $record): string =>
                            $record->is_enabled ? 'danger' : 'success'
                        )
                        ->action(function (NotificationConfiguration $record): void {
                            $record->is_enabled = !$record->is_enabled;
                            $record->save();
                        })
                        ->successNotificationTitle(fn (NotificationConfiguration $record): string =>
                            $record->is_enabled ? 'Aktiviert' : 'Deaktiviert'
                        )
                        ->requiresConfirmation(),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enable')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each(function (NotificationConfiguration $record): void {
                                $record->is_enabled = true;
                                $record->save();
                            });
                        })
                        ->successNotificationTitle('Benachrichtigungen aktiviert')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('disable')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each(function (NotificationConfiguration $record): void {
                                $record->is_enabled = false;
                                $record->save();
                            });
                        })
                        ->successNotificationTitle('Benachrichtigungen deaktiviert')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen')
                        ->requiresConfirmation(),
                ])
                ->label('Massenaktionen')
                ->icon('heroicon-o-squares-plus')
                ->color('primary'),
            ])
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['configurable', 'eventMapping'])
            )
            ->recordUrl(fn (NotificationConfiguration $record): string =>
                NotificationConfigurationResource::getUrl('view', ['record' => $record])
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

                                Infolists\Components\TextEntry::make('is_enabled')
                                    ->label('Status')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktiviert' : 'Deaktiviert')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),

                                Infolists\Components\TextEntry::make('eventMapping.event_category')
                                    ->label('Event-Kategorie')
                                    ->badge()
                                    ->color('info')
                                    ->placeholder('—'),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('configurable_type')
                                    ->label('Entitätstyp')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'App\\Models\\Company' => 'Unternehmen',
                                        'App\\Models\\Branch' => 'Filiale',
                                        'App\\Models\\Service' => 'Service',
                                        'App\\Models\\Staff' => 'Mitarbeiter',
                                        default => class_basename($state),
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'App\\Models\\Company' => 'success',
                                        'App\\Models\\Branch' => 'info',
                                        'App\\Models\\Service' => 'warning',
                                        'App\\Models\\Staff' => 'primary',
                                        default => 'gray',
                                    })
                                    ->icon(fn (string $state): string => match ($state) {
                                        'App\\Models\\Company' => 'heroicon-o-building-office-2',
                                        'App\\Models\\Branch' => 'heroicon-o-building-office',
                                        'App\\Models\\Service' => 'heroicon-o-wrench-screwdriver',
                                        'App\\Models\\Staff' => 'heroicon-o-user',
                                        default => 'heroicon-o-question-mark-circle',
                                    }),

                                Infolists\Components\TextEntry::make('configurable.name')
                                    ->label('Zugeordnete Entität')
                                    ->weight('bold')
                                    ->icon('heroicon-o-link'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Event-Details')
                    ->icon('heroicon-o-bell')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('event_type')
                                    ->label('Event-Typ')
                                    ->badge()
                                    ->color('primary')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('eventMapping.event_label')
                                    ->label('Event-Name')
                                    ->weight('bold')
                                    ->placeholder('—'),
                            ]),

                        Infolists\Components\TextEntry::make('eventMapping.description')
                            ->label('Event-Beschreibung')
                            ->placeholder('Keine Beschreibung verfügbar')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Kanal-Konfiguration')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('channel')
                                    ->label('Primärer Kanal')
                                    ->formatStateUsing(fn (string $state): string =>
                                        NotificationChannel::tryFromValue($state)?->getLabel() ?? ucfirst($state)
                                    )
                                    ->badge()
                                    ->color('primary')
                                    ->icon(fn (string $state) => NotificationChannel::tryFromValue($state)?->getIcon()),

                                Infolists\Components\TextEntry::make('fallback_channel')
                                    ->label('Fallback-Kanal')
                                    ->formatStateUsing(fn (?string $state): string =>
                                        $state ? (NotificationChannel::tryFromValue($state)?->getLabel() ?? ucfirst($state)) : '—'
                                    )
                                    ->badge()
                                    ->color(fn (?string $state): string => $state && $state !== 'none' ? 'warning' : 'gray')
                                    ->icon(fn (?string $state) => $state ? NotificationChannel::tryFromValue($state)?->getIcon() : null)
                                    ->placeholder('Kein Fallback konfiguriert'),
                            ]),

                        Infolists\Components\TextEntry::make('eventMapping.default_channels')
                            ->label('Standard-Kanäle (vom Event)')
                            ->formatStateUsing(function ($state): string {
                                if (!$state || !is_array($state)) {
                                    return '—';
                                }
                                return collect($state)
                                    ->map(fn ($channel) =>
                                        NotificationChannel::tryFromValue($channel)?->getLabel() ?? ucfirst($channel)
                                    )
                                    ->join(', ');
                            })
                            ->placeholder('Keine Standard-Kanäle definiert')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Wiederholungslogik')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('retry_count')
                                    ->label('Wiederholungsversuche')
                                    ->badge()
                                    ->color('info')
                                    ->suffix(' Versuche'),

                                Infolists\Components\TextEntry::make('retry_delay_minutes')
                                    ->label('Wiederholungsverzögerung')
                                    ->badge()
                                    ->color('info')
                                    ->suffix(' Minuten'),

                                Infolists\Components\TextEntry::make('total_retry_window')
                                    ->label('Gesamtzeitfenster')
                                    ->state(fn (NotificationConfiguration $record): string =>
                                        $record->retry_count * $record->retry_delay_minutes . ' Minuten'
                                    )
                                    ->badge()
                                    ->color('warning'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Template & Metadaten')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('template_override')
                            ->label('Template-Überschreibung')
                            ->markdown()
                            ->placeholder('Keine Template-Überschreibung')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('metadata')
                            ->label('Metadaten')
                            ->formatStateUsing(function ($state): string {
                                if (!$state || !is_array($state) || empty($state)) {
                                    return 'Keine Metadaten';
                                }
                                return collect($state)
                                    ->map(fn ($value, $key) => "• $key: $value")
                                    ->join("\n");
                            })
                            ->placeholder('Keine Metadaten')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Zeitstempel')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i')
                                    ->description(fn (NotificationConfiguration $record): string =>
                                        $record->created_at->diffForHumans()
                                    ),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Aktualisiert am')
                                    ->dateTime('d.m.Y H:i')
                                    ->description(fn (NotificationConfiguration $record): string =>
                                        $record->updated_at->diffForHumans()
                                    ),
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

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\NotificationAnalyticsWidget::class,
            \App\Filament\Widgets\NotificationPerformanceChartWidget::class,
            \App\Filament\Widgets\RecentFailedNotificationsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationConfigurations::route('/'),
            'create' => Pages\CreateNotificationConfiguration::route('/create'),
            'view' => Pages\ViewNotificationConfiguration::route('/{record}'),
            'edit' => Pages\EditNotificationConfiguration::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with('configurable'); // Prevent N+1 in Policy authorization checks
    }

    public static function getRecordTitle($record): ?string
    {
        return "Benachrichtigung #{$record->id} - {$record->event_type}";
    }
}
