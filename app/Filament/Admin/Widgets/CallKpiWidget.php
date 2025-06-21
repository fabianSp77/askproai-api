<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Dashboard\DashboardMetricsService;

/**
 * KPI-Widget für Anrufe-Seite
 * 
 * Zeigt die 6 wichtigsten Call-KPIs:
 * - Anrufe heute
 * - Durchschnittliche Dauer
 * - Erfolgsquote (führt zu Termin)
 * - Positive Stimmung
 * - Durchschnittliche Kosten
 * - ROI
 */
class CallKpiWidget extends UniversalKpiWidget
{
    protected static ?int $sort = 1;
    
    protected array $widgetConfig = [
        'layout' => 'grid',
        'columns' => 3,
        'show_trends' => true,
        'auto_refresh' => true,
        'refresh_interval' => 30, // Schnellere Updates für Live-Monitoring
    ];

    protected function getKpis(array $filters): array
    {
        return $this->getMetricsService()->getCallKpis($filters);
    }

    protected function getWidgetTitle(): string
    {
        return 'Anrufe KPIs';
    }

    protected function getWidgetIcon(): string
    {
        return 'heroicon-o-phone';
    }
    
    /**
     * Erweiterte Tooltips für Call-spezifische Insights
     */
    protected function getKpiTooltip(string $key, array $kpi): string
    {
        $baseTooltip = parent::getKpiTooltip($key, $kpi);
        
        $additionalInfo = match($key) {
            'total_calls' => "\n\n📊 Peak-Zeiten analysieren für bessere Personalplanung.",
            'avg_duration' => "\n\n⏱️ Optimal: 2-5 Minuten. Zu kurz = verpasste Chancen.",
            'success_rate' => "\n\n🎯 Jede 10% Steigerung = ~20% mehr Umsatz.",
            'sentiment_positive' => "\n\n😊 Korreliert stark mit Buchungswahrscheinlichkeit.",
            'avg_cost' => "\n\n💰 Vergleichen Sie mit durchschnittlichem Terminwert.",
            'roi' => "\n\n📈 ROI >200% ist exzellent für AI-Telefonie.",
            default => '',
        };
        
        return $baseTooltip . $additionalInfo;
    }
    
    /**
     * Spezielle Farblogik für Call-Metriken
     */
    protected function getKpiColor(string $key, string $trend): string
    {
        // ROI-spezifische Farben
        if ($key === 'roi') {
            $value = $this->getKpiValue($key);
            if ($value < 100) return 'danger';     // Verlustgeschäft
            if ($value < 200) return 'warning';    // Verbesserungspotenzial
            return 'success';                       // Profitabel
        }
        
        // Erfolgsquote-spezifische Farben
        if ($key === 'success_rate') {
            $value = $this->getKpiValue($key);
            if ($value < 30) return 'danger';      // Kritisch niedrig
            if ($value < 50) return 'warning';     // Optimierungsbedarf
            return 'success';                       // Gut
        }
        
        // Sentiment-spezifische Farben
        if ($key === 'sentiment_positive') {
            $value = $this->getKpiValue($key);
            if ($value < 40) return 'danger';      // Viele negative Anrufe
            if ($value < 60) return 'warning';     // Gemischt
            return 'success';                       // Überwiegend positiv
        }
        
        return parent::getKpiColor($key, $trend);
    }
    
    /**
     * Call-spezifische Icons mit Status-Indikation
     */
    protected function getKpiIcon(string $key, string $trend): string
    {
        return match($key) {
            'total_calls' => 'heroicon-o-phone-arrow-down-left',
            'avg_duration' => 'heroicon-o-clock',
            'success_rate' => 'heroicon-o-check-circle',
            'sentiment_positive' => 'heroicon-o-face-smile',
            'avg_cost' => 'heroicon-o-banknotes',
            'roi' => 'heroicon-o-arrow-trending-up',
            default => parent::getKpiIcon($key, $trend),
        };
    }
    
    /**
     * Formatierungs-Override für bessere Lesbarkeit
     */
    protected function formatKpis(array $kpis): array
    {
        $formatted = parent::formatKpis($kpis);
        
        // Füge Call-spezifische Formatierungen hinzu
        foreach ($formatted as &$kpi) {
            if ($kpi['key'] === 'avg_duration' && isset($kpi['raw_value'])) {
                // Zeige Minuten:Sekunden Format
                $seconds = (int)$kpi['raw_value'];
                $kpi['value'] = sprintf('%d:%02d', floor($seconds / 60), $seconds % 60);
            }
            
            if ($kpi['key'] === 'roi' && isset($kpi['raw_value'])) {
                // ROI mit + Zeichen für positive Werte
                if ($kpi['raw_value'] > 0) {
                    $kpi['value'] = '+' . $kpi['value'];
                }
            }
        }
        
        return $formatted;
    }
    
    private function getKpiValue(string $key): float
    {
        // Use globalFilters directly instead of getFilters() method
        $filters = $this->globalFilters ?? [];
        $kpis = $this->getMetricsService()->getCallKpis($filters);
        return $kpis[$key]['value'] ?? 0;
    }
}