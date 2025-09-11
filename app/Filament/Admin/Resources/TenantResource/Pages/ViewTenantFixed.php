<?php

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use App\Filament\Admin\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewTenantFixed extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = TenantResource::class;
    
    protected static string $view = 'filament.admin.resources.tenant-resource.pages.view-tenant';
    
    public $tenantId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'Tenant not found');
        }
        
        $this->tenantId = $this->record->id;
        
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
            'tenantId' => $this->tenantId,
        ];
    }
    
    public function getTitle(): string 
    {
        return $this->record->name ?? 'Tenant #' . $this->record->id;
    }
}
