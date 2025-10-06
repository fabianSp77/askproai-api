<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogWidget extends BaseWidget
{
    protected static ?int $sort = 10; // Moved to end to prevent loading issues
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = null; // Disable polling for this widget

    public function table(Table $table): Table
    {
        try {
        return $table
            ->query(
                User::query()
                    ->select('id', 'name', 'email', 'created_at', 'updated_at')
                    ->latest('updated_at')
                    ->limit(5) // Reduced from 10 to 5 for better performance
            )
            ->deferLoading() // Enable lazy loading
            ->paginated([5, 10, 20]) // Add pagination
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Benutzer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registriert am')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Letztes Update')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped();
        } catch (\Exception $e) {
            \Log::error('ActivityLogWidget Error: ' . $e->getMessage());
            return $table
                ->query(User::query()->whereRaw('0=1')) // Empty query on error
                ->columns([
                    Tables\Columns\TextColumn::make('error')
                        ->label('Fehler')
                        ->default('Widget konnte nicht geladen werden')
                ]);
        }
    }
}
