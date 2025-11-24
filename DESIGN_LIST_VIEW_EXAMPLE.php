<?php

namespace App\Filament\Resources\CallResource\Pages;

use Filament\Tables;
use Filament\Tables\Table;

class ListCalls extends ListRecords
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Call ID')
                    ->prefix('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime('M j, H:i')
                    ->sortable(),

                // PRIMARY STATUS COLUMN
                Tables\Columns\BadgeColumn::make('appointment.status')
                    ->label('Status')
                    ->formatStateUsing(function ($state, $record) {
                        // Show abbreviated version in badge
                        return match($state) {
                            'scheduled' => 'Scheduled',
                            'completed' => 'Complete',
                            'cancelled' => 'Cancelled',
                            'no_show' => 'No Show',
                            default => 'N/A'
                        };
                    })
                    ->colors([
                        'success' => 'scheduled',
                        'info' => 'completed',
                        'warning' => 'cancelled',
                        'danger' => 'no_show',
                    ])
                    ->icon(fn ($state) => $state === 'cancelled' ? 'heroicon-o-exclamation-triangle' : null)

                    // TOOLTIP WITH CANCELLATION DETAILS
                    ->tooltip(function ($record) {
                        if ($record->appointment?->status !== 'cancelled') {
                            return null;
                        }

                        $cancellation = $record->appointment->cancellation;
                        if (!$cancellation) {
                            return 'Cancelled (details unavailable)';
                        }

                        return view('filament.tooltips.cancellation-info', [
                            'cancellation' => $cancellation,
                            'appointment' => $record->appointment,
                        ])->render();
                    }),

                Tables\Columns\TextColumn::make('duration')
                    ->formatStateUsing(fn ($state) => gmdate('i:s', $state)),

                // LINKED CALL INDICATOR (subtle)
                Tables\Columns\IconColumn::make('has_related_calls')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-link')
                    ->falseIcon('')
                    ->tooltip(function ($record) {
                        $relatedCount = $record->relatedCalls()->count();
                        if ($relatedCount > 0) {
                            return "{$relatedCount} related call(s)";
                        }
                        return null;
                    }),
            ])

            // Row styling for cancelled appointments
            ->recordClasses(function ($record) {
                if ($record->appointment?->status === 'cancelled') {
                    return 'bg-orange-50 dark:bg-orange-950/10';
                }
                return '';
            });
    }
}
