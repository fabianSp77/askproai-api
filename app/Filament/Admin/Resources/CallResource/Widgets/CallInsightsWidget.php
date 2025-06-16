<?php

namespace App\Filament\Admin\Resources\CallResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\Call;

class CallInsightsWidget extends Widget
{
    protected static string $view = 'filament.resources.call-resource.widgets.call-insights';
    
    public ?Call $record = null;
    
    protected function getViewData(): array
    {
        if (!$this->record) {
            return ['insights' => []];
        }
        
        $insights = [];
        
        // Conversion Score berechnen
        $conversionScore = $this->calculateConversionScore();
        $insights[] = [
            'icon' => 'heroicon-o-chart-bar',
            'title' => 'Conversion Wahrscheinlichkeit',
            'value' => $conversionScore . '%',
            'color' => $conversionScore >= 70 ? 'success' : ($conversionScore >= 40 ? 'warning' : 'danger'),
            'description' => $this->getConversionDescription($conversionScore),
        ];
        
        // Kundentyp erkennen
        $customerType = $this->detectCustomerType();
        $insights[] = [
            'icon' => 'heroicon-o-user-circle',
            'title' => 'Kundentyp',
            'value' => $customerType['label'],
            'color' => $customerType['color'],
            'description' => $customerType['description'],
        ];
        
        // Opportunity Score
        $opportunityScore = $this->calculateOpportunityScore();
        $insights[] = [
            'icon' => 'heroicon-o-currency-euro',
            'title' => 'Geschäftspotenzial',
            'value' => $opportunityScore['value'],
            'color' => $opportunityScore['color'],
            'description' => $opportunityScore['description'],
        ];
        
        // Risk Assessment
        $risk = $this->assessRisk();
        if ($risk['level'] !== 'none') {
            $insights[] = [
                'icon' => 'heroicon-o-exclamation-triangle',
                'title' => 'Risiko-Bewertung',
                'value' => $risk['label'],
                'color' => $risk['color'],
                'description' => $risk['description'],
            ];
        }
        
        return ['insights' => $insights];
    }
    
    private function calculateConversionScore(): int
    {
        $score = 50; // Basis-Score
        
        // Sentiment Analysis
        $sentiment = $this->record->analysis['sentiment'] ?? null;
        if ($sentiment === 'positive') {
            $score += 20;
        } elseif ($sentiment === 'negative') {
            $score -= 30;
        }
        
        // Call Duration
        if ($this->record->duration_sec > 180) {
            $score += 15;
        } elseif ($this->record->duration_sec < 30) {
            $score -= 20;
        }
        
        // Entities detected
        $entities = $this->record->analysis['entities'] ?? [];
        if (!empty($entities['date'])) $score += 15;
        if (!empty($entities['time'])) $score += 10;
        if (!empty($entities['service'])) $score += 10;
        if (!empty($entities['email'])) $score += 5;
        
        // Customer exists
        if ($this->record->customer_id) {
            $score += 10;
        }
        
        // Already has appointment
        if ($this->record->appointment_id) {
            return 100;
        }
        
        return max(0, min(95, $score));
    }
    
    private function getConversionDescription(int $score): string
    {
        if ($score >= 80) {
            return "Sehr hohe Chance auf Terminbuchung. Sofort nachfassen!";
        } elseif ($score >= 60) {
            return "Gute Chancen. Zeitnah Kontakt aufnehmen.";
        } elseif ($score >= 40) {
            return "Mittlere Chancen. Follow-up empfohlen.";
        } else {
            return "Geringe Chancen. Eventuell später erneut versuchen.";
        }
    }
    
    private function detectCustomerType(): array
    {
        // Check if existing customer
        if ($this->record->customer_id && $this->record->customer) {
            $customer = $this->record->customer;
            $appointmentCount = $customer->appointments()->count();
            
            if ($appointmentCount > 10) {
                return [
                    'label' => 'VIP Stammkunde',
                    'color' => 'success',
                    'description' => $appointmentCount . ' bisherige Termine',
                ];
            } elseif ($appointmentCount > 3) {
                return [
                    'label' => 'Stammkunde',
                    'color' => 'info',
                    'description' => $appointmentCount . ' bisherige Termine',
                ];
            } else {
                return [
                    'label' => 'Bestandskunde',
                    'color' => 'gray',
                    'description' => $appointmentCount . ' bisherige Termine',
                ];
            }
        }
        
        // New customer
        return [
            'label' => 'Neukunde',
            'color' => 'warning',
            'description' => 'Erste Kontaktaufnahme',
        ];
    }
    
    private function calculateOpportunityScore(): array
    {
        $value = 0;
        $factors = [];
        
        // Check for premium services mentioned
        $transcript = strtolower($this->record->transcript ?? '');
        $premiumKeywords = ['premium', 'vip', 'exklusiv', 'paket', 'komplett'];
        
        foreach ($premiumKeywords as $keyword) {
            if (str_contains($transcript, $keyword)) {
                $value += 50;
                $factors[] = 'Premium-Service erwähnt';
                break;
            }
        }
        
        // Long call duration indicates interest
        if ($this->record->duration_sec > 300) {
            $value += 30;
            $factors[] = 'Langes Gespräch';
        }
        
        // Multiple services mentioned
        if (isset($this->record->analysis['entities']['service'])) {
            $services = is_array($this->record->analysis['entities']['service']) 
                ? $this->record->analysis['entities']['service'] 
                : [$this->record->analysis['entities']['service']];
            
            if (count($services) > 1) {
                $value += 40;
                $factors[] = 'Mehrere Services';
            }
        }
        
        // Urgency
        if (isset($this->record->analysis['urgency']) && $this->record->analysis['urgency'] === 'high') {
            $value += 20;
            $factors[] = 'Hohe Dringlichkeit';
        }
        
        if ($value >= 80) {
            return [
                'value' => '€€€€',
                'color' => 'success',
                'description' => 'Hohes Umsatzpotenzial: ' . implode(', ', $factors),
            ];
        } elseif ($value >= 40) {
            return [
                'value' => '€€€',
                'color' => 'warning',
                'description' => 'Mittleres Potenzial: ' . implode(', ', $factors),
            ];
        } else {
            return [
                'value' => '€€',
                'color' => 'gray',
                'description' => 'Standard-Potenzial',
            ];
        }
    }
    
    private function assessRisk(): array
    {
        $risks = [];
        
        // Negative sentiment
        if (isset($this->record->analysis['sentiment']) && $this->record->analysis['sentiment'] === 'negative') {
            $risks[] = 'Negative Stimmung';
        }
        
        // Keywords indicating problems
        $problemKeywords = ['beschwerde', 'problem', 'unzufrieden', 'ärger', 'schlecht', 'falsch'];
        $transcript = strtolower($this->record->transcript ?? '');
        
        foreach ($problemKeywords as $keyword) {
            if (str_contains($transcript, $keyword)) {
                $risks[] = 'Beschwerde erkannt';
                break;
            }
        }
        
        // Very short call might indicate hang-up
        if ($this->record->duration_sec < 15) {
            $risks[] = 'Sehr kurzer Anruf';
        }
        
        // Disconnection reason
        if ($this->record->disconnection_reason === 'customer_hung_up') {
            $risks[] = 'Kunde hat aufgelegt';
        }
        
        if (count($risks) >= 2) {
            return [
                'level' => 'high',
                'label' => 'Hoch',
                'color' => 'danger',
                'description' => implode(', ', $risks),
            ];
        } elseif (count($risks) === 1) {
            return [
                'level' => 'medium',
                'label' => 'Mittel',
                'color' => 'warning',
                'description' => implode(', ', $risks),
            ];
        } else {
            return [
                'level' => 'none',
                'label' => 'Kein Risiko',
                'color' => 'success',
                'description' => '',
            ];
        }
    }
}