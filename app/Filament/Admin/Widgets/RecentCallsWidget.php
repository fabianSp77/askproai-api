<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Filament\Admin\Resources\CallResource;
use App\Filament\Admin\Resources\CustomerResource;
use App\Filament\Admin\Resources\AppointmentResource;

class RecentCallsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '10s';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Call::query()
                    ->with(['customer', 'appointment'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeit')
                    ->dateTime('H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
                    
                Tables\Columns\TextColumn::make('from_number')
                    ->label('Anrufer')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-phone'),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->placeholder('Unbekannt')
                    ->url(fn ($record) => $record->customer ? CustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                    
                Tables\Columns\TextColumn::make('sentiment')
                    ->label('Stimmung')
                    ->getStateUsing(function ($record) {
                        // Try different ways to get sentiment
                        $sentiment = $record->sentiment ?? 
                                   $record->user_sentiment ?? 
                                   (is_array($record->analysis) ? ($record->analysis['sentiment'] ?? null) : null);
                        
                        return match($sentiment) {
                            'positive' => 'Positiv',
                            'negative' => 'Negativ',
                            'neutral' => 'Neutral',
                            default => '-'
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Positiv' => 'success',
                        'Negativ' => 'danger',
                        'Neutral' => 'gray',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Dauer')
                    ->formatStateUsing(fn ($state) => gmdate('i:s', $state)),
                    
                Tables\Columns\IconColumn::make('appointment_id')
                    ->label('Termin')
                    ->boolean()
                    ->trueIcon('heroicon-o-calendar-days')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                    
                Tables\Columns\TextColumn::make('analysis')
                    ->label('Absicht')
                    ->placeholder('-')
                    ->getStateUsing(function ($record) {
                        $intent = null;
                        if (is_array($record->analysis) && isset($record->analysis['intent'])) {
                            $intent = $record->analysis['intent'];
                        }
                        
                        return match($intent) {
                            'appointment_booking' => 'Terminbuchung',
                            'cancellation' => 'Stornierung',
                            'inquiry' => 'Anfrage',
                            null => '-',
                            default => ucfirst(str_replace('_', ' ', $intent))
                        };
                    })
                    ->badge()
                    ->color('info'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => CallResource::getUrl('view', ['record' => $record])),
                    
                Tables\Actions\Action::make('play')
                    ->label('Anhören')
                    ->icon('heroicon-o-play-circle')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->audio_url))
                    ->modalContent(fn ($record) => view('filament.modals.audio-player', [
                        'url' => $record->audio_url,
                        'duration' => $record->duration_sec,
                    ]))
                    ->modalHeading('Anrufaufzeichnung')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen'),
            ])
            ->bulkActions([])
            ->paginated(false);
    }
}