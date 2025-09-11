<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
    
    protected static bool $canCreateAnother = false;
    
    public function mount(): void
    {
        // Get the record ID from the URL
        $recordId = request()->segment(3); // Gets the ID from /admin/transactions/{id}
        
        if ($recordId && is_numeric($recordId)) {
            // Load the existing record
            $this->record = static::getResource()::getModel()::find($recordId);
            
            if ($this->record) {
                // Fill the form with existing data
                $this->form->fill($this->record->toArray());
            }
        }
        
        parent::mount();
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // No notification for view-only
    }
    
    public function create(bool $another = false): void
    {
        // Prevent creation
        return;
    }
}