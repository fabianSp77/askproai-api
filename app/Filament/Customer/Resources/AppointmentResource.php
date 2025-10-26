<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Termine';
    protected static ?string $modelLabel = 'Termin';
    protected static ?string $pluralModelLabel = 'Termine';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('company_id', auth()->user()->company_id)
            ->whereNotNull('starts_at')
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('company_id', auth()->user()->company_id)
            ->whereNotNull('starts_at')
            ->count();
        return $count > 50 ? 'danger' : ($count > 20 ? 'warning' : 'info');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                // Time slot column with smart formatting
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Zeit')
                    ->dateTime('H:i')
                    ->date('d.m.Y')
                    ->description(fn ($record) =>
                        Carbon::parse($record->starts_at)->format('D') . ' | ' .
                        Carbon::parse($record->starts_at)->diffForHumans()
                    )
                    ->sortable()
                    ->icon('heroicon-m-clock')
                    ->iconColor(fn ($record) =>
                        Carbon::parse($record->starts_at)->isPast() ? 'gray' : 'primary'
                    ),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->description(fn ($record) => $record->customer?->phone),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->searchable()
                    ->toggleable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'primary' => 'in_progress',
                        'info' => 'completed',
                        'danger' => 'cancelled',
                        'gray' => 'no_show',
                    ])
                    ->icon(fn (string $state): ?string => match($state) {
                        'pending' => 'heroicon-m-clock',
                        'confirmed' => 'heroicon-m-check-circle',
                        'in_progress' => 'heroicon-m-arrow-path',
                        'completed' => 'heroicon-m-sparkles',
                        'cancelled' => 'heroicon-m-x-circle',
                        'no_show' => 'heroicon-m-user-minus',
                        default => null,
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Ausstehend',
                        'confirmed' => 'Bestätigt',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Storniert',
                        'no_show' => 'Nicht erschienen',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Dauer')
                    ->getStateUsing(fn ($record) =>
                        $record->starts_at && $record->ends_at
                            ? Carbon::parse($record->starts_at)->diffInMinutes($record->ends_at) . ' Min'
                            : '-'
                    )
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('source')
                    ->label('Quelle')
                    ->badge()
                    ->colors([
                        'primary' => 'phone',
                        'success' => 'online',
                        'warning' => 'walk_in',
                        'info' => 'app',
                        'danger' => 'ai_assistant',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'phone' => '📞 Telefon',
                        'online' => '💻 Online',
                        'walk_in' => '🚶 Walk-In',
                        'app' => '📱 App',
                        'ai_assistant' => '🤖 KI',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Quick filters for common views
                Tables\Filters\TernaryFilter::make('time_filter')
                    ->label('Zeitraum')
                    ->placeholder('Alle Termine')
                    ->trueLabel('Heute')
                    ->falseLabel('Diese Woche')
                    ->queries(
                        true: fn (Builder $query) => $query->whereDate('starts_at', today()),
                        false: fn (Builder $query) => $query->whereBetween('starts_at', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ]),
                        blank: fn (Builder $query) => $query,
                    ),

                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'pending' => 'Ausstehend',
                        'confirmed' => 'Bestätigt',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Storniert',
                        'no_show' => 'Nicht erschienen',
                    ]),

                SelectFilter::make('staff_id')
                    ->label('Mitarbeiter')
                    ->relationship('staff', 'name', fn ($query) =>
                        $query->where('company_id', auth()->user()->company_id)
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'name', fn ($query) =>
                        $query->where('company_id', auth()->user()->company_id)
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name', fn ($query) =>
                        $query->where('company_id', auth()->user()->company_id)
                    )
                    ->searchable()
                    ->preload(),

                Filter::make('upcoming')
                    ->label('Bevorstehend')
                    ->query(fn (Builder $query): Builder => $query->where('starts_at', '>=', now()))
                    ->default(),

                Filter::make('past')
                    ->label('Vergangen')
                    ->query(fn (Builder $query): Builder => $query->where('starts_at', '<', now())),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->poll('60s')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped()
            ->defaultPaginationPageOption(25);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Hauptinformationen
                InfoSection::make('Terminübersicht')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Termin-ID')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => '⏳ Ausstehend',
                                        'confirmed' => '✅ Bestätigt',
                                        'in_progress' => '🔄 In Bearbeitung',
                                        'completed' => '✨ Abgeschlossen',
                                        'cancelled' => '❌ Storniert',
                                        'no_show' => '👻 Nicht erschienen',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'confirmed' => 'success',
                                        'in_progress' => 'primary',
                                        'completed' => 'info',
                                        'cancelled' => 'danger',
                                        'no_show' => 'gray',
                                        default => 'gray',
                                    }),

                                TextEntry::make('source')
                                    ->label('Buchungsart')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'online' => '💻 Online',
                                        'phone' => '📞 Telefon',
                                        'walk_in' => '🚶 Walk-In',
                                        'app' => '📱 App',
                                        'ai_assistant' => '🤖 KI-Assistent',
                                        default => $state ?? 'Standard',
                                    }),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('starts_at')
                                    ->label('Beginn')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-calendar')
                                    ->size('lg'),

                                TextEntry::make('ends_at')
                                    ->label('Ende')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-clock'),
                            ]),
                    ])
                    ->icon('heroicon-o-calendar-days')
                    ->collapsible(),

                // Teilnehmer
                InfoSection::make('Teilnehmer')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Kunde')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('Kein Kunde zugeordnet'),

                                TextEntry::make('staff.name')
                                    ->label('Mitarbeiter')
                                    ->icon('heroicon-o-user-circle')
                                    ->placeholder('Kein Mitarbeiter zugeordnet'),
                            ]),

                        TextEntry::make('service.name')
                            ->label('Service')
                            ->icon('heroicon-o-briefcase')
                            ->placeholder('Kein Service zugeordnet'),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('branch.name')
                                    ->label('Filiale')
                                    ->icon('heroicon-o-building-storefront')
                                    ->placeholder('Keine Filiale'),

                                TextEntry::make('customer.phone')
                                    ->label('Telefon')
                                    ->icon('heroicon-o-phone')
                                    ->placeholder('Keine Telefonnummer'),
                            ]),
                    ])
                    ->icon('heroicon-o-user-group')
                    ->collapsible(),

                // Service-Details
                InfoSection::make('Service & Preise')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('service.duration_minutes')
                                    ->label('Dauer')
                                    ->formatStateUsing(fn ($state): string => $state ? "{$state} Min." : 'N/A')
                                    ->icon('heroicon-o-clock'),

                                TextEntry::make('price')
                                    ->label('Preis')
                                    ->money('EUR')
                                    ->icon('heroicon-o-currency-euro'),

                                TextEntry::make('booking_type')
                                    ->label('Buchungstyp')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'single' => 'Einzeltermin',
                                        'series' => 'Serie',
                                        'group' => 'Gruppe',
                                        'package' => 'Paket',
                                        default => $state ?? 'Einzeltermin',
                                    }),
                            ]),

                        TextEntry::make('notes')
                            ->label('Notizen')
                            ->columnSpanFull()
                            ->placeholder('Keine Notizen vorhanden'),
                    ])
                    ->icon('heroicon-o-currency-euro')
                    ->collapsible()
                    ->collapsed(true),

                // Erinnerungen & Metadaten
                InfoSection::make('Weitere Informationen')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('reminder_24h_sent_at')
                                    ->label('24h Erinnerung')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Nicht gesendet')
                                    ->icon('heroicon-o-bell'),

                                TextEntry::make('created_at')
                                    ->label('Erstellt')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-calendar'),
                            ]),
                    ])
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'view' => Pages\ViewAppointment::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id)
            ->with([
                'customer:id,name,email,phone',
                'service:id,name,price,duration_minutes',
                'staff:id,name',
                'branch:id,name',
            ])
            ->withCasts([
                'starts_at' => 'datetime',
                'ends_at' => 'datetime',
            ]);
    }

    public static function canCreate(): bool
    {
        return false; // Company users cannot create appointments directly
    }

    public static function canEdit($record): bool
    {
        return false; // Company users cannot edit appointments directly
    }

    public static function canDelete($record): bool
    {
        return false; // Company users cannot delete appointments
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['customer.name', 'service.name', 'staff.name', 'notes'];
    }
}
