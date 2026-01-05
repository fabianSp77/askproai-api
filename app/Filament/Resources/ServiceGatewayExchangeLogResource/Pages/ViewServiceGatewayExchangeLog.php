<?php

namespace App\Filament\Resources\ServiceGatewayExchangeLogResource\Pages;

use App\Filament\Resources\ServiceGatewayExchangeLogResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Response;

class ViewServiceGatewayExchangeLog extends ViewRecord
{
    protected static string $resource = ServiceGatewayExchangeLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportActions')
                ->label('Export')
                ->icon('heroicon-o-share')
                ->color('gray')
                ->modalHeading('Export für Partner')
                ->modalDescription('Wählen Sie ein Export-Format für die Partner-Kommunikation.')
                ->modalContent(fn (): View => view('filament.components.exchange-log-export-buttons', [
                    'log' => $this->record,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Schließen'),

            Action::make('downloadJson')
                ->label('Download JSON')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(fn () => $this->downloadAsJson()),
        ];
    }

    /**
     * Download the exchange log as a JSON file.
     */
    public function downloadAsJson()
    {
        $data = $this->record->toExportJson();
        $filename = "webhook-{$this->record->short_event_id}-" . now()->format('Y-m-d-His') . '.json';

        return Response::streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function getTitle(): string
    {
        return 'Exchange Log: ' . $this->record->short_event_id . '...';
    }

    public function getSubheading(): ?string
    {
        $direction = $this->record->direction === 'outbound' ? 'Ausgehend' : 'Eingehend';
        $status = $this->record->isSuccessful() ? 'Erfolgreich' : 'Fehlgeschlagen';

        return "{$direction} | {$this->record->http_method} | {$status}";
    }
}
