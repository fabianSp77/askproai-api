<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UnifiedEventTypeResource\Pages;
use App\Models\UnifiedEventType;
use App\Models\Branch;
use App\Services\CalcomImportService; // HIER WAR DER FEHLER - Diese Zeile fehlte!
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;

class UnifiedEventTypeResource extends Resource
{
    protected static ?string $model = UnifiedEventType::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Event Type Management';
    protected static ?string $modelLabel = 'Event Type';
    protected static ?string $pluralModelLabel = 'Event Types';
    protected static ?int $navigationSort = 15;
    protected static ?string $navigationGroup = 'Personal & Services';
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hide from navigation as we use wizards now
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Event Type Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('duration_minutes')
                                    ->label('Dauer (Minuten)')
                                    ->numeric()
                                    ->default(30)
                                    ->required(),

                                Forms\Components\TextInput::make('price')
                                    ->label('Preis')
                                    ->numeric()
                                    ->prefix('€')
                                    ->default(0),

                                Forms\Components\Select::make('provider')
                                    ->label('Provider')
                                    ->options([
                                        'calcom' => 'Cal.com',
                                        'google' => 'Google Calendar',
                                        'outlook' => 'Outlook',
                                        'calendly' => 'Calendly',
                                    ])
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Zuordnung')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->label('Filiale zuordnen')
                            ->relationship('branch', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->company->name . ' - ' . $record->name)
                            ->searchable()
                            ->preload()
                            ->placeholder('Keine Zuordnung')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('assignment_status', $state ? 'assigned' : 'unassigned');
                            }),

                        Forms\Components\Radio::make('assignment_status')
                            ->label('Status')
                            ->options([
                                'unassigned' => 'Nicht zugeordnet',
                                'assigned' => 'Zugeordnet',
                            ])
                            ->inline()
                            ->disabled(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('branch.company.name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->default('Nicht zugeordnet'),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->default('Nicht zugeordnet'),

                Tables\Columns\BadgeColumn::make('assignment_status')
                    ->label('Status')
                    ->colors([
                        'danger' => 'unassigned',
                        'success' => 'assigned',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'assigned' ? 'Zugeordnet' : 'Nicht zugeordnet'),

                Tables\Columns\BadgeColumn::make('provider')
                    ->label('Provider')
                    ->colors([
                        'success' => 'calcom',
                        'info' => 'google',
                        'warning' => 'outlook',
                        'danger' => 'calendly',
                    ]),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Dauer')
                    ->suffix(' Min.')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->alignRight(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('import_status')
                    ->label('Import Status')
                    ->badge()
                    ->colors([
                        'success' => 'success',
                        'duplicate' => 'warning',
                        'error' => 'danger',
                        'pending_review' => 'info',
                    ])
                    ->visible(fn ($livewire) => $livewire->activeTab === 'duplicates'),

                Tables\Columns\TextColumn::make('imported_at')
                    ->label('Importiert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Zugeordnet am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn ($livewire) => $livewire->activeTab === 'assigned'),

            ])
            ->defaultSort('assignment_status', 'asc')
            ->groups([
                Tables\Grouping\Group::make('branch.company.name')
                    ->label('Nach Firma gruppieren')
                    ->collapsible(),
                Tables\Grouping\Group::make('branch.name')
                    ->label('Nach Filiale gruppieren')
                    ->collapsible(),
                Tables\Grouping\Group::make('provider')
                    ->label('Nach Provider gruppieren')
                    ->collapsible(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->company->name . ' - ' . $record->name)
                    ->searchable()
                    ->preload(),

                SelectFilter::make('provider')
                    ->label('Provider')
                    ->options([
                        'calcom' => 'Cal.com',
                        'google' => 'Google Calendar',
                        'outlook' => 'Outlook',
                        'calendly' => 'Calendly',
                    ]),

                SelectFilter::make('assignment_status')
                    ->label('Zuordnungsstatus')
                    ->options([
                        'assigned' => 'Zugeordnet',
                        'unassigned' => 'Nicht zugeordnet',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assign')
                    ->label('Zuordnen')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->visible(fn ($record) => $record->assignment_status === 'unassigned')
                    ->form([
                        Forms\Components\Select::make('branch_id')
                            ->label('Filiale auswählen')
                            ->relationship('branch', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->company->name . ' - ' . $record->name)
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->assignToBranch($data['branch_id']);
                        Notification::make()
                            ->title('Event Type zugeordnet')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('unassign')
                    ->label('Zuordnung aufheben')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->assignment_status === 'assigned')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->assignToBranch(null);
                        Notification::make()
                            ->title('Zuordnung aufgehoben')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('viewDuplicate')
                    ->label('Duplikat prüfen')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->visible(fn (UnifiedEventType $record): bool => $record->isDuplicate())
                    ->modalHeading(fn (UnifiedEventType $record): string => "Duplikat prüfen: {$record->name}")
                    ->modalContent(fn (UnifiedEventType $record) => view(
                        'filament.resources.unified-event-type-resource.duplicate-comparison',
                        ['record' => $record]
                    ))
                    ->modalFooterActions(fn (UnifiedEventType $record): array => [
                        Tables\Actions\Action::make('keepLocal')
                            ->label('Lokale Daten behalten')
                            ->color('success')
                            ->action(function (UnifiedEventType $record) {
                                app(CalcomImportService::class)->resolveDuplicate($record->id, 'keep_local');
                                Notification::make()
                                    ->title('Lokale Daten beibehalten')
                                    ->success()
                                    ->send();
                            }),
                        Tables\Actions\Action::make('useCalcom')
                            ->label('Cal.com Daten übernehmen')
                            ->color('warning')
                            ->action(function (UnifiedEventType $record) {
                                app(CalcomImportService::class)->resolveDuplicate($record->id, 'use_calcom');
                                Notification::make()
                                    ->title('Cal.com Daten übernommen')
                                    ->success()
                                    ->send();
                            }),
                    ]),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    BulkAction::make('bulk_assign')
                        ->label('Mehrfach zuordnen')
                        ->icon('heroicon-o-link')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('branch_id')
                                ->label('Filiale auswählen')
                                ->relationship('branch', 'name')
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->company->name . ' - ' . $record->name)
                                ->required()
                                ->searchable()
                                ->preload(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->assignToBranch($data['branch_id']);
                            });

                            Notification::make()
                                ->title($records->count() . ' Event Types zugeordnet')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_unassign')
                        ->label('Zuordnungen aufheben')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->assignToBranch(null);
                            });

                            Notification::make()
                                ->title($records->count() . ' Zuordnungen aufgehoben')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnifiedEventTypes::route('/'),
            'create' => Pages\CreateUnifiedEventType::route('/create'),
            'edit' => Pages\EditUnifiedEventType::route('/{record}/edit'),
        ];
    }
}
