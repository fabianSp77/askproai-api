<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Quick Actions (Prominent)
        $actions[] = Actions\Action::make('call')
            ->label('Anrufen')
            ->icon('heroicon-o-phone')
            ->color('success')
            ->visible(fn () => !empty($this->record->phone))
            ->url(fn () => 'tel:' . $this->record->phone)
            ->openUrlInNewTab(false);

        $actions[] = Actions\Action::make('bookAppointment')
            ->label('Termin buchen')
            ->icon('heroicon-o-calendar-days')
            ->color('primary')
            ->url(fn () => route('filament.admin.resources.appointments.create', [
                'customer_id' => $this->record->id
            ]));

        $actions[] = Actions\Action::make('addEmail')
            ->label('E-Mail hinzufügen')
            ->icon('heroicon-o-envelope')
            ->color('info')
            ->visible(fn () => empty($this->record->email))
            ->form([
                \Filament\Forms\Components\TextInput::make('email')
                    ->label('E-Mail-Adresse')
                    ->email()
                    ->required(),
            ])
            ->action(function (array $data) {
                $this->record->update(['email' => $data['email']]);
                Notification::success()
                    ->title('E-Mail hinzugefügt')
                    ->body('E-Mail-Adresse wurde erfolgreich gespeichert.')
                    ->send();
            });

        $actions[] = Actions\Action::make('addNote')
            ->label('Notiz')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->form([
                \Filament\Forms\Components\TextInput::make('subject')
                    ->label('Betreff')
                    ->required(),
                \Filament\Forms\Components\Textarea::make('content')
                    ->label('Inhalt')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data) {
                $this->record->notes()->create([
                    'subject' => $data['subject'],
                    'content' => $data['content'],
                    'type' => 'general',
                    'created_by' => auth()->id(),
                ]);

                Notification::success()
                    ->title('Notiz hinzugefügt')
                    ->send();
            });

        // Standard Actions
        $actions[] = Actions\EditAction::make();

        // Duplicate Warning (if found)
        $duplicates = $this->findDuplicates();
        if ($duplicates->isNotEmpty()) {
            $actions[] = Actions\Action::make('viewDuplicates')
                ->label('Duplikate (' . $duplicates->count() . ')')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->modalHeading('Duplikate gefunden')
                ->modalDescription('Folgende Kunden teilen sich die gleiche Telefonnummer oder E-Mail:')
                ->modalContent(view('filament.pages.customer-duplicates', [
                    'current' => $this->record,
                    'duplicates' => $duplicates,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Schließen');

            // Add individual merge actions for each duplicate
            foreach ($duplicates->take(3) as $index => $duplicate) {
                $actions[] = Actions\Action::make('mergeDuplicate_' . $duplicate->id)
                    ->label('Duplikat #' . $duplicate->id . ' zusammenführen')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Kunden zusammenführen?')
                    ->modalDescription(function () use ($duplicate) {
                        $service = new \App\Services\Customer\CustomerMergeService();
                        $preview = $service->previewMerge($this->record, $duplicate);

                        return "Kunde #{$duplicate->id} ({$duplicate->name}) wird mit diesem Kunden zusammengeführt.\n\n" .
                               "Übertragen werden:\n" .
                               "• {$preview['duplicate']['calls']} Anruf(e)\n" .
                               "• {$preview['duplicate']['appointments']} Termin(e)\n" .
                               "• €" . number_format($preview['duplicate']['revenue'], 2) . " Umsatz\n\n" .
                               "Dieser Vorgang kann nicht rückgängig gemacht werden!";
                    })
                    ->modalSubmitActionLabel('Jetzt zusammenführen')
                    ->action(function () use ($duplicate) {
                        $service = new \App\Services\Customer\CustomerMergeService();
                        $stats = $service->merge($this->record, $duplicate);

                        Notification::success()
                            ->title('Kunden erfolgreich zusammengeführt')
                            ->body("Übertragen: {$stats['calls_transferred']} Anrufe, {$stats['appointments_transferred']} Termine")
                            ->send();

                        // Refresh the page
                        redirect()->to(route('filament.admin.resources.customers.view', ['record' => $this->record->id]));
                    });
            }
        }

        $actions[] = Actions\DeleteAction::make();

        return $actions;
    }

    // ✅ RESTORED (2025-10-21) - New individual customer widgets
    // Old widgets (CustomerOverview, CustomerRiskAlerts) were removed due to 500 errors
    // They were designed for LIST page (all customers), not VIEW page (one customer)
    // New widgets are designed specifically for individual customer view
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\CustomerResource\Widgets\CustomerCriticalAlerts::class, // URGENT actions first!
            \App\Filament\Resources\CustomerResource\Widgets\CustomerDetailStats::class,
            \App\Filament\Resources\CustomerResource\Widgets\CustomerIntelligencePanel::class,
        ];
    }

    /**
     * Performance optimization: Eager load relations to prevent N+1 queries
     */
    protected function configureTableQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->withCount(['calls', 'appointments']);
    }

    /**
     * Load customer with all needed relations for widgets
     */
    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        return static::getModel()::query()
            ->withCount([
                'calls',
                'appointments',
                'calls as failed_bookings_count' => function ($query) {
                    $query->where('appointment_made', 1)->whereNull('converted_appointment_id');
                },
            ])
            ->with([
                'calls' => function ($query) {
                    $query->latest('created_at')->limit(1);
                },
                'appointments' => function ($query) {
                    $query->latest('created_at')->limit(1);
                },
            ])
            ->findOrFail($key);
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Resources\CustomerResource\Widgets\CustomerJourneyTimeline::class,
            \App\Filament\Resources\CustomerResource\Widgets\CustomerActivityTimeline::class,
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name ?? 'Kunde anzeigen';
    }

    /**
     * Find duplicate customers by phone or email
     */
    protected function findDuplicates()
    {
        $duplicates = collect();

        // Find by phone
        if ($this->record->phone) {
            $phoneDuplicates = Customer::where('phone', $this->record->phone)
                ->where('id', '!=', $this->record->id)
                ->get();
            $duplicates = $duplicates->merge($phoneDuplicates);
        }

        // Find by email
        if ($this->record->email) {
            $emailDuplicates = Customer::where('email', $this->record->email)
                ->where('id', '!=', $this->record->id)
                ->get();
            $duplicates = $duplicates->merge($emailDuplicates);
        }

        return $duplicates->unique('id');
    }
}