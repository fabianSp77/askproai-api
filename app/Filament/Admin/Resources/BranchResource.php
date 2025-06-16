<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BranchResource\Pages;
use App\Models\Branch;
use App\Models\Company;
use App\Models\RetellAgent;
use App\Services\RetellAgentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\ViewField;
use Filament\Support\Enums\MaxWidth;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationLabel = 'Filialen';
    
    protected static ?string $pluralLabel = 'Filialen';
    
    protected static ?string $label = 'Filiale';
    
    protected static ?string $navigationGroup = 'Unternehmensstruktur';
    
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Fortschrittsanzeige oben
                ViewField::make('configuration_progress')
                    ->view('filament.forms.components.branch-progress')
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record !== null),

                // Tab-basierte Navigation
                Tabs::make('Hauptbereiche')
                    ->tabs([
                        // Tab 1: Grunddaten
                        Tabs\Tab::make('Grunddaten')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('Unternehmenszuordnung')
                                    ->schema([
                                        Forms\Components\Select::make('company_id')
                                            ->label('Unternehmen')
                                            ->relationship('company', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, $record) {
                                                if (!$record && $state) {
                                                    // Bei neuer Filiale: E-Mail vom Unternehmen übernehmen
                                                    $company = Company::find($state);
                                                    if ($company && $company->email && !$set->get('notification_email')) {
                                                        $set('notification_email', $company->email);
                                                    }
                                                }
                                            })
                                            ->helperText('Wählen Sie das übergeordnete Unternehmen aus'),
                                    ]),

                                Section::make('Filial-Informationen')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Filialname')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('z.B. Filiale Hauptstraße')
                                            ->helperText('Der Name dieser Filiale'),
                                    ])
                                    ->columns(1),

                                Section::make('Adresse')
                                    ->schema([
                                        Forms\Components\TextInput::make('address')
                                            ->label('Straße und Hausnummer')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('z.B. Hauptstraße 123'),
                                        
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('postal_code')
                                                    ->label('PLZ')
                                                    ->required()
                                                    ->maxLength(10)
                                                    ->placeholder('12345'),
                                                
                                                Forms\Components\TextInput::make('city')
                                                    ->label('Stadt')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Berlin'),
                                                
                                                Forms\Components\TextInput::make('country')
                                                    ->label('Land')
                                                    ->default('Deutschland')
                                                    ->required()
                                                    ->maxLength(255),
                                            ]),
                                        
                                        // Google Maps Link
                                        Forms\Components\Placeholder::make('google_maps_link')
                                            ->label('')
                                            ->content(function ($record) {
                                                if ($record && $record->address && $record->city && $record->postal_code) {
                                                    $address = urlencode("{$record->address}, {$record->postal_code} {$record->city}, {$record->country}");
                                                    $url = "https://www.google.com/maps/search/?api=1&query={$address}";
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<a href="' . $url . '" target="_blank" class="text-primary-600 hover:text-primary-500 font-medium flex items-center gap-2">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            </svg>
                                                            Adresse in Google Maps anzeigen
                                                        </a>'
                                                    );
                                                }
                                                return 'Bitte geben Sie zuerst eine vollständige Adresse ein.';
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Kontaktdaten')
                                    ->schema([
                                        Forms\Components\TextInput::make('phone_number')
                                            ->label('Telefonnummer')
                                            ->tel()
                                            ->required()
                                            ->helperText('Diese Nummer rufen die Kunden an')
                                            ->placeholder('+49 30 12345678')
                                            ->maxLength(255),
                                        
                                        Forms\Components\TextInput::make('notification_email')
                                            ->label('E-Mail für Benachrichtigungen')
                                            ->email()
                                            ->required()
                                            ->placeholder('filiale@beispiel.de')
                                            ->helperText('An diese E-Mail werden Terminbestätigungen gesendet')
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),

                                Section::make('Website (optional)')
                                    ->schema([
                                        Forms\Components\TextInput::make('website')
                                            ->label('Filial-Website (falls vorhanden)')
                                            ->url()
                                            ->prefix('https://')
                                            ->placeholder('www.filiale-beispiel.de')
                                            ->helperText('Nur ausfüllen, wenn diese Filiale eine eigene Website hat')
                                            ->maxLength(255),
                                    ])
                                    ->collapsed()
                                    ->collapsible(),

                                Section::make('Status')
                                    ->schema([
                                        Forms\Components\Toggle::make('active')
                                            ->label('Filiale aktivieren')
                                            ->default(false)
                                            ->disabled(fn ($record) => !$record || !$record->canBeActivated())
                                            ->helperText(fn ($record) => 
                                                !$record || !$record->canBeActivated() 
                                                    ? 'Die Filiale kann erst aktiviert werden, wenn alle Pflichtfelder ausgefüllt sind.' 
                                                    : 'Aktivierte Filialen können Termine entgegennehmen'
                                            ),
                                        
                                        Forms\Components\Toggle::make('notify_on_booking')
                                            ->label('E-Mail-Benachrichtigung bei Buchungen')
                                            ->default(true)
                                            ->helperText('Sendet eine E-Mail bei jeder neuen Terminbuchung'),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 2: Öffnungszeiten
Tabs\Tab::make('Öffnungszeiten')
    ->icon('heroicon-o-clock')
    ->schema([
        Section::make()
            ->heading('Geschäftszeiten konfigurieren')
            ->description('Legen Sie die Öffnungszeiten für diese Filiale fest')
            ->schema([
                Forms\Components\ViewField::make('business_hours')
                    ->label('')
                    ->view('filament.forms.components.business-hours-field')
                    ->columnSpanFull(),
            ]),
    ]),

                        // Tab 3: KI-Telefonie
Tabs\Tab::make('KI-Telefonie (Retell.ai)')
    ->icon('heroicon-o-phone')
    ->schema([
        Section::make('Retell.ai Agent Konfiguration')
            ->description('Verwalten Sie den KI-Agenten für diese Filiale')
            ->schema([
                Forms\Components\ViewField::make('retell_agent_info')
                    ->label('')
                    ->view('filament.forms.components.retell-agent-info')
                    ->columnSpanFull(),
                    
                Forms\Components\TextInput::make('retell_agent_id')
                    ->label('Agent ID')
                    ->placeholder('agent_xxxxxx')
                    ->helperText('Die Retell.ai Agent ID für diese Filiale')
                    ->maxLength(255),
            ]),
    ]),
                        // Tab 4: Services & Mitarbeiter
                        Tabs\Tab::make('Services & Mitarbeiter')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Section::make('Dienstleistungen')
                                    ->description('Wählen Sie die in dieser Filiale verfügbaren Services')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('services')
                                            ->relationship('masterServices', 'name')
                                            ->columns(2)
                                            ->searchable()
                                            ->helperText('Diese Services können in dieser Filiale gebucht werden'),
                                    ]),

                                Section::make('Mitarbeiter')
                                    ->description('Mitarbeiter dieser Filiale mit ihren verfügbaren Dienstleistungen')
                                    ->schema([
                                        Forms\Components\Repeater::make('staff_members')
                                            ->label('Mitarbeiter')
                                            ->relationship('availableStaff')
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Name')
                                                    ->required(),
                                                
                                                Forms\Components\TextInput::make('email')
                                                    ->label('E-Mail')
                                                    ->email(),
                                                
                                                Forms\Components\Select::make('calcom_user_id')
                                                    ->label('Cal.com Benutzer-ID')
                                                    ->helperText('Die Benutzer-ID in Cal.com für diesen Mitarbeiter'),
                                                
                                                Forms\Components\Select::make('services')
                                                    ->label('Verfügbare Services')
                                                    ->multiple()
                                                    ->relationship('services', 'name')
                                                    ->helperText('Welche Services kann dieser Mitarbeiter durchführen?'),
                                                
                                                Forms\Components\Toggle::make('active')
                                                    ->label('Aktiv')
                                                    ->default(true),
                                            ])
                                            ->columns(2)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                                    ]),
                            ]),

                        // Tab 5: Kalender-Integration
                        Tabs\Tab::make('Kalender-Integration')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Section::make('Kalendermodus')
                                    ->schema([
                                        Forms\Components\Radio::make('calendar_mode')
                                            ->label('Kalenderverwaltung')
                                            ->options([
                                                'inherit' => 'Unternehmenseinstellungen verwenden',
                                                'override' => 'Eigene Einstellungen für diese Filiale',
                                            ])
                                            ->default('inherit')
                                            ->live()
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Cal.com Konfiguration')
                                    ->schema([
                                        Forms\Components\TextInput::make('calcom_api_key')
                                            ->label('Cal.com API-Schlüssel')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255),
                                        
                                        Forms\Components\TextInput::make('calcom_event_type_id')
                                            ->label('Event Type ID')
                                            ->numeric()
                                            ->helperText('Die ID des Event-Typs in Cal.com'),
                                        
                                        Forms\Components\TextInput::make('calcom_team_slug')
                                            ->label('Team Slug')
                                            ->maxLength(64)
                                            ->helperText('Der Team-Slug in Cal.com (optional)'),
                                    ])
                                    ->columns(3)
                                    ->visible(fn (Forms\Get $get) => $get('calendar_mode') === 'override'),

                                // Test-Aktionen
                                Section::make('Kalender-Test')
                                    ->schema([
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('testCalendar')
                                                ->label('Kalender-Verbindung testen')
                                                ->icon('heroicon-o-beaker')
                                                ->action(function ($record) {
                                                    if ($record) {
                                                        $results = $record->testIntegrations();
                                                        
                                                        if ($results['calcom']['success'] ?? false) {
                                                            Notification::make()
                                                                ->title('Kalender-Test erfolgreich')
                                                                ->success()
                                                                ->send();
                                                        } else {
                                                            Notification::make()
                                                                ->title('Kalender-Test fehlgeschlagen')
                                                                ->body($results['calcom']['message'] ?? 'Unbekannter Fehler')
                                                                ->danger()
                                                                ->send();
                                                        }
                                                    }
                                                })
                                                ->disabled(fn ($record) => !$record),
                                        ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Filialname')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('city')
                    ->label('Stadt')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Telefon')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-phone'),
                
                Tables\Columns\TextColumn::make('configuration_progress.percentage')
                    ->label('Konfiguration')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 100 => 'success',
                        $state >= 75 => 'warning',
                        default => 'danger',
                    }),
                
                Tables\Columns\IconColumn::make('active')
                    ->label('Aktiv')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('retell_agent_id')
                    ->label('KI-Agent')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status')
                    ->placeholder('Alle Filialen')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
                
                Tables\Filters\Filter::make('fully_configured')
                    ->label('Vollständig konfiguriert')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('retell_agent_id')
                              ->where('phone_number', '!=', '')
                              ->whereNotNull('phone_number')
                    ),
                
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('test')
                        ->label('Testen')
                        ->icon('heroicon-o-beaker')
                        ->color('success')
                        ->action(function (Branch $record) {
                            $results = $record->testIntegrations();
                            
                            $message = collect($results)
                                ->map(fn ($result, $service) => 
                                    $service . ': ' . ($result['success'] ? '✓' : '✗')
                                )
                                ->join(', ');
                            
                            Notification::make()
                                ->title('Integrations-Test')
                                ->body($message)
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'view' => Pages\ViewBranch::route('/{record}'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
