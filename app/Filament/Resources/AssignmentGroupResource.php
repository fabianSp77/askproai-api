<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentGroupResource\Pages;
use App\Models\AssignmentGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * AssignmentGroupResource
 *
 * ServiceNow-style team management for Service Gateway.
 * Allows creating groups of staff members for ticket assignment.
 */
class AssignmentGroupResource extends Resource
{
    protected static ?string $model = AssignmentGroup::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?string $navigationLabel = 'Zuweisungsgruppen';
    protected static ?string $modelLabel = 'Zuweisungsgruppe';
    protected static ?string $pluralModelLabel = 'Zuweisungsgruppen';
    protected static ?int $navigationSort = 12; // After Categories

    /**
     * Only show in navigation when Service Gateway is enabled.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('gateway.mode_enabled', false);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Gruppeninformationen')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Gruppenname')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. IT-Support Level 1'),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(2)
                            ->placeholder('Beschreibung der Gruppe und Zuständigkeit')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('email')
                            ->label('Gruppen-E-Mail')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('support@example.com')
                            ->helperText('Für Benachrichtigungen bei Gruppenzuweisungen'),
                    ])->columns(2),

                Forms\Components\Section::make('Mitglieder')
                    ->schema([
                        Forms\Components\Select::make('members')
                            ->label('Gruppenmitglieder')
                            ->relationship('members', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Staff #{$record->id}")
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Wählen Sie die Mitarbeiter, die dieser Gruppe angehören'),
                    ]),

                Forms\Components\Section::make('Einstellungen')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Inaktive Gruppen können nicht für Zuweisungen verwendet werden'),
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
                    ->label('Gruppenname')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user-group')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Firma')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('members_count')
                    ->label('Mitglieder')
                    ->counts('members')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cases_count')
                    ->label('Offene Cases')
                    ->getStateUsing(fn (AssignmentGroup $record) => $record->openCases()->count())
                    ->badge()
                    ->color(fn ($state) => $state > 10 ? 'danger' : ($state > 5 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('workload')
                    ->label('Workload')
                    ->getStateUsing(fn (AssignmentGroup $record) => $record->workload)
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . ' Cases/Person')
                    ->color(fn ($state) => $state > 5 ? 'danger' : ($state > 3 ? 'warning' : 'success'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Firma')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_cases')
                    ->label('Cases anzeigen')
                    ->icon('heroicon-o-ticket')
                    ->color('info')
                    ->url(fn (AssignmentGroup $record) =>
                        route('filament.admin.resources.service-cases.index', [
                            'tableFilters[assigned_group_id][value]' => $record->id,
                        ])
                    ),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->before(function (AssignmentGroup $record, Tables\Actions\DeleteAction $action) {
                        if ($record->cases()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gruppe kann nicht gelöscht werden')
                                ->body('Es sind noch Cases dieser Gruppe zugewiesen.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
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
            ->reorderable('sort_order');
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
            'index' => Pages\ListAssignmentGroups::route('/'),
            'create' => Pages\CreateAssignmentGroup::route('/create'),
            'edit' => Pages\EditAssignmentGroup::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }
}
