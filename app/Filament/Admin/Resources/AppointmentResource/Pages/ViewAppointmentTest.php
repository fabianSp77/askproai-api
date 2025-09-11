<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Pages;

use App\Filament\Admin\Resources\AppointmentResource;
use App\Helpers\GermanFormatter;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Concerns\InteractsWithInfolist;

class ViewAppointmentTest extends ViewRecord
{
    use InteractsWithInfolist;

    protected static string $resource = AppointmentResource::class;

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

    public function getTitle(): string
    {
        if (!isset($this->record) || !$this->record) {
            return 'Termin Details';
        }
        
        $formatter = new GermanFormatter();
        $date = $this->record->starts_at ? 
            $formatter->formatDate($this->record->starts_at) : 
            'Termin';
        
        return 'Termin am ' . $date;
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Ensure record is loaded with all relationships
        if ($this->record) {
            $this->record->load([
                'customer',
                'service',
                'staff',
                'branch',
                'call',
                'calcomEventType'
            ]);
        }
    }
    
    // Not defining infolist() to use the Resource's default
}