<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Pages;

use App\Filament\Admin\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Concerns\InteractsWithInfolist;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;

class ViewAppointmentSimple extends ViewRecord
{
    use InteractsWithInfolist;

    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Basic Info')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Database ID'),
                        TextEntry::make('calcom_v2_booking_id')
                            ->label('Cal.com Booking ID'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('customer.name')
                            ->label('Customer Name'),
                        TextEntry::make('customer.email')
                            ->label('Customer Email'),
                        TextEntry::make('starts_at')
                            ->label('Start Time')
                            ->dateTime('d.m.Y H:i'),
                        TextEntry::make('ends_at')
                            ->label('End Time')
                            ->dateTime('d.m.Y H:i'),
                        TextEntry::make('meeting_url')
                            ->label('Meeting URL')
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab(),
                    ])
                    ->columns(2),
            ]);
    }
}