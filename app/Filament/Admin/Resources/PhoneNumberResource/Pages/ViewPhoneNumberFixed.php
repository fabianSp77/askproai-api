<?php

namespace App\Filament\Admin\Resources\PhoneNumberResource\Pages;

use App\Filament\Admin\Resources\PhoneNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewPhoneNumberFixed extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = PhoneNumberResource::class;
    
    protected static string $view = 'filament.admin.resources.phonenumber-resource.pages.view-phonenumber';
    
    public $phonenumberId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'PhoneNumber not found');
        }
        
        $this->phonenumberId = $this->record->id;
        
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
            'phonenumberId' => $this->phonenumberId,
        ];
    }
    
    public function getTitle(): string 
    {
        return $this->record->name ?? 'PhoneNumber #' . $this->record->id;
    }
}
