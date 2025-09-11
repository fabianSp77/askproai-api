<?php

namespace App\Filament\Admin\Resources\BalanceTopupResource\Pages;

use App\Filament\Admin\Resources\BalanceTopupResource;
use App\Models\BalanceTopup;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Notifications\Notification;

class ViewBalanceTopup extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = BalanceTopupResource::class;
    
    protected static string $view = 'filament.admin.resources.balance-topup-resource.pages.view-balance-topup';
    
    public $topupId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'Aufladung nicht gefunden');
        }
        
        $this->topupId = $this->record->id;
        
        static::authorizeResourceAccess();

        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_pdf')
                ->label('PDF Export')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $exporter = new \App\Services\BalanceTopupPdfExporter();
                    return $exporter->exportTopup($this->record);
                }),
            
            Actions\Action::make('export_receipt')
                ->label('Als Beleg')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->visible(fn () => $this->record->status === 'succeeded')
                ->action(function () {
                    $exporter = new \App\Services\BalanceTopupPdfExporter();
                    return $exporter->exportReceipt($this->record);
                }),
            
            Actions\EditAction::make()
                ->visible(fn () => !in_array($this->record->status, ['succeeded', 'failed']))
                ->url(fn () => route('filament.admin.resources.balance-topups.edit', $this->record)),
            
            Actions\Action::make('approve')
                ->label('Genehmigen')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aufladung genehmigen')
                ->modalDescription('Sind Sie sicher, dass Sie diese Aufladung genehmigen möchten?')
                ->action(function () {
                    $this->record->markAsSucceeded();
                    
                    Notification::make()
                        ->title('Aufladung genehmigt')
                        ->body("Aufladung #{$this->record->id} wurde erfolgreich genehmigt")
                        ->success()
                        ->send();
                    
                    $this->refreshFormData(['status']);
                })
                ->visible(fn () => in_array($this->record->status, ['pending', 'processing'])),
            
            Actions\Action::make('reject')
                ->label('Ablehnen')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\TextInput::make('reason')
                        ->label('Grund für Ablehnung')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->markAsFailed($data['reason']);
                    
                    Notification::make()
                        ->title('Aufladung abgelehnt')
                        ->body("Aufladung #{$this->record->id} wurde abgelehnt")
                        ->warning()
                        ->send();
                    
                    $this->refreshFormData(['status']);
                })
                ->visible(fn () => in_array($this->record->status, ['pending', 'processing'])),
        ];
    }
    
    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'topupId' => $this->topupId,
        ];
    }
    
    public function getTitle(): string 
    {
        return 'Aufladung #' . $this->record->id . ' ansehen';
    }
    
    public function getBreadcrumb(): string
    {
        return 'Ansehen';
    }
}