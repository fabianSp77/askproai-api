<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceOutputConfigurationResource\Pages;
use App\Models\ServiceOutputConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceOutputConfigurationResource extends Resource
{
    protected static ?string $model = ServiceOutputConfiguration::class;
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?string $navigationLabel = 'Output Konfigurationen';
    protected static ?string $modelLabel = 'Output Konfiguration';
    protected static ?string $pluralModelLabel = 'Output Konfigurationen';
    protected static ?int $navigationSort = 12;

    /**
     * Only show in navigation when Service Gateway is enabled.
     * @see config/gateway.php 'mode_enabled'
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('gateway.mode_enabled', false);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grundeinstellungen')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Eindeutiger Name für diese Konfiguration'),
                        Forms\Components\Select::make('output_type')
                            ->label('Ausgabetyp')
                            ->options([
                                ServiceOutputConfiguration::TYPE_EMAIL => 'E-Mail',
                                ServiceOutputConfiguration::TYPE_WEBHOOK => 'Webhook',
                                ServiceOutputConfiguration::TYPE_BOTH => 'Beides',
                            ])
                            ->required()
                            ->default(ServiceOutputConfiguration::TYPE_EMAIL)
                            ->live()
                            ->helperText('Art der Benachrichtigung'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Deaktivierte Konfigurationen werden nicht verwendet'),
                        Forms\Components\Toggle::make('retry_on_failure')
                            ->label('Bei Fehler wiederholen')
                            ->default(true)
                            ->helperText('Automatische Wiederholung bei fehlgeschlagener Zustellung'),
                    ])->columns(2),

                Forms\Components\Section::make('E-Mail Konfiguration')
                    ->schema([
                        Forms\Components\TagsInput::make('email_recipients')
                            ->label('Empfänger')
                            ->placeholder('E-Mail-Adresse eingeben')
                            ->helperText('Primäre E-Mail-Empfänger für diese Konfiguration')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('email_subject_template')
                            ->label('Betreff-Template')
                            ->maxLength(255)
                            ->helperText('Variablen: {{subject}}, {{case_type}}, {{priority}}, {{ticket_id}}')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('email_body_template')
                            ->label('Inhalt-Template')
                            ->rows(6)
                            ->helperText('Variablen: {{subject}}, {{description}}, {{case_type}}, {{priority}}, {{ticket_id}}, {{customer_name}}, {{customer_phone}}')
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('fallback_emails')
                            ->label('Fallback E-Mails')
                            ->placeholder('E-Mail-Adresse eingeben')
                            ->helperText('Wird verwendet, wenn primäre Zustellung fehlschlägt')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                        ServiceOutputConfiguration::TYPE_EMAIL,
                        ServiceOutputConfiguration::TYPE_BOTH,
                    ])),

                Forms\Components\Section::make('Webhook Konfiguration')
                    ->schema([
                        Forms\Components\TextInput::make('webhook_url')
                            ->label('Webhook URL')
                            ->url()
                            ->maxLength(2048)
                            ->helperText('Ziel-URL für Webhook-Anfragen')
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('webhook_headers')
                            ->label('HTTP Headers')
                            ->keyLabel('Header Name')
                            ->valueLabel('Header Wert')
                            ->addActionLabel('Header hinzufügen')
                            ->helperText('z.B. Authorization, Content-Type, X-API-Key')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('webhook_payload_template')
                            ->label('Payload-Template (JSON)')
                            ->rows(8)
                            ->helperText('JSON-Template für den Webhook-Body. Variablen: {{subject}}, {{description}}, {{case_type}}, {{priority}}, {{ticket_id}}, {{customer_name}}, {{customer_phone}}, {{structured_data}}')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                        ServiceOutputConfiguration::TYPE_WEBHOOK,
                        ServiceOutputConfiguration::TYPE_BOTH,
                    ])),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('output_type')
                    ->label('Ausgabetyp')
                    ->colors([
                        'info' => ServiceOutputConfiguration::TYPE_EMAIL,
                        'warning' => ServiceOutputConfiguration::TYPE_WEBHOOK,
                        'success' => ServiceOutputConfiguration::TYPE_BOTH,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ServiceOutputConfiguration::TYPE_EMAIL => 'E-Mail',
                        ServiceOutputConfiguration::TYPE_WEBHOOK => 'Webhook',
                        ServiceOutputConfiguration::TYPE_BOTH => 'Beides',
                        default => $state,
                    }),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktiv')
                    ->sortable(),
                Tables\Columns\TextColumn::make('categories_count')
                    ->label('Kategorien')
                    ->counts('categories')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('retry_on_failure')
                    ->label('Retry')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('output_type')
                    ->label('Ausgabetyp')
                    ->options([
                        ServiceOutputConfiguration::TYPE_EMAIL => 'E-Mail',
                        ServiceOutputConfiguration::TYPE_WEBHOOK => 'Webhook',
                        ServiceOutputConfiguration::TYPE_BOTH => 'Beides',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->before(function (Tables\Actions\DeleteAction $action, ServiceOutputConfiguration $record) {
                        if ($record->categories()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Konfiguration kann nicht geloescht werden')
                                ->body('Es existieren noch Kategorien, die diese Konfiguration verwenden.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (ServiceOutputConfiguration $record) => $record->is_active ? 'Deaktivieren' : 'Aktivieren')
                    ->icon(fn (ServiceOutputConfiguration $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (ServiceOutputConfiguration $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (ServiceOutputConfiguration $record) => $record->update(['is_active' => !$record->is_active])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ]);
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
            'index' => Pages\ListServiceOutputConfigurations::route('/'),
            'create' => Pages\CreateServiceOutputConfiguration::route('/create'),
            'edit' => Pages\EditServiceOutputConfiguration::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }
}
