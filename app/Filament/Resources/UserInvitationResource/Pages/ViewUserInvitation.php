<?php

namespace App\Filament\Resources\UserInvitationResource\Pages;

use App\Filament\Resources\UserInvitationResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewUserInvitation extends ViewRecord
{
    protected static string $resource = UserInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resend')
                ->label('Erneut senden')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $this->record->status !== 'accepted' && !$this->record->trashed())
                ->action(function () {
                    $this->record->update(['status' => 'pending']);
                    \Illuminate\Support\Facades\Notification::route('mail', $this->record->email)
                        ->notify(new \App\Notifications\UserInvitationNotification($this->record));
                    \Filament\Notifications\Notification::make()
                        ->title('Einladung erneut versendet')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),

            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->label('Abbrechen'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Einladungsinformationen')
                    ->schema([
                        Infolists\Components\TextEntry::make('email')
                            ->label('E-Mail-Adresse')
                            ->copyable()
                            ->icon('heroicon-o-envelope'),

                        Infolists\Components\TextEntry::make('status')
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
                            }),

                        Infolists\Components\TextEntry::make('role.name')
                            ->label('Rolle'),

                        Infolists\Components\TextEntry::make('branch.name')
                            ->label('Filiale')
                            ->placeholder('Alle Filialen'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Personen')
                    ->schema([
                        Infolists\Components\TextEntry::make('inviter.name')
                            ->label('Eingeladen von'),

                        Infolists\Components\TextEntry::make('inviter.email')
                            ->label('E-Mail des Einladenden'),

                        Infolists\Components\TextEntry::make('customer.name')
                            ->label('Registrierter Kunde')
                            ->placeholder('Noch nicht registriert')
                            ->visible(fn () => $this->record->customer_id !== null),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Zeitstempel')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Erstellt am')
                            ->dateTime('d.m.Y H:i'),

                        Infolists\Components\TextEntry::make('expires_at')
                            ->label('Gültig bis')
                            ->dateTime('d.m.Y H:i')
                            ->color(fn () => $this->record->expires_at->isPast() ? 'danger' : 'success'),

                        Infolists\Components\TextEntry::make('accepted_at')
                            ->label('Akzeptiert am')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Noch nicht akzeptiert'),

                        Infolists\Components\TextEntry::make('last_sent_at')
                            ->label('Zuletzt versendet')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Noch nicht versendet'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Zusätzliche Informationen')
                    ->schema([
                        Infolists\Components\TextEntry::make('token')
                            ->label('Einladungs-Token')
                            ->copyable()
                            ->fontFamily('mono'),

                        Infolists\Components\TextEntry::make('metadata.personal_message')
                            ->label('Persönliche Nachricht')
                            ->placeholder('Keine persönliche Nachricht')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('url')
                            ->label('Einladungslink')
                            ->state(fn () => url('/kundenportal/einladung/' . $this->record->token))
                            ->copyable()
                            ->url(fn () => url('/kundenportal/einladung/' . $this->record->token))
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }
}
