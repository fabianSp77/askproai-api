<?php

namespace App\Filament\Admin\Resources\RetellAgentResource\Pages;

use App\Filament\Admin\Resources\RetellAgentResource;
use App\Models\RetellAgent;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Notifications\Notification;

class ViewRetellAgent extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = RetellAgentResource::class;
    
    protected static string $view = 'filament.admin.resources.retell-agent-resource.pages.view-retell-agent';
    
    public $agentId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'Agent nicht gefunden');
        }
        
        $this->agentId = $this->record->id;
        
        static::authorizeResourceAccess();

        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label('Synchronisieren')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    // Sync logic here
                    $this->record->update([
                        'last_synced_at' => now(),
                        'sync_status' => 'synced'
                    ]);
                    
                    Notification::make()
                        ->title('Agent synchronisiert')
                        ->body("Agent #{$this->record->id} wurde erfolgreich synchronisiert")
                        ->success()
                        ->send();
                }),
            
            Actions\Action::make('toggle_active')
                ->label(fn () => $this->record->is_active ? 'Deaktivieren' : 'Aktivieren')
                ->icon(fn () => $this->record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                ->color(fn () => $this->record->is_active ? 'warning' : 'success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'is_active' => !$this->record->is_active
                    ]);
                    
                    Notification::make()
                        ->title($this->record->is_active ? 'Agent aktiviert' : 'Agent deaktiviert')
                        ->success()
                        ->send();
                }),
            
            Actions\EditAction::make()
                ->url(fn () => route('filament.admin.resources.retell-agents.edit', $this->record)),
            
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'agentId' => $this->agentId,
        ];
    }
    
    public function getTitle(): string 
    {
        return 'Agent #' . $this->record->id . ' - ' . $this->record->name;
    }
    
    public function getBreadcrumb(): string
    {
        return 'Ansehen';
    }
}