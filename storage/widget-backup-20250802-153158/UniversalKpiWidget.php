<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Traits\HasGlobalFilters;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

/**
 * Universelle Basis-Klasse für alle KPI-Widgets
 * 
 * Bietet einheitliche Struktur für KPI-Darstellung mit:
 * - Automatisches Caching
 * - Responsive Layout
 * - Trend-Indikatoren
 * - Error Handling
 * - Multi-Tenant Filtering
 * - Global Filter Synchronization
 */
abstract class UniversalKpiWidget extends Widget
{
    use HasGlobalFilters;
    protected static string $view = 'filament.admin.widgets.universal-kpi';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;
    
    /**
     * Auto-refresh Interval in Sekunden
     */
    protected static ?string $pollingInterval = '60s';
    
    /**
     * Widget-spezifische Konfiguration
     */
    protected array $widgetConfig = [];
    
    /**
     * Service-Instanz für KPI-Berechnungen
     */
    protected ?DashboardMetricsService $metricsService = null;
    
    public function mount(): void
    {
        $this->mountHasGlobalFilters();
    }
    
    protected function getMetricsService(): DashboardMetricsService
    {
        if (!$this->metricsService) {
            $this->metricsService = app(DashboardMetricsService::class);
        }
        return $this->metricsService;
    }
    
    /**
     * Handle Widget-Refresh bei Filter-Updates
     */
    #[On('refreshWidget')]
    public function refresh(): void
    {
        // Trigger Livewire refresh
    }
    
    /**
     * Hauptmethode: Liefert alle Daten für das Widget
     */
    public function getViewData(): array
    {
        try {
            // Ensure metricsService is initialized
            if (!isset($this->metricsService)) {
                $this->metricsService = app(DashboardMetricsService::class);
            }
            
            // Ensure globalFilters is initialized
            if (!isset($this->globalFilters['company_id']) || !$this->globalFilters['company_id']) {
                return [
                    'title' => $this->getWidgetTitle(),
                    'icon' => $this->getWidgetIcon(),
                    'kpis' => $this->formatKpis($this->getDefaultKpis()),
                    'config' => $this->getWidgetConfig(),
                    'filters' => $this->globalFilters,
                    'hasData' => false,
                    'errorMessage' => 'Bitte melden Sie sich an, um die Daten zu sehen.',
                ];
            }
            
            // Verwende globale Filter statt lokale
            $kpis = $this->getKpis($this->globalFilters);
            
            return [
                'title' => $this->getWidgetTitle(),
                'icon' => $this->getWidgetIcon(),
                'kpis' => $this->formatKpis($kpis),
                'config' => $this->getWidgetConfig(),
                'filters' => $this->globalFilters,
                'hasData' => $this->hasValidData($kpis),
                'errorMessage' => $this->getErrorMessage($kpis),
            ];
        } catch (\Exception $e) {
            Log::error('Universal KPI Widget error', [
                'widget' => static::class,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getErrorViewData($e);
        }
    }
    
    /**
     * Abstrakte Methoden - müssen von Child-Widgets implementiert werden
     */
    abstract protected function getKpis(array $filters): array;
    abstract protected function getWidgetTitle(): string;
    abstract protected function getWidgetIcon(): string;
    
    /**
     * Formatiert KPIs für die Anzeige
     */
    protected function formatKpis(array $kpis): array
    {
        $formatted = [];
        
        foreach ($kpis as $key => $kpi) {
            $formatted[] = [
                'key' => $key,
                'label' => $this->getKpiLabel($key),
                'value' => $kpi['formatted'] ?? $kpi['value'],
                'raw_value' => $kpi['value'],
                'previous' => $kpi['previous'] ?? 0,
                'change' => $kpi['change'] ?? 0,
                'trend' => $kpi['trend'] ?? 'stable',
                'icon' => $this->getKpiIcon($key, $kpi['trend'] ?? 'stable'),
                'color' => $this->getKpiColor($key, $kpi['trend'] ?? 'stable'),
                'tooltip' => $this->getKpiTooltip($key, $kpi),
                'is_percentage' => $this->isPercentageKpi($key),
                'is_currency' => $this->isCurrencyKpi($key),
                'priority' => $this->getKpiPriority($key),
            ];
        }
        
        // Sortiere nach Priorität
        usort($formatted, fn($a, $b) => $a['priority'] <=> $b['priority']);
        
        return $formatted;
    }
    
    /**
     * Widget-Konfiguration
     */
    protected function getWidgetConfig(): array
    {
        return array_merge([
            'responsive' => true,
            'auto_refresh' => true,
            'show_trends' => true,
            'show_tooltips' => true,
            'max_kpis' => 6,
            'layout' => 'grid', // grid|list|compact
        ], $this->widgetConfig);
    }
    
    /**
     * Callback wenn Filter aktualisiert wurden
     */
    protected function onFiltersUpdated(): void
    {
        // Cache invalidieren bei Filter-Änderung
        $cacheKey = $this->getCacheKey($this->globalFilters);
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
    }
    
    /**
     * KPI-spezifische Labels
     */
    protected function getKpiLabel(string $key): string
    {
        return match($key) {
            // Appointment KPIs
            'revenue' => 'Umsatz',
            'occupancy' => 'Auslastung',
            'conversion' => 'Conversion',
            'no_show_rate' => 'No-Show Rate',
            'avg_duration' => 'Ø Dauer',
            'revenue_per_appointment' => 'Umsatz/Termin',
            
            // Call KPIs
            'total_calls' => 'Anrufe',
            'avg_duration' => 'Ø Dauer',
            'success_rate' => 'Erfolgsquote',
            'sentiment_positive' => 'Positive Stimmung',
            'avg_cost' => 'Ø Kosten',
            'roi' => 'ROI',
            
            // Customer KPIs
            'total_customers' => 'Kunden gesamt',
            'new_customers' => 'Neue Kunden',
            'avg_clv' => 'Ø CLV',
            'returning_rate' => 'Wiederkehrend',
            'churn_rate' => 'Churn Rate',
            'top_customers_revenue' => 'Top-Kunden Anteil',
            
            default => ucfirst(str_replace('_', ' ', $key)),
        };
    }
    
    /**
     * KPI-spezifische Icons
     */
    protected function getKpiIcon(string $key, string $trend): string
    {
        $baseIcon = match($key) {
            'revenue', 'avg_clv', 'revenue_per_appointment' => 'heroicon-o-currency-euro',
            'occupancy', 'success_rate', 'conversion' => 'heroicon-o-chart-bar',
            'no_show_rate', 'churn_rate' => 'heroicon-o-exclamation-triangle',
            'avg_duration' => 'heroicon-o-clock',
            'total_calls', 'total_customers' => 'heroicon-o-users',
            'new_customers' => 'heroicon-o-user-plus',
            'sentiment_positive' => 'heroicon-o-face-smile',
            'avg_cost' => 'heroicon-o-credit-card',
            'roi' => 'heroicon-o-arrow-trending-up',
            'returning_rate' => 'heroicon-o-arrow-path',
            'top_customers_revenue' => 'heroicon-o-star',
            default => 'heroicon-o-chart-bar',
        };
        
        return $baseIcon;
    }
    
    /**
     * KPI-spezifische Farben basierend auf Trend
     */
    protected function getKpiColor(string $key, string $trend): string
    {
        // Für manche KPIs ist "down" besser (z.B. No-Show Rate, Kosten)
        $isInversed = in_array($key, ['no_show_rate', 'churn_rate', 'avg_cost']);
        
        return match($trend) {
            'up' => $isInversed ? 'danger' : 'success',
            'down' => $isInversed ? 'success' : 'warning',
            'stable' => 'gray',
            default => 'gray',
        };
    }
    
    /**
     * KPI-spezifische Tooltips
     */
    protected function getKpiTooltip(string $key, array $kpi): string
    {
        $change = $kpi['change'] ?? 0;
        $trend = $kpi['trend'] ?? 'stable';
        $previous = $kpi['previous'] ?? 0;
        
        $trendText = match($trend) {
            'up' => $change > 0 ? "+{$change}" : 'Gestiegen',
            'down' => $change < 0 ? "{$change}" : 'Gesunken',
            'stable' => 'Unverändert',
            default => '',
        };
        
        $explanation = match($key) {
            'revenue' => 'Umsatz aus abgeschlossenen Terminen',
            'occupancy' => 'Verhältnis gebuchte zu verfügbaren Termine',
            'conversion' => 'Anteil Anrufe die zu Terminen werden',
            'no_show_rate' => 'Anteil Termine ohne Erscheinen',
            'avg_duration' => 'Durchschnittliche Termindauer',
            'sentiment_positive' => 'Anteil positiver Anrufe basierend auf KI-Analyse',
            'roi' => 'Return on Investment: (Umsatz - Kosten) / Kosten',
            'churn_rate' => 'Anteil Kunden ohne Termin in letzten 90 Tagen',
            default => '',
        };
        
        if ($kpi['value'] == 0 && $previous == 0) {
            return $explanation . "\n\nNoch keine Daten für den gewählten Zeitraum verfügbar.";
        }
        
        return $explanation . "\n\nTrend: {$trendText} (Vorperiode: " . ($kpi['formatted_previous'] ?? $previous) . ")";
    }
    
    /**
     * Prüft ob KPI ein Prozent-Wert ist
     */
    protected function isPercentageKpi(string $key): bool
    {
        return in_array($key, [
            'occupancy', 'conversion', 'no_show_rate', 'success_rate', 
            'sentiment_positive', 'roi', 'returning_rate', 'churn_rate', 
            'top_customers_revenue'
        ]);
    }
    
    /**
     * Prüft ob KPI ein Währungs-Wert ist
     */
    protected function isCurrencyKpi(string $key): bool
    {
        return in_array($key, [
            'revenue', 'avg_clv', 'revenue_per_appointment', 'avg_cost'
        ]);
    }
    
    /**
     * KPI-Priorität für Sortierung (niedrigere Zahl = höhere Priorität)
     */
    protected function getKpiPriority(string $key): int
    {
        return match($key) {
            // Appointments: Umsatz am wichtigsten
            'revenue' => 1,
            'occupancy' => 2,
            'conversion' => 3,
            'no_show_rate' => 4,
            'avg_duration' => 5,
            'revenue_per_appointment' => 6,
            
            // Calls: Anzahl am wichtigsten
            'total_calls' => 1,
            'success_rate' => 2,
            'avg_duration' => 3,
            'sentiment_positive' => 4,
            'roi' => 5,
            'avg_cost' => 6,
            
            // Customers: Wachstum am wichtigsten
            'total_customers' => 1,
            'new_customers' => 2,
            'avg_clv' => 3,
            'returning_rate' => 4,
            'churn_rate' => 5,
            'top_customers_revenue' => 6,
            
            default => 99,
        };
    }
    
    /**
     * Prüft ob gültige Daten vorhanden sind
     */
    protected function hasValidData(array $kpis): bool
    {
        foreach ($kpis as $kpi) {
            if (($kpi['value'] ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Generiert Error-Message basierend auf KPI-Daten
     */
    protected function getErrorMessage(array $kpis): ?string
    {
        if (!$this->hasValidData($kpis)) {
            $periodOptions = $this->getPeriodOptions();
            $period = $periodOptions[$this->globalFilters['period']]['label'] ?? 'heute';
            return "Keine Daten für {$period} verfügbar. Versuchen Sie einen anderen Zeitraum oder prüfen Sie die Datenintegration.";
        }
        
        return null;
    }
    
    /**
     * Fallback-Daten bei Fehlern
     */
    protected function getErrorViewData(\Exception $e): array
    {
        return [
            'title' => $this->getWidgetTitle(),
            'icon' => 'heroicon-o-exclamation-triangle',
            'kpis' => [],
            'config' => $this->getWidgetConfig(),
            'hasData' => false,
            'errorMessage' => 'Fehler beim Laden der Daten. Bitte versuchen Sie es später erneut.',
            'error_details' => app()->environment('local') ? $e->getMessage() : null,
        ];
    }
    
    /**
     * Hilfsmethode: Formatiert Zahlen
     */
    protected function formatNumber(float $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, ',', '.');
    }
    
    /**
     * Hilfsmethode: Formatiert Prozente
     */
    protected function formatPercentage(float $value, int $decimals = 1): string
    {
        return number_format($value, $decimals, ',', '.') . '%';
    }
    
    /**
     * Hilfsmethode: Formatiert Währung
     */
    protected function formatCurrency(float $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, ',', '.') . '€';
    }
    
    /**
     * Cache-Key für Session-spezifische Daten
     */
    protected function getCacheKey(array $filters): string
    {
        return 'widget_' . static::class . '_' . md5(serialize($filters));
    }
    
    /**
     * Get default KPIs when filters are not initialized
     */
    protected function getDefaultKpis(): array
    {
        return [
            'revenue' => ['value' => 0, 'formatted' => '0,00 €', 'trend' => 'stable'],
            'appointments' => ['value' => 0, 'formatted' => '0', 'trend' => 'stable'],
            'occupancy' => ['value' => 0, 'formatted' => '0%', 'trend' => 'stable'],
            'conversion' => ['value' => 0, 'formatted' => '0%', 'trend' => 'stable'],
        ];
    }
}