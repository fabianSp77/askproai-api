<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        // Cache tab counts for 60 seconds to avoid multiple queries
        $counts = Cache::remember('call_tab_counts_' . auth()->user()->company_id, 60, function() {
            $companyId = auth()->user()->company_id;
            
            return DB::table('calls')
                ->where('company_id', $companyId)
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(CASE WHEN DATE(start_timestamp) = CURDATE() THEN 1 END) as today,
                    COUNT(CASE WHEN appointment_id IS NOT NULL THEN 1 END) as with_appointments,
                    COUNT(CASE WHEN appointment_id IS NULL THEN 1 END) as without_appointments,
                    COUNT(CASE WHEN duration_sec > 300 THEN 1 END) as long_calls,
                    COUNT(CASE WHEN call_status = 'failed' THEN 1 END) as failed
                ")
                ->first();
        });
        
        return [
            'all' => Tab::make('Alle Anrufe')
                ->icon('heroicon-m-phone')
                ->badge($counts->total)
                ->badgeColor('gray'),
                
            'today' => Tab::make('Heute')
                ->icon('heroicon-m-calendar')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('start_timestamp', today()))
                ->badge($counts->today)
                ->badgeColor('primary'),
                
            'with_appointments' => Tab::make('Mit Termin')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('appointment_id'))
                ->badge($counts->with_appointments)
                ->badgeColor('success'),
                
            'without_appointments' => Tab::make('Ohne Termin')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('appointment_id'))
                ->badge($counts->without_appointments)
                ->badgeColor('warning'),
                
            'long_calls' => Tab::make('Lange Gespräche')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('duration_sec', '>', 300))
                ->badge($counts->long_calls)
                ->badgeColor('info'),
                
            'failed' => Tab::make('Fehlgeschlagen')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('call_status', 'failed'))
                ->badge($counts->failed)
                ->badgeColor('danger'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\CallLiveStatusWidget::class,
            \App\Filament\Admin\Widgets\GlobalFilterWidget::class,
            \App\Filament\Admin\Widgets\CallKpiWidget::class,
        ];
    }
}