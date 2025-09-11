<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Pages;

use App\Filament\Admin\Resources\AppointmentResource;
use App\Helpers\GermanFormatter;
use App\Models\Appointment;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewAppointment extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = AppointmentResource::class;
    
    protected static string $view = 'filament.admin.resources.appointment-resource.pages.view-appointment';
    
    public $appointmentId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'Termin nicht gefunden');
        }
        
        $this->appointmentId = $this->record->id;
        
        // Eager load relationships
        $this->record->load([
            'customer',
            'service',
            'staff',
            'branch',
            'call',
            'calcomEventType'
        ]);
        
        static::authorizeResourceAccess();
        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_meeting')
                ->label('Meeting öffnen')
                ->icon('heroicon-o-video-camera')
                ->color('success')
                ->url(fn () => $this->record->meeting_url)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->meeting_url),
            
            Actions\Action::make('export_details')
                ->label('Details exportieren')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    // Export logic here if needed
                }),
            
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->color('primary'),
            
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->color('danger'),
        ];
    }
    
    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'appointmentId' => $this->appointmentId,
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
    
    public function getBreadcrumb(): string
    {
        return 'Ansehen';
    }
}