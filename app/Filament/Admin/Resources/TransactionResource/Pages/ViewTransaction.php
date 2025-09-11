<?php

namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewTransaction extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = TransactionResource::class;
    
    protected static string $view = 'filament.admin.resources.transaction-resource.pages.view-transaction-wrapper';
    
    public $transactionId;
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        if (!$this->record) {
            abort(404, 'Transaktion nicht gefunden');
        }
        
        $this->transactionId = $this->record->id;
        
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
                    $exporter = new \App\Services\TransactionPdfExporter();
                    return $exporter->exportTransaction($this->record);
                }),
            Actions\Action::make('export_invoice')
                ->label('Als Rechnung')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->visible(fn () => $this->record->type === 'topup')
                ->action(function () {
                    $exporter = new \App\Services\TransactionPdfExporter();
                    return $exporter->exportInvoice($this->record);
                }),
            Actions\EditAction::make()
                ->url(fn () => route('filament.admin.resources.transactions.edit', $this->record)),
        ];
    }
    
    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'transactionId' => $this->transactionId,
        ];
    }
    
    public function getTitle(): string 
    {
        return $this->record->description . ' ansehen';
    }
    
    public function getBreadcrumb(): string
    {
        return 'Ansehen';
    }
}