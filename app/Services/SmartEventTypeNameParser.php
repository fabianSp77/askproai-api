<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Str;

class SmartEventTypeNameParser
{
    /**
     * Extrahiere einen sauberen Service-Namen aus Marketing-Text
     */
    public function extractCleanServiceName(string $eventTypeName): string
    {
        $original = $eventTypeName;
        
        // Schritt 1: Teile den Namen bei + auf und analysiere jeden Teil
        $parts = [];
        if (strpos($original, '+') !== false) {
            $parts = array_map('trim', explode('+', $original));
        } else {
            $parts = [$original];
        }
        
        // Service-Keywords
        $serviceKeywords = [
            'Beratung', 'Termin', 'Behandlung', 'Therapie', 'Konsultation',
            'Check-up', 'Untersuchung', 'Training', 'Kurs', 'Workshop',
            'Haarschnitt', 'Färben', 'Styling', 'Massage', 'Coaching',
            'Gespräch', 'Analyse', 'Planung', 'Erstgespräch', 'Schnitt',
            'Betreuung', 'Sitzung', 'Session', 'Besprechung'
        ];
        
        // Suche zuerst nach Service-Keywords in allen Teilen
        foreach ($parts as $part) {
            foreach ($serviceKeywords as $keyword) {
                if (stripos($part, $keyword) !== false) {
                    // Gefunden! Bereinige diesen Teil und gib ihn zurück
                    $eventTypeName = $part;
                    goto cleanup; // Springe zur Bereinigung
                }
            }
        }
        
        // Wenn kein Service-Keyword gefunden, nimm den ganzen Namen
        $eventTypeName = $original;
        
        cleanup:
        // Schritt 2: Entferne bekannte Firmen/Location-Namen
        $companyPatterns = [
            'AskProAI',
            'ModernHair',
            'FitXpert',
            'Salon Demo',
            // Füge weitere Firmennamen hinzu
        ];
        
        foreach ($companyPatterns as $pattern) {
            $eventTypeName = str_ireplace($pattern, '', $eventTypeName);
        }
        
        // Schritt 3: Entferne Ortsangaben
        $locationPatterns = [
            '/(^|\s)(aus|in|bei)\s+\w+/i',  // "aus Berlin", "in München"
            '/\s*(Berlin|München|Hamburg|Köln|Frankfurt|Stuttgart)(\s|$)/i',
        ];
        
        foreach ($locationPatterns as $pattern) {
            $eventTypeName = preg_replace($pattern, ' ', $eventTypeName);
        }
        
        // Schritt 4: Entferne Marketing-Fluff
        $marketingPatterns = [
            '/\d+%\s*(mehr\s*)?(Umsatz|Gewinn|Erfolg)[^,]*/i',
            '/für Sie und.*$/i',
            '/(besten?\s*)?(Kunden)?service\s*24\/7/i',
            '/24\/7/i',
            '/\+\s*/i',  // Entferne übrig gebliebene + Zeichen
            '/\s*-\s*/', // Entferne - Zeichen
        ];
        
        foreach ($marketingPatterns as $pattern) {
            $eventTypeName = preg_replace($pattern, ' ', $eventTypeName);
        }
        
        // Schritt 4: Identifiziere den Kern-Service
        $serviceKeywords = [
            'Beratung', 'Termin', 'Behandlung', 'Therapie', 'Konsultation',
            'Check-up', 'Untersuchung', 'Training', 'Kurs', 'Workshop',
            'Haarschnitt', 'Färben', 'Styling', 'Massage', 'Coaching',
            'Gespräch', 'Analyse', 'Planung', 'Erstgespräch'
        ];
        
        $words = preg_split('/\s+/', trim($eventTypeName));
        $serviceFound = '';
        
        foreach ($words as $word) {
            foreach ($serviceKeywords as $keyword) {
                if (stripos($word, $keyword) !== false) {
                    $serviceFound = $keyword;
                    break 2;
                }
            }
        }
        
        // Wenn Service gefunden, nutze ihn mit Zeitangabe falls vorhanden
        if ($serviceFound) {
            // Suche nach Zeitangaben
            if (preg_match('/(\d+)\s*(Min(uten)?|Std|Stunden?)/i', $original, $matches)) {
                return $matches[1] . ' Min ' . $serviceFound;
            }
            return $serviceFound;
        }
        
        // Fallback: Bereinige und kürze den Namen
        $eventTypeName = trim(preg_replace('/\s+/', ' ', $eventTypeName));
        
        if (empty($eventTypeName) || strlen($eventTypeName) < 3) {
            // Versuche Zeitangabe aus Original zu extrahieren
            if (preg_match('/(\d+)\s*(Min(uten)?|Std|Stunden?)/i', $original, $matches)) {
                return $matches[1] . ' Minuten Termin';
            }
            return 'Termin';
        }
        
        // Kürze auf maximal 30 Zeichen
        if (strlen($eventTypeName) > 30) {
            $words = explode(' ', $eventTypeName);
            $eventTypeName = implode(' ', array_slice($words, 0, 3));
        }
        
        return $eventTypeName;
    }
    
    /**
     * Generiere verschiedene Namensformate für den Import
     */
    public function generateNameFormats(Branch $branch, string $serviceName): array
    {
        $company = $branch->company->name;
        $branchName = $branch->name;
        
        // Bereinige Service-Name
        $cleanService = $this->extractCleanServiceName($serviceName);
        
        return [
            'standard' => "{$branchName}-{$company}-{$cleanService}",
            'compact' => "{$branchName} - {$cleanService}",
            'service_first' => "{$cleanService} ({$branchName})",
            'full' => "{$company} {$branchName}: {$cleanService}",
        ];
    }
    
    /**
     * Analysiere Event-Types für Import mit intelligenter Namensgebung
     */
    public function analyzeEventTypesForImport(array $eventTypes, Branch $targetBranch): array
    {
        $results = [];
        
        foreach ($eventTypes as $eventType) {
            $originalName = $eventType['title'] ?? $eventType['name'] ?? 'Unbenannt';
            $cleanService = $this->extractCleanServiceName($originalName);
            $formats = $this->generateNameFormats($targetBranch, $originalName);
            
            $results[] = [
                'original' => $eventType,
                'original_name' => $originalName,
                'extracted_service' => $cleanService,
                'suggested_names' => $formats,
                'recommended_name' => $formats['compact'], // Default recommendation
                'import_ready' => true
            ];
        }
        
        return $results;
    }
}