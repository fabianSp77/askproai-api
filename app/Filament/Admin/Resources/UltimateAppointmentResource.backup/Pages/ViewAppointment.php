<?php

namespace App\Filament\Admin\Resources\UltimateAppointmentResource\Pages;

use App\Filament\Admin\Resources\UltimateAppointmentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = UltimateAppointmentResource::class;

    protected static string $view = 'filament.admin.pages.ultra-appointment-view';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('check_in')
                ->label('Check In')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->size('lg')
                ->visible(fn () => $this->record->status === 'confirmed' && $this->record->starts_at->isToday())
                ->action(function () {
                    $this->record->update([
                        'status' => 'checked_in',
                        'checked_in_at' => now(),
                    ]);
                    $this->refreshFormData(['record']);
                }),

            Actions\Action::make('complete')
                ->label('Mark Complete')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->size('lg')
                ->visible(fn () => in_array($this->record->status, ['checked_in', 'in_progress']))
                ->action(function () {
                    $this->record->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                    $this->refreshFormData(['record']);
                }),

            Actions\Action::make('no_show')
                ->label('No Show')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'confirmed' && $this->record->starts_at->isPast())
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'no_show']);
                    
                    // Update customer no-show count
                    $this->record->customer->increment('no_show_count');
                    
                    $this->refreshFormData(['record']);
                }),

            Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => $this->js('window.print()')),

            Actions\EditAction::make()
                ->size('lg'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Appointment Overview')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Customer')
                                    ->icon('heroicon-o-user')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg')
                                    ->url(fn () => route('filament.admin.resources.ultimate-customers.view', $this->record->customer)),
                                Infolists\Components\TextEntry::make('customer.phone')
                                    ->label('Phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('customer.email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->default('No email'),
                            ]),

                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('service.name')
                                    ->label('Service')
                                    ->icon('heroicon-o-sparkles')
                                    ->weight(FontWeight::Bold),
                                Infolists\Components\TextEntry::make('staff.name')
                                    ->label('Staff Member')
                                    ->icon('heroicon-o-user-circle')
                                    ->default('Unassigned'),
                                Infolists\Components\TextEntry::make('branch.name')
                                    ->label('Branch')
                                    ->icon('heroicon-o-building-office'),
                            ]),

                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn (string $state): string => match ($state) {
                                        'scheduled' => 'info',
                                        'confirmed' => 'primary',
                                        'checked_in' => 'warning',
                                        'in_progress' => 'warning',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        'no_show' => 'gray',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('price')
                                    ->label('Price')
                                    ->money('EUR')
                                    ->size('lg')
                                    ->weight(FontWeight::Bold),
                            ]),
                        ]),
                    ]),

                Infolists\Components\Section::make('Schedule')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('starts_at')
                                ->label('Date')
                                ->date()
                                ->icon('heroicon-o-calendar'),
                            Infolists\Components\TextEntry::make('starts_at')
                                ->label('Start Time')
                                ->time()
                                ->icon('heroicon-o-play'),
                            Infolists\Components\TextEntry::make('ends_at')
                                ->label('End Time')
                                ->time()
                                ->icon('heroicon-o-stop'),
                            Infolists\Components\TextEntry::make('duration')
                                ->label('Duration')
                                ->getStateUsing(fn () => 
                                    $this->record->starts_at->diffInMinutes($this->record->ends_at) . ' minutes'
                                )
                                ->icon('heroicon-o-clock'),
                        ]),
                    ]),

                Infolists\Components\Section::make('Status Timeline')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Infolists\Components\ViewEntry::make('status_timeline')
                            ->label(false)
                            ->view('filament.admin.components.appointment-timeline'),
                    ]),

                Infolists\Components\Section::make('Notes & Details')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('notes')
                                ->label('Internal Notes')
                                ->prose()
                                ->columnSpan(1)
                                ->default('No internal notes'),
                            Infolists\Components\TextEntry::make('customer_notes')
                                ->label('Customer Notes')
                                ->prose()
                                ->columnSpan(1)
                                ->default('No customer notes'),
                        ]),
                    ]),

                Infolists\Components\Section::make('History & Metadata')
                    ->icon('heroicon-o-clock')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('source')
                                ->label('Booking Source')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'phone' => 'success',
                                    'online' => 'primary',
                                    'walk-in' => 'warning',
                                    'admin' => 'gray',
                                    default => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Created')
                                ->dateTime(),
                            Infolists\Components\TextEntry::make('confirmed_at')
                                ->label('Confirmed')
                                ->dateTime()
                                ->default('Not confirmed'),
                            Infolists\Components\TextEntry::make('checked_in_at')
                                ->label('Checked In')
                                ->dateTime()
                                ->default('Not checked in'),
                            Infolists\Components\TextEntry::make('completed_at')
                                ->label('Completed')
                                ->dateTime()
                                ->default('Not completed'),
                            Infolists\Components\TextEntry::make('rescheduled_count')
                                ->label('Times Rescheduled')
                                ->badge()
                                ->color(fn (int $state): string => 
                                    $state === 0 ? 'success' : ($state > 2 ? 'danger' : 'warning')
                                ),
                        ]),

                        Infolists\Components\KeyValueEntry::make('metadata')
                            ->label('Custom Fields')
                            ->columnSpanFull()
                            ->default([]),
                    ]),

                Infolists\Components\Section::make('Related Information')
                    ->icon('heroicon-o-link')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\ViewEntry::make('customer_appointments')
                                ->label('Customer Appointment History')
                                ->view('filament.admin.components.customer-appointment-history'),

                            Infolists\Components\ViewEntry::make('staff_schedule')
                                ->label('Staff Schedule Today')
                                ->view('filament.admin.components.staff-day-schedule'),
                        ]),
                    ]),
            ]);
    }

    public function refreshFormData(array $attributes): void
    {
        $this->refreshFormDataCore($attributes);
        $this->dispatch('refresh');
    }

    protected function refreshFormDataCore(array $attributes): void
    {
        if (! count($attributes)) {
            return;
        }

        if (in_array('record', $attributes)) {
            $this->record = $this->resolveRecord($this->record->getKey());
        }
    }
}