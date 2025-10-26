<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\CallHistoryResource\Pages;
use App\Models\RetellCallSession;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CallHistoryResource extends Resource
{
    protected static ?string $model = RetellCallSession::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Anrufliste';
    protected static ?string $modelLabel = 'Anruf';
    protected static ?string $pluralModelLabel = 'Anrufe';
    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('call_id')
                    ->label('Anruf-ID')
                    ->searchable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Telefon')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('call_status')
                    ->label('Status')
                    ->colors([
                        'success' => 'completed',
                        'danger' => 'failed',
                        'warning' => 'in_progress',
                    ]),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Dauer')
                    ->formatStateUsing(fn ($state) => $state ? round($state / 1000) . 's' : '-'),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('call_status')
                    ->options([
                        'completed' => 'Abgeschlossen',
                        'failed' => 'Fehlgeschlagen',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallHistory::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
