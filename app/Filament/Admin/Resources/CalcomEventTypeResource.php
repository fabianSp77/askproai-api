<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CalcomEventTypeResource\Pages;
use App\Filament\Admin\Resources\CalcomEventTypeResource\RelationManagers;
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
    protected static ?string $model = CalcomEventType::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Personal & Services';
    
    protected static ?string $navigationLabel = 'Event-Type Verwaltung';
    
    protected static ?int $navigationSort = 210;

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
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Unternehmen')
                    ->options(Company::pluck('name', 'id'))
                    ->searchable(),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            // Temporarily disable widget to test
            // \App\Filament\Admin\Widgets\EventTypeAnalyticsWidget::class,
        ];
    }
}