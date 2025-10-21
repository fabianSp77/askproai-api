<?php

namespace App\Filament\Resources\PhoneNumberResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CallsRelationManager extends RelationManager
{
    protected static string $relationship = 'calls';

    protected static ?string $title = 'Call History';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('call_id')
                    ->label('Call ID')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('started_at')
                    ->label('Started At')
                    ->disabled(),
                Forms\Components\TextInput::make('duration')
                    ->label('Duration (seconds)')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('call_id')
                    ->label('Call ID')
                    ->searchable()
                    ->sortable()
                    ,
                Tables\Columns\TextColumn::make('customer_display')
                    ->label('Customer')
                    ->getStateUsing(function ($record) {
                        // Same logic as in CallResource
                        if ($record->customer_name) {
                            $prefix = '';
                            if ($record->customer_name_verified === true) {
                                $prefix = '✓ ';
                            } elseif ($record->customer_name_verified === false) {
                                $prefix = '? ';
                            }
                            return $prefix . $record->customer_name;
                        }

                        if ($record->customer_id && $record->customer) {
                            return '✓ ' . $record->customer->name;
                        }

                        return $record->from_number === 'anonymous' ? 'Anonym' : 'Unknown';
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ended_at')
                    ->label('Ended')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'N/A';
                        $minutes = floor($state / 60);
                        $seconds = $state % 60;
                        return sprintf('%d:%02d', $minutes, $seconds);
                    })
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'in_progress',
                        'danger' => 'failed',
                        'secondary' => 'no_answer',
                    ]),
                Tables\Columns\TextColumn::make('direction')
                    ->label('Direction')
                    ->badge()
                    ->colors([
                        'primary' => 'inbound',
                        'success' => 'outbound',
                    ]),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'in_progress' => 'In Progress',
                        'failed' => 'Failed',
                        'no_answer' => 'No Answer',
                    ]),
                Tables\Filters\SelectFilter::make('direction')
                    ->options([
                        'inbound' => 'Inbound',
                        'outbound' => 'Outbound',
                    ]),
                Tables\Filters\Filter::make('today')
                    ->query(fn ($query) => $query->whereDate('started_at', today()))
                    ->label('Today'),
                Tables\Filters\Filter::make('this_week')
                    ->query(fn ($query) => $query->whereBetween('started_at', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->label('This Week'),
            ])
            ->headerActions([
                // No create action for calls - they're created by the system
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for call history
            ])
            ->emptyStateHeading('No calls yet')
            ->emptyStateDescription('Call history will appear here once calls are made.')
            ->emptyStateIcon('heroicon-o-phone');
    }
}