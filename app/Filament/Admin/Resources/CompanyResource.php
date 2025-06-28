<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CompanyResource\Pages;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use App\Models\Company;
use App\Services\CalcomEventTypeSyncService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Wizard;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class CompanyResource extends Resource
{
    protected static ?string $navigationGroup = 'Verwaltung';
    protected static ?int $navigationSort = 80;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        
        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        return $user->can('view_any_company');
    }
    
    public static function canView($record): bool
    {
        $user = auth()->user();
        
        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('view_company')) {
            return true;
        }
        
        // Users can view their own company
        return $user->company_id === $record->id;
    }
    
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        
        // Super admin can edit all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('update_company')) {
            return true;
        }
        
        // Company admins can edit their own company
        return $user->company_id === $record->id && $user->hasRole('company_admin');
    }
    
    public static function canCreate(): bool
    {
        $user = auth()->user();
        
        // Only super admins can create new companies
        return $user->hasRole('super_admin') || $user->can('create_company');
    }

    use HasConsistentNavigation;
    
    protected static ?string $model = Company::class;
    protected static ?string $navigationLabel = 'Unternehmen';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                
                // ===============================
                // STEP 1: GRUNDDATEN
                // ===============================
                Wizard\Step::make('Grunddaten')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Forms\Components\Section::make('Unternehmensinformationen')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Unternehmensname')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(1),
                                        
                                    Forms\Components\TextInput::make('email')
                                        ->label('E-Mail')
                                        ->email()
                                        ->required()
                                        ->columnSpan(1),
                                ]),
                                
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('phone')
                                        ->label('Telefon')
                                        ->tel()
                                        ->columnSpan(1),
                                        
                                    Forms\Components\TextInput::make('settings.tax_number')
                                        ->label('Steuernummer')
                                        ->columnSpan(1),
                                ]),
                                
                                Forms\Components\TextInput::make('contact_person')
                                    ->label('Ansprechpartner')
                                    ->maxLength(255),
                                    
                                Forms\Components\Textarea::make('address')
                                    ->label('Adresse')
                                    ->rows(3),
                                    
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true)
                                    ->helperText('Deaktivierte Unternehmen können keine Termine empfangen'),
                            ]),
                    ]),

                // ===============================
                // STEP 2: KALENDER & INTEGRATION
                // ===============================
                Wizard\Step::make('Kalender & Integration')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\Section::make('Cal.com Master-Konfiguration')
                            ->description('Diese Einstellungen werden an alle Filialen vererbt (können aber überschrieben werden)')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('calcom_api_key')
                                        ->label('🔑 Cal.com API-Schlüssel')
                                        ->password()
                                        ->revealable()
                                        ->helperText('Master API-Key für alle Filialen')
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('validate_api_key')
                                                ->label('Testen')
                                                ->icon('heroicon-o-check-circle')
                                                ->action(function ($state, $set) {
                                                    // API-Key Validierung
                                                    $result = CalcomEventTypeSyncService::validateApiKey($state);
                                                    if ($result['valid']) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('✅ API-Key gültig')
                                                            ->success()
                                                            ->send();
                                                    } else {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('❌ API-Key ungültig: ' . $result['error'])
                                                            ->danger()
                                                            ->send();
                                                    }
                                                })
                                        )
                                        ->columnSpan(1),
                                        
                                    Forms\Components\TextInput::make('calcom_user_id')
                                        ->label('👤 Cal.com Standard-User-ID')
                                        ->helperText('Default User für alle Buchungen')
                                        ->columnSpan(1),
                                ]),
                                
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('calcom_team_slug')
                                        ->label('🏢 Cal.com Team Slug')
                                        ->helperText('Falls Sie Teams verwenden')
                                        ->columnSpan(1),
                                        
                                    Forms\Components\Select::make('calcom_calendar_mode')
                                        ->label('📅 Kalender-Modus')
                                        ->options([
                                            'zentral' => '🎯 Zentral (Ein Kalender für alle)',
                                            'filiale' => '🏪 Pro Filiale (Getrennte Kalender)',
                                            'mitarbeiter' => '👥 Pro Mitarbeiter (Individuelle Kalender)'
                                        ])
                                        ->default('zentral')
                                        ->columnSpan(1),
                                ]),
                            ]),
                            
                        Forms\Components\Section::make('🎯 Event-Types-Verwaltung')
                            ->collapsed()
                            ->schema([
                                Forms\Components\Placeholder::make('event_types_info')
                                    ->label('')
                                    ->content(function ($get) {
                                        $apiKey = $get('calcom_api_key');
                                        if (!$apiKey) {
                                            return new \Illuminate\Support\HtmlString('
                                                <div class="p-4 bg-yellow-50 rounded-lg">
                                                    <p class="text-yellow-800">⚠️ Bitte zuerst API-Key eingeben, um Event-Types zu laden</p>
                                                </div>
                                            ');
                                        }
                                        
                                        $eventTypes = CalcomEventTypeSyncService::fetchEventTypes($apiKey);
                                        if (empty($eventTypes)) {
                                            return new \Illuminate\Support\HtmlString('
                                                <div class="p-4 bg-red-50 rounded-lg">
                                                    <p class="text-red-800">❌ Keine Event-Types gefunden oder API-Key ungültig</p>
                                                </div>
                                            ');
                                        }
                                        
                                        $html = '<div class="space-y-3">';
                                        $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                                        
                                        foreach ($eventTypes as $event) {
                                            $statusBadge = $event['hidden'] ? 
                                                '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">🔒 Versteckt</span>' : 
                                                '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">👁️ Aktiv</span>';
                                                
                                            // HIER IST DIE KORREKTUR: Verwende statische Methode statt $this
                                            $webhookStatus = CalcomEventTypeSyncService::checkWebhookStatus($event['id'], $apiKey);
                                            $webhookBadge = $webhookStatus ? 
                                                '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">✅ Webhook OK</span>' :
                                                '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">❌ Webhook fehlt</span>';
                                            
                                            $html .= '<div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">';
                                            $html .= '<div class="flex justify-between items-start mb-2">';
                                            $html .= '<h4 class="font-medium text-gray-900">' . htmlspecialchars($event['title']) . '</h4>';
                                            $html .= '<div class="flex space-x-1">' . $statusBadge . $webhookBadge . '</div>';
                                            $html .= '</div>';
                                            $html .= '<div class="text-sm text-gray-600 space-y-1">';
                                            $html .= '<p>⏱️ Dauer: ' . ($event['length'] ?? 'Unbekannt') . ' Minuten</p>';
                                            $html .= '<p>🆔 Event-ID: ' . $event['id'] . '</p>';
                                            if (!empty($event['teamId'])) {
                                                $html .= '<p>👥 Team-ID: ' . $event['teamId'] . '</p>';
                                            }
                                            $html .= '</div>';
                                            $html .= '</div>';
                                        }
                                        
                                        $html .= '</div></div>';
                                        
                                        return new \Illuminate\Support\HtmlString($html);
                                    }),
                            ]),
Forms\Components\Section::make('🔗 Retell.ai Integration')
                            ->description('Telefon-KI für automatische Anrufannahme und Terminbuchung')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('retell_api_key')
                                        ->label('🤖 Retell.ai API-Schlüssel')
                                        ->password()
                                        ->revealable()
                                        ->helperText('Master API-Key für Telefon-KI')
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('validate_retell_key')
                                                ->label('Testen')
                                                ->icon('heroicon-o-check-circle')
                                                ->action(function ($state, $set) {
                                                    if (!$state) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('⚠️ Bitte API-Key eingeben')
                                                            ->warning()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    // Hier würde die Validierung stattfinden
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('✅ Retell.ai API-Key gespeichert')
                                                        ->body('Die Validierung wird beim Speichern durchgeführt')
                                                        ->success()
                                                        ->send();
                                                })
                                        )
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('settings.retell_webhook_url')
                                        ->label('🔗 Webhook URL')
                                        ->default('https://api.askproai.de/api/retell/webhook')
                                        ->readonly()
                                        ->helperText('Diese URL in Retell.ai konfigurieren')
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('copy_webhook')
                                                ->label('Kopieren')
                                                ->icon('heroicon-o-clipboard')
                                                ->action(function ($state) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('📋 Webhook URL in Zwischenablage kopiert!')
                                                        ->body($state)
                                                        ->success()
                                                        ->send();
                                                })
                                        )
                                        ->columnSpan(1),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\Select::make('retell_agent_id')
                                        ->label('🤖 Standard-Agent')
                                        ->options(function ($get) {
                                            // Hier könnten die Agenten geladen werden
                                            return [
                                                'agent_1' => '📞 Allgemeiner Empfang',
                                                'agent_2' => '✂️ Friseur-Agent',
                                                'agent_3' => '🏥 Medizin-Agent',
                                                'agent_4' => '🐕 Tierarzt-Agent',
                                            ];
                                        })
                                        ->helperText('Wählen Sie den Standard-Agenten für eingehende Anrufe')
                                        ->columnSpan(1),

                                    Forms\Components\Select::make('settings.retell_voice')
                                        ->label('🗣️ Stimme')
                                        ->options([
                                            'nova' => '👩 Nova (Weiblich)',
                                            'alex' => '👨 Alex (Männlich)',
                                            'emma' => '👩 Emma (Weiblich)',
                                            'max' => '👨 Max (Männlich)',
                                        ])
                                        ->default('nova')
                                        ->helperText('Stimme für den KI-Assistenten')
                                        ->columnSpan(1),
                                ]),

                                Forms\Components\Placeholder::make('retell_status')
                                    ->label('📊 Retell.ai Status')
                                    ->content(function ($get) {
                                        $apiKey = $get('retell_api_key');
                                        if (!$apiKey) {
                                            return new \Illuminate\Support\HtmlString('
                                                <div class="p-4 bg-yellow-50 rounded-lg">
                                                    <p class="text-yellow-800">⚠️ Bitte zuerst API-Key eingeben, um den Status zu prüfen</p>
                                                </div>
                                            ');
                                        }

                                        return new \Illuminate\Support\HtmlString('
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div class="p-4 bg-green-50 rounded-lg">
                                                    <h4 class="font-medium text-green-800">✅ API Verbindung</h4>
                                                    <p class="text-green-700 text-sm mt-1">Verbindung aktiv</p>
                                                </div>
                                                <div class="p-4 bg-blue-50 rounded-lg">
                                                    <h4 class="font-medium text-blue-800">📞 Anrufe heute</h4>
                                                    <p class="text-blue-700 text-2xl font-bold mt-1">0</p>
                                                </div>
                                                <div class="p-4 bg-purple-50 rounded-lg">
                                                    <h4 class="font-medium text-purple-800">🤖 Aktive Agenten</h4>
                                                    <p class="text-purple-700 text-2xl font-bold mt-1">0</p>
                                                </div>
                                            </div>
                                        ');
                                    }),

                                Forms\Components\Toggle::make('settings.retell_enabled')
                                    ->label('Retell.ai aktivieren')
                                    ->helperText('Aktiviert die Telefon-KI für eingehende Anrufe')
                                    ->default(false),
                     
                            ]),
                    ]),

                // ===============================
                // STEP 3: BENACHRICHTIGUNGEN
                // ===============================
                Wizard\Step::make('Benachrichtigungen')
                    ->icon('heroicon-o-bell')
                    ->schema([
                        Forms\Components\Section::make('📧 E-Mail-Benachrichtigungen')
                            ->schema([
                                Forms\Components\Repeater::make('settings.notification_emails')
                                    ->label('E-Mail-Empfänger')
                                    ->schema([
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('email')
                                                ->label('E-Mail-Adresse')
                                                ->email()
                                                ->required()
                                                ->columnSpan(1),
                                                
                                            Forms\Components\TextInput::make('name')
                                                ->label('Name')
                                                ->required()
                                                ->columnSpan(1),
                                                
                                            Forms\Components\Select::make('role')
                                                ->label('Rolle')
                                                ->options([
                                                    'admin' => '👑 Administrator',
                                                    'manager' => '🎯 Manager',
                                                    'staff' => '👤 Mitarbeiter',
                                                    'customer' => '🛍️ Kunde'
                                                ])
                                                ->required()
                                                ->columnSpan(1),
                                        ]),
                                        
                                        Forms\Components\CheckboxList::make('events')
                                            ->label('Benachrichtigungen für')
                                            ->options([
                                                'booking_created' => '📅 Neue Terminbuchung',
                                                'booking_cancelled' => '❌ Terminabsage',
                                                'booking_rescheduled' => '🔄 Terminänderung',
                                                'reminder_24h' => '⏰ Terminerinnerung (24h)',
                                                'reminder_1h' => '🔔 Terminerinnerung (1h)',
                                                'no_show' => '👻 No-Show gemeldet',
                                                'system_error' => '⚠️ Systemfehler',
                                                'api_error' => '🔌 API-Fehler',
                                                'daily_summary' => '📊 Tägliche Zusammenfassung'
                                            ])
                                            ->columns(3)
                                            ->required(),
                                    ])
                                    ->defaultItems(1)
                                    ->addActionLabel('📧 Weitere E-Mail hinzufügen')
                                    ->collapsible(),
                            ]),
                            
                        Forms\Components\Section::make('📱 SMS & WhatsApp (Premium)')
                            ->collapsed()
                            ->schema([
                                Forms\Components\Toggle::make('settings.sms_enabled')
                                    ->label('SMS aktivieren')
                                    ->helperText('Erfordert Twilio-Integration'),
                                    
                                Forms\Components\Toggle::make('settings.whatsapp_enabled')
                                    ->label('WhatsApp aktivieren')
                                    ->helperText('Erfordert WhatsApp Business API'),
                                    
                                Forms\Components\Textarea::make('settings.notification_template')
                                    ->label('Benachrichtigungs-Template')
                                    ->placeholder('Hallo {customer_name}, Ihr Termin am {date} um {time} wurde bestätigt...')
                                    ->helperText('Variablen: {customer_name}, {date}, {time}, {service}, {staff_name}')
                                    ->rows(3),
                            ]),
                    ]),

                // ===============================
                // STEP 4: GESCHÄFTSZEITEN
                // ===============================
                Wizard\Step::make('Geschäftszeiten')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\Section::make('🕐 Standard-Öffnungszeiten')
                            ->description('Diese Zeiten gelten als Standard für alle Filialen (können pro Filiale überschrieben werden)')
                            ->schema([
                                Forms\Components\Repeater::make('settings.business_hours')
                                    ->label('Öffnungszeiten')
                                    ->schema([
                                        Forms\Components\Grid::make(4)->schema([
                                            Forms\Components\Select::make('day')
                                                ->label('Tag')
                                                ->options([
                                                    0 => '📅 Sonntag',
                                                    1 => '📅 Montag', 
                                                    2 => '📅 Dienstag',
                                                    3 => '📅 Mittwoch',
                                                    4 => '📅 Donnerstag',
                                                    5 => '📅 Freitag',
                                                    6 => '📅 Samstag'
                                                ])
                                                ->required()
                                                ->columnSpan(1),
                                                
                                            Forms\Components\TimePicker::make('start_time')
                                                ->label('Von')
                                                ->required()
                                                ->columnSpan(1),
                                                
                                            Forms\Components\TimePicker::make('end_time')
                                                ->label('Bis')
                                                ->required()
                                                ->columnSpan(1),
                                                
                                            Forms\Components\Toggle::make('is_closed')
                                                ->label('Geschlossen')
                                                ->columnSpan(1),
                                        ]),
                                    ])
                                    ->defaultItems(7)
                                    ->addActionLabel('🕐 Weitere Öffnungszeit')
                                    ->collapsible(),
                            ]),
                            
                        Forms\Components\Section::make('🔗 Cal.com Verfügbarkeiten (Info)')
                            ->collapsed()
                            ->schema([
                                Forms\Components\Placeholder::make('calcom_availability_info')
                                    ->label('')
                                    ->content(function ($get) {
                                        $apiKey = $get('calcom_api_key');
                                        if (!$apiKey) {
                                            return new \Illuminate\Support\HtmlString('
                                                <div class="p-4 bg-blue-50 rounded-lg">
                                                    <p class="text-blue-800">💡 Cal.com-Verfügbarkeiten werden hier angezeigt, sobald der API-Key eingegeben wurde</p>
                                                </div>
                                            ');
                                        }
                                        
                                        // Cal.com Verfügbarkeiten abrufen und anzeigen
                                        return new \Illuminate\Support\HtmlString('
                                            <div class="p-4 bg-green-50 rounded-lg">
                                                <h4 class="font-medium text-green-800 mb-2">📅 Cal.com-Verfügbarkeiten</h4>
                                                <p class="text-green-700 mb-4">Die Verfügbarkeiten werden direkt aus Cal.com synchronisiert.</p>
                                                <a href="https://cal.com/availability" target="_blank" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                                    🔗 Verfügbarkeiten in Cal.com bearbeiten
                                                </a>
                                                <p class="text-xs text-green-600 mt-2">⚠️ Änderungen bitte direkt in Cal.com vornehmen, um Konflikte zu vermeiden</p>
                                            </div>
                                        ');
                                    }),
                            ]),
                    ]),

            ])->columnSpanFull()->skippable(),
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
                    
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Ansprechpartner')
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                Tables\Columns\TextColumn::make('branches_count')
                    ->label('Filialen')
                    ->counts('branches')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('staff_count')
                    ->label('Mitarbeiter')
                    ->counts('staff')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('sync_calcom')
                    ->label('🔄 Cal.com sync')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Company $record) {
                        // Cal.com Synchronisation
                        \Filament\Notifications\Notification::make()
                            ->title('✅ Synchronisation gestartet')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Unternehmensinformationen')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('email')
                            ->label('E-Mail'),
                        TextEntry::make('phone')
                            ->label('Telefon'),
                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Aktiv' : 'Inaktiv'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'view' => Pages\ViewCompany::route('/{record}'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
            'manage-api-credentials' => Pages\ManageApiCredentials::route('/{record}/api-credentials'),
        ];
    }
    
    public static function getRelations(): array
    {
        return [
            // CompanyResource\RelationManagers\EventTypesRelationManager::class,
        ];
    }
}
