<?php

namespace App\Filament\Admin\Resources\CustomerResource\Pages;

use App\Filament\Admin\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewCustomerFixed extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = CustomerResource::class;
    
    protected static string $view = 'filament.admin.resources.customer-resource.pages.view-customer';
    
    public $customerId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'Customer not found');
        }
        
        $this->customerId = $this->record->id;
        
        static::authorizeResourceAccess();
        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }
    
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
    
    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'customerId' => $this->customerId,
        ];
    }
    
    public function getTitle(): string 
    {
        return $this->record->name;
    }
    
    public function getBreadcrumb(): string
    {
        return 'View';
    }
}