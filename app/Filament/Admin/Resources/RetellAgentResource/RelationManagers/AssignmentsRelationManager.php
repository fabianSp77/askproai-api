<?php

namespace App\Filament\Admin\Resources\RetellAgentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\AgentAssignment;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('assignment_type')
                    ->options(AgentAssignment::getTypes())
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $this->handleTypeChange($state, $set)),

                Forms\Components\TextInput::make('priority')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                // Time-based fields
                Forms\Components\TimePicker::make('start_time')
                    ->visible(fn (Forms\Get $get) => $get('assignment_type') === 'time_based'),

                Forms\Components\TimePicker::make('end_time')
                    ->visible(fn (Forms\Get $get) => $get('assignment_type') === 'time_based'),

                Forms\Components\CheckboxList::make('days_of_week')
                    ->options([
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                    ])
                    ->columns(7)
                    ->visible(fn (Forms\Get $get) => $get('assignment_type') === 'time_based'),

                // Service-based fields
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (Forms\Get $get) => $get('assignment_type') === 'service_based')
                    ->required(fn (Forms\Get $get) => $get('assignment_type') === 'service_based'),

                // Branch-based fields
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (Forms\Get $get) => $get('assignment_type') === 'branch_based')
                    ->required(fn (Forms\Get $get) => $get('assignment_type') === 'branch_based'),

                // A/B Testing fields
                Forms\Components\Section::make('A/B Testing')
                    ->schema([
                        Forms\Components\Toggle::make('is_test')
                            ->label('Enable A/B Testing')
                            ->reactive(),

                        Forms\Components\TextInput::make('traffic_percentage')
                            ->label('Traffic Percentage')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(50)
                            ->suffix('%')
                            ->visible(fn (Forms\Get $get) => $get('is_test')),

                        Forms\Components\DateTimePicker::make('test_start_date')
                            ->visible(fn (Forms\Get $get) => $get('is_test')),

                        Forms\Components\DateTimePicker::make('test_end_date')
                            ->visible(fn (Forms\Get $get) => $get('is_test'))
                            ->after('test_start_date'),
                    ])
                    ->columns(2),

                // Criteria field for complex assignments
                Forms\Components\KeyValue::make('criteria')
                    ->label('Additional Criteria')
                    ->addButtonLabel('Add Criterion')
                    ->keyLabel('Criterion')
                    ->valueLabel('Value')
                    ->helperText('Additional criteria for agent selection'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('assignment_type')
            ->columns([
                Tables\Columns\BadgeColumn::make('assignment_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => AgentAssignment::getTypes()[$state] ?? $state)
                    ->colors([
                        'primary' => 'time_based',
                        'success' => 'service_based',
                        'warning' => 'branch_based',
                        'info' => 'customer_segment',
                        'secondary' => 'language_based',
                        'danger' => 'skill_based',
                    ]),

                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('schedule')
                    ->label('Schedule')
                    ->getStateUsing(function ($record) {
                        if ($record->assignment_type !== 'time_based') {
                            return '-';
                        }
                        
                        $schedule = '';
                        if ($record->start_time && $record->end_time) {
                            $schedule = substr($record->start_time, 0, 5) . ' - ' . substr($record->end_time, 0, 5);
                        }
                        
                        if ($record->days_of_week) {
                            $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            $days = array_map(fn($day) => $dayNames[$day], $record->days_of_week);
                            $schedule .= ' (' . implode(', ', $days) . ')';
                        }
                        
                        return $schedule ?: '-';
                    }),

                Tables\Columns\TextColumn::make('target')
                    ->label('Target')
                    ->getStateUsing(function ($record) {
                        return match($record->assignment_type) {
                            'service_based' => $record->service?->name ?? '-',
                            'branch_based' => $record->branch?->name ?? '-',
                            default => '-',
                        };
                    }),

                Tables\Columns\IconColumn::make('is_test')
                    ->label('A/B Test')
                    ->boolean()
                    ->tooltip(fn ($record) => $record->is_test ? "Traffic: {$record->traffic_percentage}%" : null),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assignment_type')
                    ->options(AgentAssignment::getTypes()),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                
                Tables\Filters\TernaryFilter::make('is_test')
                    ->label('A/B Test'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('toggle')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('priority', 'desc');
    }

    protected function handleTypeChange(string $type, Forms\Set $set): void
    {
        // Reset fields when type changes
        match($type) {
            'time_based' => [
                $set('service_id', null),
                $set('branch_id', null),
            ],
            'service_based' => [
                $set('start_time', null),
                $set('end_time', null),
                $set('days_of_week', null),
                $set('branch_id', null),
            ],
            'branch_based' => [
                $set('start_time', null),
                $set('end_time', null),
                $set('days_of_week', null),
                $set('service_id', null),
            ],
            default => [
                $set('start_time', null),
                $set('end_time', null),
                $set('days_of_week', null),
                $set('service_id', null),
                $set('branch_id', null),
            ],
        };
    }
}