<?php

namespace App\Filament\Admin\Resources\CustomerResource\Pages;

use App\Filament\Admin\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('book')
                ->label('Termin buchen')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->url(fn ($record) => "/admin/appointments/create?customer_id={$record->id}"),
            Actions\Action::make('timeline')
                ->label('Timeline anzeigen')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->modalHeading(fn ($record) => 'Timeline: ' . $record->name)
                ->modalContent(fn ($record) => view('filament.customer.timeline', ['customer' => $record]))
                ->modalWidth('7xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Schließen'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            CustomerResource\Widgets\CustomerStatsWidget::class,
        ];
    }
}