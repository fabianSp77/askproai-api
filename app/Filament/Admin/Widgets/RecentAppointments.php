<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentAppointments extends TableWidget
{
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 'full',
        'lg' => 2,
        'xl' => 2,
    ];
    
    protected static bool $isLazy = false;
    
    protected static ?int $sort = 6;
    
    protected static ?string $heading = 'Nächste Termine';
    
    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::query()
                    ->where('starts_at', '>=', Carbon::now())
                    ->orderBy('starts_at')
                    ->limit(10)
            )
            ->emptyStateHeading('Keine anstehenden Termine')
            ->emptyStateDescription('Es sind aktuell keine Termine geplant.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Datum & Zeit')
                    ->dateTime('d.m. H:i')
                    ->description(fn (Appointment $record): string => 
                        Carbon::parse($record->starts_at)->diffForHumans()
                    )
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->iconColor(fn (Appointment $record): string => 
                        Carbon::parse($record->starts_at)->isToday() ? 'warning' : 'gray'
                    ),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn (Appointment $record): ?string => 
                        $record->customer?->name
                    ),
                    
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->limit(15)
                    ->toggleable()
                    ->toggledHiddenByDefault(true),
                    
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->limit(15)
                    ->default('Nicht zugewiesen')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'gray',
                        'no_show' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'Geplant',
                        'confirmed' => 'Bestätigt',
                        'cancelled' => 'Abgesagt',
                        'completed' => 'Abgeschlossen',
                        'no_show' => 'Nicht erschienen',
                        default => ucfirst($state),
                    }),
            ])
            ->striped()
            ->poll('30s')
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Bestätigen')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Appointment $record): bool => 
                        $record->status === 'scheduled'
                    )
                    ->requiresConfirmation()
                    ->action(fn (Appointment $record) => 
                        $record->update(['status' => 'confirmed'])
                    ),
                    
                Tables\Actions\ViewAction::make()
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->tooltip('Details anzeigen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('confirm_selected')
                    ->label('Ausgewählte bestätigen')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['status' => 'confirmed'])),
            ]);
    }
}
