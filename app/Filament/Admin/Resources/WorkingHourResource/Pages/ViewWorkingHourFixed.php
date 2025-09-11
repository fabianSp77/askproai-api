<?php

namespace App\Filament\Admin\Resources\WorkingHourResource\Pages;

use App\Filament\Admin\Resources\WorkingHourResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewWorkingHourFixed extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = WorkingHourResource::class;
    
    protected static string $view = 'filament.admin.resources.workinghour-resource.pages.view-workinghour';
    
    public $workinghourId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'WorkingHour not found');
        }
        
        $this->workinghourId = $this->record->id;
        
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
            'workinghourId' => $this->workinghourId,
        ];
    }
    
    public function getTitle(): string 
    {
        return $this->record->name ?? 'WorkingHour #' . $this->record->id;
    }
}
