<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserInvitationResource\Pages;
use App\Filament\Resources\UserInvitationResource\RelationManagers;
use App\Models\UserInvitation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserInvitationResource extends Resource
{
    protected static ?string $model = UserInvitation::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Kundeneinladungen';

    protected static ?string $modelLabel = 'Kundeneinladung';

    protected static ?string $pluralModelLabel = 'Kundeneinladungen';

    protected static ?string $navigationGroup = 'Kundenverwaltung';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Einladungsdetails')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->label('E-Mail-Adresse')
                            ->unique(
                                table: 'user_invitations',
                                ignorable: fn ($record) => $record,
                                modifyRuleUsing: fn ($rule) => $rule
                                    ->where('company_id', auth()->user()->company_id)
                                    ->whereNull('deleted_at')
                                    ->whereNull('accepted_at')
                                    ->where('expires_at', '>', now())
                            )
                            ->helperText('E-Mail-Adresse des einzuladenden Kunden'),

                        Forms\Components\Select::make('role_id')
                            ->relationship(
                                name: 'role',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereIn('name', [
                                    'viewer',      // Nur Leserechte
                                    'operator',    // Operative Tätigkeiten
                                    'manager',     // Management
                                ])
                            )
                            ->required()
                            ->label('Kundenrolle')
                            ->helperText('Wählen Sie die passende Rolle für den Kunden')
                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                $record->name . ' – ' . ($record->description ?? 'Keine Beschreibung')
                            )
                            ->searchable()
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rollen-Erklärung')
                    ->schema([
                        Forms\Components\Placeholder::make('role_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div style="background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 16px; border-radius: 6px;">
                                    <h4 style="margin: 0 0 12px 0; color: #0c4a6e; font-weight: 600;">📌 Verfügbare Kundenrollen:</h4>
                                    <div style="display: grid; gap: 10px;">
                                        <div style="background: white; padding: 10px; border-radius: 4px; border-left: 3px solid #10b981;">
                                            <strong style="color: #065f46;">👁️ Viewer (Betrachter)</strong><br>
                                            <span style="font-size: 0.9em; color: #374151;">Nur Leserechte – Kann eigene Termine ansehen, aber keine Änderungen vornehmen.</span>
                                        </div>
                                        <div style="background: white; padding: 10px; border-radius: 4px; border-left: 3px solid #f59e0b;">
                                            <strong style="color: #92400e;">⚙️ Operator (Bearbeiter)</strong><br>
                                            <span style="font-size: 0.9em; color: #374151;">Operative Tätigkeiten – Kann Termine ansehen, buchen, verschieben und stornieren.</span>
                                        </div>
                                        <div style="background: white; padding: 10px; border-radius: 4px; border-left: 3px solid #8b5cf6;">
                                            <strong style="color: #5b21b6;">👔 Manager (Verwalter)</strong><br>
                                            <span style="font-size: 0.9em; color: #374151;">Management-Rechte – Vollständige Kontrolle über Termine und erweiterte Funktionen.</span>
                                        </div>
                                    </div>
                                </div>
                            ')),
                    ])
                    ->collapsed(false),

                Forms\Components\Section::make('Persönliche Nachricht')
                    ->schema([
                        Forms\Components\Textarea::make('metadata.personal_message')
                            ->label('Persönliche Nachricht (optional)')
                            ->maxLength(500)
                            ->rows(4)
                            ->helperText('Optional: Fügen Sie eine persönliche Nachricht zur Einladungs-E-Mail hinzu')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('System-Informationen')
                    ->schema([
                        Forms\Components\TextInput::make('token')
                            ->label('Token')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Gültig bis')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        Forms\Components\DateTimePicker::make('accepted_at')
                            ->label('Akzeptiert am')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record?->accepted_at !== null),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record !== null)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'sent',
                        'success' => 'accepted',
                        'warning' => 'expired',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Ausstehend',
                        'sent' => 'Versendet',
                        'accepted' => 'Akzeptiert',
                        'expired' => 'Abgelaufen',
                        'failed' => 'Fehlgeschlagen',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('role.name')
                    ->label('Rolle')
                    ->sortable(),

                Tables\Columns\TextColumn::make('inviter.name')
                    ->label('Eingeladen von')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Gültig bis')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at->isPast() ? 'danger' : null),

                Tables\Columns\TextColumn::make('accepted_at')
                    ->label('Akzeptiert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'sent' => 'Versendet',
                        'accepted' => 'Akzeptiert',
                        'expired' => 'Abgelaufen',
                        'failed' => 'Fehlgeschlagen',
                    ]),

                Tables\Filters\Filter::make('expired')
                    ->label('Nur abgelaufene')
                    ->query(fn ($query) => $query->where('expires_at', '<', now())),

                Tables\Filters\Filter::make('pending_invitations')
                    ->label('Nur ausstehende')
                    ->query(fn ($query) => $query->whereNull('accepted_at')->where('expires_at', '>', now())),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('resend')
                    ->label('Erneut senden')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn ($record) => $record->status !== 'accepted' && !$record->trashed())
                    ->action(function ($record) {
                        $record->update(['status' => 'pending']);
                        \Illuminate\Support\Facades\Notification::route('mail', $record->email)
                            ->notify(new \App\Notifications\UserInvitationNotification($record));
                        \Filament\Notifications\Notification::make()
                            ->title('Einladung erneut versendet')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->label('Abbrechen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Ausgewählte abbrechen'),
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
            'index' => Pages\ListUserInvitations::route('/'),
            'create' => Pages\CreateUserInvitation::route('/create'),
            'view' => Pages\ViewUserInvitation::route('/{record}'),
            'edit' => Pages\EditUserInvitation::route('/{record}/edit'),
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
