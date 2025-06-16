<?php

namespace App\Filament\Admin\Resources\CustomerResource\Pages;

use App\Filament\Admin\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Admin\Widgets\CustomerInsightsWidget;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('duplicates')
                ->label('Duplikate finden')
                ->icon('heroicon-o-magnifying-glass-circle')
                ->color('warning')
                ->url(fn () => CustomerResource::getUrl('duplicates')),
            Actions\Action::make('export')
                ->label('Exportieren')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    // Export logic would go here
                    $this->notify('success', 'Export wird vorbereitet...');
                }),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            CustomerInsightsWidget::class,
        ];
    }
    
    public function getTitle(): string
    {
        return 'Kunden-Intelligence';
    }
    
    public function getSubheading(): ?string
    {
        return 'Verwalten Sie Ihre Endkunden mit intelligenten Insights und Analysen';
    }
}
