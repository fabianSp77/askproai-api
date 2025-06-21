<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Str;

class EventTypeNameParser
{
    /**
     * Parse einen Event-Type Namen nach dem Schema: "Filial-Unternehmen-Dienstleistung"
     * 
     * @param string $eventTypeName
     * @return array
     */
    public function parseEventTypeName(string $eventTypeName): array
    {
        // Grundlegendes Parsing mit Bindestrich als Trenner
        $parts = explode('-', $eventTypeName, 3);
        
        if (count($parts) < 3) {
            return [
                'success' => false,
                'branch_name' => null,
                'company_name' => null,
                'service_name' => null,
                'original_name' => $eventTypeName,
                'error' => 'Name entspricht nicht dem Schema "Filial-Unternehmen-Dienstleistung"'
            ];
        }
        
        return [
            'success' => true,
            'branch_name' => trim($parts[0]),
            'company_name' => trim($parts[1]),
            'service_name' => trim($parts[2]),
            'original_name' => $eventTypeName,
            'error' => null
        ];
    }
    
    /**
     * Validiere ob der geparste Filialname zur ausgewählten Filiale passt
     * 
     * @param string $parsedBranchName
     * @param Branch $selectedBranch
     * @return bool
     */
    public function validateBranchMatch(string $parsedBranchName, Branch $selectedBranch): bool
    {
        // Normalisiere beide Namen für besseren Vergleich
        $normalizedParsed = $this->normalizeString($parsedBranchName);
        $normalizedBranch = $this->normalizeString($selectedBranch->name);
        
        // Exakter Match
        if ($normalizedParsed === $normalizedBranch) {
            return true;
        }
        
        // Teilstring-Match (z.B. "Berlin Mitte" vs "Mitte")
        if (str_contains($normalizedBranch, $normalizedParsed) || 
            str_contains($normalizedParsed, $normalizedBranch)) {
            return true;
        }
        
        // Ähnlichkeitscheck (80% Übereinstimmung)
        similar_text($normalizedParsed, $normalizedBranch, $percent);
        if ($percent >= 80) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Generiere den korrekten Event-Type Namen nach Schema
     * 
     * @param Branch $branch
     * @param string $serviceName
     * @return string
     */
    public function generateEventTypeName(Branch $branch, string $serviceName): string
    {
        $company = $branch->company;
        
        // Bereinige Namen von Sonderzeichen die Probleme machen könnten
        $branchName = $this->cleanNameForSchema($branch->name);
        $companyName = $this->cleanNameForSchema($company->name);
        $serviceName = $this->cleanNameForSchema($serviceName);
        
        return "{$branchName}-{$companyName}-{$serviceName}";
    }
    
    /**
     * Analysiere eine Liste von Event-Types und schlage Zuordnungen vor
     * 
     * @param array $eventTypes
     * @param Branch $targetBranch
     * @return array
     */
    public function analyzeEventTypesForImport(array $eventTypes, Branch $targetBranch): array
    {
        $results = [];
        
        foreach ($eventTypes as $eventType) {
            $parsed = $this->parseEventTypeName($eventType['title'] ?? $eventType['name']);
            
            if ($parsed['success']) {
                $matchesBranch = $this->validateBranchMatch($parsed['branch_name'], $targetBranch);
                
                $results[] = [
                    'original' => $eventType,
                    'parsed' => $parsed,
                    'matches_branch' => $matchesBranch,
                    'suggested_action' => $matchesBranch ? 'import' : 'skip',
                    'warning' => !$matchesBranch ? 'Event-Type gehört zu anderer Filiale' : null,
                    'suggested_name' => $this->generateEventTypeName($targetBranch, $parsed['service_name'])
                ];
            } else {
                // Name konnte nicht geparst werden
                $results[] = [
                    'original' => $eventType,
                    'parsed' => $parsed,
                    'matches_branch' => false,
                    'suggested_action' => 'manual',
                    'warning' => 'Name entspricht nicht dem erwarteten Schema',
                    'suggested_name' => $this->generateEventTypeName(
                        $targetBranch, 
                        $this->extractServiceName($eventType['title'] ?? $eventType['name'])
                    )
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Normalisiere String für Vergleiche
     */
    private function normalizeString(string $str): string
    {
        // Kleinschreibung, keine Sonderzeichen, keine Mehrfach-Leerzeichen
        $str = mb_strtolower($str);
        $str = preg_replace('/[^a-z0-9äöüß\s]/u', '', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        return trim($str);
    }
    
    /**
     * Bereinige Namen für Schema-Verwendung
     */
    protected function cleanNameForSchema(string $name): string
    {
        // Entferne Bindestriche da sie als Trenner verwendet werden
        $name = str_replace('-', ' ', $name);
        // Entferne Sonderzeichen außer Buchstaben, Zahlen und Leerzeichen
        $name = preg_replace('/[^a-zA-Z0-9äöüßÄÖÜ\s]/u', '', $name);
        // Ersetze mehrfache Leerzeichen
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }
    
    /**
     * Extrahiere nur den Service-Namen aus einem vollständigen Event-Type Namen
     */
    public function extractServiceName(string $eventTypeName): string
    {
        // Entferne Marketing-Phrasen und extrahiere den Kern-Service-Namen
        $name = $eventTypeName;
        
        // Entferne bekannte Marketing-Phrasen
        $marketingPhrases = [
            '/\d+%\s*mehr\s*Umsatz[^,]*/',
            '/für Sie und.*/',
            '/besten\s*Kundenservice\s*24\/7/',
            '/24\/7/',
            '/und besten.*/',
            '/\+\s*aus\s*\w+/',  // Remove "+ aus Berlin" etc.
        ];
        
        foreach ($marketingPhrases as $pattern) {
            $name = preg_replace($pattern, '', $name);
        }
        
        // Wenn der Name ein + enthält, nimm nur den Teil nach dem letzten +
        if (strpos($name, '+') !== false) {
            $parts = explode('+', $name);
            // Suche nach dem Teil, der am ehesten ein Service-Name ist
            foreach (array_reverse($parts) as $part) {
                $cleaned = trim($part);
                if (strlen($cleaned) > 3 && !$this->isCompanyOrLocationName($cleaned)) {
                    $name = $cleaned;
                    break;
                }
            }
        }
        
        // Bereinige den Namen
        $name = $this->cleanNameForSchema($name);
        
        // Wenn der Name zu lang ist, kürze ihn
        if (strlen($name) > 50) {
            $words = explode(' ', $name);
            $name = implode(' ', array_slice($words, 0, 5));
        }
        
        return $name ?: 'Service';
    }
    
    /**
     * Prüfe ob ein String ein Firmen- oder Ortsname ist
     */
    private function isCompanyOrLocationName(string $name): bool
    {
        $normalized = $this->normalizeString($name);
        
        // Liste bekannter Firmen-/Ortsnamen
        $knownNames = [
            'askproai', 'berlin', 'münchen', 'hamburg', 'köln', 'frankfurt',
            'stuttgart', 'deutschland', 'germany', 'gmbh', 'ag', 'ug', 'e.k.',
            'salon', 'praxis', 'klinik', 'zentrum'
        ];
        
        foreach ($knownNames as $known) {
            if (strpos($normalized, $known) !== false) {
                return true;
            }
        }
        
        return false;
    }
}