<?php

namespace App\Filament\Admin\Resources\ResellerResource\Pages;

use App\Filament\Admin\Resources\ResellerResource;
use App\Filament\Admin\Resources\ResellerResource\Widgets;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReseller extends ViewRecord
{
    protected static string $resource = ResellerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dashboard')
                ->label('View Dashboard')
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->url(fn () => ResellerResource::getUrl('dashboard', ['record' => $this->getRecord()])),

            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),
                
            Actions\Action::make('add_client')
                ->label('Add Client')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(fn () => route('filament.admin.resources.companies.create', [
                    'parent_company_id' => $this->getRecord()->id
                ])),

            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    $record = $this->getRecord();
                    if ($record->childCompanies()->count() > 0) {
                        $action->cancel();
                        \Filament\Notifications\Notification::make()
                            ->title('Cannot delete reseller')
                            ->body('This reseller has active clients. Please remove or transfer clients first.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\ResellerPerformanceWidget::class,
        ];
    }

    public function getTitle(): string 
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string
    {
        $clientCount = $this->getRecord()->childCompanies()->count();
        return $clientCount . ' ' . str('client')->plural($clientCount);
    }
}