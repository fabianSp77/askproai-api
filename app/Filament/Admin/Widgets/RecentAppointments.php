<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentAppointments extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Appointment::query()->latest('starts_at')->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Start')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make('view')
                    ->label('Details anzeigen'),
            ]);
    }
}
