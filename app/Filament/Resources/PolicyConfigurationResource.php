<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\PolicyConfigurationResource\Pages;
use App\Models\PolicyConfiguration;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PolicyConfigurationResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = PolicyConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Richtlinien';

    protected static ?string $navigationLabel = 'Richtlinienkonfigurationen';

    protected static ?string $modelLabel = 'Richtlinienkonfiguration';

    protected static ?string $pluralModelLabel = 'Richtlinienkonfigurationen';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return static::getCachedBadge(function() {
            // SECURITY FIX (SEC-002): Explicit company filtering to prevent IDOR
            $user = auth()->user();

            if (!$user || !$user->company_id) {
                return 0;
            }

            // Explicitly filter by company_id (don't rely on global scopes)
            return static::getModel()::where('company_id', $user->company_id)->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Zuordnung')
                    ->icon('heroicon-o-link')
                    ->description('Entität, für die diese Richtlinie gilt')
                    ->schema([
                        Forms\Components\MorphToSelect::make('configurable')
                            ->label('Zugeordnete Entität')
                            ->types([
                                Forms\Components\MorphToSelect\Type::make(Company::class)
                                    ->titleAttribute('name')
                                    ->label('Unternehmen'),
                                Forms\Components\MorphToSelect\Type::make(Branch::class)
                                    ->titleAttribute('name')
                                    ->label('Filiale'),
                                Forms\Components\MorphToSelect\Type::make(Service::class)
                                    ->titleAttribute('name')
                                    ->label('Service'),
                                Forms\Components\MorphToSelect\Type::make(Staff::class)
                                    ->titleAttribute('name')
                                    ->label('Mitarbeiter'),
                            ])
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Richtliniendetails')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Select::make('policy_type')
                            ->label('Richtlinientyp')
                            ->options([
                                PolicyConfiguration::POLICY_TYPE_CANCELLATION => 'Stornierung',
                                PolicyConfiguration::POLICY_TYPE_RESCHEDULE => 'Umbuchung',
                                PolicyConfiguration::POLICY_TYPE_RECURRING => 'Wiederkehrend',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Art der Richtlinie: Stornierung, Umbuchung oder wiederkehrende Termine')
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('config')
                            ->label('Konfiguration')
                            ->keyLabel('Einstellung')
                            ->valueLabel('Wert')
                            ->addActionLabel('Einstellung hinzufügen')
                            ->reorderable()
                            ->helperText('📋 Verfügbare Einstellungen: **hours_before** (Vorlauf in Stunden, z.B. 24), **fee_percentage** (Gebühr in %, z.B. 50), **max_cancellations_per_month** (Max. Stornos/Monat, z.B. 3), **max_reschedules_per_appointment** (Max. Umbuchungen pro Termin, z.B. 2). ⚠️ Nur Zahlen als Werte, keine Anführungszeichen!')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Hierarchie & Überschreibung')
                    ->icon('heroicon-o-arrows-up-down')
                    ->description('Definieren Sie Überschreibungsbeziehungen in der Richtlinienhierarchie')
                    ->schema([
                        Forms\Components\Toggle::make('is_override')
                            ->label('Ist Überschreibung')
                            ->helperText('Aktivieren Sie diese Option, wenn diese Richtlinie eine übergeordnete Richtlinie überschreibt')
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, $state) => !$state ? $set('overrides_id', null) : null)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('overrides_id')
                            ->label('Überschreibt Richtlinie')
                            ->relationship('overrides', 'id')
                            ->getOptionLabelFromRecordUsing(fn (PolicyConfiguration $record): string =>
                                "#{$record->id} - {$record->configurable_type} ({$record->policy_type})"
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->visible(fn (Forms\Get $get): bool => $get('is_override') === true)
                            ->helperText('Wählen Sie die übergeordnete Richtlinie aus, die überschrieben werden soll')
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

                Tables\Columns\TextColumn::make('policy_type')
                    ->label('Richtlinientyp')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        PolicyConfiguration::POLICY_TYPE_CANCELLATION => 'Stornierung',
                        PolicyConfiguration::POLICY_TYPE_RESCHEDULE => 'Umbuchung',
                        PolicyConfiguration::POLICY_TYPE_RECURRING => 'Wiederkehrend',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PolicyConfiguration::POLICY_TYPE_CANCELLATION => 'danger',
                        PolicyConfiguration::POLICY_TYPE_RESCHEDULE => 'warning',
                        PolicyConfiguration::POLICY_TYPE_RECURRING => 'success',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        PolicyConfiguration::POLICY_TYPE_CANCELLATION => 'heroicon-o-x-circle',
                        PolicyConfiguration::POLICY_TYPE_RESCHEDULE => 'heroicon-o-arrow-path',
                        PolicyConfiguration::POLICY_TYPE_RECURRING => 'heroicon-o-arrow-path-rounded-square',
                        default => 'heroicon-o-document-text',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_override')
                    ->label('Überschreibung')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-up')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('overrides.id')
                    ->label('Überschreibt #')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

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
                Tables\Filters\SelectFilter::make('policy_type')
                    ->label('Richtlinientyp')
                    ->multiple()
                    ->options([
                        PolicyConfiguration::POLICY_TYPE_CANCELLATION => 'Stornierung',
                        PolicyConfiguration::POLICY_TYPE_RESCHEDULE => 'Umbuchung',
                        PolicyConfiguration::POLICY_TYPE_RECURRING => 'Wiederkehrend',
                    ]),

                Tables\Filters\TernaryFilter::make('is_override')
                    ->label('Überschreibung')
                    ->placeholder('Alle anzeigen')
                    ->trueLabel('Nur Überschreibungen')
                    ->falseLabel('Nur Basisrichtlinien'),

                Tables\Filters\SelectFilter::make('configurable_type')
                    ->label('Entitätstyp')
                    ->multiple()
                    ->options([
                        'App\\Models\\Company' => 'Unternehmen',
                        'App\\Models\\Branch' => 'Filiale',
                        'App\\Models\\Service' => 'Service',
                        'App\\Models\\Staff' => 'Mitarbeiter',
                    ]),

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

                Tables\Filters\TrashedFilter::make(),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ActionGroup::make([
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
                            $records->each(function (PolicyConfiguration $record): void {
                                $record->is_active = true;
                                $record->save();
                            });
                        })
                        ->successNotificationTitle('Policies aktiviert')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each(function (PolicyConfiguration $record): void {
                                $record->is_active = false;
                                $record->save();
                            });
                        })
                        ->successNotificationTitle('Policies deaktiviert')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen')
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
                $query->with(['configurable', 'overrides'])
            )
            ->recordUrl(fn (PolicyConfiguration $record): string =>
                PolicyConfigurationResource::getUrl('view', ['record' => $record])
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

                                Infolists\Components\TextEntry::make('policy_type')
                                    ->label('Richtlinientyp')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        PolicyConfiguration::POLICY_TYPE_CANCELLATION => 'Stornierung',
                                        PolicyConfiguration::POLICY_TYPE_RESCHEDULE => 'Umbuchung',
                                        PolicyConfiguration::POLICY_TYPE_RECURRING => 'Wiederkehrend',
                                        default => $state,
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        PolicyConfiguration::POLICY_TYPE_CANCELLATION => 'danger',
                                        PolicyConfiguration::POLICY_TYPE_RESCHEDULE => 'warning',
                                        PolicyConfiguration::POLICY_TYPE_RECURRING => 'success',
                                        default => 'gray',
                                    })
                                    ->icon(fn (string $state): string => match ($state) {
                                        PolicyConfiguration::POLICY_TYPE_CANCELLATION => 'heroicon-o-x-circle',
                                        PolicyConfiguration::POLICY_TYPE_RESCHEDULE => 'heroicon-o-arrow-path',
                                        PolicyConfiguration::POLICY_TYPE_RECURRING => 'heroicon-o-arrow-path-rounded-square',
                                        default => 'heroicon-o-document-text',
                                    }),

                                Infolists\Components\TextEntry::make('is_override')
                                    ->label('Überschreibung')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ja' : 'Nein')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray')
                                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-arrow-up' : 'heroicon-o-minus'),
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

                Infolists\Components\Section::make('Rohe Konfiguration')
                    ->icon('heroicon-o-code-bracket')
                    ->description('Direkt in dieser Richtlinie definierte Einstellungen')
                    ->schema([
                        Infolists\Components\TextEntry::make('config')
                            ->label('')
                            ->formatStateUsing(function ($state): string {
                                if (!$state || !is_array($state) || empty($state)) {
                                    return 'Keine Konfiguration definiert';
                                }
                                return collect($state)
                                    ->map(fn ($value, $key) => "$key: $value")
                                    ->join("\n");
                            })
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Effektive Konfiguration')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->description('Vollständige Konfiguration nach Anwendung der Hierarchie und Überschreibungen')
                    ->schema([
                        Infolists\Components\TextEntry::make('effective_config')
                            ->label('')
                            ->state(fn (PolicyConfiguration $record): array => $record->getEffectiveConfig())
                            ->formatStateUsing(function ($state): string {
                                if (!$state || !is_array($state) || empty($state)) {
                                    return 'Keine effektive Konfiguration';
                                }
                                return collect($state)
                                    ->map(fn ($value, $key) => "✓ $key: $value")
                                    ->join("\n");
                            })
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Hierarchie')
                    ->icon('heroicon-o-arrows-up-down')
                    ->description('Überschreibungsbeziehungen')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('overrides.id')
                                    ->label('Überschreibt Richtlinie')
                                    ->formatStateUsing(fn ($state, PolicyConfiguration $record): string =>
                                        $record->overrides
                                            ? "#{$record->overrides->id} - {$record->overrides->configurable_type} ({$record->overrides->policy_type})"
                                            : '—'
                                    )
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-arrow-up')
                                    ->placeholder('Keine übergeordnete Richtlinie'),

                                Infolists\Components\TextEntry::make('overridden_by_count')
                                    ->label('Wird überschrieben von')
                                    ->state(fn (PolicyConfiguration $record): int => $record->overriddenBy()->count())
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-arrow-down')
                                    ->suffix(fn ($state): string => $state === 1 ? ' Richtlinie' : ' Richtlinien'),
                            ]),
                    ])
                    ->visible(fn (PolicyConfiguration $record): bool =>
                        $record->is_override || $record->overriddenBy()->exists()
                    ),

                Infolists\Components\Section::make('Zeitstempel')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i')
                                    ->helperText(fn (PolicyConfiguration $record): string =>
                                        $record->created_at->diffForHumans()
                                    ),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Aktualisiert am')
                                    ->dateTime('d.m.Y H:i')
                                    ->helperText(fn (PolicyConfiguration $record): string =>
                                        $record->updated_at->diffForHumans()
                                    ),

                                Infolists\Components\TextEntry::make('deleted_at')
                                    ->label('Gelöscht am')
                                    ->dateTime('d.m.Y H:i')
                                    ->helperText(fn (PolicyConfiguration $record): ?string =>
                                        $record->deleted_at ? $record->deleted_at->diffForHumans() : null
                                    )
                                    ->placeholder('Nicht gelöscht')
                                    ->color('danger')
                                    ->visible(fn (PolicyConfiguration $record): bool => $record->deleted_at !== null),
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
            \App\Filament\Widgets\PolicyAnalyticsWidget::class,
            \App\Filament\Widgets\PolicyChartsWidget::class,
            \App\Filament\Widgets\PolicyTrendWidget::class,
            \App\Filament\Widgets\PolicyViolationsTableWidget::class,
            \App\Filament\Widgets\CustomerComplianceWidget::class,
            \App\Filament\Widgets\StaffPerformanceWidget::class,
            \App\Filament\Widgets\TimeBasedAnalyticsWidget::class,
            \App\Filament\Widgets\PolicyEffectivenessWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolicyConfigurations::route('/'),
            'create' => Pages\CreatePolicyConfiguration::route('/create'),
            'view' => Pages\ViewPolicyConfiguration::route('/{record}'),
            'edit' => Pages\EditPolicyConfiguration::route('/{record}/edit'),
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
        return "Richtlinie #{$record->id} - {$record->policy_type}";
    }
}
