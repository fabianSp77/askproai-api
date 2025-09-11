<?php

namespace App\Filament\Admin\Resources\BranchResource\Pages;

use App\Filament\Admin\Resources\BranchResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewBranchFixed extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = BranchResource::class;
    
    protected static string $view = 'filament.admin.resources.branch-resource.pages.view-branch';
    
    public $branchId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'Branch not found');
        }
        
        $this->branchId = $this->record->id;
        
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
            'branchId' => $this->branchId,
        ];
    }
    
    public function getTitle(): string 
    {
        return $this->record->name ?? 'Branch #' . $this->record->id;
    }
}
