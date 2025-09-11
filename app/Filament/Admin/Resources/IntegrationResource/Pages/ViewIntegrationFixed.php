<?php

namespace App\Filament\Admin\Resources\IntegrationResource\Pages;

use App\Filament\Admin\Resources\IntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewIntegrationFixed extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = IntegrationResource::class;
    
    protected static string $view = 'filament.admin.resources.integration-resource.pages.view-integration';
    
    public $integrationId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'Integration not found');
        }
        
        $this->integrationId = $this->record->id;
        
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
            'integrationId' => $this->integrationId,
        ];
    }
    
    public function getTitle(): string 
    {
        return $this->record->name ?? 'Integration #' . $this->record->id;
    }
}