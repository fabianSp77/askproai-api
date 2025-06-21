<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Dashboard\DashboardMetricsService;

/**
 * KPI-Widget für Kunden-Seite
 * 
 * Zeigt die 6 wichtigsten Customer-KPIs:
 * - Kunden gesamt
 * - Neue Kunden
 * - Durchschnittlicher CLV
 * - Wiederkehrende Kunden
 * - Churn Rate
 * - Top-Kunden Umsatzanteil
 */
class CustomerKpiWidget extends UniversalKpiWidget
{
    protected static ?int $sort = 1;
    
    protected array $widgetConfig = [
        'layout' => 'grid',
        'columns' => 3,
        'show_trends' => true,
        'auto_refresh' => true,
    ];

    protected function getKpis(array $filters): array
    {
        return $this->getMetricsService()->getCustomerKpis($filters);
    }

    protected function getWidgetTitle(): string
    {
        return 'Kunden KPIs';
    }

    protected function getWidgetIcon(): string
    {
        return 'heroicon-o-user-group';
    }
    
    /**
     * Customer-spezifische Tooltips mit Business-Insights
     */
    protected function getKpiTooltip(string $key, array $kpi): string
    {
        $baseTooltip = parent::getKpiTooltip($key, $kpi);
        
        $additionalInfo = match($key) {
            'total_customers' => "\n\n👥 Aktive Kundenbasis - Fundament Ihres Geschäfts.",
            'new_customers' => "\n\n🆕 Wachstumsindikator - Ziel: 10-20% pro Monat.",
            'avg_clv' => "\n\n💎 Customer Lifetime Value - Basis für Marketing-Budget.",
            'returning_rate' => "\n\n🔄 Loyalität-Indikator - >60% ist exzellent.",
            'churn_rate' => "\n\n⚠️ Verlustrate - Unter 5% monatlich ist gut.",
            'top_customers_revenue' => "\n\n⭐ 80/20 Regel - Fokus auf VIP-Betreuung.",
            default => '',
        };
        
        return $baseTooltip . $additionalInfo;
    }
    
    /**
     * Erweiterte Farblogik für Customer-Metriken
     */
    protected function getKpiColor(string $key, string $trend): string
    {
        // CLV-spezifische Farben
        if ($key === 'avg_clv') {
            $value = $this->getKpiValue($key);
            if ($value < 100) return 'danger';     // Zu niedrig für Profitabilität
            if ($value < 300) return 'warning';    // Ausbaufähig
            return 'success';                       // Gesunder CLV
        }
        
        // Wiederkehrende Kunden
        if ($key === 'returning_rate') {
            $value = $this->getKpiValue($key);
            if ($value < 30) return 'danger';      // Kritisch - keine Bindung
            if ($value < 60) return 'warning';     // Verbesserungspotenzial
            return 'success';                       // Starke Kundenbindung
        }
        
        // Churn Rate (niedriger ist besser)
        if ($key === 'churn_rate') {
            $value = $this->getKpiValue($key);
            if ($value > 10) return 'danger';      // Kritischer Kundenverlust
            if ($value > 5) return 'warning';      // Erhöhte Abwanderung
            return 'success';                       // Gesunde Retention
        }
        
        // Top-Kunden Konzentration
        if ($key === 'top_customers_revenue') {
            $value = $this->getKpiValue($key);
            if ($value > 60) return 'warning';     // Zu hohe Abhängigkeit
            if ($value < 20) return 'warning';     // Zu wenig VIP-Umsatz
            return 'success';                       // Gesunde Verteilung
        }
        
        return parent::getKpiColor($key, $trend);
    }
    
    /**
     * Customer-spezifische Icons
     */
    protected function getKpiIcon(string $key, string $trend): string
    {
        return match($key) {
            'total_customers' => 'heroicon-o-users',
            'new_customers' => 'heroicon-o-user-plus',
            'avg_clv' => 'heroicon-o-currency-euro',
            'returning_rate' => 'heroicon-o-arrow-path',
            'churn_rate' => 'heroicon-o-user-minus',
            'top_customers_revenue' => 'heroicon-o-star',
            default => parent::getKpiIcon($key, $trend),
        };
    }
    
    /**
     * Erweiterte Formatierung für Customer-Metriken
     */
    protected function formatKpis(array $kpis): array
    {
        $formatted = parent::formatKpis($kpis);
        
        foreach ($formatted as &$kpi) {
            // Füge Kontext für neue Kunden hinzu
            if ($kpi['key'] === 'new_customers' && isset($kpi['change'])) {
                if ($kpi['change'] > 0) {
                    $kpi['value'] = '+' . $kpi['raw_value'] . ' (' . $kpi['value'] . ')';
                }
            }
            
            // Zeige CLV mit Vergleich zum Durchschnitt
            if ($kpi['key'] === 'avg_clv' && isset($kpi['raw_value'])) {
                $industryAvg = 250; // Branchen-Durchschnitt
                $diff = $kpi['raw_value'] - $industryAvg;
                if ($diff > 0) {
                    $kpi['tooltip'] .= sprintf("\n\n📊 %s€ über Branchenschnitt!", 
                        number_format($diff, 0, ',', '.'));
                }
            }
        }
        
        return $formatted;
    }
    
    /**
     * Customer-spezifische Prioritäten
     */
    protected function getKpiPriority(string $key): int
    {
        return match($key) {
            'total_customers' => 1,           // Gesamtbestand
            'new_customers' => 2,             // Wachstum
            'avg_clv' => 3,                  // Wertschöpfung
            'returning_rate' => 4,           // Loyalität
            'churn_rate' => 5,               // Verlust-Kontrolle
            'top_customers_revenue' => 6,    // Risiko-Verteilung
            default => 99,
        };
    }
    
    private function getKpiValue(string $key): float
    {
        $kpis = $this->getMetricsService()->getCustomerKpis($this->globalFilters ?? []);
        return $kpis[$key]['value'] ?? 0;
    }
}