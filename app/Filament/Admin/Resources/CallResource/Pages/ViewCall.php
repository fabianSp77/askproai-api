<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use App\Models\Call;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Notifications\Notification;

class ViewCall extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = CallResource::class;
    
    protected static string $view = 'filament.admin.resources.call-resource.pages.view-call';
    
    public $callId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'Call nicht gefunden');
        }
        
        $this->callId = $this->record->id;
        
        static::authorizeResourceAccess();

        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_recording')
                ->label('Aufnahme herunterladen')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->visible(fn () => $this->record->recording_url)
                ->url(fn () => $this->record->recording_url),
            
            Actions\Action::make('view_transcript')
                ->label('Transkript anzeigen')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->modalContent(function () {
                    $transcript = $this->record->transcript;
                    if (is_string($transcript)) {
                        $transcript = json_decode($transcript, true);
                    }
                    return view('filament.modals.transcript', ['transcript' => $transcript]);
                })
                ->visible(fn () => $this->record->transcript),
            
            Actions\Action::make('refresh_analysis')
                ->label('Analyse aktualisieren')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    // Trigger re-analysis
                    $this->record->update([
                        'analyzed_at' => now()
                    ]);
                    
                    Notification::make()
                        ->title('Analyse aktualisiert')
                        ->body("Call #{$this->record->id} wurde zur erneuten Analyse markiert")
                        ->success()
                        ->send();
                }),
            
            Actions\EditAction::make()
                ->url(fn () => route('filament.admin.resources.calls.edit', $this->record)),
            
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'callId' => $this->callId,
        ];
    }
    
    public function getTitle(): string 
    {
        return 'Call #' . $this->record->id . ' - ' . $this->record->call_id;
    }
    
    public function getBreadcrumb(): string
    {
        return 'Ansehen';
    }
}