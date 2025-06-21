<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use App\Filament\Admin\Resources\CallResource\Widgets;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Call;
use Carbon\Carbon;

class ListCalls extends ListRecords
{
    protected static string $resource = CallResource::class;
    
    // public ?string $activeTab = 'today';
    
    public function getTitle(): string
    {
        return 'Anrufliste';
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
                ->modalDescription('Möchten Sie alle Anrufe von Retell.ai abrufen? Dies kann einen Moment dauern.')
                ->modalSubmitActionLabel('Ja, abrufen')
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
                        // Verwende Company-spezifische API Key
                        $apiKey = $company->retell_api_key;
                        
                        // Fallback auf globale Config
                        if (empty($apiKey)) {
                            $apiKey = config('services.retell.api_key');
                        }
                        
                        if (empty($apiKey)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Konfigurationsfehler')
                                ->body('Retell API Key ist weder für Ihre Firma noch global konfiguriert.')
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }
                        
                        // Direkte API-Anfrage an Retell
                        // Versuche verschiedene Ansätze
                        // WICHTIG: Die korrekte URL ist retellai.com, nicht retell.ai!
                        $baseUrl = config('services.retell.base_url', 'https://api.retellai.com');
                        $baseUrl = rtrim($baseUrl, '/');
                        
                        // Ersetze falsche URLs
                        $baseUrl = str_replace('retell.ai', 'retellai.com', $baseUrl);
                        
                        // Erstelle Request-Parameter
                        $params = [
                            'limit' => 100,
                            'sort_order' => 'descending'
                        ];
                        
                        // Füge Agent ID hinzu, falls vorhanden
                        if ($company->retell_agent_id) {
                            $params['agent_id'] = $company->retell_agent_id;
                        }
                        
                        // Debug-Logging vor dem API-Call
                        \Illuminate\Support\Facades\Log::info('Attempting Retell API call', [
                            'baseUrl' => $baseUrl,
                            'params' => $params,
                            'api_key_present' => !empty($apiKey),
                            'api_key_start' => substr($apiKey, 0, 10) . '...'
                        ]);
                        
                        // Methode 1: Mit withToken (wie in CallDataRefresher)
                        $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                            ->timeout(30)
                            ->post($baseUrl . '/v2/list-calls', $params);
                        
                        if (!$response->successful()) {
                            throw new \Exception('API Error: ' . $response->body());
                        }
                        
                        $calls = $response->json();
                        
                        // Debug-Logging
                        \Illuminate\Support\Facades\Log::info('Retell API Response:', [
                            'status' => $response->status(),
                            'body' => $calls,
                            'api_key_used' => substr($apiKey, 0, 10) . '...'
                        ]);
                        
                        // Prüfe verschiedene Response-Formate
                        $results = null;
                        if (isset($calls['results'])) {
                            $results = $calls['results'];
                        } elseif (isset($calls['calls'])) {
                            $results = $calls['calls'];
                        } elseif (is_array($calls) && !empty($calls) && !isset($calls['error'])) {
                            // Falls die Response direkt ein Array ist
                            $results = $calls;
                        }
                        
                        if ($results && is_array($results) && count($results) > 0) {
                            $count = count($results);
                            
                            foreach ($results as $callData) {
                                \App\Jobs\ProcessRetellCallEndedJob::dispatch([
                                    'event' => 'call_ended',
                                    'call' => $callData
                                ], $company);
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Anrufe werden importiert')
                                ->body("$count Anrufe wurden zur Verarbeitung eingereicht.")
                                ->success()
                                ->send();
                        } else {
                            // Falls keine Daten gefunden, versuche GET Request
                            $getResponse = \Illuminate\Support\Facades\Http::withToken($apiKey)
                                ->get($baseUrl . '/v2/list-calls', [
                                    'limit' => 100,
                                    'sort_order' => 'descending'
                                ]);
                                
                            if ($getResponse->successful()) {
                                $getCalls = $getResponse->json();
                                \Illuminate\Support\Facades\Log::info('Retell GET Response:', ['body' => $getCalls]);
                                
                                // Prüfe GET Response
                                if (isset($getCalls['results']) || isset($getCalls['calls']) || (is_array($getCalls) && !empty($getCalls))) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Hinweis')
                                        ->body('GET Request funktioniert. Bitte informieren Sie den Entwickler.')
                                        ->warning()
                                        ->send();
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Keine Anrufe gefunden')
                                ->body('API Response: ' . substr(json_encode($calls), 0, 200) . '...')
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Fehler beim Abrufen')
                            ->body('Fehler: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\CreateAction::make()
                ->label('Neuer Anruf')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle Anrufe')
                ->icon('heroicon-m-phone')
                ->badge(Call::count())
                ->badgeColor('gray')
                ->extraAttributes([
                    'title' => 'Zeigt alle eingegangenen Anrufe unabhängig vom Status oder Datum. Nutzen Sie diese Ansicht für eine vollständige Übersicht aller Kundeninteraktionen.',
                    'class' => 'tab-with-tooltip'
                ]),
                
            'today' => Tab::make('Heutige Anrufe')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function($q) {
                    $q->whereDate('start_timestamp', today())
                      ->orWhereDate('created_at', today());
                }))
                ->icon('heroicon-m-calendar')
                ->badge(Call::where(function($q) {
                    $q->whereDate('start_timestamp', today())
                      ->orWhereDate('created_at', today());
                })->count())
                ->badgeColor('primary')
                ->extraAttributes([
                    'title' => 'Zeigt nur Anrufe, die heute eingegangen sind. Ideal für die tägliche Nachbearbeitung und um keinen aktuellen Anruf zu verpassen.',
                    'class' => 'tab-with-tooltip'
                ]),
                
            'high_conversion' => Tab::make('Verkaufschancen')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereJsonContains('analysis->sentiment', 'positive')
                    ->where('duration_sec', '>', 120)
                    ->whereNull('appointment_id'))
                ->icon('heroicon-m-fire')
                ->badge(Call::whereJsonContains('analysis->sentiment', 'positive')
                    ->where('duration_sec', '>', 120)
                    ->whereNull('appointment_id')
                    ->count())
                ->badgeColor('success')
                ->extraAttributes([
                    'title' => 'Anrufe mit positiver Stimmung, über 2 Minuten Dauer und ohne gebuchten Termin. Diese Anrufer zeigen hohes Interesse und sollten prioritär kontaktiert werden!',
                    'class' => 'tab-with-tooltip'
                ]),
                
            'needs_followup' => Tab::make('Dringend')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where(function ($q) {
                        $q->whereJsonContains('analysis->sentiment', 'negative')
                          ->orWhereJsonContains('analysis->urgency', 'high')
                          ->orWhereJsonContains('analysis->appointment_requested', true);
                    })
                    ->whereNull('appointment_id'))
                ->icon('heroicon-m-exclamation-triangle')
                ->badge(Call::where(function ($q) {
                        $q->whereJsonContains('analysis->sentiment', 'negative')
                          ->orWhereJsonContains('analysis->urgency', 'high')
                          ->orWhereJsonContains('analysis->appointment_requested', true);
                    })
                    ->whereNull('appointment_id')
                    ->count())
                ->badgeColor('danger')
                ->extraAttributes([
                    'title' => 'Anrufe mit negativer Stimmung, hoher Dringlichkeit oder explizitem Terminwunsch. Diese Anrufer benötigen sofortige Aufmerksamkeit!',
                    'class' => 'tab-with-tooltip'
                ]),
                
            'with_appointment' => Tab::make('Erledigt')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('appointment_id'))
                ->icon('heroicon-m-check-circle')
                ->badge(Call::whereNotNull('appointment_id')->count())
                ->badgeColor('success')
                ->extraAttributes([
                    'title' => 'Erfolgreich abgeschlossene Anrufe mit bereits gebuchtem Termin. Keine weitere Aktion erforderlich.',
                    'class' => 'tab-with-tooltip'
                ]),
                
            'without_customer' => Tab::make('Unbekannt')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('customer_id'))
                ->icon('heroicon-m-question-mark-circle')
                ->badge(Call::whereNull('customer_id')->count())
                ->badgeColor('warning')
                ->extraAttributes([
                    'title' => 'Anrufe von unbekannten Nummern ohne Kundenzuordnung. Prüfen Sie, ob es sich um neue Interessenten handelt.',
                    'class' => 'tab-with-tooltip'
                ]),
        ];
    }
    
    public function getDefaultActiveTab(): string | int | null
    {
        return 'today';
    }
    
    // public function updatedActiveTab(): void
    // {
    //     // Trigger re-render when tab changes
    //     $this->dispatch('tab-changed', tab: $this->activeTab);
    // }
    
    // public function getActiveTab(): string | int | null
    // {
    //     return $this->activeTab;
    // }
    
    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }
    
    protected function getHeaderWidgets(): array
    {
        try {
            return [
                \App\Filament\Admin\Widgets\GlobalFilterWidget::class,
                \App\Filament\Admin\Widgets\CallKpiWidget::class,
                \App\Filament\Admin\Widgets\CallDurationHistogramWidget::class,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading widgets', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return [];
        }
    }
    
    // public function updated($property): void
    // {
    //     if ($property === 'activeTab') {
    //         $this->dispatch('tab-changed', tab: $this->activeTab);
    //     }
    //     
    //     parent::updated($property);
    // }
}