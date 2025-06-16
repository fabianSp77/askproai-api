<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CompanyResource\Pages;
use App\Filament\Admin\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Tabs;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Unternehmen';
    protected static ?string $modelLabel = 'Unternehmen';
    protected static ?string $pluralModelLabel = 'Unternehmen';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Grunddaten')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Unternehmensinformationen')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Unternehmensname')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('email')
                                            ->label('E-Mail')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Telefon')
                                            ->tel()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('address')
                                            ->label('Adresse')
                                            ->rows(3)
                                            ->columnSpan(2),
                                        Forms\Components\Select::make('industry')
                                            ->label('Branche')
                                            ->options([
                                                'friseur' => 'Friseur',
                                                'physiotherapie' => 'Physiotherapie',
                                                'tierarzt' => 'Tierarzt',
                                                'kosmetik' => 'Kosmetik',
                                                'massage' => 'Massage',
                                                'zahnarzt' => 'Zahnarzt',
                                                'arzt' => 'Arzt',
                                                'handwerk' => 'Handwerk',
                                                'andere' => 'Andere',
                                            ])
                                            ->searchable(),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Aktiv')
                                            ->default(true),
                                    ])
                                    ->columns(2),
                            ]),
                        Tabs\Tab::make('Kalender & Integration')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Forms\Components\Section::make('Kalender-Einstellungen')
                                    ->schema([
                                        Forms\Components\Select::make('calendar_provider')
                                            ->label('Kalender-Anbieter')
                                            ->options([
                                                'calcom' => 'Cal.com',
                                                'google' => 'Google Calendar',
                                                'samedi' => 'Samedi',
                                            ])
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                                $state === null ? $set('calendar_api_key', null) : null
                                            ),
                                        Forms\Components\TextInput::make('calendar_api_key')
                                            ->label('API-Schlüssel')
                                            ->password()
                                            ->maxLength(255)
                                            ->visible(fn (Forms\Get $get) => $get('calendar_provider') !== null),
                                        Forms\Components\TextInput::make('calendar_user_id')
                                            ->label('Benutzer-ID')
                                            ->maxLength(255)
                                            ->visible(fn (Forms\Get $get) => $get('calendar_provider') === 'calcom'),
                                        Forms\Components\TextInput::make('event_type_id')
                                            ->label('Standard Event-Type ID')
                                            ->maxLength(255)
                                            ->visible(fn (Forms\Get $get) => $get('calendar_provider') === 'calcom'),
                                    ])
                                    ->columns(2),
                                Forms\Components\Section::make('Retell.ai Integration')
                                    ->schema([
                                        Forms\Components\Toggle::make('retell_enabled')
                                            ->label('Retell.ai aktiviert')
                                            ->reactive(),
                                        Forms\Components\TextInput::make('retell_agent_id')
                                            ->label('Agent ID')
                                            ->maxLength(255)
                                            ->visible(fn (Forms\Get $get) => $get('retell_enabled')),
                                        Forms\Components\TextInput::make('retell_phone_number')
                                            ->label('Telefonnummer für KI')
                                            ->tel()
                                            ->maxLength(255)
                                            ->visible(fn (Forms\Get $get) => $get('retell_enabled')),
                                    ])
                                    ->columns(2),
                            ]),
                        Tabs\Tab::make('Geschäftszeiten')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Forms\Components\Section::make('Standard-Geschäftszeiten')
                                    ->schema([
                                        Forms\Components\Repeater::make('business_hours')
                                            ->label('Öffnungszeiten')
                                            ->schema([
                                                Forms\Components\Select::make('day')
                                                    ->label('Wochentag')
                                                    ->options([
                                                        'monday' => 'Montag',
                                                        'tuesday' => 'Dienstag',
                                                        'wednesday' => 'Mittwoch',
                                                        'thursday' => 'Donnerstag',
                                                        'friday' => 'Freitag',
                                                        'saturday' => 'Samstag',
                                                        'sunday' => 'Sonntag',
                                                    ])
                                                    ->required(),
                                                Forms\Components\TimePicker::make('open_time')
                                                    ->label('Öffnung')
                                                    ->required(),
                                                Forms\Components\TimePicker::make('close_time')
                                                    ->label('Schließung')
                                                    ->required(),
                                                Forms\Components\Toggle::make('is_closed')
                                                    ->label('Geschlossen'),
                                            ])
                                            ->columns(4)
                                            ->defaultItems(7)
                                            ->collapsible()
                                            ->cloneable(),
                                    ]),
                            ]),
                        Tabs\Tab::make('Abrechnungseinstellungen')
                            ->icon('heroicon-o-currency-euro')
                            ->schema([
                                Forms\Components\Section::make('Abrechnung')
                                    ->schema([
                                        Forms\Components\Select::make('billing_plan')
                                            ->label('Abrechnungsplan')
                                            ->options([
                                                'starter' => 'Starter (0€ + 0,45€/Min)',
                                                'basic' => 'Basic (299€/Monat)',
                                                'business' => 'Business (499€/Monat)',
                                                'premium' => 'Premium (899€/Monat)',
                                            ])
                                            ->default('starter'),
                                        Forms\Components\TextInput::make('billing_email')
                                            ->label('Rechnungs-E-Mail')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('billing_address')
                                            ->label('Rechnungsadresse')
                                            ->rows(3),
                                        Forms\Components\TextInput::make('tax_id')
                                            ->label('Steuernummer')
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('industry')
                    ->label('Branche')
                    ->colors([
                        'primary' => 'friseur',
                        'success' => 'physiotherapie',
                        'warning' => 'tierarzt',
                        'danger' => 'zahnarzt',
                    ]),
                Tables\Columns\BadgeColumn::make('billing_plan')
                    ->label('Plan')
                    ->colors([
                        'gray' => 'starter',
                        'primary' => 'basic',
                        'success' => 'business',
                        'warning' => 'premium',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
                Tables\Columns\TextColumn::make('branches_count')
                    ->label('Filialen')
                    ->counts('branches')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('industry')
                    ->label('Branche')
                    ->options([
                        'friseur' => 'Friseur',
                        'physiotherapie' => 'Physiotherapie',
                        'tierarzt' => 'Tierarzt',
                        'kosmetik' => 'Kosmetik',
                        'massage' => 'Massage',
                        'zahnarzt' => 'Zahnarzt',
                        'arzt' => 'Arzt',
                        'handwerk' => 'Handwerk',
                        'andere' => 'Andere',
                    ]),
                Tables\Filters\SelectFilter::make('billing_plan')
                    ->label('Abrechnungsplan')
                    ->options([
                        'starter' => 'Starter',
                        'basic' => 'Basic',
                        'business' => 'Business',
                        'premium' => 'Premium',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BranchesRelationManager::class,
            RelationManagers\StaffRelationManager::class,
            RelationManagers\CustomersRelationManager::class,
            RelationManagers\AppointmentsRelationManager::class,
            RelationManagers\PhoneNumbersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            // 'view' => Pages\ViewCompany::route('/{record}'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('is_active', true)->count() > 0 ? 'success' : 'gray';
    }
}
