<?php

namespace App\Filament\Resources\CallResource\Widgets;

use App\Models\Call;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentCallsActivity extends BaseWidget
{
    protected static ?string $heading = 'Letzte Anrufe';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Call::with(['customer', 'agent', 'company'])  // Fixed relationship name
            ->latest('created_at')
            ->limit(10);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeit')
                    ->dateTime('H:i')
                    ->description(fn ($record) => $record->created_at->format('d.m.Y'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->limit(20)
                    ->url(fn ($record) => $record->customer
                        ? route('filament.admin.resources.customers.edit', $record->customer)
                        : null),
                Tables\Columns\BadgeColumn::make('call_successful')  // ✅ Uses accessor (status === 'completed')
                    ->label('Status')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-m-x-circle'),
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Dauer')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        $minutes = floor($state / 60);
                        $seconds = $state % 60;
                        return $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
                    }),
                Tables\Columns\TextColumn::make('sentiment')
                    ->label('Stimmung')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'positive' => '😊 Positiv',
                        'neutral' => '😐 Neutral',
                        'negative' => '😟 Negativ',
                        null => '-',
                        default => $state,
                    })
                    ->color(fn (?string $state): string => match($state) {
                        'positive' => 'success',
                        'neutral' => 'gray',
                        'negative' => 'danger',
                        default => 'secondary',
                    }),
                Tables\Columns\IconColumn::make('appointment_made')  // ✅ Uses accessor (has_appointment)
                    ->label('Termin')
                    ->boolean()
                    ->trueIcon('heroicon-o-calendar-days')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('agent.name')
                    ->label('Agent')
                    ->limit(15)
                    ->default('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('calculated_cost')  // ✅ FIXED: uses calculated_cost instead of cost_cents
                    ->label('Kosten')
                    ->money('EUR', divideBy: 100)
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10])
            ->poll('10s')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Call $record): string => route('filament.admin.resources.calls.view', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('playRecording')
                    ->label('Anhören')
                    ->icon('heroicon-m-play')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->recording_url))
                    ->url(fn ($record) => $record->recording_url)
                    ->openUrlInNewTab(),
            ])
            ->striped()
            ->emptyStateHeading('Noch keine Anrufe heute')
            ->emptyStateDescription('Neue Anrufe werden hier automatisch angezeigt')
            ->emptyStateIcon('heroicon-o-phone');
    }
}