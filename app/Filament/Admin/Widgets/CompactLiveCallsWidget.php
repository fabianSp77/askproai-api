<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CompactLiveCallsWidget extends BaseWidget
{
    protected static ?int $sort = -99; // Show after LiveCallsWidget
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static ?string $heading = 'Aktive Anrufe (Kompakt)';
    
    public static function canView(): bool
    {
        // Only show if there are more than 10 active calls
        return Call::query()
            ->whereNull('end_timestamp')
            ->where('created_at', '>', now()->subHours(2))
            ->count() > 10;
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Call::query()
                    ->whereNull('end_timestamp')
                    ->where('created_at', '>', now()->subHours(2))
                    ->orderBy('start_timestamp', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('from_number')
                    ->label('Anrufer')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-phone'),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->default('Unbekannt')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('duration_live')
                    ->label('Dauer')
                    ->getStateUsing(function (Call $record): string {
                        if (!$record->start_timestamp) {
                            return '00:00';
                        }
                        $seconds = now()->diffInSeconds($record->start_timestamp);
                        return sprintf('%02d:%02d', floor($seconds / 60), $seconds % 60);
                    })
                    ->badge()
                    ->color('danger'),
                    
                Tables\Columns\TextColumn::make('agent_id')
                    ->label('Agent')
                    ->limit(15),
                    
                Tables\Columns\TextColumn::make('start_timestamp')
                    ->label('Start')
                    ->dateTime('H:i:s')
                    ->sortable(),
                    
                Tables\Columns\ViewColumn::make('status')
                    ->label('Status')
                    ->view('filament.tables.columns.live-indicator'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Call $record) => "/admin/calls/{$record->id}")
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->striped()
            ->poll('5s');
    }
}