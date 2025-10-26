<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\CustomerNoteResource\Pages;
use App\Models\CustomerNote;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Illuminate\Database\Eloquent\Builder;

class CustomerNoteResource extends Resource
{
    protected static ?string $model = CustomerNote::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Notizen';
    protected static ?string $modelLabel = 'Notiz';
    protected static ?string $pluralModelLabel = 'Notizen';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereHas('customer', fn ($q) =>
            $q->where('company_id', auth()->user()->company_id)
        )->count();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Betreff')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),

                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->colors([
                        'info' => 'general',
                        'warning' => 'important',
                        'success' => 'feedback',
                        'danger' => 'complaint',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'general' => 'Allgemein',
                        'important' => 'Wichtig',
                        'feedback' => 'Feedback',
                        'complaint' => 'Beschwerde',
                        'follow_up' => 'Follow-up',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Erstellt von')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Kunde')
                    ->relationship('customer', 'name', fn ($query) =>
                        $query->where('company_id', auth()->user()->company_id)
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'general' => 'Allgemein',
                        'important' => 'Wichtig',
                        'feedback' => 'Feedback',
                        'complaint' => 'Beschwerde',
                        'follow_up' => 'Follow-up',
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
                InfoSection::make('Notiz-Details')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Kunde')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('type')
                                    ->label('Typ')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'general' => 'Allgemein',
                                        'important' => 'Wichtig',
                                        'feedback' => 'Feedback',
                                        'complaint' => 'Beschwerde',
                                        'follow_up' => 'Follow-up',
                                        default => $state,
                                    }),
                            ]),

                        TextEntry::make('subject')
                            ->label('Betreff')
                            ->weight('bold')
                            ->size('lg')
                            ->columnSpanFull(),

                        TextEntry::make('content')
                            ->label('Inhalt')
                            ->columnSpanFull()
                            ->markdown(),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('createdBy.name')
                                    ->label('Erstellt von')
                                    ->icon('heroicon-o-user-circle'),

                                TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-calendar'),
                            ]),
                    ])
                    ->icon('heroicon-o-document-text')
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerNotes::route('/'),
            'view' => Pages\ViewCustomerNote::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('customer', fn ($query) =>
                $query->where('company_id', auth()->user()->company_id)
            )
            ->with(['customer:id,name', 'createdBy:id,name']);
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
        return ['subject', 'content', 'customer.name'];
    }
}
