<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentCalls extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Call::query()->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable(),
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Dauer (s)')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make('view')
                    ->label('Details anzeigen'),
            ]);
    }
}
