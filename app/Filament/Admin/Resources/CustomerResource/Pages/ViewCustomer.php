<?php

namespace App\Filament\Admin\Resources\CustomerResource\Pages;

use App\Filament\Admin\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Concerns\InteractsWithInfolist;

class ViewCustomer extends ViewRecord
{
    use InteractsWithInfolist;

    protected static string $resource = CustomerResource::class;
    
    // protected static string $view = 'filament.admin.resources.customer-resource.view';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->color('primary'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->color('danger'),
        ];
    }
    
    public function getTitle(): string
    {
        return $this->record->name;
    }
}