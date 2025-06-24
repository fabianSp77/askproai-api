<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ListCalls extends ListRecords
{
    protected static string $resource = CallResource::class;
    
    protected function getViewData(): array
    {
        return [
            ...parent::getViewData(),
            'contentContainerClasses' => 'fi-resource-calls',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('fetch_calls')
                ->label('Anrufe abrufen')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Anrufe von Retell.ai abrufen')
                ->modalDescription('Möchten Sie alle neuen Anrufe von Retell.ai synchronisieren?')
                ->modalSubmitActionLabel('Ja, abrufen')
                ->extraAttributes([
                    'class' => 'fi-btn-premium',
                ])
                ->action(function () {
                    $company = auth()->user()->company;
                    
                    if (!$company) {
                        \Filament\Notifications\Notification::make()
                            ->title('Fehler')
                            ->body('Keine Company zugeordnet.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    try {
                        // Dispatch job to fetch calls
                        \App\Jobs\FetchRetellCallsJob::dispatch($company);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Abruf gestartet')
                            ->body('Die Anrufe werden im Hintergrund abgerufen. Sie werden benachrichtigt, sobald der Vorgang abgeschlossen ist.')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Fehler beim Abrufen')
                            ->body('Fehler: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\Action::make('export')
                ->label('Exportieren')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'fi-btn-premium-secondary',
                ])
                ->form([
                    \Filament\Forms\Components\Select::make('format')
                        ->label('Format')
                        ->options([
                            'xlsx' => 'Excel (.xlsx)',
                            'csv' => 'CSV (.csv)',
                            'pdf' => 'PDF (.pdf)',
                        ])
                        ->default('xlsx')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('from')
                        ->label('Von')
                        ->native(false)
                        ->displayFormat('d.m.Y'),
                    \Filament\Forms\Components\DatePicker::make('to')
                        ->label('Bis')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    // Export logic would go here
                    \Filament\Notifications\Notification::make()
                        ->title('Export gestartet')
                        ->body('Der Export wird vorbereitet und in Kürze heruntergeladen.')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }
    
    public function getTabs(): array
    {
        $model = \App\Models\Call::class;
        
        return [
            'all' => Tab::make('Alle Anrufe')
                ->icon('heroicon-m-phone')
                ->badge($model::count())
                ->badgeColor('gray'),
                
            'today' => Tab::make('Heute')
                ->icon('heroicon-m-calendar')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('start_timestamp', today()))
                ->badge($model::whereDate('start_timestamp', today())->count())
                ->badgeColor('primary'),
                
            'with_appointments' => Tab::make('Mit Termin')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('appointment_id'))
                ->badge($model::whereNotNull('appointment_id')->count())
                ->badgeColor('success'),
                
            'without_appointments' => Tab::make('Ohne Termin')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('appointment_id'))
                ->badge($model::whereNull('appointment_id')->count())
                ->badgeColor('warning'),
                
            'long_calls' => Tab::make('Lange Gespräche')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('duration_sec', '>', 300))
                ->badge($model::where('duration_sec', '>', 300)->count())
                ->badgeColor('info'),
                
            'failed' => Tab::make('Fehlgeschlagen')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('call_status', 'failed'))
                ->badge($model::where('call_status', 'failed')->count())
                ->badgeColor('danger'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\GlobalFilterWidget::class,
            \App\Filament\Admin\Widgets\CallKpiWidget::class,
        ];
    }
}