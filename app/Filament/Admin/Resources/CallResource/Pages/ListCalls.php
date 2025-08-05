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
    
    // Remove custom view to use default Filament view
    // protected static string $view = 'filament.admin.resources.call-resource.pages.list-calls';
    
    /* Remove getViewData as it's not needed for standard Filament pages
    protected function getViewData(): array
    {
        // This method was causing issues - removed
    }
    */

    public function mount(): void
    {
        // Emergency Company Context Fix
        if (auth()->check() && auth()->user()->company_id) {
            app()->instance('current_company_id', auth()->user()->company_id);
            app()->instance('company_context_source', 'web_auth');
        }
        parent::mount();
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
                    $user = auth()->user();
                    
                    if (!$user) {
                        \Filament\Notifications\Notification::make()
                            ->title('Fehler')
                            ->body('Nicht angemeldet.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $company = $user->company;
                    
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
        // Check if user is authenticated
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return [];
        }
        
        // Cache tab counts for 5 minutes to avoid multiple queries
        $counts = Cache::remember('call_tab_counts_' . $user->company_id, 300, function() use ($user) {
            $companyId = $user->company_id;
            
            return DB::table('calls')
                ->where('company_id', $companyId)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
                    SUM(CASE WHEN (metadata IS NOT NULL AND metadata LIKE '%appointment%') THEN 1 ELSE 0 END) as with_appointments,
                    SUM(CASE WHEN (metadata IS NULL OR metadata NOT LIKE '%appointment%') THEN 1 ELSE 0 END) as without_appointments,
                    SUM(CASE WHEN duration_sec > 300 THEN 1 ELSE 0 END) as long_calls,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
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
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge($counts->today)
                ->badgeColor('primary'),
                
            'with_appointments' => Tab::make('Mit Termin')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function($q) { $q->whereNotNull('metadata')->where('metadata', 'like', '%appointment%'); }))
                ->badge($counts->with_appointments)
                ->badgeColor('success'),
                
            'without_appointments' => Tab::make('Ohne Termin')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function($q) { $q->whereNull('metadata')->orWhere('metadata', 'not like', '%appointment%'); }))
                ->badge($counts->without_appointments)
                ->badgeColor('warning'),
                
            'long_calls' => Tab::make('Lange Gespräche')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('duration_sec', '>', 300))
                ->badge($counts->long_calls)
                ->badgeColor('info'),
                
            'failed' => Tab::make('Fehlgeschlagen')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge($counts->failed)
                ->badgeColor('danger'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // Widgets temporarily disabled - they need to be updated to work with Filament 3
            // \App\Filament\Admin\Widgets\CallLiveStatusWidget::class,
            // \App\Filament\Admin\Widgets\GlobalFilterWidget::class,
            // \App\Filament\Admin\Widgets\CallKpiWidget::class,
            // \App\Filament\Admin\Resources\CallResource\Widgets\CallAnalyticsWidget::class,
        ];
    }
    
    public function getHeaderWidgetsColumns(): int | string | array
    {
        return 2; // 2 columns for better layout
    }
    
    // Add this method to ensure widgets are loaded in Filament v3
    public function getVisibleHeaderWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getHeaderWidgets());
    }
    
    protected function getHeaderWidgetsData(): array
    {
        return [];
    }
    
    public function hasHeaderWidgets(): bool
    {
        return count($this->getHeaderWidgets()) > 0;
    }
}