<?php

namespace App\Filament\Admin\Resources\StaffResource\Pages;

use App\Filament\Admin\Resources\StaffResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewStaffFixed extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = StaffResource::class;
    
    protected static string $view = 'filament.admin.resources.staff-resource.pages.view-staff';
    
    public $staffId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'Staff not found');
        }
        
        $this->staffId = $this->record->id;
        
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
            'staffId' => $this->staffId,
        ];
    }
    
    public function getTitle(): string 
    {
        return $this->record->name ?? 'Staff #' . $this->record->id;
    }
}
