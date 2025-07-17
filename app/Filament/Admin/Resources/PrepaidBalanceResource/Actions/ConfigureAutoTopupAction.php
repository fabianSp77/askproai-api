<?php

namespace App\Filament\Admin\Resources\PrepaidBalanceResource\Actions;

use App\Models\PrepaidBalance;
use Filament\Forms;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class ConfigureAutoTopupAction
{
    public static function make(): Action
    {
        return Action::make('configureAutoTopup')
            ->label('Auto-Aufladung')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->modalHeading('Auto-Aufladung konfigurieren')
            ->modalWidth('lg')
            ->form([
                Forms\Components\Toggle::make('auto_topup_enabled')
                    ->label('Auto-Aufladung aktivieren')
                    ->helperText('Automatisch Guthaben aufladen, wenn der Schwellenwert unterschritten wird')
                    ->reactive()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $state ?: $set('auto_topup_payment_method_id', null)),
                    
                Forms\Components\Section::make('Einstellungen')
                    ->visible(fn (Forms\Get $get) => $get('auto_topup_enabled'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('auto_topup_threshold')
                                    ->label('Schwellenwert')
                                    ->prefix('€')
                                    ->numeric()
                                    ->required()
                                    ->minValue(10)
                                    ->maxValue(500)
                                    ->step(10)
                                    ->helperText('Bei diesem Guthaben wird automatisch aufgeladen'),
                                    
                                Forms\Components\TextInput::make('auto_topup_amount')
                                    ->label('Aufladebetrag')
                                    ->prefix('€')
                                    ->numeric()
                                    ->required()
                                    ->minValue(50)
                                    ->maxValue(5000)
                                    ->step(50)
                                    ->helperText('Betrag der automatisch aufgeladen wird'),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('auto_topup_daily_limit')
                                    ->label('Tageslimit')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->default(2)
                                    ->helperText('Max. Aufladungen pro Tag'),
                                    
                                Forms\Components\TextInput::make('auto_topup_monthly_limit')
                                    ->label('Monatslimit')
                                    ->numeric()
                                    ->required()
                                    ->minValue(5)
                                    ->maxValue(30)
                                    ->default(10)
                                    ->helperText('Max. Aufladungen pro Monat'),
                            ]),
                            
                        Forms\Components\Placeholder::make('payment_method_info')
                            ->label('Zahlungsmethode')
                            ->content(function (PrepaidBalance $record) {
                                if (!$record->company->stripe_customer_id) {
                                    return 'Keine Zahlungsmethode hinterlegt. Der Kunde muss zuerst eine Zahlungsmethode im Portal hinzufügen.';
                                }
                                
                                return 'Die hinterlegte Zahlungsmethode wird für Auto-Aufladungen verwendet.';
                            }),
                    ]),
                    
                Forms\Components\Section::make('Sicherheitshinweise')
                    ->visible(fn (Forms\Get $get) => $get('auto_topup_enabled'))
                    ->schema([
                        Forms\Components\Placeholder::make('security_info')
                            ->label('')
                            ->content(fn () => view('filament.resources.prepaid-balance.auto-topup-info')),
                    ]),
            ])
            ->fillForm(fn (PrepaidBalance $record): array => [
                'auto_topup_enabled' => $record->auto_topup_enabled,
                'auto_topup_threshold' => $record->auto_topup_threshold,
                'auto_topup_amount' => $record->auto_topup_amount,
                'auto_topup_daily_limit' => $record->auto_topup_daily_limit ?? 2,
                'auto_topup_monthly_limit' => $record->auto_topup_monthly_limit ?? 10,
            ])
            ->action(function (PrepaidBalance $record, array $data): void {
                $record->update($data);
                
                // Log the change
                activity()
                    ->performedOn($record)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'auto_topup_enabled' => $data['auto_topup_enabled'],
                        'settings' => $data
                    ])
                    ->log($data['auto_topup_enabled'] ? 'Auto-Aufladung aktiviert' : 'Auto-Aufladung deaktiviert');
                
                Notification::make()
                    ->title('Auto-Aufladung aktualisiert')
                    ->success()
                    ->send();
            });
    }
}