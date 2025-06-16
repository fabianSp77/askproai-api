<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnifiedEventTypeResource\Pages;
use App\Models\UnifiedEventType;
use App\Models\Branch;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class UnifiedEventTypeResource extends Resource
{
    protected static ?string $model = UnifiedEventType::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Event Types';
    protected static ?string $modelLabel = 'Event Typ';
    protected static ?string $pluralModelLabel = 'Event Types';
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basis-Informationen')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Firma')
                            ->options(Company::pluck('name', 'id'))
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('branch_id', null);
                                }
                            }),

                        Forms\Components\Select::make('branch_id')
                            ->label('Filiale')
                            ->options(function (Forms\Get $get) {
                                $companyId = $get('company_id');
                                if ($companyId) {
                                    // WICHTIG: Verwende 'id' als Wert, nicht 'uuid'
                                    return Branch::where('company_id', $companyId)
                                        ->pluck('name', 'id');
                                }
                                // Wenn keine Firma ausgewählt, alle Filialen anzeigen
                                return Branch::with('company')
                                    ->get()
                                    ->mapWithKeys(function ($branch) {
                                        // WICHTIG: Verwende $branch->id, nicht $branch->uuid
                                        return [$branch->id => $branch->company->name . ' - ' . $branch->name];
                                    });
                            })
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state && !$get('company_id')) {
                                    // Wenn eine Filiale gewählt wurde, aber keine Firma, setze die Firma automatisch
                                    $branch = Branch::find($state);
                                    if ($branch) {
                                        $set('company_id', $branch->company_id);
                                    }
                                }
                            })
                            ->dehydrateStateUsing(fn ($state) => $state), // Stelle sicher, dass der Wert korrekt gespeichert wird

                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Dauer (Minuten)')
                            ->numeric()
                            ->default(30)
                            ->required(),

                        Forms\Components\TextInput::make('price')
                            ->label('Preis')
                            ->numeric()
                            ->prefix('€'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Provider-Informationen')
                    ->schema([
                        Forms\Components\Select::make('provider')
                            ->label('Provider')
                            ->options([
                                'calcom' => 'Cal.com',
                                'google_calendar' => 'Google Calendar',
                                'custom' => 'Eigener Kalender',
                            ])
                            ->default('calcom')
                            ->required(),

                        Forms\Components\TextInput::make('external_id')
                            ->label('Externe ID')
                            ->helperText('Die ID des Event Types im externen System'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Import-Status')
                    ->schema([
                        Forms\Components\Select::make('import_status')
                            ->label('Import Status')
                            ->options([
                                'pending' => 'Ausstehend',
                                'imported' => 'Importiert',
                                'duplicate' => 'Duplikat',
                                'updated' => 'Aktualisiert',
                                'conflict' => 'Konflikt',
                            ])
                            ->disabled(),

                        Forms\Components\Select::make('assignment_status')
                            ->label('Zuweisungsstatus')
                            ->options([
                                'unassigned' => 'Nicht zugewiesen',
                                'assigned' => 'Zugewiesen',
                                'conflict' => 'Konflikt',
                            ])
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('imported_at')
                            ->label('Importiert am')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('assigned_at')
                            ->label('Zugewiesen am')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsed(),
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

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Firma')
                    ->getStateUsing(function ($record) {
                        if ($record->company) {
                            return $record->company->name;
                        } elseif ($record->branch && $record->branch->company) {
                            return $record->branch->company->name;
                        }
                        return '-';
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Dauer')
                    ->suffix(' Min.')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                Tables\Columns\BadgeColumn::make('import_status')
                    ->label('Import Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'imported',
                        'danger' => 'duplicate',
                        'info' => 'updated',
                        'gray' => 'conflict',
                    ]),

                Tables\Columns\BadgeColumn::make('assignment_status')
                    ->label('Zuweisung')
                    ->colors([
                        'gray' => 'unassigned',
                        'success' => 'assigned',
                        'danger' => 'conflict',
                    ]),
            ])
            ->filters([
                SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->label('Firma'),

                SelectFilter::make('branch')
                    ->relationship('branch', 'name')
                    ->label('Filiale'),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktiv',
                        '0' => 'Inaktiv',
                    ]),

                SelectFilter::make('import_status')
                    ->label('Import Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'imported' => 'Importiert',
                        'duplicate' => 'Duplikat',
                        'updated' => 'Aktualisiert',
                        'conflict' => 'Konflikt',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListUnifiedEventTypes::route('/'),
            'create' => Pages\CreateUnifiedEventType::route('/create'),
            'edit' => Pages\EditUnifiedEventType::route('/{record}/edit'),
        ];
    }
}
