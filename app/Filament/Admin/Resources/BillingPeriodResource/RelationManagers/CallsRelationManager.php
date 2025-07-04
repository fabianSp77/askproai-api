<?php

namespace App\Filament\Admin\Resources\BillingPeriodResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CallsRelationManager extends RelationManager
{
    protected static string $relationship = 'calls';
    protected static ?string $title = 'Calls in Period';
    protected static ?string $icon = 'heroicon-o-phone';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('call_id')
                    ->label('Call ID')
                    ->searchable()
                    ->copyable()
                    ->limit(15),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->default('Unknown'),
                    
                Tables\Columns\TextColumn::make('from_number')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('start_timestamp')
                    ->label('Date & Time')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Duration')
                    ->state(fn ($record) => gmdate('i:s', $record->duration_sec ?? 0))
                    ->description(fn ($record) => 
                        number_format(($record->duration_sec ?? 0) / 60, 1) . ' min'
                    ),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        'no-answer' => 'warning',
                        'in-progress' => 'primary',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('appointment_status')
                    ->label('Booking')
                    ->state(function ($record) {
                        if ($record->appointment_id) {
                            return 'Booked';
                        }
                        return $record->appointment_scheduled ? 'Scheduled' : 'No booking';
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Booked' => 'success',
                        'Scheduled' => 'warning',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('cost_calculation')
                    ->label('Cost')
                    ->state(function ($record) use ($table) {
                        $period = $this->ownerRecord;
                        $minutes = ($record->duration_sec ?? 0) / 60;
                        
                        // Only charge for overage minutes
                        if ($period->used_minutes <= $period->included_minutes) {
                            return '€0.00';
                        }
                        
                        // Calculate this call's contribution to overage
                        $cost = $minutes * $period->price_per_minute;
                        return '€' . number_format($cost, 2);
                    })
                    ->description('Estimated')
                    ->alignEnd(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'no-answer' => 'No Answer',
                    ]),
                    
                Tables\Filters\Filter::make('has_booking')
                    ->label('Has Booking')
                    ->query(fn (Builder $query): Builder => 
                        $query->where(function ($q) {
                            $q->whereNotNull('appointment_id')
                              ->orWhere('appointment_scheduled', true);
                        })
                    )
                    ->toggle(),
                    
                Tables\Filters\Filter::make('long_calls')
                    ->label('Long Calls (>5 min)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('duration_sec', '>', 300)
                    )
                    ->toggle(),
            ])
            ->headerActions([
                // No create action for calls
            ])
            ->actions([
                Tables\Actions\Action::make('view_call')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => 
                        route('filament.admin.resources.calls.view', $record)
                    )
                    ->openUrlInNewTab(),
                    
                Tables\Actions\Action::make('play_recording')
                    ->label('Play')
                    ->icon('heroicon-o-play')
                    ->visible(fn ($record) => !empty($record->recording_url))
                    ->modalContent(fn ($record) => view('filament.modals.audio-player', [
                        'url' => $record->recording_url,
                        'title' => 'Call Recording'
                    ]))
                    ->modalHeading('Call Recording')
                    ->modalWidth('md'),
            ])
            ->bulkActions([
                // No bulk actions for calls
            ])
            ->defaultSort('start_timestamp', 'desc')
            ->paginated([10, 25, 50])
            ->poll('60s');
    }
    
    public function isReadOnly(): bool
    {
        return true;
    }
}