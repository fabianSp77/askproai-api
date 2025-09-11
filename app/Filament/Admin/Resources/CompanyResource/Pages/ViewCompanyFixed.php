<?php

namespace App\Filament\Admin\Resources\CompanyResource\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewCompanyFixed extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = CompanyResource::class;
    
    protected static string $view = 'filament.admin.resources.company-resource.pages.view-company';
    
    public $companyId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'Company not found');
        }
        
        $this->companyId = $this->record->id;
        
        static::authorizeResourceAccess();
        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    
    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'companyId' => $this->companyId,
        ];
    }
    
    public function getTitle(): string 
    {
        return $this->record->name ?? 'Company #' . $this->record->id;
    }
}
