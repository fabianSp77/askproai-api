<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Str;

class ImprovedEventTypeNameParser extends EventTypeNameParser
{
    /**
     * Extract a clean service name from a marketing-style event type name
     * 
     * @param string $eventTypeName
     * @param Branch|null $branch
     * @param Company|null $company
     * @return string
     */
    public function extractCleanServiceName(string $eventTypeName, ?Branch $branch = null, ?Company $company = null): string
    {
        $serviceName = $eventTypeName;
        
        // Remove company and branch names if present
        if ($company) {
            $serviceName = str_ireplace($company->name, '', $serviceName);
        }
        if ($branch) {
            $serviceName = str_ireplace($branch->name, '', $serviceName);
            // Also remove common location phrases
            $serviceName = preg_replace('/\baus\s+' . preg_quote($branch->name, '/') . '\b/i', '', $serviceName);
        }
        
        // Remove marketing fluff and symbols
        $patterns = [
            '/\s*\+\s*/' => ' ',                    // Replace + with space
            '/\s*–\s*/' => ' ',                     // Replace en-dash with space
            '/\s*-\s*/' => ' ',                     // Replace hyphen with space
            '/\b\d+%\s*mehr\s*[^\s]+/i' => '',      // Remove "X% mehr [word]"
            '/\bfür Sie\b/i' => '',                 // Remove "für Sie"
            '/\bfür Ihren?\s+\w+\b/i' => '',        // Remove "für Ihren/Ihre [word]"
            '/\b24\/7\b/' => '',                    // Remove "24/7"
            '/\bbesten?\s+\w+\s*\w*/i' => '',       // Remove "besten/beste [word] [word]"
            '/\bund\s+besten?\s+\w+\s*\w*/i' => '', // Remove "und besten [word] [word]"
            '/\baus\s+einer\s+Hand\b/i' => '',      // Remove "aus einer Hand"
            '/\bAlles\s+aus\s+\w+\s+\w+\b/i' => '', // Remove "Alles aus [word] [word]"
            '/\s+/' => ' ',                         // Collapse multiple spaces
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $serviceName = preg_replace($pattern, $replacement, $serviceName);
        }
        
        // Trim and clean up
        $serviceName = trim($serviceName);
        
        // If we ended up with an empty or very short name, try to extract key terms
        if (strlen($serviceName) < 5) {
            $serviceName = $this->extractKeyTerms($eventTypeName);
        }
        
        // If still too long, truncate intelligently
        if (strlen($serviceName) > 50) {
            $serviceName = $this->truncateIntelligently($serviceName, 50);
        }
        
        return $serviceName ?: 'Standardtermin';
    }
    
    /**
     * Extract key terms from a marketing text
     */
    private function extractKeyTerms(string $text): string
    {
        // Look for common service keywords
        $keywords = [
            'Beratung', 'Termin', 'Gespräch', 'Konsultation', 'Service',
            'Behandlung', 'Sitzung', 'Meeting', 'Besprechung', 'Coaching'
        ];
        
        foreach ($keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                // Extract context around the keyword
                if (preg_match('/(\w+\s+)?' . preg_quote($keyword, '/') . '(\s+\w+)?/i', $text, $matches)) {
                    return trim($matches[0]);
                }
            }
        }
        
        // Look for duration patterns
        if (preg_match('/\b(\d+)\s*(min|minuten|stunden?|h)\b/i', $text, $matches)) {
            return $matches[0] . ' Termin';
        }
        
        // Fallback: first meaningful words
        $words = preg_split('/\s+/', $text);
        $meaningful = array_filter($words, function($word) {
            return strlen($word) > 3 && !in_array(strtolower($word), ['und', 'oder', 'für', 'mit', 'aus']);
        });
        
        return implode(' ', array_slice($meaningful, 0, 3));
    }
    
    /**
     * Truncate a string intelligently at word boundaries
     */
    private function truncateIntelligently(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        
        // Try to cut at word boundary
        $truncated = substr($text, 0, $maxLength);
        $lastSpace = strrpos($truncated, ' ');
        
        if ($lastSpace !== false && $lastSpace > $maxLength * 0.6) {
            return substr($truncated, 0, $lastSpace);
        }
        
        return $truncated;
    }
    
    /**
     * Improved analyze method that handles marketing-style names better
     */
    public function analyzeEventTypesForImport(array $eventTypes, Branch $targetBranch): array
    {
        $results = [];
        
        foreach ($eventTypes as $eventType) {
            $eventTypeName = $eventType['title'] ?? $eventType['name'] ?? '';
            
            // First try standard parsing
            $parsed = $this->parseEventTypeName($eventTypeName);
            
            if ($parsed['success']) {
                // Standard schema worked
                $matchesBranch = $this->validateBranchMatch($parsed['branch_name'], $targetBranch);
                $serviceName = $parsed['service_name'];
                $suggestedAction = $matchesBranch ? 'import' : 'skip';
                $warning = !$matchesBranch ? 'Event-Type gehört zu anderer Filiale' : null;
            } else {
                // Extract clean service name from marketing text
                $serviceName = $this->extractCleanServiceName(
                    $eventTypeName, 
                    $targetBranch, 
                    $targetBranch->company
                );
                $suggestedAction = 'manual';
                $warning = 'Name wurde aus Marketingtext extrahiert - bitte überprüfen';
            }
            
            $results[] = [
                'original' => $eventType,
                'parsed' => $parsed,
                'matches_branch' => $parsed['success'] ? $this->validateBranchMatch($parsed['branch_name'], $targetBranch) : true,
                'suggested_action' => $suggestedAction,
                'warning' => $warning,
                'suggested_name' => $this->generateEventTypeName($targetBranch, $serviceName),
                'extracted_service_name' => $serviceName
            ];
        }
        
        return $results;
    }
    
    /**
     * Generate a standardized event type name with optional format
     */
    public function generateEventTypeName(Branch $branch, string $serviceName, string $format = 'standard'): string
    {
        $company = $branch->company;
        
        // Clean names
        $branchName = $this->cleanNameForSchema($branch->name);
        $companyName = $this->cleanNameForSchema($company->name);
        $serviceName = $this->cleanNameForSchema($serviceName);
        
        switch ($format) {
            case 'compact':
                // More compact format for UI
                return "{$branchName} - {$serviceName}";
                
            case 'full':
                // Full format with company
                return "{$companyName} {$branchName} - {$serviceName}";
                
            case 'service_first':
                // Service-first format
                return "{$serviceName} ({$branchName})";
                
            case 'standard':
            default:
                // Standard format for compatibility
                return "{$branchName}-{$companyName}-{$serviceName}";
        }
    }
}