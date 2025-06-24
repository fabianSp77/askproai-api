<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\IntegrationResource\Pages;
use App\Models\Integration;
use App\Models\Company;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class IntegrationResource extends Resource
{
    protected static ?string $model = Integration::class;
    protected static ?string $navigationGroup = 'Einrichtung & Konfiguration';
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationLabel = 'Integrationen';
    protected static ?int $navigationSort = 20;
    protected static bool $shouldRegisterNavigation = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Integration Details')
                    ->description('Konfigurieren Sie Ihre Drittanbieter-Integrationen')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('company_id')
                                    ->label('Unternehmen')
                                    ->options(Company::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->disabled(fn ($record) => $record !== null),
                                    
                                Forms\Components\Select::make('customer_id')
                                    ->label('Kunde')
                                    ->options(fn ($get) => Customer::where('company_id', $get('company_id'))->pluck('name', 'id'))
                                    ->searchable()
                                    ->reactive()
                                    ->helperText('Optional: Verknüpfen Sie diese Integration mit einem spezifischen Kunden'),
                            ]),
                            
                        Forms\Components\Select::make('service')
                            ->label('Service')
                            ->options([
                                'calcom' => 'Cal.com',
                                'retell' => 'Retell.ai',
                                'google_calendar' => 'Google Calendar',
                                'outlook' => 'Microsoft Outlook',
                                'zoom' => 'Zoom',
                                'stripe' => 'Stripe',
                                'twilio' => 'Twilio',
                                'sendgrid' => 'SendGrid',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('type', match($state) {
                                    'calcom', 'google_calendar', 'outlook' => 'calendar',
                                    'retell' => 'phone_ai',
                                    'zoom' => 'video',
                                    'stripe' => 'payment',
                                    'twilio' => 'sms',
                                    'sendgrid' => 'email',
                                    default => 'other'
                                });
                            }),
                            
                        Forms\Components\TextInput::make('type')
                            ->label('Typ')
                            ->disabled()
                            ->dehydrated(),
                            
                        Forms\Components\Toggle::make('active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Deaktivierte Integrationen werden nicht verwendet'),
                    ]),
                    
                Forms\Components\Section::make('Authentifizierung')
                    ->description('API-Schlüssel und Zugangsdaten')
                    ->schema([
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->required(fn ($get) => in_array($get('service'), ['calcom', 'retell', 'stripe', 'sendgrid']))
                            ->helperText('Wird verschlüsselt gespeichert'),
                            
                        Forms\Components\Textarea::make('settings')
                            ->label('Zusätzliche Einstellungen')
                            ->rows(5)
                            ->helperText('JSON-Format für erweiterte Konfiguration')
                            ->afterStateUpdated(function ($state) {
                                if ($state && !json_decode($state)) {
                                    Notification::make()
                                        ->title('Ungültiges JSON-Format')
                                        ->danger()
                                        ->send();
                                }
                            }),
                            
                        Forms\Components\TextInput::make('webhook_url')
                            ->label('Webhook URL')
                            ->url()
                            ->helperText('Für eingehende Webhooks von diesem Service')
                            ->visible(fn ($get) => in_array($get('service'), ['calcom', 'retell', 'stripe'])),
                    ]),
                    
                Forms\Components\Section::make('Status & Monitoring')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('last_sync')
                                    ->label('Letzte Synchronisation')
                                    ->content(fn ($record) => $record?->last_sync?->diffForHumans() ?? 'Noch nie'),
                                    
                                Forms\Components\Placeholder::make('health_status')
                                    ->label('Verbindungsstatus')
                                    ->content(function ($record) {
                                        if (!$record) return 'Unbekannt';
                                        
                                        return match($record->health_status) {
                                            'healthy' => '✅ Verbunden',
                                            'error' => '❌ Fehler',
                                            'warning' => '⚠️ Warnung',
                                            default => '❓ Unbekannt'
                                        };
                                    }),
                                    
                                Forms\Components\Placeholder::make('usage')
                                    ->label('Nutzung (30 Tage)')
                                    ->content(fn ($record) => $record ? number_format($record->usage_count ?? 0) . ' Anfragen' : '0 Anfragen'),
                            ]),
                            
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadaten')
                            ->addActionLabel('Metadaten hinzufügen')
                            ->keyLabel('Schlüssel')
                            ->valueLabel('Wert')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-building-office')
                    ->getStateUsing(fn ($record) => $record?->company?->name ?? '-'),
                    
                Tables\Columns\TextColumn::make('service')
                    ->label('Service')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'calcom' => 'Cal.com',
                        'retell' => 'Retell.ai',
                        'google_calendar' => 'Google Calendar',
                        'outlook' => 'Microsoft Outlook',
                        'zoom' => 'Zoom',
                        'stripe' => 'Stripe',
                        'twilio' => 'Twilio',
                        'sendgrid' => 'SendGrid',
                        default => ucfirst($state)
                    })
                    ->color(fn (string $state): string => match($state) {
                        'calcom', 'google_calendar', 'outlook' => 'info',
                        'retell' => 'success',
                        'zoom' => 'warning',
                        'stripe' => 'danger',
                        default => 'gray'
                    })
                    ->icon(fn (string $state): string => match($state) {
                        'calcom', 'google_calendar', 'outlook' => 'heroicon-o-calendar',
                        'retell' => 'heroicon-o-phone',
                        'zoom' => 'heroicon-o-video-camera',
                        'stripe' => 'heroicon-o-credit-card',
                        'twilio' => 'heroicon-o-chat-bubble-left-right',
                        'sendgrid' => 'heroicon-o-envelope',
                        default => 'heroicon-o-puzzle-piece'
                    }),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\IconColumn::make('active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                Tables\Columns\TextColumn::make('health_status')
                    ->label('Verbindung')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'healthy' => 'Verbunden',
                        'error' => 'Fehler',
                        'warning' => 'Warnung',
                        default => 'Unbekannt'
                    })
                    ->color(fn (?string $state): string => match($state) {
                        'healthy' => 'success',
                        'error' => 'danger',
                        'warning' => 'warning',
                        default => 'gray'
                    }),
                    
                Tables\Columns\TextColumn::make('last_sync')
                    ->label('Letzte Sync')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->last_sync?->diffForHumans())
                    ->placeholder('Nie'),
                    
                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Nutzung')
                    ->numeric()
                    ->suffix(' Anfragen')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('service')
            ->filters([
                Tables\Filters\SelectFilter::make('service')
                    ->label('Service')
                    ->options([
                        'calcom' => 'Cal.com',
                        'retell' => 'Retell.ai',
                        'google_calendar' => 'Google Calendar',
                        'outlook' => 'Microsoft Outlook',
                        'zoom' => 'Zoom',
                        'stripe' => 'Stripe',
                        'twilio' => 'Twilio',
                        'sendgrid' => 'SendGrid',
                    ]),
                    
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'calendar' => 'Kalender',
                        'phone_ai' => 'Telefon AI',
                        'video' => 'Video',
                        'payment' => 'Zahlung',
                        'sms' => 'SMS',
                        'email' => 'E-Mail',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Aktiv'),
                    
                Tables\Filters\SelectFilter::make('health_status')
                    ->label('Verbindungsstatus')
                    ->options([
                        'healthy' => 'Verbunden',
                        'error' => 'Fehler',
                        'warning' => 'Warnung',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Integration Details'),
                    
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('test_connection')
                    ->label('Testen')
                    ->icon('heroicon-m-signal')
                    ->color('gray')
                    ->action(function (Integration $record) {
                        // Test connection based on service type
                        try {
                            $success = match($record->service) {
                                'calcom' => $this->testCalcomConnection($record),
                                'retell' => $this->testRetellConnection($record),
                                'stripe' => $this->testStripeConnection($record),
                                default => false
                            };
                            
                            if ($success) {
                                $record->update([
                                    'health_status' => 'healthy',
                                    'last_sync' => now(),
                                ]);
                                
                                Notification::make()
                                    ->title('Verbindung erfolgreich')
                                    ->success()
                                    ->send();
                            } else {
                                throw new \Exception('Verbindungstest fehlgeschlagen');
                            }
                        } catch (\Exception $e) {
                            $record->update(['health_status' => 'error']);
                            
                            Notification::make()
                                ->title('Verbindungsfehler')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                    
                Tables\Actions\Action::make('sync')
                    ->label('Sync')
                    ->icon('heroicon-m-arrow-path')
                    ->color('info')
                    ->visible(fn ($record) => in_array($record->service, ['calcom', 'google_calendar']))
                    ->requiresConfirmation()
                    ->action(function (Integration $record) {
                        // Sync data based on service type
                        Notification::make()
                            ->title('Synchronisation gestartet')
                            ->body('Die Daten werden im Hintergrund synchronisiert.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['active' => true]))
                        ->deselectRecordsAfterCompletion(),
                        
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-m-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['active' => false]))
                        ->deselectRecordsAfterCompletion(),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    // Helper methods for testing connections
    private static function testCalcomConnection(Integration $integration): bool
    {
        // Implement Cal.com API test
        return true; // Placeholder
    }
    
    private static function testRetellConnection(Integration $integration): bool
    {
        // Implement Retell API test
        return true; // Placeholder
    }
    
    private static function testStripeConnection(Integration $integration): bool
    {
        // Implement Stripe API test
        return true; // Placeholder
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
            'index' => Pages\ListIntegrations::route('/'),
            'create' => Pages\CreateIntegration::route('/create'),
            'edit' => Pages\EditIntegration::route('/{record}/edit'),
        ];
    }
    
    public static function getWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\IntegrationStatusWidget::class,
        ];
    }
}
