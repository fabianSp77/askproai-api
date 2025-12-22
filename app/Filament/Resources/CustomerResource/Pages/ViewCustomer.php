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

        // PRIMARY ACTIONS GROUP (Always visible)
        $actions[] = Actions\ActionGroup::make([
            Actions\Action::make('call')
                ->label('Anrufen')
                ->icon('heroicon-o-phone')
                ->color('success')
                ->visible(fn () => !empty($this->record->phone))
                ->url(fn () => 'tel:' . $this->record->phone)
                ->openUrlInNewTab(false),

            Actions\Action::make('bookAppointment')
                ->label('Termin buchen')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->url(fn () => route('filament.admin.resources.appointments.create', [
                    'customer_id' => $this->record->id
                ])),
        ])
            ->label('Schnellaktionen')
            ->icon('heroicon-o-bolt')
            ->color('primary')
            ->button();

        // CUSTOMER MANAGEMENT GROUP
        $customerActions = [];

        if (empty($this->record->email)) {
            $customerActions[] = Actions\Action::make('addEmail')
                ->label('E-Mail hinzufügen')
                ->icon('heroicon-o-envelope')
                ->form([
                    \Filament\Forms\Components\TextInput::make('email')
                        ->label('E-Mail-Adresse')
                        ->email()
                        ->required(),
                ])
                ->action(fn (array $data) => $this->addEmailAddress($data));
        }

        $customerActions[] = Actions\Action::make('addNote')
            ->label('Notiz hinzufügen')
            ->icon('heroicon-o-pencil-square')
            ->form([
                \Filament\Forms\Components\TextInput::make('subject')
                    ->label('Betreff')
                    ->required(),
                \Filament\Forms\Components\Textarea::make('content')
                    ->label('Inhalt')
                    ->required()
                    ->rows(3),
            ])
            ->action(fn (array $data) => $this->addCustomerNote($data));

        $actions[] = Actions\ActionGroup::make($customerActions)
            ->label('Kunde')
            ->icon('heroicon-o-user')
            ->button();

        // DUPLICATE MANAGEMENT GROUP (Single grouped action)
        $duplicates = $this->findDuplicates();
        if ($duplicates->isNotEmpty()) {
            $duplicateActions = [];

            $duplicateActions[] = Actions\Action::make('viewAllDuplicates')
                ->label('Alle anzeigen (' . $duplicates->count() . ')')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading('Duplikate gefunden')
                ->modalDescription('Folgende Kunden teilen sich die gleiche Telefonnummer oder E-Mail:')
                ->modalContent(view('filament.pages.customer-duplicates', [
                    'current' => $this->record,
                    'duplicates' => $duplicates,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Schließen');

            // Add merge actions for each duplicate
            foreach ($duplicates->take(3) as $duplicate) {
                $duplicateId = $duplicate->id; // Capture only ID, not entire model
                $duplicateName = $duplicate->name; // Capture only name

                $duplicateActions[] = Actions\Action::make('merge_' . $duplicateId)
                    ->label('Mit #' . $duplicateId . ' zusammenführen')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('Kunden zusammenführen?')
                    ->modalDescription(fn () => $this->getMergeDescription($duplicateId, $duplicateName))
                    ->modalSubmitActionLabel('Jetzt zusammenführen')
                    ->action(fn () => $this->mergeWithDuplicate($duplicateId));
            }

            $actions[] = Actions\ActionGroup::make($duplicateActions)
                ->label('Duplikate (' . $duplicates->count() . ')')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->button();
        }

        // STANDARD ACTIONS
        $actions[] = Actions\EditAction::make();
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
        $name = $this->record->name ?? 'Kunde anzeigen';

        // Truncate long names to prevent line breaks in breadcrumb/header
        // Max 40 chars for comfortable reading, add ellipsis if truncated
        if (mb_strlen($name) > 40) {
            return mb_substr($name, 0, 37) . '...';
        }

        return $name;
    }

    /**
     * Find duplicate customers by phone or email
     * Multi-Tenancy Fix: Only show duplicates within the same company
     */
    protected function findDuplicates()
    {
        $duplicates = collect();

        // Find by phone - Multi-Tenancy Fix: Filter by company_id
        if ($this->record->phone) {
            $phoneDuplicates = Customer::where('phone', $this->record->phone)
                ->where('id', '!=', $this->record->id)
                ->where('company_id', $this->record->company_id)
                ->get();
            $duplicates = $duplicates->merge($phoneDuplicates);
        }

        // Find by email - Multi-Tenancy Fix: Filter by company_id
        if ($this->record->email) {
            $emailDuplicates = Customer::where('email', $this->record->email)
                ->where('id', '!=', $this->record->id)
                ->where('company_id', $this->record->company_id)
                ->get();
            $duplicates = $duplicates->merge($emailDuplicates);
        }

        return $duplicates->unique('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Livewire Action Methods (2025-10-22)
    |--------------------------------------------------------------------------
    | These methods replace closures in getHeaderActions() to fix Livewire
    | serialization issues. Closures cannot be serialized, especially those
    | with use() clauses that capture model instances.
    */

    /**
     * Add email address to customer
     * Extracted from closure for Livewire serialization
     */
    public function addEmailAddress(array $data): void
    {
        $this->record->update(['email' => $data['email']]);
        Notification::success()
            ->title('E-Mail hinzugefügt')
            ->body('E-Mail-Adresse wurde erfolgreich gespeichert.')
            ->send();
    }

    /**
     * Add customer note
     * Extracted from closure for Livewire serialization
     */
    public function addCustomerNote(array $data): void
    {
        $this->record->notes()->create([
            'subject' => $data['subject'],
            'content' => $data['content'],
            'type' => 'general',
            'created_by' => auth()->id(),
        ]);

        Notification::success()
            ->title('Notiz hinzugefügt')
            ->send();
    }

    /**
     * Get merge description for duplicate customer
     * Extracted from closure for Livewire serialization
     */
    public function getMergeDescription(int $duplicateId, string $duplicateName): string
    {
        $duplicate = Customer::findOrFail($duplicateId);
        $service = new \App\Services\Customer\CustomerMergeService();
        $preview = $service->previewMerge($this->record, $duplicate);

        return "Kunde #{$duplicateId} ({$duplicateName}) wird mit diesem Kunden zusammengeführt.\n\n" .
               "Übertragen werden:\n" .
               "• {$preview['duplicate']['calls']} Anruf(e)\n" .
               "• {$preview['duplicate']['appointments']} Termin(e)\n" .
               "• €" . number_format($preview['duplicate']['revenue'], 2) . " Umsatz\n\n" .
               "Dieser Vorgang kann nicht rückgängig gemacht werden!";
    }

    /**
     * Merge customer with duplicate
     * Extracted from closure for Livewire serialization
     */
    public function mergeWithDuplicate(int $duplicateId): void
    {
        $duplicate = Customer::findOrFail($duplicateId);
        $service = new \App\Services\Customer\CustomerMergeService();
        $stats = $service->merge($this->record, $duplicate);

        Notification::success()
            ->title('Kunden erfolgreich zusammengeführt')
            ->body("Übertragen: {$stats['calls_transferred']} Anrufe, {$stats['appointments_transferred']} Termine")
            ->send();

        redirect()->to(route('filament.admin.resources.customers.view', ['record' => $this->record->id]));
    }
}