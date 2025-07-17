<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PortalUserResource\Pages;
use App\Models\PortalUser;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class PortalUserResource extends Resource
{
    protected static ?string $model = PortalUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'Verwaltung';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $navigationLabel = 'Portal Benutzer';
    
    protected static ?string $pluralModelLabel = 'Portal Benutzer';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Benutzerinformationen')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Firma')
                            ->options(Company::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Select::make('role')
                            ->label('Rolle')
                            ->options([
                                'admin' => 'Administrator',
                                'manager' => 'Manager',
                                'employee' => 'Mitarbeiter',
                                'viewer' => 'Nur Ansicht',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Status & Berechtigungen')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->helperText('Schalten Sie den Benutzer frei, damit er sich einloggen kann')
                            ->reactive()
                            ->afterStateUpdated(function ($state, $record) {
                                if ($state && $record && !$record->is_active) {
                                    // Send activation email when user is activated
                                    try {
                                        Mail::to($record->email)->send(new \App\Mail\PortalUserActivated($record));
                                        Notification::make()
                                            ->title('Aktivierungsmail gesendet')
                                            ->success()
                                            ->send();
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Fehler beim Senden der Aktivierungsmail')
                                            ->danger()
                                            ->send();
                                    }
                                }
                            }),
                        Forms\Components\DateTimePicker::make('last_login_at')
                            ->label('Letzter Login')
                            ->disabled(),
                        Forms\Components\KeyValue::make('permissions')
                            ->label('Berechtigungen')
                            ->keyLabel('Berechtigung')
                            ->valueLabel('Erlaubt')
                            ->default([]),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Registrierungsdaten')
                    ->schema([
                        Forms\Components\KeyValue::make('registration_data')
                            ->label('Registrierungsinformationen')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record && $record->registration_data),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Firma')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rolle')
                    ->badge()
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'manager',
                        'info' => 'employee',
                        'gray' => 'viewer',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registriert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Letzter Login')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Noch nie'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktiv',
                        '0' => 'Inaktiv',
                    ]),
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rolle')
                    ->options([
                        'admin' => 'Administrator',
                        'manager' => 'Manager',
                        'employee' => 'Mitarbeiter',
                        'viewer' => 'Nur Ansicht',
                    ]),
                Tables\Filters\Filter::make('pending_activation')
                    ->label('Wartet auf Freischaltung')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', false)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Freischalten')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('Benutzer freischalten')
                    ->modalDescription('Möchten Sie diesen Benutzer wirklich freischalten? Der Benutzer erhält eine E-Mail-Benachrichtigung.')
                    ->action(function ($record) {
                        $record->update(['is_active' => true]);
                        
                        // Send activation email
                        try {
                            Mail::to($record->email)->send(new \App\Mail\PortalUserActivated($record));
                            Notification::make()
                                ->title('Benutzer freigeschaltet')
                                ->body('Der Benutzer wurde aktiviert und benachrichtigt.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Benutzer freigeschaltet')
                                ->body('Der Benutzer wurde aktiviert, aber die E-Mail konnte nicht gesendet werden.')
                                ->warning()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('deactivate')
                    ->label('Deaktivieren')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->is_active)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['is_active' => false])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Freischalten')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPortalUsers::route('/'),
            'create' => Pages\CreatePortalUser::route('/create'),
            'edit' => Pages\EditPortalUser::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', false)->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}