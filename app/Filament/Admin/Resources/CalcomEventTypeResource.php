<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CalcomEventTypeResource\Pages;
use App\Filament\Admin\Resources\CalcomEventTypeResource\RelationManagers;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use App\Models\CalcomEventType;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;

class CalcomEventTypeResource extends Resource
{
    use HasConsistentNavigation;
    protected static ?string $model = CalcomEventType::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Personal & Services';
    
    protected static ?string $navigationLabel = 'Event-Types';
    
    protected static ?int $navigationSort = 220;
    
    public static function shouldRegisterNavigation(): bool
    {
        return true; // Now visible in navigation
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basis-Informationen')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Unternehmen')
                            ->options(Company::pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('branch_id', null)),
                            
                        Forms\Components\Select::make('branch_id')
                            ->label('Filiale (optional)')
                            ->options(function ($get) {
                                $companyId = $get('company_id');
                                if (!$companyId) {
                                    return [];
                                }
                                return Branch::where('company_id', $companyId)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable(),
                            
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Event-Type Details')
                    ->schema([
                        Forms\Components\TextInput::make('calcom_event_type_id')
                            ->label('Cal.com Event Type ID')
                            ->required(),
                            
                        Forms\Components\TextInput::make('calcom_numeric_event_type_id')
                            ->label('Numerische ID')
                            ->numeric(),
                            
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Dauer (Minuten)')
                            ->numeric()
                            ->required()
                            ->default(30),
                            
                        Forms\Components\TextInput::make('price')
                            ->label('Preis')
                            ->numeric()
                            ->prefix('€')
                            ->nullable(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                            
                        Forms\Components\Toggle::make('is_team_event')
                            ->label('Team-Event'),
                            
                        Forms\Components\Toggle::make('requires_confirmation')
                            ->label('Bestätigung erforderlich'),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Synchronisation')
                    ->schema([
                        Forms\Components\DateTimePicker::make('last_synced_at')
                            ->label('Letzte Synchronisation')
                            ->disabled(),
                            
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->disabled(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Alle Filialen'),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
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
                    
                Tables\Columns\IconColumn::make('is_team_event')
                    ->label('Team')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('assignedStaff')
                    ->label('Zugeordnete Mitarbeiter')
                    ->formatStateUsing(fn ($record) => $record->assignedStaff->count())
                    ->suffix(' Mitarbeiter'),
                    
                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('Letzte Sync')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // Basis-Filter
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Unternehmen')
                    ->options(Company::pluck('name', 'id'))
                    ->searchable(),
                    
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->options(Branch::pluck('name', 'id'))
                    ->searchable(),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                    
                Tables\Filters\TernaryFilter::make('is_team_event')
                    ->label('Team-Event'),
                    
                // Erweiterte Filter
                Tables\Filters\Filter::make('duration')
                    ->form([
                        Forms\Components\TextInput::make('duration_from')
                            ->label('Dauer von (Min.)')
                            ->numeric(),
                        Forms\Components\TextInput::make('duration_to')
                            ->label('Dauer bis (Min.)')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['duration_from'],
                                fn (Builder $query, $duration): Builder => $query->where('duration_minutes', '>=', $duration),
                            )
                            ->when(
                                $data['duration_to'],
                                fn (Builder $query, $duration): Builder => $query->where('duration_minutes', '<=', $duration),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['duration_from'] ?? null) {
                            $indicators['duration_from'] = 'Dauer ab ' . $data['duration_from'] . ' Min.';
                        }
                        if ($data['duration_to'] ?? null) {
                            $indicators['duration_to'] = 'Dauer bis ' . $data['duration_to'] . ' Min.';
                        }
                        return $indicators;
                    }),
                    
                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\TextInput::make('price_from')
                            ->label('Preis von (€)')
                            ->numeric()
                            ->prefix('€'),
                        Forms\Components\TextInput::make('price_to')
                            ->label('Preis bis (€)')
                            ->numeric()
                            ->prefix('€'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('price', '>=', $price),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('price', '<=', $price),
                            );
                    }),
                    
                Tables\Filters\Filter::make('staff_assigned')
                    ->label('Mitarbeiter-Zuordnung')
                    ->form([
                        Forms\Components\Select::make('has_staff')
                            ->label('Mitarbeiter zugeordnet')
                            ->options([
                                'with' => 'Mit Mitarbeitern',
                                'without' => 'Ohne Mitarbeiter',
                                'minimum' => 'Mindestanzahl',
                            ])
                            ->reactive(),
                        Forms\Components\TextInput::make('min_staff')
                            ->label('Mindestens X Mitarbeiter')
                            ->numeric()
                            ->visible(fn ($get) => $get('has_staff') === 'minimum'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['has_staff'] === 'with') {
                            return $query->has('assignedStaff');
                        } elseif ($data['has_staff'] === 'without') {
                            return $query->doesntHave('assignedStaff');
                        } elseif ($data['has_staff'] === 'minimum' && $data['min_staff']) {
                            return $query->has('assignedStaff', '>=', $data['min_staff']);
                        }
                        return $query;
                    }),
                    
                Tables\Filters\Filter::make('sync_status')
                    ->label('Synchronisations-Status')
                    ->form([
                        Forms\Components\Select::make('sync_age')
                            ->label('Synchronisation')
                            ->options([
                                '1' => 'Heute synchronisiert',
                                '7' => 'Diese Woche synchronisiert',
                                '30' => 'Diesen Monat synchronisiert',
                                'never' => 'Nie synchronisiert',
                                'error' => 'Mit Fehlern',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['sync_age']) {
                            return $query;
                        }
                        
                        if ($data['sync_age'] === 'never') {
                            return $query->whereNull('last_synced_at');
                        } elseif ($data['sync_age'] === 'error') {
                            return $query->whereNotNull('sync_error');
                        } else {
                            return $query->where('last_synced_at', '>=', now()->subDays($data['sync_age']));
                        }
                    }),
                    
                Tables\Filters\TernaryFilter::make('requires_confirmation')
                    ->label('Bestätigung erforderlich'),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assign_staff')
                    ->label('Mitarbeiter zuordnen')
                    ->icon('heroicon-o-user-group')
                    ->form([
                        Forms\Components\CheckboxList::make('staff_ids')
                            ->label('Mitarbeiter auswählen')
                            ->options(function ($record) {
                                return Staff::where('company_id', $record->company_id)
                                    ->where('active', true)
                                    ->where('is_bookable', true)
                                    ->pluck('name', 'id');
                            })
                            ->default(function ($record) {
                                return $record->assignedStaff->pluck('id')->toArray();
                            })
                            ->columns(2),
                    ])
                    ->action(function ($record, array $data) {
                        // Entferne alle bestehenden Zuordnungen
                        \DB::table('staff_event_types')
                            ->where('event_type_id', $record->id)
                            ->delete();
                        
                        // Füge neue Zuordnungen hinzu
                        $inserts = [];
                        foreach ($data['staff_ids'] as $staffId) {
                            $inserts[] = [
                                'staff_id' => $staffId,
                                'event_type_id' => $record->id,
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                        }
                        
                        if (!empty($inserts)) {
                            \DB::table('staff_event_types')->insert($inserts);
                        }
                    })
                    ->successNotificationTitle('Mitarbeiter erfolgreich zugeordnet'),
                    
                Tables\Actions\Action::make('sync')
                    ->label('Synchronisieren')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $syncService = new \App\Services\CalcomSyncService();
                        $syncService->syncEventTypesForCompany($record->company_id);
                    })
                    ->successNotificationTitle('Synchronisation gestartet'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('bulk_assign_staff')
                        ->label('Mitarbeiter zuordnen')
                        ->icon('heroicon-o-user-group')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\CheckboxList::make('staff_ids')
                                ->label('Mitarbeiter auswählen')
                                ->options(function () {
                                    // Zeige alle aktiven Mitarbeiter
                                    return Staff::where('active', true)
                                        ->where('is_bookable', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->columns(2)
                                ->required(),
                            Forms\Components\Radio::make('action')
                                ->label('Aktion')
                                ->options([
                                    'add' => 'Hinzufügen (bestehende behalten)',
                                    'replace' => 'Ersetzen (bestehende entfernen)',
                                ])
                                ->default('add')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $record) {
                                if ($data['action'] === 'replace') {
                                    // Entferne alle bestehenden Zuordnungen
                                    \DB::table('staff_event_types')
                                        ->where('event_type_id', $record->id)
                                        ->delete();
                                }
                                
                                // Füge neue Zuordnungen hinzu
                                $inserts = [];
                                foreach ($data['staff_ids'] as $staffId) {
                                    // Prüfe ob Zuordnung bereits existiert
                                    $exists = \DB::table('staff_event_types')
                                        ->where('staff_id', $staffId)
                                        ->where('event_type_id', $record->id)
                                        ->exists();
                                    
                                    if (!$exists) {
                                        $inserts[] = [
                                            'staff_id' => $staffId,
                                            'event_type_id' => $record->id,
                                            'created_at' => now(),
                                            'updated_at' => now()
                                        ];
                                    }
                                }
                                
                                if (!empty($inserts)) {
                                    \DB::table('staff_event_types')->insert($inserts);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Mitarbeiter erfolgreich zugeordnet'),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StaffRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalcomEventTypes::route('/'),
            'create' => Pages\CreateCalcomEventType::route('/create'),
            'edit' => Pages\EditCalcomEventType::route('/{record}/edit'),
        ];
    }
    
    public static function getWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\EventTypeAnalyticsWidget::class,
        ];
    }
}