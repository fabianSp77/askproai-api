<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceCaseCategoryResource\Pages;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ServiceCaseCategoryResource extends Resource
{
    protected static ?string $model = ServiceCaseCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?string $navigationLabel = 'Kategorien';
    protected static ?string $modelLabel = 'Kategorie';
    protected static ?string $pluralModelLabel = 'Kategorien';
    protected static ?int $navigationSort = 11;

    /**
     * Only show in navigation when Service Gateway is enabled.
     * @see config/gateway.php 'mode_enabled'
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('gateway.mode_enabled', false);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                if (!empty($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Wird automatisch generiert, kann aber angepasst werden'),
                        Forms\Components\Select::make('parent_id')
                            ->label('Übergeordnete Kategorie')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Leer lassen für Root-Kategorie'),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('AI Intent Matching')
                    ->schema([
                        Forms\Components\TagsInput::make('intent_keywords')
                            ->label('Intent Keywords')
                            ->helperText('Schlüsselwörter zur automatischen Kategorisierung durch KI')
                            ->placeholder('z.B. termin, buchung, stornierung')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('confidence_threshold')
                            ->label('Confidence Threshold')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->default(0.5)
                            ->helperText('Minimale Übereinstimmung für automatische Zuordnung (0.0 - 1.0)'),
                    ])->columns(2),

                Forms\Components\Section::make('Default Values')
                    ->schema([
                        Forms\Components\Select::make('default_case_type')
                            ->label('Standard Case Typ')
                            ->options([
                                ServiceCase::TYPE_INCIDENT => 'Störung',
                                ServiceCase::TYPE_REQUEST => 'Anfrage',
                                ServiceCase::TYPE_INQUIRY => 'Anliegen',
                            ])
                            ->helperText('Wird für neue Cases in dieser Kategorie verwendet'),
                        Forms\Components\Select::make('default_priority')
                            ->label('Standard Priorität')
                            ->options([
                                ServiceCase::PRIORITY_LOW => 'Niedrig',
                                ServiceCase::PRIORITY_NORMAL => 'Normal',
                                ServiceCase::PRIORITY_HIGH => 'Hoch',
                                ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                            ])
                            ->helperText('Wird für neue Cases in dieser Kategorie verwendet'),
                        Forms\Components\TextInput::make('sla_response_hours')
                            ->label('SLA Response (Stunden)')
                            ->numeric()
                            ->minValue(0)
                            ->default(4)
                            ->helperText('Zeit bis zur ersten Antwort'),
                        Forms\Components\TextInput::make('sla_resolution_hours')
                            ->label('SLA Resolution (Stunden)')
                            ->numeric()
                            ->minValue(0)
                            ->default(24)
                            ->helperText('Zeit bis zur Lösung'),
                    ])->columns(2),

                Forms\Components\Section::make('Output Configuration')
                    ->schema([
                        Forms\Components\Select::make('output_configuration_id')
                            ->label('Output Konfiguration')
                            ->relationship('outputConfiguration', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Email/Webhook-Konfiguration für diese Kategorie'),
                    ]),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Deaktivierte Kategorien können nicht für neue Cases verwendet werden'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sortierung')
                            ->numeric()
                            ->default(0)
                            ->helperText('Niedrigere Werte werden zuerst angezeigt'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ServiceCaseCategory $record): ?string => $record->parent ? "↳ Untergeordnet: {$record->parent->name}" : null),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Übergeordnet')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('default_case_type')
                    ->label('Standard Typ')
                    ->colors([
                        'danger' => ServiceCase::TYPE_INCIDENT,
                        'warning' => ServiceCase::TYPE_REQUEST,
                        'info' => ServiceCase::TYPE_INQUIRY,
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        ServiceCase::TYPE_INCIDENT => 'Störung',
                        ServiceCase::TYPE_REQUEST => 'Anfrage',
                        ServiceCase::TYPE_INQUIRY => 'Anliegen',
                        default => '-',
                    })
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('default_priority')
                    ->label('Standard Priorität')
                    ->colors([
                        'gray' => ServiceCase::PRIORITY_LOW,
                        'primary' => ServiceCase::PRIORITY_NORMAL,
                        'warning' => ServiceCase::PRIORITY_HIGH,
                        'danger' => ServiceCase::PRIORITY_CRITICAL,
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        ServiceCase::PRIORITY_LOW => 'Niedrig',
                        ServiceCase::PRIORITY_NORMAL => 'Normal',
                        ServiceCase::PRIORITY_HIGH => 'Hoch',
                        ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                        default => '-',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cases_count')
                    ->label('Cases')
                    ->counts('cases')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('outputConfiguration.name')
                    ->label('Output Config')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sortierung')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Übergeordnete Kategorie')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\Filter::make('root')
                    ->label('Nur Root-Kategorien')
                    ->query(fn ($query) => $query->whereNull('parent_id'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->action(function (ServiceCaseCategory $record) {
                        if ($record->cases()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Kategorie kann nicht gelöscht werden')
                                ->body('Es existieren noch Cases in dieser Kategorie.')
                                ->danger()
                                ->send();
                            return;
                        }
                        if ($record->children()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Kategorie kann nicht gelöscht werden')
                                ->body('Es existieren noch Unterkategorien.')
                                ->danger()
                                ->send();
                            return;
                        }
                        $record->delete();
                    }),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (ServiceCaseCategory $record) => $record->is_active ? 'Deaktivieren' : 'Aktivieren')
                    ->icon(fn (ServiceCaseCategory $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (ServiceCaseCategory $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (ServiceCaseCategory $record) => $record->update(['is_active' => !$record->is_active])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultGroup('parent.name');
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
            'index' => Pages\ListServiceCaseCategories::route('/'),
            'create' => Pages\CreateServiceCaseCategory::route('/create'),
            'edit' => Pages\EditServiceCaseCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }
}
