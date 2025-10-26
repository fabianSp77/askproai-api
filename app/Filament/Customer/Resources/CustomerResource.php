<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\CustomerResource\Pages;
use App\Models\Customer;
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

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Kunden';
    protected static ?string $modelLabel = 'Kunde';
    protected static ?string $pluralModelLabel = 'Kunden';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->count();
        return $count > 100 ? 'success' : ($count > 50 ? 'info' : 'warning');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['preferredBranch:id,name', 'preferredStaff:id,name'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer_number')
                    ->label('Nr.')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->size('xs')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) =>
                        $record->email ?: $record->phone
                    )
                    ->icon(fn ($record) => match($record->salutation) {
                        'Herr' => 'heroicon-m-user',
                        'Frau' => 'heroicon-m-user',
                        default => 'heroicon-m-users',
                    }),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontakt')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->size('sm')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('journey_status')
                    ->label('Journey')
                    ->badge()
                    ->colors([
                        'gray' => 'lead',
                        'info' => 'prospect',
                        'success' => 'customer',
                        'warning' => 'regular',
                        'danger' => 'at_risk',
                        'secondary' => 'churned',
                    ])
                    ->icon(fn (string $state): ?string => $state === 'vip' ? 'heroicon-m-sparkles' : null)
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'lead' => '🌱 Lead',
                        'prospect' => '🔍 Interessent',
                        'customer' => '⭐ Kunde',
                        'regular' => '💎 Stammkunde',
                        'vip' => '👑 VIP',
                        'at_risk' => '⚠️ Gefährdet',
                        'churned' => '❌ Verloren',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('last_activity')
                    ->label('Aktivität')
                    ->getStateUsing(fn ($record) =>
                        $record->last_appointment_at
                            ? Carbon::parse($record->last_appointment_at)->diffForHumans()
                            : 'Nie'
                    )
                    ->badge()
                    ->color(fn ($record) =>
                        $record->last_appointment_at && Carbon::parse($record->last_appointment_at)->gt(now()->subDays(30))
                            ? 'success'
                            : ($record->last_appointment_at && Carbon::parse($record->last_appointment_at)->gt(now()->subDays(90))
                                ? 'warning'
                                : 'danger')
                    )
                    ->icon('heroicon-m-clock'),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Umsatz')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state) =>
                        $state > 1000 ? 'success' :
                        ($state > 500 ? 'warning' : 'gray')
                    ),

                Tables\Columns\TextColumn::make('engagement_score')
                    ->label('Engagement')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->color(fn ($state) =>
                        $state >= 80 ? 'success' :
                        ($state >= 50 ? 'warning' : 'danger')
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger' => 'blocked',
                        'gray' => 'archived',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'active' => '✅ Aktiv',
                        'inactive' => '⏸️ Inaktiv',
                        'blocked' => '🚫 Gesperrt',
                        'archived' => '📦 Archiviert',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('communication_preferences')
                    ->label('Präferenzen')
                    ->getStateUsing(fn ($record) => [
                        'sms' => $record->sms_opt_in,
                        'email' => $record->email_opt_in,
                    ])
                    ->icons([
                        'heroicon-o-chat-bubble-left' => fn ($state) => $state['sms'] ?? false,
                        'heroicon-o-envelope' => fn ($state) => $state['email'] ?? false,
                    ])
                    ->colors([
                        'success' => true,
                        'gray' => false,
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activity')
                    ->label('Aktivität')
                    ->placeholder('Alle Kunden')
                    ->trueLabel('Aktive (30 Tage)')
                    ->falseLabel('Inaktive (90+ Tage)')
                    ->queries(
                        true: fn (Builder $query) => $query->where('last_appointment_at', '>=', now()->subDays(30)),
                        false: fn (Builder $query) => $query->where('last_appointment_at', '<', now()->subDays(90)),
                        blank: fn (Builder $query) => $query,
                    ),

                SelectFilter::make('journey_status')
                    ->label('Journey Status')
                    ->multiple()
                    ->options([
                        'lead' => '🌱 Lead',
                        'prospect' => '🔍 Interessent',
                        'customer' => '⭐ Kunde',
                        'regular' => '💎 Stammkunde',
                        'vip' => '👑 VIP',
                        'at_risk' => '⚠️ Gefährdet',
                        'churned' => '❌ Verloren',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktiv',
                        'inactive' => 'Inaktiv',
                        'blocked' => 'Gesperrt',
                        'archived' => 'Archiviert',
                    ]),

                Filter::make('high_value')
                    ->label('High Value (>€1000)')
                    ->query(fn (Builder $query): Builder => $query->where('total_revenue', '>', 1000)),

                Filter::make('new_customers')
                    ->label('Neue Kunden (30 Tage)')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30))),

                SelectFilter::make('preferred_branch_id')
                    ->label('Filiale')
                    ->relationship('preferredBranch', 'name', fn ($query) =>
                        $query->where('company_id', auth()->user()->company_id)
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultPaginationPageOption(25)
            ->poll('30s')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Kundeninformationen')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('customer_number')
                                    ->label('Kundennummer')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'active' => '✅ Aktiv',
                                        'inactive' => '⏸️ Inaktiv',
                                        'blocked' => '🚫 Gesperrt',
                                        'archived' => '📦 Archiviert',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match($state) {
                                        'active' => 'success',
                                        'inactive' => 'warning',
                                        'blocked' => 'danger',
                                        'archived' => 'gray',
                                        default => 'gray',
                                    }),

                                TextEntry::make('journey_status')
                                    ->label('Journey Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'lead' => '🌱 Lead',
                                        'prospect' => '🔍 Interessent',
                                        'customer' => '⭐ Kunde',
                                        'regular' => '💎 Stammkunde',
                                        'vip' => '👑 VIP',
                                        'at_risk' => '⚠️ Gefährdet',
                                        'churned' => '❌ Verloren',
                                        default => $state,
                                    }),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name')
                                    ->icon('heroicon-o-user')
                                    ->size('lg'),

                                TextEntry::make('salutation')
                                    ->label('Anrede'),
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

                                TextEntry::make('mobile')
                                    ->label('Mobil')
                                    ->icon('heroicon-o-device-phone-mobile')
                                    ->placeholder('Keine Mobilnummer'),
                            ]),

                        TextEntry::make('address')
                            ->label('Adresse')
                            ->placeholder('Keine Adresse hinterlegt')
                            ->columnSpanFull(),

                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('postal_code')
                                    ->label('PLZ'),

                                TextEntry::make('city')
                                    ->label('Stadt'),

                                TextEntry::make('country')
                                    ->label('Land')
                                    ->default('Deutschland'),
                            ]),
                    ])
                    ->icon('heroicon-o-user')
                    ->collapsible(),

                InfoSection::make('Customer Journey')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('acquisition_channel')
                                    ->label('Akquisitionskanal')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'website' => '🌐 Website',
                                        'social_media' => '📱 Social Media',
                                        'referral' => '👥 Empfehlung',
                                        'walk_in' => '🚶 Walk-In',
                                        'phone' => '📞 Telefon',
                                        'email' => '✉️ E-Mail',
                                        'event' => '📅 Event',
                                        'advertising' => '📢 Werbung',
                                        default => $state ?? 'Unbekannt',
                                    }),

                                TextEntry::make('engagement_score')
                                    ->label('Engagement Score')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state . '%')
                                    ->color(fn ($state) =>
                                        $state >= 80 ? 'success' :
                                        ($state >= 50 ? 'warning' : 'danger')
                                    ),

                                TextEntry::make('last_appointment_at')
                                    ->label('Letzter Termin')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Kein Termin'),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('preferredBranch.name')
                                    ->label('Bevorzugte Filiale')
                                    ->icon('heroicon-o-building-storefront')
                                    ->placeholder('Keine Präferenz'),

                                TextEntry::make('preferredStaff.name')
                                    ->label('Bevorzugter Mitarbeiter')
                                    ->icon('heroicon-o-user-circle')
                                    ->placeholder('Keine Präferenz'),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('sms_opt_in')
                                    ->label('SMS-Benachrichtigungen')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Erlaubt' : 'Nicht erlaubt')
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                                TextEntry::make('email_opt_in')
                                    ->label('E-Mail-Marketing')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Erlaubt' : 'Nicht erlaubt')
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                            ]),

                        TextEntry::make('notes')
                            ->label('Notizen')
                            ->columnSpanFull()
                            ->placeholder('Keine Notizen'),
                    ])
                    ->icon('heroicon-o-chart-bar')
                    ->collapsible(),

                InfoSection::make('Finanzen & Loyalität')
                    ->schema([
                        InfoGrid::make(4)
                            ->schema([
                                TextEntry::make('total_revenue')
                                    ->label('Gesamtumsatz')
                                    ->money('EUR')
                                    ->icon('heroicon-o-currency-euro'),

                                TextEntry::make('appointment_count')
                                    ->label('Termine')
                                    ->badge(),

                                TextEntry::make('cancellation_count')
                                    ->label('Stornierungen')
                                    ->badge()
                                    ->color('danger'),

                                TextEntry::make('loyalty_points')
                                    ->label('Treuepunkte')
                                    ->badge()
                                    ->color('success'),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('is_vip')
                                    ->label('VIP-Kunde')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? '👑 Ja' : 'Nein')
                                    ->color(fn ($state) => $state ? 'warning' : 'gray'),

                                TextEntry::make('discount_percentage')
                                    ->label('Rabatt')
                                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : 'Kein Rabatt'),
                            ]),
                    ])
                    ->icon('heroicon-o-currency-euro')
                    ->collapsible()
                    ->collapsed(true),

                InfoSection::make('Weitere Informationen')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('date_of_birth')
                                    ->label('Geburtsdatum')
                                    ->date('d.m.Y')
                                    ->placeholder('Nicht angegeben'),

                                TextEntry::make('created_at')
                                    ->label('Kunde seit')
                                    ->dateTime('d.m.Y H:i'),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('gdpr_consent')
                                    ->label('DSGVO-Einwilligung')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Erteilt' : 'Nicht erteilt')
                                    ->color(fn ($state) => $state ? 'success' : 'danger'),

                                TextEntry::make('gdpr_consent_date')
                                    ->label('DSGVO-Einwilligung Datum')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Nicht erteilt'),
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
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id)
            ->with(['preferredBranch:id,name', 'preferredStaff:id,name']);
    }

    public static function canCreate(): bool
    {
        return false; // Company users cannot create customers directly
    }

    public static function canEdit($record): bool
    {
        return false; // Company users cannot edit customers directly
    }

    public static function canDelete($record): bool
    {
        return false; // Company users cannot delete customers
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'customer_number'];
    }
}
