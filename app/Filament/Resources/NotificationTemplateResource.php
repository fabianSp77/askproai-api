<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationTemplateResource\Pages;
use App\Models\NotificationTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Tabs;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Benachrichtigungen';

    protected static ?string $navigationLabel = 'Vorlagen';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grundinformationen')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Schlüssel')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(2)
                            ->columnSpanFull(),
                        
                        Forms\Components\Select::make('channel')
                            ->label('Kanal')
                            ->options([
                                'email' => 'E-Mail',
                                'sms' => 'SMS',
                                'whatsapp' => 'WhatsApp',
                                'push' => 'Push-Benachrichtigung'
                            ])
                            ->required()
                            ->reactive(),
                        
                        Forms\Components\Select::make('type')
                            ->label('Typ')
                            ->options([
                                'confirmation' => 'Bestätigung',
                                'reminder' => 'Erinnerung',
                                'cancellation' => 'Stornierung',
                                'rescheduled' => 'Verschiebung',
                                'marketing' => 'Marketing',
                                'system' => 'System'
                            ])
                            ->required(),
                        
                        Forms\Components\TextInput::make('priority')
                            ->label('Priorität')
                            ->numeric()
                            ->default(5)
                            ->minValue(1)
                            ->maxValue(10)
                            ->helperText('1 = höchste, 10 = niedrigste'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Inhalte')
                    ->schema([
                        Tabs::make('Sprachen')
                            ->tabs([
                                Tabs\Tab::make('Deutsch')
                                    ->schema([
                                        Forms\Components\TextInput::make('subject.de')
                                            ->label('Betreff')
                                            ->visible(fn (Forms\Get $get): bool => in_array($get('channel'), ['email'])),
                                        
                                        Forms\Components\Textarea::make('content.de')
                                            ->label('Inhalt')
                                            ->required()
                                            ->rows(10)
                                            ->helperText('Verfügbare Variablen: {name}, {date}, {time}, {location}, {service}, {employee}, {amount:currency}'),
                                    ]),
                                
                                Tabs\Tab::make('Englisch')
                                    ->schema([
                                        Forms\Components\TextInput::make('subject.en')
                                            ->label('Subject')
                                            ->visible(fn (Forms\Get $get): bool => in_array($get('channel'), ['email'])),
                                        
                                        Forms\Components\Textarea::make('content.en')
                                            ->label('Content')
                                            ->rows(10)
                                            ->helperText('Available variables: {name}, {date}, {time}, {location}, {service}, {employee}, {amount:currency}'),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Konfiguration')
                    ->schema([
                        KeyValue::make('variables')
                            ->label('Verfügbare Variablen')
                            ->keyLabel('Variable')
                            ->valueLabel('Beschreibung')
                            ->addButtonLabel('Variable hinzufügen')
                            ->columnSpanFull(),
                        
                        KeyValue::make('metadata')
                            ->label('Metadaten')
                            ->keyLabel('Schlüssel')
                            ->valueLabel('Wert')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Schlüssel')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('channel')
                    ->label('Kanal')
                    ->badge()
                    ->colors([
                        'primary' => 'email',
                        'success' => 'whatsapp',
                        'warning' => 'sms',
                        'danger' => 'push',
                    ]),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->colors([
                        'success' => 'confirmation',
                        'warning' => 'reminder',
                        'danger' => 'cancellation',
                        'secondary' => 'system',
                    ]),
                
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktiv'),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->label('Kanal')
                    ->options([
                        'email' => 'E-Mail',
                        'sms' => 'SMS',
                        'whatsapp' => 'WhatsApp',
                        'push' => 'Push-Benachrichtigung'
                    ]),
                
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'confirmation' => 'Bestätigung',
                        'reminder' => 'Erinnerung',
                        'cancellation' => 'Stornierung',
                        'rescheduled' => 'Verschiebung',
                        'marketing' => 'Marketing',
                        'system' => 'System'
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('preview')
                    ->label('Vorschau')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Template-Vorschau')
                    ->modalContent(fn (NotificationTemplate $record): \Illuminate\View\View => view(
                        'filament.notifications.template-preview',
                        ['template' => $record]
                    )),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplizieren')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (NotificationTemplate $record): void {
                        $newTemplate = $record->replicate();
                        $newTemplate->key = $record->key . '_copy';
                        $newTemplate->name = $record->name . ' (Kopie)';
                        $newTemplate->save();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationTemplates::route('/'),
            'create' => Pages\CreateNotificationTemplate::route('/create'),
            'edit' => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}