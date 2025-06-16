<?php

namespace App\Filament\Admin\Resources\CompanyResource\RelationManagers;

use App\Models\CalcomEventType;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class EventTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'eventTypes';
    
    protected static ?string $title = 'Event Types';
    
    protected static ?string $modelLabel = 'Event Type';
    
    protected static ?string $pluralModelLabel = 'Event Types';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('calcom_id')
                                    ->label('Cal.com ID')
                                    ->numeric()
                                    ->required(),
                                    
                                Forms\Components\Select::make('branch_id')
                                    ->label('Filiale')
                                    ->relationship('branch', 'name', fn (Builder $query) => 
                                        $query->where('company_id', $this->ownerRecord->id)
                                    )
                                    ->preload()
                                    ->searchable()
                                    ->placeholder('Unternehmensweit'),
                                    
                                Forms\Components\TextInput::make('duration')
                                    ->label('Dauer (Minuten)')
                                    ->numeric()
                                    ->default(30)
                                    ->required(),
                                    
                                Forms\Components\TextInput::make('price')
                                    ->label('Preis')
                                    ->numeric()
                                    ->prefix('€')
                                    ->default(0),
                                    
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true),
                            ]),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->placeholder('Unternehmensweit')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('duration')
                    ->label('Dauer')
                    ->suffix(' Min.')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('assignedStaff')
                    ->label('Zugewiesene Mitarbeiter')
                    ->formatStateUsing(fn ($record) => $record->assignedStaff()->count())
                    ->suffix(' Mitarbeiter'),
                    
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktiv'),
                    
                Tables\Columns\TextColumn::make('sync_status')
                    ->label('Sync Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'synced' => 'success',
                        'pending' => 'warning',
                        'error' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name', fn (Builder $query) => 
                        $query->where('company_id', $this->ownerRecord->id)
                    )
                    ->multiple()
                    ->preload(),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                    
                Tables\Filters\SelectFilter::make('sync_status')
                    ->label('Sync Status')
                    ->options([
                        'synced' => 'Synchronisiert',
                        'pending' => 'Ausstehend',
                        'error' => 'Fehler',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('sync')
                    ->label('Mit Cal.com synchronisieren')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function () {
                        // TODO: Implement sync logic
                        $this->notify('success', 'Synchronisation gestartet');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('assignStaff')
                    ->label('Mitarbeiter zuweisen')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('staff_ids')
                            ->label('Mitarbeiter')
                            ->multiple()
                            ->options(
                                Staff::where('company_id', $this->ownerRecord->id)
                                    ->where('active', true)
                                    ->pluck('name', 'id')
                            )
                            ->preload()
                            ->searchable(),
                    ])
                    ->action(function (CalcomEventType $record, array $data) {
                        $record->assignedStaff()->sync($data['staff_ids'] ?? []);
                        $this->notify('success', 'Mitarbeiter wurden zugewiesen');
                    }),
                    
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkAssignStaff')
                        ->label('Mitarbeiter zuweisen')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Forms\Components\Select::make('staff_ids')
                                ->label('Mitarbeiter')
                                ->multiple()
                                ->options(
                                    Staff::where('company_id', $this->ownerRecord->id)
                                        ->where('active', true)
                                        ->pluck('name', 'id')
                                )
                                ->preload()
                                ->searchable(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->assignedStaff()->syncWithoutDetaching($data['staff_ids'] ?? []);
                            }
                            $this->notify('success', 'Mitarbeiter wurden den Event Types zugewiesen');
                        }),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    protected function notify(string $status, string $message): void
    {
        session()->flash('filament.notifications', [
            [
                'status' => $status,
                'message' => $message,
            ]
        ]);
    }
}