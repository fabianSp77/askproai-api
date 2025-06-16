<?php

namespace App\Filament\Resources;
use Filament\Notifications\Notification;
use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\RetellAgent;
use App\Services\RetellAgentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\MaxWidth;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationLabel = 'Filialen';
    
    protected static ?string $pluralLabel = 'Filialen';
    
    protected static ?string $label = 'Filiale';
    
    protected static ?string $navigationGroup = 'Stammdaten';
    
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Fortschrittsanzeige oben
                Forms\Components\Placeholder::make('configuration_progress')
                    ->content('Branch Progress')
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record !== null),

                // Tab-basierte Navigation
                Tabs::make('Hauptbereiche')
                    ->tabs([
                        // Tab 1: Grunddaten
                        Tabs\Tab::make('Grunddaten')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('company_id')
                                            ->label('Unternehmen')
                                            ->relationship('company', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255),
                                            ]),
                                        
                                        Forms\Components\Select::make('customer_id')
                                            ->label('Kunde')
                                            ->relationship('customer', 'name')
                                            ->searchable()
                                            ->preload(),
                                    ]),

                                Section::make('Basis-Informationen')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Filialname')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                                $set('slug', \Str::slug($state))
                                            ),
                                        
                                        Forms\Components\TextInput::make('slug')
                                            ->label('URL-Slug')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),

                                Section::make('Adresse')
                                    ->schema([
                                        Forms\Components\TextInput::make('address')
                                            ->label('Straße und Hausnummer')
                                            ->maxLength(255),
                                        
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('postal_code')
                                                    ->label('PLZ')
                                                    ->maxLength(10),
                                                
                                                Forms\Components\TextInput::make('city')
                                                    ->label('Stadt')
                                                    ->required()
                                                    ->maxLength(255),
                                                
                                                Forms\Components\TextInput::make('country')
                                                    ->label('Land')
                                                    ->default('Deutschland')
                                                    ->maxLength(255),
                                            ]),
                                    ]),

                                Section::make('Kontaktdaten')
                                    ->schema([
                                        Forms\Components\TextInput::make('phone_number')
                                            ->label('Telefonnummer')
                                            ->tel()
                                            ->required()
                                            ->maxLength(255),
                                        
                                        Forms\Components\TextInput::make('notification_email')
                                            ->label('E-Mail für Benachrichtigungen')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                        
                                        Forms\Components\TextInput::make('website')
                                            ->label('Website')
                                            ->url()
                                            ->prefix('https://')
                                            ->maxLength(255),
                                    ])
                                    ->columns(3),

                                Section::make('Status')
                                    ->schema([
                                        Forms\Components\Toggle::make('active')
                                            ->label('Filiale aktiv')
                                            ->default(false)
                                            ->helperText('Inaktive Filialen nehmen keine Termine entgegen'),
                                        
                                        Forms\Components\Toggle::make('notify_on_booking')
                                            ->label('E-Mail-Benachrichtigung bei Buchungen')
                                            ->default(true),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 2: Öffnungszeiten
Tabs\Tab::make('Öffnungszeiten')
    ->icon('heroicon-o-clock')
    ->schema([
        Section::make()
            ->heading('Geschäftszeiten konfigurieren - NEUE VERSION')
            ->description('TEST - Diese Änderung sollte sichtbar sein!')
            ->schema([
                Forms\Components\TextInput::make('test_field')
                    ->label('TEST FELD - Wenn Sie das sehen, funktionieren die Änderungen!')
                    ->default('TEST 123'),
                            ]),
                        ]),
                        
                        // Tab 3: KI-Telefonie
                        Tabs\Tab::make('KI-Telefonie (Retell.ai)')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Section::make('Retell.ai Agent Konfiguration')
                                    ->description('Verwalten Sie den KI-Agenten für diese Filiale')
                                    ->schema([
                                        Forms\Components\View::make('retell_agent_info')
                                            ->view('filament.forms.components.retell-agent-info')
                                            ->columnSpanFull(),
                                        
                                        Forms\Components\TextInput::make('retell_agent_id')
                                            ->label('Agent ID')
                                            ->placeholder('agent_xxxxxx')
                                            ->helperText('Die Retell.ai Agent ID für diese Filiale')
                                            ->maxLength(255),
                                    ]),
                                    
                                Section::make('Agent-Details')
                                    ->description('Informationen über den konfigurierten Agenten')
                                    ->collapsible()
                                    ->visible(fn ($record) => $record && $record->retell_agent_id)
                                    ->schema([
                                        Forms\Components\Placeholder::make('agent_name')
                                            ->label('Agent Name')
                                            ->content(fn ($record) => $record->retell_agent_name ?? 'Nicht verfügbar'),
                                            
                                        Forms\Components\Placeholder::make('agent_prompt')
                                            ->label('Prompt')
                                            ->content(fn ($record) => $record->retell_agent_prompt ?? 'Nicht verfügbar'),
                                            
                                        Forms\Components\Placeholder::make('voice_settings')
                                            ->label('Stimme')
                                            ->content(fn ($record) => $record->retell_voice_model ?? 'Nicht verfügbar'),
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
                                            ->searchable(),
                                    ]),

                                Section::make('Mitarbeiter')
                                    ->description('Ordnen Sie Mitarbeiter dieser Filiale zu')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('availableStaff')
                                            ->relationship('availableStaff', 'name')
                                            ->columns(2)
                                            ->searchable(),
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
                                                })
                                                ->disabled(fn ($record) => !$record),
                                        ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString()
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
                              ->whereNotNull('calcom_api_key')
                              ->whereNotNull('business_hours')
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
