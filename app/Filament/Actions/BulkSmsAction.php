<?php

namespace App\Filament\Actions;

use App\Services\Notifications\NotificationManager;
use Filament\Forms;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class BulkSmsAction extends BulkAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('bulk_sms');
        $this->label('SMS versenden');
        $this->icon('heroicon-o-device-phone-mobile');
        $this->color('info');
        $this->modalHeading('Massen-SMS versenden');
        $this->modalDescription('Versenden Sie eine SMS an alle ausgewählten Kunden');
        $this->modalWidth('lg');

        $this->form($this->getFormSchema());

        $this->action(function (Collection $records, array $data) {
            $this->sendBulkSms($records, $data);
        });

        $this->requiresConfirmation();
        $this->modalButton('SMS versenden');

        $this->deselectRecordsAfterCompletion();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('template')
                ->label('Vorlage')
                ->options($this->getSmsTemplates())
                ->reactive()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if ($state) {
                        $template = $this->getTemplateContent($state);
                        $set('message', $template);
                    }
                }),

            Forms\Components\Textarea::make('message')
                ->label('Nachricht')
                ->required()
                ->maxLength(160)
                ->helperText(fn ($state) => 'Zeichen: ' . strlen($state ?? '') . '/160')
                ->placeholder('Geben Sie Ihre Nachricht ein...')
                ->rows(4),

            Forms\Components\TagsInput::make('variables')
                ->label('Verfügbare Variablen')
                ->default(['{{name}}', '{{appointment_date}}', '{{service}}', '{{staff}}'])
                ->disabled()
                ->helperText('Diese Variablen können in der Nachricht verwendet werden'),

            Forms\Components\Select::make('sender_id')
                ->label('Absender')
                ->options($this->getAvailableSenders())
                ->default('default')
                ->required(),

            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label('Zeitplanung (optional)')
                ->native(false)
                ->minDate(now())
                ->helperText('Lassen Sie leer für sofortigen Versand'),

            Forms\Components\Toggle::make('test_mode')
                ->label('Testmodus')
                ->default(false)
                ->helperText('Im Testmodus werden keine echten SMS versendet'),

            Forms\Components\Section::make('Erweiterte Optionen')
                ->schema([
                    Forms\Components\Toggle::make('skip_opted_out')
                        ->label('Abgemeldete überspringen')
                        ->default(true)
                        ->helperText('Kunden, die sich abgemeldet haben, werden übersprungen'),

                    Forms\Components\Toggle::make('skip_invalid_numbers')
                        ->label('Ungültige Nummern überspringen')
                        ->default(true)
                        ->helperText('Ungültige Telefonnummern werden übersprungen'),

                    Forms\Components\Select::make('priority')
                        ->label('Priorität')
                        ->options([
                            'low' => 'Niedrig',
                            'normal' => 'Normal',
                            'high' => 'Hoch',
                            'urgent' => 'Dringend',
                        ])
                        ->default('normal'),
                ])
                ->collapsible()
                ->collapsed(),
        ];
    }

    protected function sendBulkSms(Collection $records, array $data): void
    {
        $successCount = 0;
        $failureCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($records as $record) {
            try {
                // Skip if customer has no phone number
                if (empty($record->phone) && empty($record->mobile)) {
                    $skippedCount++;
                    continue;
                }

                // Skip opted out customers if enabled
                if ($data['skip_opted_out'] ?? true) {
                    if ($record->sms_opt_out ?? false) {
                        $skippedCount++;
                        continue;
                    }
                }

                $phone = $record->mobile ?? $record->phone;

                // Validate phone number if enabled
                if ($data['skip_invalid_numbers'] ?? true) {
                    if (!$this->isValidPhoneNumber($phone)) {
                        $skippedCount++;
                        continue;
                    }
                }

                // Replace variables in message
                $message = $this->replaceVariables($data['message'], $record);

                // Send SMS (or queue if scheduled)
                if ($data['scheduled_at'] ?? false) {
                    $this->queueSms($record, $message, $data);
                } else {
                    $this->sendSms($record, $message, $data);
                }

                $successCount++;

                // Log the SMS sent
                $this->logSmsSent($record, $message, $data);

            } catch (\Exception $e) {
                $failureCount++;
                $errors[] = "Fehler bei {$record->name}: " . $e->getMessage();
                Log::error('Bulk SMS failed for customer', [
                    'customer_id' => $record->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Send notification about results
        $this->sendResultNotification($successCount, $failureCount, $skippedCount, $errors);
    }

    protected function sendSms($record, string $message, array $data): void
    {
        if ($data['test_mode'] ?? false) {
            Log::info('Test SMS would be sent', [
                'customer_id' => $record->id,
                'phone' => $record->mobile ?? $record->phone,
                'message' => $message,
            ]);
            return;
        }

        // Here you would integrate with your SMS service
        // For example, using NotificationManager
        app(NotificationManager::class)->send(
            $record,
            'sms',
            ['message' => $message],
            ['sms'],
            ['priority' => $data['priority'] ?? 'normal']
        );
    }

    protected function queueSms($record, string $message, array $data): void
    {
        // Queue the SMS for later sending
        \App\Models\NotificationQueue::create([
            'customer_id' => $record->id,
            'type' => 'sms',
            'channel' => 'sms',
            'data' => json_encode(['message' => $message]),
            'scheduled_at' => $data['scheduled_at'],
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'pending',
        ]);
    }

    protected function replaceVariables(string $message, $record): string
    {
        $replacements = [
            '{{name}}' => $record->name,
            '{{first_name}}' => explode(' ', $record->name)[0] ?? $record->name,
            '{{email}}' => $record->email,
            '{{phone}}' => $record->phone,
        ];

        // Add appointment-related variables if customer has upcoming appointment
        if ($record->appointments) {
            $nextAppointment = $record->appointments()
                ->where('starts_at', '>', now())
                ->orderBy('starts_at')
                ->first();

            if ($nextAppointment) {
                $replacements['{{appointment_date}}'] = $nextAppointment->starts_at->format('d.m.Y');
                $replacements['{{appointment_time}}'] = $nextAppointment->starts_at->format('H:i');
                $replacements['{{service}}'] = $nextAppointment->service?->name ?? '';
                $replacements['{{staff}}'] = $nextAppointment->staff?->full_name ?? '';
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    protected function isValidPhoneNumber(string $phone): bool
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Check if it's a valid German mobile number
        // German mobile numbers start with 015, 016, 017, or 01511-01529
        if (preg_match('/^(49|0)1[567][0-9]{7,}$/', $phone)) {
            return true;
        }

        // Check for international format
        if (preg_match('/^\+?[1-9][0-9]{7,14}$/', $phone)) {
            return true;
        }

        return false;
    }

    protected function getSmsTemplates(): array
    {
        return [
            'appointment_reminder' => 'Terminerinnerung',
            'appointment_confirmation' => 'Terminbestätigung',
            'marketing' => 'Marketing',
            'birthday' => 'Geburtstag',
            'feedback' => 'Feedback-Anfrage',
            'custom' => 'Benutzerdefiniert',
        ];
    }

    protected function getTemplateContent(string $template): string
    {
        $templates = [
            'appointment_reminder' => 'Hallo {{name}}, dies ist eine Erinnerung an Ihren Termin am {{appointment_date}} um {{appointment_time}}. Bei Fragen rufen Sie uns gerne an.',
            'appointment_confirmation' => 'Hallo {{name}}, Ihr Termin am {{appointment_date}} um {{appointment_time}} wurde bestätigt. Wir freuen uns auf Sie!',
            'marketing' => 'Hallo {{name}}, entdecken Sie unsere neuen Angebote! Besuchen Sie uns noch heute.',
            'birthday' => 'Alles Gute zum Geburtstag, {{name}}! Als Geschenk erhalten Sie 20% Rabatt auf Ihren nächsten Besuch.',
            'feedback' => 'Hallo {{name}}, wie war Ihr letzter Besuch bei uns? Wir würden uns über Ihr Feedback freuen.',
        ];

        return $templates[$template] ?? '';
    }

    protected function getAvailableSenders(): array
    {
        return [
            'default' => 'Standard (Unternehmen)',
            'marketing' => 'Marketing',
            'service' => 'Kundenservice',
            'support' => 'Support',
        ];
    }

    protected function logSmsSent($record, string $message, array $data): void
    {
        // Log activity
        activity()
            ->performedOn($record)
            ->causedBy(auth()->user())
            ->withProperties([
                'message' => $message,
                'sender_id' => $data['sender_id'] ?? 'default',
                'test_mode' => $data['test_mode'] ?? false,
            ])
            ->log('SMS sent');
    }

    protected function sendResultNotification(int $success, int $failure, int $skipped, array $errors): void
    {
        $body = "Erfolgreich: {$success}\n";
        $body .= "Fehlgeschlagen: {$failure}\n";
        $body .= "Übersprungen: {$skipped}";

        if (!empty($errors)) {
            $body .= "\n\nFehler:\n" . implode("\n", array_slice($errors, 0, 5));
        }

        $notification = Notification::make()
            ->title('Massen-SMS Versand abgeschlossen')
            ->body($body);

        if ($failure > 0) {
            $notification->warning();
        } else {
            $notification->success();
        }

        $notification->send();
    }
}