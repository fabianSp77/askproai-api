<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;

class RecentCalls extends TableWidget
{
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 'full',
        'lg' => 1,
        'xl' => 1,
    ];
    
    protected static bool $isLazy = false;
    
    protected static ?int $sort = 7;
    
    protected static ?string $heading = 'Letzte Anrufe';
    
    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Call::query()
                    ->with(['customer'])
                    ->latest()
                    ->limit(15)
            )
            ->emptyStateHeading('Keine Anrufe')
            ->emptyStateDescription('Es wurden noch keine Anrufe getätigt.')
            ->emptyStateIcon('heroicon-o-phone-x-mark')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeit')
                    ->dateTime('H:i')
                    ->description(fn (Call $record): string => 
                        Carbon::parse($record->created_at)->format('d.m.Y')
                    )
                    ->sortable()
                    ->color(fn (Call $record): string => 
                        $record->created_at->isToday() ? 'primary' : 'gray'
                    ),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->default(fn (Call $record): string => 
                        $record->phone_number ?? 'Unbekannt'
                    )
                    ->searchable()
                    ->limit(25)
                    ->description(fn (Call $record): ?string => 
                        $record->phone_number
                    ),
                    
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Dauer')
                    ->formatStateUsing(fn (int $state): string => 
                        $state > 0 ? gmdate('i:s', $state) : '-'
                    )
                    ->icon('heroicon-o-clock')
                    ->iconColor(fn (int $state): string => 
                        $state > 300 ? 'warning' : 'gray'
                    ),
                    
                Tables\Columns\IconColumn::make('appointment_id')
                    ->label('Termin')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (Call $record): string => 
                        $record->appointment_id ? 'Termin erstellt' : 'Kein Termin'
                    ),
                    
                Tables\Columns\TextColumn::make('call_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'in-progress', 'in_progress' => 'In Bearbeitung',
                        'ended', 'completed' => 'Beendet',
                        'error', 'failed' => 'Fehler',
                        'missed' => 'Verpasst',
                        null => 'Unbekannt',
                        default => ucfirst($state ?? 'Unbekannt')
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'completed', 'ended' => 'success',
                        'in_progress', 'in-progress' => 'warning',
                        'missed' => 'danger',
                        'failed', 'error' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable()
                    ->toggledHiddenByDefault(true),
            ])
            ->striped()
            ->poll('15s')
            ->actions([
                Tables\Actions\Action::make('transcript')
                    ->label('Transkript')
                    ->icon('heroicon-o-document-text')
                    ->iconButton()
                    ->tooltip('Transkript anzeigen')
                    ->modalHeading('Anruf-Transkript')
                    ->modalContent(fn (Call $record): HtmlString => new HtmlString(
                        '<div class="prose dark:prose-invert max-w-none">' . 
                        nl2br(e($record->transcript ?? 'Kein Transkript verfügbar')) . 
                        '</div>'
                    ))
                    ->modalSubmitAction(false)
                    ->visible(fn (Call $record): bool => 
                        !empty($record->transcript)
                    ),
                    
                Tables\Actions\Action::make('create_appointment')
                    ->label('Termin')
                    ->icon('heroicon-o-calendar')
                    ->iconButton()
                    ->tooltip('Termin erstellen')
                    ->color('success')
                    ->visible(fn (Call $record): bool => 
                        empty($record->appointment_id) && !empty($record->customer_id)
                    )
                    ->url(fn (Call $record): string => 
                        '/admin/appointments/create?customer=' . $record->customer_id
                    ),
            ]);
    }
}
