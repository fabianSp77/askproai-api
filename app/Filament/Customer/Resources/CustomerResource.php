<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Customer\Resources\CustomerResource\Pages;
use App\Filament\Customer\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Models\Company;
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
    use HasCachedNavigationBadge;

    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Kunden';
    protected static ?string $modelLabel = 'Kunde';
    protected static ?string $pluralModelLabel = 'Kunden';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getCachedBadge(function() {
            return static::getModel()::where('company_id', auth()->user()->company_id)
                ->where('status', 'active')
                ->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getCachedBadgeColor(function() {
            $count = static::getModel()::where('company_id', auth()->user()->company_id)
                ->where('status', 'active')
                ->count();
            return $count > 100 ? 'success' : ($count > 50 ? 'info' : 'warning');
        });
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
                // Hauptinformationen
                InfoSection::make('Kundeninformationen')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('customer_number')
                                    ->label('Kundennummer')
                                    ->badge()
                                    ->color('gray')
                                    ->icon('heroicon-o-hashtag'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'active' => '✅ Aktiv',
                                        'inactive' => '⏸️ Inaktiv',
                                        'blocked' => '🚫 Gesperrt',
                                        'vip' => '⭐ VIP',
                                        default => $state ?? 'Aktiv',
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        'active' => 'success',
                                        'inactive' => 'gray',
                                        'blocked' => 'danger',
                                        'vip' => 'warning',
                                        default => 'gray',
                                    }),

                                TextEntry::make('customer_type')
                                    ->label('Kundentyp')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'private' => '👤 Privat',
                                        'business' => '🏢 Geschäft',
                                        'insurance' => '🏥 Krankenkasse',
                                        default => $state ?? 'Privat',
                                    }),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('company.name')
                                    ->label('Unternehmen')
                                    ->icon('heroicon-o-building-office')
                                    ->url(fn ($record) => $record->company_id
                                        ? Company::class . '::getUrl(\'view\', [\'record\' => ' . $record->company_id . '])'
                                        : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('Kein Unternehmen'),
                            ]),
                    ])
                    ->icon('heroicon-o-user-circle')
                    ->collapsible(),

                // Kontaktdaten
                InfoSection::make('Kontaktinformationen')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('email')
                                    ->label('E-Mail')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->placeholder('Keine E-Mail'),

                                TextEntry::make('phone')
                                    ->label('Telefon')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->placeholder('Keine Telefonnummer'),

                                TextEntry::make('mobile')
                                    ->label('Mobil')
                                    ->icon('heroicon-o-device-phone-mobile')
                                    ->copyable()
                                    ->placeholder('Keine Mobilnummer'),
                            ]),

                        TextEntry::make('address')
                            ->label('Adresse')
                            ->icon('heroicon-o-map-pin')
                            ->columnSpanFull()
                            ->placeholder('Keine Adresse'),

                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('postal_code')
                                    ->label('PLZ')
                                    ->placeholder('-'),

                                TextEntry::make('city')
                                    ->label('Stadt')
                                    ->placeholder('-'),

                                TextEntry::make('country')
                                    ->label('Land')
                                    ->placeholder('Deutschland'),
                            ]),
                    ])
                    ->icon('heroicon-o-map')
                    ->collapsible()
                    ->collapsed(true),

                // Customer Journey
                InfoSection::make('Customer Journey')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('first_appointment_at')
                                    ->label('Erster Termin')
                                    ->dateTime('d.m.Y')
                                    ->icon('heroicon-o-calendar')
                                    ->placeholder('Noch kein Termin'),

                                TextEntry::make('last_appointment_at')
                                    ->label('Letzter Termin')
                                    ->dateTime('d.m.Y')
                                    ->icon('heroicon-o-calendar-days')
                                    ->placeholder('Noch kein Termin'),

                                TextEntry::make('total_appointments')
                                    ->label('Anzahl Termine')
                                    ->badge()
                                    ->color('info')
                                    ->formatStateUsing(fn ($state): string => $state ?? '0'),
                            ]),

                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('total_spent')
                                    ->label('Gesamtumsatz')
                                    ->money('EUR')
                                    ->icon('heroicon-o-currency-euro'),

                                TextEntry::make('lifetime_value')
                                    ->label('Customer Lifetime Value')
                                    ->money('EUR')
                                    ->icon('heroicon-o-chart-bar'),

                                TextEntry::make('loyalty_points')
                                    ->label('Treuepunkte')
                                    ->badge()
                                    ->color('warning')
                                    ->formatStateUsing(fn ($state): string => $state ? number_format($state) : '0'),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('acquisition_channel')
                                    ->label('Akquisitionskanal')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'online' => '🌐 Online',
                                        'referral' => '👥 Empfehlung',
                                        'advertising' => '📢 Werbung',
                                        'walk-in' => '🚶 Walk-In',
                                        'social_media' => '📱 Social Media',
                                        default => $state ?? 'Unbekannt',
                                    }),

                                TextEntry::make('referral_source')
                                    ->label('Empfohlen von')
                                    ->placeholder('Keine Empfehlung'),
                            ]),
                    ])
                    ->icon('heroicon-o-chart-pie')
                    ->collapsible()
                    ->collapsed(true),

                // Präferenzen
                InfoSection::make('Präferenzen')
                    ->schema([
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

                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('preferred_contact_method')
                                    ->label('Bevorzugte Kontaktart')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'email' => '📧 E-Mail',
                                        'phone' => '📞 Telefon',
                                        'sms' => '💬 SMS',
                                        'whatsapp' => '📱 WhatsApp',
                                        default => $state ?? 'E-Mail',
                                    }),

                                TextEntry::make('language')
                                    ->label('Sprache')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'de' => '🇩🇪 Deutsch',
                                        'en' => '🇬🇧 Englisch',
                                        'fr' => '🇫🇷 Französisch',
                                        'it' => '🇮🇹 Italienisch',
                                        'tr' => '🇹🇷 Türkisch',
                                        default => $state ?? '🇩🇪 Deutsch',
                                    }),

                                TextEntry::make('timezone')
                                    ->label('Zeitzone')
                                    ->placeholder('Europe/Berlin'),
                            ]),

                        TextEntry::make('tags')
                            ->label('Tags')
                            ->badge()
                            ->separator(',')
                            ->columnSpanFull()
                            ->placeholder('Keine Tags'),
                    ])
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->collapsible()
                    ->collapsed(true),

                // Geburtstag & Notizen
                InfoSection::make('Persönliche Informationen')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('date_of_birth')
                                    ->label('Geburtsdatum')
                                    ->date('d.m.Y')
                                    ->icon('heroicon-o-cake')
                                    ->placeholder('Nicht angegeben'),

                                TextEntry::make('age')
                                    ->label('Alter')
                                    ->state(fn ($record): ?string =>
                                        $record->date_of_birth
                                            ? Carbon::parse($record->date_of_birth)->age . ' Jahre'
                                            : null
                                    )
                                    ->placeholder('N/A'),

                                TextEntry::make('gender')
                                    ->label('Geschlecht')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'male' => '♂️ Männlich',
                                        'female' => '♀️ Weiblich',
                                        'diverse' => '⚧ Divers',
                                        default => $state ?? 'Nicht angegeben',
                                    }),
                            ]),

                        TextEntry::make('notes')
                            ->label('Notizen')
                            ->columnSpanFull()
                            ->placeholder('Keine Notizen vorhanden'),
                    ])
                    ->icon('heroicon-o-identification')
                    ->collapsible()
                    ->collapsed(true),

                // Marketing & Kommunikation
                InfoSection::make('Marketing & Kommunikation')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('accepts_marketing')
                                    ->label('Marketing akzeptiert')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => $state ? '✅ Ja' : '❌ Nein')
                                    ->color(fn ($state): string => $state ? 'success' : 'danger'),

                                TextEntry::make('newsletter_subscribed')
                                    ->label('Newsletter')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => $state ? '📧 Abonniert' : '🚫 Nicht abonniert')
                                    ->color(fn ($state): string => $state ? 'success' : 'gray'),

                                TextEntry::make('sms_notifications')
                                    ->label('SMS-Benachrichtigungen')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => $state ? '💬 Aktiviert' : '🔕 Deaktiviert')
                                    ->color(fn ($state): string => $state ? 'success' : 'gray'),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('last_contact_at')
                                    ->label('Letzter Kontakt')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch kein Kontakt'),

                                TextEntry::make('last_marketing_campaign')
                                    ->label('Letzte Kampagne')
                                    ->placeholder('Keine Kampagne'),
                            ]),
                    ])
                    ->icon('heroicon-o-megaphone')
                    ->collapsible()
                    ->collapsed(true),

                // System-Informationen
                InfoSection::make('System-Informationen')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-clock'),

                                TextEntry::make('updated_at')
                                    ->label('Aktualisiert am')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-arrow-path'),

                                TextEntry::make('external_id')
                                    ->label('Externe ID')
                                    ->badge()
                                    ->placeholder('Keine externe ID'),
                            ]),

                        TextEntry::make('import_source')
                            ->label('Import-Quelle')
                            ->badge()
                            ->placeholder('Manuell erstellt'),
                    ])
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AppointmentsRelationManager::class,
            RelationManagers\CallsRelationManager::class,
            RelationManagers\NotesRelationManager::class,
        ];
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
        return ['name', 'email', 'phone', 'customer_number', 'notes'];
    }
}
