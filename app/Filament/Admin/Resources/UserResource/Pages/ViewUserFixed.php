<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewUserFixed extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = UserResource::class;
    
    protected static string $view = 'filament.admin.resources.user-resource.pages.view-user';
    
    public $userId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'User not found');
        }
        
        $this->userId = $this->record->id;
        
        static::authorizeResourceAccess();
        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->color('primary'),
        ];
    }
    
    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'userId' => $this->userId,
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