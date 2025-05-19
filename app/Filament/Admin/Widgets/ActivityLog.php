<?php

namespace App\Filament\Admin\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends TableWidget
{
    protected static ?string $heading = 'Letzte Aktivitäten';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Activity::query()
                ->latest()
                ->limit(20)
            )
            ->columns([
                TextColumn::make('description')
                    ->label('Aktion')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Zeitpunkt')
                    ->since()
                    ->sortable(),
            ]);
    }
}
