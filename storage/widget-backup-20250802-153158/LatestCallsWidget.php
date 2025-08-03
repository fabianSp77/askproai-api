<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestCallsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Neueste Anrufe';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Call::query()
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('call_sid')
                    ->label('Call ID')
                    ->searchable()
                    ->limit(20),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->default('Unbekannt')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Telefon')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('duration')
                    ->label('Dauer')
                    ->formatStateUsing(fn ($state) => $state ? gmdate('i:s', $state) : '-'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'in-progress' => 'warning',
                        'failed' => 'danger',
                        default => 'secondary',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ansehen')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Call $record): string => route('filament.admin.resources.calls.view', $record))
            ])
            ->paginated(false);
    }
}