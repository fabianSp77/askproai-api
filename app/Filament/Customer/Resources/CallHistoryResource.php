<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Customer\Resources\CallHistoryResource\Pages;
use App\Models\RetellCallSession;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class CallHistoryResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = RetellCallSession::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Anrufliste';
    protected static ?string $modelLabel = 'Anruf';
    protected static ?string $pluralModelLabel = 'Anrufe';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return static::getCachedBadge(function() {
            return static::getModel()::where('company_id', auth()->user()->company_id)
                ->where('call_status', 'failed')
                ->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getCachedBadgeColor(function() {
            $count = static::getModel()::where('company_id', auth()->user()->company_id)
                ->where('call_status', 'failed')
                ->count();
            return $count > 5 ? 'danger' : ($count > 0 ? 'warning' : null);
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('call_id')
                    ->label('📞 Anruf-ID')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->limit(20)
                    ->weight('medium')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\BadgeColumn::make('call_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'in_progress' => '🔄 Läuft',
                        'completed' => '✅ Abgeschlossen',
                        'failed' => '❌ Fehlgeschlagen',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->default('—')
                    ->icon('heroicon-m-user')
                    ->description(fn ($record) => $record->phone_number),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('📱 Telefon')
                    ->default('—')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('⏰ Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->started_at?->diffForHumans()),

                Tables\Columns\TextColumn::make('duration_seconds')
                    ->label('⏱️ Dauer')
                    ->getStateUsing(fn ($record) => $record->getDurationSeconds() ? $record->getDurationSeconds() . 's' : '—')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('duration_ms', $direction);
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('function_call_count')
                    ->label('🔧 Funktionen')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('error_count')
                    ->label('⚠️ Fehler')
                    ->colors([
                        'success' => 0,
                        'warning' => fn ($state) => $state > 0 && $state <= 2,
                        'danger' => fn ($state) => $state > 2,
                    ])
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('call_status')
                    ->label('Status')
                    ->options([
                        'in_progress' => '🔄 Läuft',
                        'completed' => '✅ Abgeschlossen',
                        'failed' => '❌ Fehlgeschlagen',
                    ]),

                Tables\Filters\Filter::make('has_errors')
                    ->label('Mit Fehlern')
                    ->query(fn (Builder $query): Builder => $query->where('error_count', '>', 0)),

                Tables\Filters\Filter::make('recent')
                    ->label('Letzte 24 Stunden')
                    ->query(fn (Builder $query): Builder => $query->where('started_at', '>=', now()->subHours(24))),

                Tables\Filters\Filter::make('slow_calls')
                    ->label('Langsame Anrufe (>5s)')
                    ->query(fn (Builder $query): Builder => $query->where('duration_ms', '>', 5000)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultPaginationPageOption(25)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Anruf-Details')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('call_id')
                                    ->label('Anruf-ID')
                                    ->copyable()
                                    ->icon('heroicon-o-identification'),

                                Infolists\Components\TextEntry::make('call_status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'in_progress' => '🔄 Läuft',
                                        'completed' => '✅ Abgeschlossen',
                                        'failed' => '❌ Fehlgeschlagen',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match($state) {
                                        'in_progress' => 'warning',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('duration_seconds')
                                    ->label('Dauer')
                                    ->getStateUsing(fn ($record) => $record->getDurationSeconds() ? $record->getDurationSeconds() . 's' : '—')
                                    ->icon('heroicon-o-clock'),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Kunde')
                                    ->default('Unbekannt')
                                    ->icon('heroicon-o-user'),

                                Infolists\Components\TextEntry::make('phone_number')
                                    ->label('Telefonnummer')
                                    ->copyable()
                                    ->default('—')
                                    ->icon('heroicon-o-phone'),

                                Infolists\Components\TextEntry::make('started_at')
                                    ->label('Gestartet am')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->icon('heroicon-o-calendar'),

                                Infolists\Components\TextEntry::make('ended_at')
                                    ->label('Beendet am')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->default('—')
                                    ->icon('heroicon-o-calendar-days'),
                            ]),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Performance-Metriken')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('function_call_count')
                                    ->label('Funktionsaufrufe')
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('error_count')
                                    ->label('Fehleranzahl')
                                    ->badge()
                                    ->color(fn ($state): string => match(true) {
                                        $state == 0 => 'success',
                                        $state <= 2 => 'warning',
                                        default => 'danger',
                                    }),

                                Infolists\Components\TextEntry::make('avg_response_time_ms')
                                    ->label('Ø Antwortzeit')
                                    ->suffix(' ms')
                                    ->default('—'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallHistory::route('/'),
            'view' => Pages\ViewCallHistory::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id)
            ->with([
                'customer',
                'company',
            ])
            ->withCount([
                'functionTraces',
                'errors',
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['call_id', 'phone_number', 'customer.name'];
    }
}
