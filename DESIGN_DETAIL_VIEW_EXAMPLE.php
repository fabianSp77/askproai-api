<?php

namespace App\Filament\Resources\CallResource\Pages;

use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class ViewCall extends ViewRecord
{
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // CANCELLATION BANNER (Conditional)
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\TextEntry::make('appointment.cancellation_summary')
                            ->label('')
                            ->formatStateUsing(function ($record) {
                                $appt = $record->appointment;
                                $cancel = $appt->cancellation;

                                return view('filament.infolists.cancellation-banner', [
                                    'appointment' => $appt,
                                    'cancellation' => $cancel,
                                    'originalCall' => $appt->originalCall, // Call that created appointment
                                ])->render();
                            })
                            ->html(),
                    ])
                    ->heading('')
                    ->visible(fn ($record) => $record->appointment?->status === 'cancelled')
                    ->columnSpanFull(),

                // MAIN CONTENT AREA
                Infolists\Components\Group::make()
                    ->schema([
                        // LEFT COLUMN - Call Details
                        Infolists\Components\Section::make('Call Details')
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('Call ID')
                                    ->prefix('#')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Call Time')
                                    ->dateTime('M j, Y H:i:s'),

                                Infolists\Components\TextEntry::make('duration')
                                    ->formatStateUsing(fn ($state) => gmdate('i:s', $state))
                                    ->suffix(' min'),

                                Infolists\Components\TextEntry::make('phone_number')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('call_type')
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'inbound' => 'info',
                                        'outbound' => 'success',
                                        default => 'gray',
                                    }),

                                // Transcript
                                Infolists\Components\Section::make('Transcript')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('transcript')
                                            ->prose()
                                            ->markdown()
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false),

                                // Function Calls
                                Infolists\Components\Section::make('Function Calls')
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('function_calls')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('name')
                                                    ->badge()
                                                    ->color('primary'),
                                                Infolists\Components\TextEntry::make('result')
                                                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                                                    ->fontFamily('mono')
                                                    ->size('xs'),
                                            ])
                                            ->columns(2),
                                    ])
                                    ->collapsible()
                                    ->collapsed(true),
                            ])
                            ->columnSpan(2),

                        // RIGHT COLUMN - Appointment Metadata Sidebar
                        Infolists\Components\Section::make('Appointment')
                            ->schema([
                                // Status Badge (Prominent)
                                Infolists\Components\TextEntry::make('appointment.status')
                                    ->badge()
                                    ->weight(FontWeight::Bold)
                                    ->size('lg')
                                    ->colors([
                                        'success' => 'scheduled',
                                        'info' => 'completed',
                                        'warning' => 'cancelled',
                                        'danger' => 'no_show',
                                    ])
                                    ->icon(fn ($state) => $state === 'cancelled' ? 'heroicon-o-exclamation-triangle' : null),

                                Infolists\Components\TextEntry::make('appointment.service.name')
                                    ->label('Service')
                                    ->icon('heroicon-o-scissors'),

                                Infolists\Components\TextEntry::make('appointment.customer.name')
                                    ->label('Customer')
                                    ->icon('heroicon-o-user')
                                    ->url(fn ($record) => route('filament.admin.resources.customers.view', $record->appointment->customer_id)),

                                Infolists\Components\TextEntry::make('appointment.scheduled_at')
                                    ->label('Original Time')
                                    ->dateTime('M j, Y H:i')
                                    ->icon('heroicon-o-calendar')
                                    // Strikethrough if cancelled
                                    ->extraAttributes(function ($record) {
                                        if ($record->appointment?->status === 'cancelled') {
                                            return ['style' => 'text-decoration: line-through; opacity: 0.6;'];
                                        }
                                        return [];
                                    }),

                                // CANCELLATION METADATA (Conditional)
                                Infolists\Components\Section::make('Cancellation Details')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('appointment.cancellation.cancelled_at')
                                            ->label('Cancelled At')
                                            ->dateTime('M j, Y H:i')
                                            ->icon('heroicon-o-clock'),

                                        Infolists\Components\TextEntry::make('appointment.cancellation.cancelled_by')
                                            ->label('Cancelled By')
                                            ->formatStateUsing(function ($record) {
                                                $cancel = $record->appointment->cancellation;
                                                $by = ucfirst($cancel->cancelled_by_type ?? 'Unknown');
                                                if ($cancel->cancelled_by_name) {
                                                    $by .= " ({$cancel->cancelled_by_name})";
                                                }
                                                return $by;
                                            })
                                            ->icon('heroicon-o-user-circle'),

                                        Infolists\Components\TextEntry::make('appointment.cancellation.cancellation_fee')
                                            ->label('Cancellation Fee')
                                            ->money('EUR')
                                            ->color('warning')
                                            ->icon('heroicon-o-currency-euro')
                                            ->visible(fn ($record) => ($record->appointment->cancellation->cancellation_fee ?? 0) > 0),

                                        Infolists\Components\TextEntry::make('appointment.cancellation.reason')
                                            ->label('Reason')
                                            ->columnSpanFull()
                                            ->prose(),

                                        Infolists\Components\TextEntry::make('appointment.cancellation.refund_status')
                                            ->label('Refund Status')
                                            ->badge()
                                            ->colors([
                                                'success' => 'refunded',
                                                'warning' => 'pending',
                                                'danger' => 'failed',
                                                'gray' => 'not_applicable',
                                            ])
                                            ->visible(fn ($record) => $record->appointment->cancellation->refund_status ?? false),
                                    ])
                                    ->visible(fn ($record) => $record->appointment?->status === 'cancelled')
                                    ->columnSpanFull(),

                                // RELATED CALLS (Always visible if exist)
                                Infolists\Components\Section::make('Related Calls')
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('related_calls')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('call_type_label')
                                                    ->label('Type')
                                                    ->badge()
                                                    ->color(fn ($state) => match($state) {
                                                        'Booking Call' => 'success',
                                                        'Cancellation Call' => 'warning',
                                                        'Reschedule Call' => 'info',
                                                        default => 'gray',
                                                    }),

                                                Infolists\Components\TextEntry::make('created_at')
                                                    ->dateTime('M j, H:i'),

                                                Infolists\Components\TextEntry::make('link')
                                                    ->label('')
                                                    ->formatStateUsing(fn ($record) => '→ View call')
                                                    ->url(fn ($record) => route('filament.admin.resources.calls.view', $record->id))
                                                    ->color('primary')
                                                    ->weight(FontWeight::SemiBold),
                                            ])
                                            ->columns(3)
                                            ->getStateUsing(function ($record) {
                                                return $record->getRelatedCallsWithContext();
                                            }),
                                    ])
                                    ->visible(fn ($record) => $record->relatedCalls()->exists())
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(3),
            ]);
    }
}
