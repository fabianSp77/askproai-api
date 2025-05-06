<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CallResource\Pages;
use App\Jobs\RefreshCallDataJob;
use App\Models\Call;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CallResource extends Resource
{
    /** Model & Menü-Infos */
    protected static ?string $model           = Call::class;
    protected static ?string $navigationGroup = 'Buchungen';
    protected static ?string $navigationIcon  = 'heroicon-o-phone';

    /** Tabelle */
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                /* Datum */
                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                /* Kunde */
                TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable(),

                /* Dauer */
                TextColumn::make('duration_sec')
                    ->label('Dauer')
                    ->formatStateUsing(
                        fn (?int $state) => $state
                            ? $state.' s ('.gmdate('i:s', $state).' min)'
                            : '–'
                    ),

                /* Kosten */
                TextColumn::make('cost_euro')
                    ->label('Kosten')
                    ->money('eur'),

                /* Ampel-Icon – hat Transkript? */
                IconColumn::make('transcript')
                    ->label('tg.')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')->trueColor('success')
                    ->falseIcon('heroicon-o-clock')->falseColor('warning'),

                /* Kurz-Vorschau */
                TextColumn::make('transcript')
                    ->label('Transkript')
                    ->limit(120)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->extraAttributes(['style' => 'max-width:400px']),
            ])

            ->actions([
                /* Vollansicht als Modal */
                Action::make('showTranscript')
                    ->label('Voll')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn (Call $c) => filled($c->transcript))
                    ->modalHeading('Vollständiges Transkript')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen')
                    ->modalContent(fn (Call $c) => view(
                        'filament.modals.transcript',
                        ['text' => $c->transcript]
                    )),

                /* Daten bei Retell nachladen */
                Action::make('refresh')
                    ->label('Aktualisieren')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn (Call $c) => RefreshCallDataJob::dispatch($c))
                    ->successNotificationTitle('Job gestartet'),
            ]);
    }

    /** Seiten-Routing */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalls::route('/'),
        ];
    }
}
