<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\StaffResource\Pages;
use App\Models\Staff;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Illuminate\Database\Eloquent\Builder;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?string $navigationLabel = 'Personal';
    protected static ?string $modelLabel = 'Mitarbeiter';
    protected static ?string $pluralModelLabel = 'Mitarbeiter';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->count();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['company', 'branch'])
                    ->withCount(['appointments' => fn ($q) => $q->where('status', 'confirmed')])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Mitarbeiter')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) =>
                        ($record->branch?->name ?: 'Keine Filiale') .
                        ($record->experience_level ? ' • Level ' . $record->experience_level : '')
                    )
                    ->icon(fn ($record) => match($record->experience_level) {
                        1 => 'heroicon-m-academic-cap',
                        2 => 'heroicon-m-user',
                        3 => 'heroicon-m-user-plus',
                        4 => 'heroicon-m-star',
                        5 => 'heroicon-m-trophy',
                        default => 'heroicon-m-user',
                    }),

                Tables\Columns\TextColumn::make('contact')
                    ->label('Kontakt')
                    ->getStateUsing(fn ($record) =>
                        ($record->email ?: '') .
                        ($record->email && $record->phone ? ' • ' : '') .
                        ($record->phone ?: '')
                    )
                    ->searchable(['email', 'phone'])
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('experience_level')
                    ->label('Level')
                    ->badge()
                    ->colors([
                        'gray' => 1,
                        'info' => 2,
                        'warning' => 3,
                        'success' => 4,
                        'purple' => 5,
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        1 => '🌱 Anfänger',
                        2 => '🌿 Junior',
                        3 => '🌳 Erfahren',
                        4 => '🏆 Senior',
                        5 => '👑 Expert',
                        default => '❓ Unbekannt',
                    }),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\IconColumn::make('is_bookable')
                    ->label('Buchbar')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Bewertung')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' ⭐' : '-')
                    ->color('warning')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('appointments_count')
                    ->label('Termine')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name', fn ($query) =>
                        $query->where('company_id', auth()->user()->company_id)
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('experience_level')
                    ->label('Erfahrungslevel')
                    ->options([
                        1 => '🌱 Anfänger',
                        2 => '🌿 Junior',
                        3 => '🌳 Erfahren',
                        4 => '🏆 Senior',
                        5 => '👑 Expert',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Aktiv')
                    ->falseLabel('Inaktiv'),

                Tables\Filters\TernaryFilter::make('is_bookable')
                    ->label('Buchbar')
                    ->placeholder('Alle')
                    ->trueLabel('Ja')
                    ->falseLabel('Nein'),
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
                InfoSection::make('Mitarbeiter-Informationen')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name')
                                    ->icon('heroicon-o-user')
                                    ->size('lg'),

                                TextEntry::make('branch.name')
                                    ->label('Filiale')
                                    ->icon('heroicon-o-building-storefront')
                                    ->badge(),
                            ]),

                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('email')
                                    ->label('E-Mail')
                                    ->icon('heroicon-o-envelope')
                                    ->placeholder('Keine E-Mail'),

                                TextEntry::make('phone')
                                    ->label('Telefon')
                                    ->icon('heroicon-o-phone')
                                    ->placeholder('Keine Telefonnummer'),

                                TextEntry::make('experience_level')
                                    ->label('Erfahrungslevel')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        1 => '🌱 Anfänger',
                                        2 => '🌿 Junior',
                                        3 => '🌳 Erfahren',
                                        4 => '🏆 Senior',
                                        5 => '👑 Expert',
                                        default => 'Unbekannt',
                                    }),
                            ]),

                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('is_active')
                                    ->label('Aktiv')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nein')
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                                TextEntry::make('is_bookable')
                                    ->label('Buchbar')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nein')
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                                TextEntry::make('average_rating')
                                    ->label('Bewertung')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' ⭐' : 'Noch keine Bewertungen')
                                    ->badge()
                                    ->color('warning'),
                            ]),
                    ])
                    ->icon('heroicon-o-user')
                    ->collapsible(),

                InfoSection::make('Fähigkeiten & Qualifikationen')
                    ->schema([
                        TextEntry::make('skills')
                            ->label('Fähigkeiten')
                            ->placeholder('Keine Fähigkeiten hinterlegt')
                            ->columnSpanFull(),

                        TextEntry::make('specializations')
                            ->label('Spezialisierungen')
                            ->placeholder('Keine Spezialisierungen hinterlegt')
                            ->columnSpanFull(),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('languages')
                                    ->label('Sprachen')
                                    ->placeholder('Keine Sprachen hinterlegt'),

                                TextEntry::make('certifications')
                                    ->label('Zertifikate')
                                    ->placeholder('Keine Zertifikate hinterlegt'),
                            ]),
                    ])
                    ->icon('heroicon-o-academic-cap')
                    ->collapsible()
                    ->collapsed(true),

                InfoSection::make('Arbeitszeiten')
                    ->schema([
                        TextEntry::make('working_hours')
                            ->label('Arbeitszeiten')
                            ->placeholder('Keine Arbeitszeiten hinterlegt')
                            ->columnSpanFull(),

                        TextEntry::make('notes')
                            ->label('Notizen')
                            ->placeholder('Keine Notizen')
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'view' => Pages\ViewStaff::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id)
            ->with(['company', 'branch']);
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
        return ['name', 'email', 'phone'];
    }
}
