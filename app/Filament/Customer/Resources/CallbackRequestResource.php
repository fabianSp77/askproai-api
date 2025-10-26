<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\CallbackRequestResource\Pages;
use App\Models\CallbackRequest;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Illuminate\Database\Eloquent\Builder;

class CallbackRequestResource extends Resource
{
    protected static ?string $model = CallbackRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-down-left';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Rückrufe';
    protected static ?string $modelLabel = 'Rückruf';
    protected static ?string $pluralModelLabel = 'Rückrufe';
    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('company_id', auth()->user()->company_id)
            ->where('status', 'pending')
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('company_id', auth()->user()->company_id)
            ->where('status', 'pending')
            ->count();
        return $count > 5 ? 'danger' : ($count > 0 ? 'warning' : 'success');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->description(fn ($record) => $record->phone),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => '⏳ Ausstehend',
                        'in_progress' => '🔄 In Bearbeitung',
                        'completed' => '✅ Abgeschlossen',
                        'failed' => '❌ Fehlgeschlagen',
                        'cancelled' => '🚫 Storniert',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('preferred_time')
                    ->label('Gewünschte Zeit')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->description(fn ($record) =>
                        $record->preferred_time ? $record->preferred_time->diffForHumans() : null
                    ),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->badge()
                    ->colors([
                        'success' => 'low',
                        'warning' => 'normal',
                        'danger' => 'high',
                        'purple' => 'urgent',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'low' => '🟢 Niedrig',
                        'normal' => '🟡 Normal',
                        'high' => '🔴 Hoch',
                        'urgent' => '🟣 Dringend',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'failed' => 'Fehlgeschlagen',
                        'cancelled' => 'Storniert',
                    ])
                    ->default('pending'),

                SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options([
                        'low' => 'Niedrig',
                        'normal' => 'Normal',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Rückruf-Details')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'pending' => '⏳ Ausstehend',
                                        'in_progress' => '🔄 In Bearbeitung',
                                        'completed' => '✅ Abgeschlossen',
                                        'failed' => '❌ Fehlgeschlagen',
                                        'cancelled' => '🚫 Storniert',
                                        default => $state,
                                    }),

                                TextEntry::make('priority')
                                    ->label('Priorität')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'low' => '🟢 Niedrig',
                                        'normal' => '🟡 Normal',
                                        'high' => '🔴 Hoch',
                                        'urgent' => '🟣 Dringend',
                                        default => $state,
                                    }),

                                TextEntry::make('preferred_time')
                                    ->label('Gewünschte Zeit')
                                    ->dateTime('d.m.Y H:i'),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Kunde')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('phone')
                                    ->label('Telefon')
                                    ->icon('heroicon-o-phone'),
                            ]),

                        TextEntry::make('reason')
                            ->label('Grund')
                            ->placeholder('Kein Grund angegeben')
                            ->columnSpanFull(),

                        TextEntry::make('notes')
                            ->label('Notizen')
                            ->placeholder('Keine Notizen')
                            ->columnSpanFull(),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-calendar'),

                                TextEntry::make('completed_at')
                                    ->label('Abgeschlossen am')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch nicht abgeschlossen'),
                            ]),
                    ])
                    ->icon('heroicon-o-phone-arrow-down-left')
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallbackRequests::route('/'),
            'view' => Pages\ViewCallbackRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id)
            ->with(['customer:id,name']);
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
        return ['customer.name', 'phone', 'reason'];
    }
}
