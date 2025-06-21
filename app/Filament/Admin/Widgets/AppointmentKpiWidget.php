<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Dashboard\DashboardMetricsService;

/**
 * KPI-Widget für Termine-Seite
 * 
 * Zeigt die 6 wichtigsten Termine-KPIs:
 * - Umsatz (mit Trend)
 * - Auslastung (Kapazität)  
 * - Conversion Rate (Phone → Appointment)
 * - No-Show Rate
 * - Durchschnittliche Dauer
 * - Umsatz pro Termin
 */
class AppointmentKpiWidget extends UniversalKpiWidget
{
    protected static ?int $sort = 1;
    
    protected array $widgetConfig = [
        'layout' => 'grid',
        'columns' => 3, // 2 Zeilen à 3 KPIs
        'show_trends' => true,
        'auto_refresh' => true,
    ];

    protected function getKpis(array $filters): array
    {
        return $this->getMetricsService()->getAppointmentKpis($filters);
    }

    protected function getWidgetTitle(): string
    {
        return 'Termine KPIs';
    }

    protected function getWidgetIcon(): string
    {
        return 'heroicon-o-calendar-days';
    }
    
    /**
     * Spezifische Tooltip-Erweiterungen für Termine
     */
    protected function getKpiTooltip(string $key, array $kpi): string
    {
        $baseTooltip = parent::getKpiTooltip($key, $kpi);
        
        $additionalInfo = match($key) {
            'revenue' => "\n\n💡 Tipp: Vergleichen Sie mit Vormonat um saisonale Trends zu erkennen.",
            'occupancy' => "\n\n💡 Optimaler Bereich: 70-85%. Über 90% kann zu Stress führen.",
            'conversion' => "\n\n💡 Benchmark: Gute Praxen erreichen 60-80% Conversion Rate.",
            'no_show_rate' => "\n\n💡 Maßnahmen: SMS-Erinnerungen können No-Shows um 30% reduzieren.",
            'avg_duration' => "\n\n💡 Achten Sie auf Balance zwischen Qualität und Effizienz.",
            'revenue_per_appointment' => "\n\n💡 Kann durch Service-Mix-Optimierung gesteigert werden.",
            default => '',
        };
        
        return $baseTooltip . $additionalInfo;
    }
    
    /**
     * Farb-Override für bessere Termine-Darstellung
     */
    protected function getKpiColor(string $key, string $trend): string
    {
        // Spezielle Logik für Auslastung
        if ($key === 'occupancy') {
            $value = $this->getKpiValue($key);
            if ($value < 50) return 'danger';      // Zu niedrig
            if ($value < 70) return 'warning';     // Ausbaufähig  
            if ($value < 90) return 'success';     // Optimal
            return 'warning';                      // Zu hoch
        }
        
        // Spezielle Logik für No-Show Rate
        if ($key === 'no_show_rate') {
            $value = $this->getKpiValue($key);
            if ($value < 5) return 'success';      // Sehr gut
            if ($value < 10) return 'warning';     // Akzeptabel
            return 'danger';                       // Kritisch
        }
        
        return parent::getKpiColor($key, $trend);
    }
    
    /**
     * Hilfsmethode um aktuellen KPI-Wert zu bekommen
     */
    private function getKpiValue(string $key): float
    {
        // Ensure metricsService is initialized
        
        
        $kpis = $this->getMetricsService()->getAppointmentKpis($this->globalFilters ?? []);
        return $kpis[$key]['value'] ?? 0;
    }
    
    /**
     * Erweiterte Prioritäten für Termine
     */
    protected function getKpiPriority(string $key): int
    {
        return match($key) {
            'revenue' => 1,                    // Umsatz ist König
            'occupancy' => 2,                  // Auslastung kritisch für Planung
            'conversion' => 3,                 // Conversion zeigt Marketing-Erfolg
            'revenue_per_appointment' => 4,    // Effizienz-Indikator
            'no_show_rate' => 5,              // Operationales Problem
            'avg_duration' => 6,              // Service-Qualität
            default => 99,
        };
    }
}