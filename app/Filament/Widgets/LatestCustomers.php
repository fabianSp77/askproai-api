<?php

namespace App\Filament\Widgets;

use App\Models\Customer;          // dein Customer-Modell
use App\Filament\Resources\CustomerResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Colors\Color;

class LatestCustomers extends TableWidget
{
    /** Überschrift & Spaltenbreite – gerne anpassen */
    protected static ?string $heading = 'Neueste Kunden';
    protected int|string|array $columnSpan = 'full';

    /**
     * **Wichtig:** `query()` MUSS einen Builder / Closure bekommen.
     * Wir holen die 15 zuletzt angelegten Kunden.
     */
    public function table(Table $table): Table
    {
        try {
        return $table
            ->query(fn () => Customer::query()
                ->latest('created_at')
                ->limit(15)
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('email')
                    ->label('E-Mail')
                    ->color(Color::Blue)
                    ->copyable()
                    ->copyMessage('E-Mail kopiert')
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Registriert')
                    ->since()      // "vor 2 Tagen"
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Customer $record): string => CustomerResource::getUrl('edit', ['record' => $record])),
            ]);
        } catch (\Exception $e) {
            \Log::error('LatestCustomers Widget Error: ' . $e->getMessage());
            return $table
                ->query(fn () => Customer::query()->whereRaw('0=1')) // Empty query on error
                ->columns([]);
        }
    }
}
