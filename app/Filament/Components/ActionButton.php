<?php

namespace App\Filament\Components;

use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Support\Enums\MaxWidth;
use Filament\Notifications\Notification;

class ActionButton
{
    /**
     * Create a quick action button with loading state
     */
    public static function make(string $name): Action
    {
        return Action::make($name)
            ->button()
            ->size('sm')
            ->modalWidth(MaxWidth::Medium)
            ->modalSubmitActionLabel('Ausführen')
            ->modalCancelActionLabel('Abbrechen');
    }

    /**
     * Quick appointment booking action
     */
    public static function quickBooking(string $customerRelation = 'customer'): Action
    {
        return static::make('quickBooking')
            ->label('Termin buchen')
            ->icon('heroicon-o-calendar-days')
            ->color('success')
            ->modalHeading('Schnellbuchung')
            ->modalDescription('Buchen Sie schnell einen neuen Termin für diesen Kunden')
            ->form([
                \Filament\Forms\Components\Select::make('service_id')
                    ->label('Service')
                    ->relationship('services', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\Select::make('staff_id')
                    ->label('Mitarbeiter')
                    ->relationship('staff', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\DateTimePicker::make('starts_at')
                    ->label('Datum & Zeit')
                    ->required()
                    ->native(false)
                    ->minutesStep(15)
                    ->minDate(now())
                    ->seconds(false),
                \Filament\Forms\Components\Textarea::make('notes')
                    ->label('Notizen')
                    ->rows(2),
            ])
            ->action(function ($record, array $data) {
                // Create appointment logic here
                Notification::make()
                    ->title('Termin gebucht')
                    ->body('Der Termin wurde erfolgreich gebucht.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Send SMS action
     */
    public static function sendSms(): Action
    {
        return static::make('sendSms')
            ->label('SMS senden')
            ->icon('heroicon-o-device-phone-mobile')
            ->color('info')
            ->modalHeading('SMS senden')
            ->requiresConfirmation()
            ->form([
                \Filament\Forms\Components\Select::make('template')
                    ->label('Vorlage')
                    ->options([
                        'reminder' => 'Terminerinnerung',
                        'confirmation' => 'Terminbestätigung',
                        'custom' => 'Benutzerdefiniert',
                    ])
                    ->reactive()
                    ->default('custom'),
                \Filament\Forms\Components\Textarea::make('message')
                    ->label('Nachricht')
                    ->required()
                    ->rows(3)
                    ->maxLength(160)
                    ->helperText(fn ($state) => (160 - strlen($state ?? '')) . ' Zeichen übrig')
                    ->reactive(),
            ])
            ->action(function ($record, array $data) {
                // SMS sending logic here
                Notification::make()
                    ->title('SMS gesendet')
                    ->body('Die SMS wurde erfolgreich versendet.')
                    ->success()
                    ->send();
            })
            ->visible(fn ($record) => !empty($record->phone));
    }

    /**
     * Send Email action
     */
    public static function sendEmail(): Action
    {
        return static::make('sendEmail')
            ->label('E-Mail senden')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->modalHeading('E-Mail senden')
            ->modalWidth(MaxWidth::Large)
            ->form([
                \Filament\Forms\Components\Select::make('template')
                    ->label('Vorlage')
                    ->options([
                        'appointment_reminder' => 'Terminerinnerung',
                        'appointment_confirmation' => 'Terminbestätigung',
                        'welcome' => 'Willkommen',
                        'custom' => 'Benutzerdefiniert',
                    ])
                    ->reactive()
                    ->default('custom'),
                \Filament\Forms\Components\TextInput::make('subject')
                    ->label('Betreff')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\RichEditor::make('body')
                    ->label('Nachricht')
                    ->required()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'link',
                        'bulletList',
                        'orderedList',
                    ]),
            ])
            ->action(function ($record, array $data) {
                // Email sending logic here
                Notification::make()
                    ->title('E-Mail gesendet')
                    ->body('Die E-Mail wurde erfolgreich versendet.')
                    ->success()
                    ->send();
            })
            ->visible(fn ($record) => !empty($record->email));
    }

    /**
     * Call action
     */
    public static function call(): Action
    {
        return static::make('call')
            ->label('Anrufen')
            ->icon('heroicon-o-phone')
            ->color('success')
            ->url(fn ($record) => "tel:{$record->phone}")
            ->openUrlInNewTab()
            ->visible(fn ($record) => !empty($record->phone));
    }

    /**
     * Check-in action for appointments
     */
    public static function checkIn(): Action
    {
        return static::make('checkIn')
            ->label('Check-in')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Möchten Sie den Kunden wirklich einchecken?')
            ->action(function ($record) {
                $record->update([
                    'checked_in_at' => now(),
                    'status' => 'confirmed',
                ]);
                
                Notification::make()
                    ->title('Check-in erfolgreich')
                    ->body('Der Kunde wurde erfolgreich eingecheckt.')
                    ->success()
                    ->send();
            })
            ->visible(fn ($record) => empty($record->checked_in_at) && in_array($record->status, ['scheduled', 'pending']));
    }

    /**
     * Cancel action
     */
    public static function cancel(): Action
    {
        return static::make('cancel')
            ->label('Absagen')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Termin absagen')
            ->modalDescription('Sind Sie sicher, dass Sie diesen Termin absagen möchten?')
            ->form([
                \Filament\Forms\Components\Textarea::make('cancellation_reason')
                    ->label('Grund der Absage')
                    ->required()
                    ->rows(2),
                \Filament\Forms\Components\Toggle::make('notify_customer')
                    ->label('Kunde benachrichtigen')
                    ->default(true),
            ])
            ->action(function ($record, array $data) {
                $record->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $data['cancellation_reason'],
                ]);
                
                if ($data['notify_customer']) {
                    // Send notification logic
                }
                
                Notification::make()
                    ->title('Termin abgesagt')
                    ->body('Der Termin wurde erfolgreich abgesagt.')
                    ->warning()
                    ->send();
            })
            ->visible(fn ($record) => !in_array($record->status, ['cancelled', 'completed']));
    }

    /**
     * Reschedule action
     */
    public static function reschedule(): Action
    {
        return static::make('reschedule')
            ->label('Verschieben')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->modalHeading('Termin verschieben')
            ->form([
                \Filament\Forms\Components\DateTimePicker::make('new_starts_at')
                    ->label('Neues Datum & Zeit')
                    ->required()
                    ->native(false)
                    ->minutesStep(15)
                    ->minDate(now())
                    ->seconds(false),
                \Filament\Forms\Components\Textarea::make('reschedule_reason')
                    ->label('Grund')
                    ->rows(2),
                \Filament\Forms\Components\Toggle::make('notify_customer')
                    ->label('Kunde benachrichtigen')
                    ->default(true),
            ])
            ->action(function ($record, array $data) {
                // Reschedule logic
                Notification::make()
                    ->title('Termin verschoben')
                    ->body('Der Termin wurde erfolgreich verschoben.')
                    ->success()
                    ->send();
            });
    }

    /**
     * View details action
     */
    public static function viewDetails(): Action
    {
        return static::make('viewDetails')
            ->label('Details')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->modalHeading('Details anzeigen')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Schließen');
    }

    /**
     * Duplicate action
     */
    public static function duplicate(): Action
    {
        return static::make('duplicate')
            ->label('Duplizieren')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Möchten Sie diesen Eintrag wirklich duplizieren?')
            ->action(function ($record) {
                $duplicate = $record->replicate();
                $duplicate->save();
                
                Notification::make()
                    ->title('Erfolgreich dupliziert')
                    ->body('Der Eintrag wurde erfolgreich dupliziert.')
                    ->success()
                    ->send();
                
                return redirect()->to(static::getUrl('edit', ['record' => $duplicate]));
            });
    }
}