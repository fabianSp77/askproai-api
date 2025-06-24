<?php

namespace App\Filament\Admin\Resources\UltimateAppointmentResource\Pages;

use App\Filament\Admin\Resources\UltimateAppointmentResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Actions;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class EditAppointment extends EditRecord
{
    protected static string $resource = UltimateAppointmentResource::class;

    protected static string $view = 'filament.admin.pages.ultra-appointment-edit';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('check_in')
                ->label('Check In')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->status === 'confirmed' && $this->record->starts_at->isToday())
                ->requiresConfirmation()
                ->modalHeading('Check In Customer')
                ->modalDescription('Mark this customer as checked in for their appointment?')
                ->action(function () {
                    $this->record->update([
                        'status' => 'checked_in',
                        'checked_in_at' => now(),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Customer checked in')
                        ->send();
                }),

            Actions\Action::make('reschedule')
                ->label('Reschedule')
                ->icon('heroicon-o-calendar')
                ->color('warning')
                ->visible(fn () => in_array($this->record->status, ['scheduled', 'confirmed']))
                ->form([
                    Forms\Components\DateTimePicker::make('new_starts_at')
                        ->label('New Date & Time')
                        ->required()
                        ->native(false)
                        ->seconds(false)
                        ->minutesStep(15)
                        ->minDate(now())
                        ->default($this->record->starts_at),
                ])
                ->action(function (array $data) {
                    $duration = $this->record->starts_at->diffInMinutes($this->record->ends_at);
                    $newStartsAt = Carbon::parse($data['new_starts_at']);
                    
                    $this->record->update([
                        'starts_at' => $newStartsAt,
                        'ends_at' => $newStartsAt->copy()->addMinutes($duration),
                        'rescheduled_at' => now(),
                        'rescheduled_count' => $this->record->rescheduled_count + 1,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Appointment rescheduled')
                        ->body('Customer will be notified of the new time.')
                        ->send();
                }),

            Actions\Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => !in_array($this->record->status, ['cancelled', 'completed', 'no_show']))
                ->requiresConfirmation()
                ->modalHeading('Cancel Appointment')
                ->modalDescription('Are you sure you want to cancel this appointment?')
                ->form([
                    Forms\Components\Textarea::make('cancellation_reason')
                        ->label('Cancellation Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancellation_reason' => $data['cancellation_reason'],
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Appointment cancelled')
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->hasRole('admin')),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Appointment Details')
                    ->description('Core appointment information')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Select::make('customer_id')
                                ->label('Customer')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled(fn () => in_array($this->record->status, ['completed', 'cancelled'])),

                            Forms\Components\Select::make('service_id')
                                ->label('Service')
                                ->relationship('service', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->disabled(fn () => in_array($this->record->status, ['completed', 'cancelled']))
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $service = \App\Models\Service::find($state);
                                        if ($service) {
                                            $set('price', $service->price);
                                        }
                                    }
                                }),

                            Forms\Components\Select::make('branch_id')
                                ->label('Branch')
                                ->relationship('branch', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->disabled(fn () => in_array($this->record->status, ['completed', 'cancelled']))
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('staff_id', null)),

                            Forms\Components\Select::make('staff_id')
                                ->label('Staff Member')
                                ->options(function (Forms\Get $get) {
                                    $branchId = $get('branch_id');
                                    $serviceId = $get('service_id');
                                    
                                    if (!$branchId || !$serviceId) {
                                        return [];
                                    }

                                    return \App\Models\Staff::query()
                                        ->where('company_id', session('company_id'))
                                        ->where('branch_id', $branchId)
                                        ->whereHas('services', function ($query) use ($serviceId) {
                                            $query->where('services.id', $serviceId);
                                        })
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->disabled(fn () => in_array($this->record->status, ['completed', 'cancelled'])),
                        ]),
                    ]),

                Section::make('Schedule')
                    ->description('Appointment timing and status')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\DateTimePicker::make('starts_at')
                                ->label('Start Date & Time')
                                ->required()
                                ->native(false)
                                ->seconds(false)
                                ->minutesStep(15)
                                ->reactive()
                                ->disabled(fn () => in_array($this->record->status, ['completed', 'cancelled']))
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if ($state && $get('ends_at')) {
                                        $duration = Carbon::parse($get('starts_at'))->diffInMinutes(Carbon::parse($get('ends_at')));
                                        $set('ends_at', Carbon::parse($state)->addMinutes($duration));
                                    }
                                }),

                            Forms\Components\DateTimePicker::make('ends_at')
                                ->label('End Date & Time')
                                ->required()
                                ->native(false)
                                ->seconds(false)
                                ->minutesStep(15)
                                ->minDate(fn (Forms\Get $get) => $get('starts_at'))
                                ->disabled(fn () => in_array($this->record->status, ['completed', 'cancelled'])),

                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'scheduled' => 'Scheduled',
                                    'confirmed' => 'Confirmed',
                                    'checked_in' => 'Checked In',
                                    'in_progress' => 'In Progress',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                    'no_show' => 'No Show',
                                ])
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, $old) {
                                    if ($state === 'completed' && $old !== 'completed') {
                                        $this->record->update(['completed_at' => now()]);
                                    }
                                }),

                            Forms\Components\TextInput::make('price')
                                ->label('Price')
                                ->numeric()
                                ->prefix('€')
                                ->disabled(fn () => $this->record->status === 'completed'),
                        ]),
                    ]),

                Section::make('Additional Information')
                    ->description('Notes and metadata')
                    ->icon('heroicon-o-document-text')
                    ->collapsed()
                    ->schema([
                        Grid::make(1)->schema([
                            Forms\Components\Textarea::make('notes')
                                ->label('Internal Notes')
                                ->rows(3)
                                ->helperText('These notes are only visible to staff'),

                            Forms\Components\Textarea::make('customer_notes')
                                ->label('Customer Notes')
                                ->rows(3)
                                ->helperText('These notes are visible to the customer'),

                            Forms\Components\KeyValue::make('metadata')
                                ->label('Custom Fields')
                                ->keyLabel('Field')
                                ->valueLabel('Value')
                                ->addButtonLabel('Add Field'),
                        ]),
                    ]),

                Section::make('History')
                    ->description('Appointment history and changes')
                    ->icon('heroicon-o-clock')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\Placeholder::make('created_at')
                                ->label('Created')
                                ->content(fn () => $this->record->created_at->format('M j, Y - g:i A')),

                            Forms\Components\Placeholder::make('updated_at')
                                ->label('Last Updated')
                                ->content(fn () => $this->record->updated_at->diffForHumans()),

                            Forms\Components\Placeholder::make('confirmed_at')
                                ->label('Confirmed At')
                                ->content(fn () => $this->record->confirmed_at?->format('M j, Y - g:i A') ?? 'Not confirmed'),

                            Forms\Components\Placeholder::make('rescheduled_count')
                                ->label('Times Rescheduled')
                                ->content(fn () => $this->record->rescheduled_count ?? 0),

                            Forms\Components\Placeholder::make('source')
                                ->label('Booking Source')
                                ->content(fn () => ucfirst($this->record->source ?? 'Unknown')),

                            Forms\Components\Placeholder::make('call_id')
                                ->label('Related Call')
                                ->content(fn () => $this->record->call_id ? "Call #{$this->record->call_id}" : 'No related call'),
                        ]),
                    ]),
            ]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Appointment updated')
            ->body('The appointment has been successfully updated.')
            ->send();
    }

    protected function afterSave(): void
    {
        // Send notifications if needed
        if ($this->data['send_update_notification'] ?? false) {
            // Trigger notification job
            // dispatch(new SendAppointmentUpdateNotification($this->record));
        }
    }
}