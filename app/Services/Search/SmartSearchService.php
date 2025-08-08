<?php

namespace App\Services\Search;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SmartSearchService
{
    /**
     * Search patterns and their interpretations
     */
    private const SEARCH_PATTERNS = [
        // Phone number patterns
        '/^(\+?[0-9\s\-\(\)]+)$/' => 'phone',
        
        // Date patterns
        '/\b(heute|today)\b/i' => 'date:today',
        '/\b(gestern|yesterday)\b/i' => 'date:yesterday',
        '/\b(diese woche|this week)\b/i' => 'date:this_week',
        '/\b(letzte woche|last week)\b/i' => 'date:last_week',
        '/\b(\d{1,2})\.(\d{1,2})\.(\d{4})\b/' => 'date:dmy',
        '/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/' => 'date:ymd',
        
        // Duration patterns
        '/\b(\d+)\s*(min|minute|minuten)/i' => 'duration:minutes',
        '/\b(\d+)\s*(sec|second|sekunde|sekunden)/i' => 'duration:seconds',
        '/\b(kurz|short)\b/i' => 'duration:short',
        '/\b(lang|long)\b/i' => 'duration:long',
        
        // Status patterns
        '/\b(erfolg|success|erfolgreich)\b/i' => 'status:success',
        '/\b(fehler|failed|fehlgeschlagen)\b/i' => 'status:failed',
        '/\b(termin|appointment|buchung)\b/i' => 'appointment:yes',
        '/\b(kein termin|no appointment|ohne termin)\b/i' => 'appointment:no',
        
        // Sentiment patterns
        '/\b(positiv|positive|gut|good|zufrieden|happy)\b/i' => 'sentiment:positive',
        '/\b(negativ|negative|schlecht|bad|unzufrieden|unhappy)\b/i' => 'sentiment:negative',
        '/\b(neutral)\b/i' => 'sentiment:neutral',
        
        // Cost patterns
        '/\b(\d+(?:\.\d{2})?)\s*(?:€|eur|euro)/i' => 'cost:amount',
        '/\b(teuer|expensive|kostspielig)\b/i' => 'cost:high',
        '/\b(günstig|cheap|billig)\b/i' => 'cost:low',
    ];
    
    /**
     * Natural language processors
     */
    private array $processors = [];
    
    public function __construct()
    {
        $this->initializeProcessors();
    }
    
    /**
     * Perform smart search
     */
    public function search(string $query, int $companyId): array
    {
        // Parse the search query
        $parsedQuery = $this->parseQuery($query);
        
        // Build search results
        $results = [
            'calls' => $this->searchCalls($parsedQuery, $companyId),
            'customers' => $this->searchCustomers($parsedQuery, $companyId),
            'insights' => $this->generateInsights($parsedQuery, $companyId),
            'suggestions' => $this->generateSuggestions($query, $companyId),
            'interpretation' => $parsedQuery['interpretation'],
        ];
        
        return $results;
    }
    
    /**
     * Parse search query using patterns and NLP
     */
    protected function parseQuery(string $query): array
    {
        $parsed = [
            'original' => $query,
            'tokens' => [],
            'filters' => [],
            'interpretation' => '',
            'confidence' => 0,
        ];
        
        // Tokenize and clean query
        $cleanQuery = trim(strtolower($query));
        $tokens = preg_split('/\s+/', $cleanQuery);
        $parsed['tokens'] = $tokens;
        
        // Apply pattern matching
        foreach (self::SEARCH_PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $query, $matches)) {
                $parsed['filters'][] = $this->processPattern($type, $matches, $query);
            }
        }
        
        // Apply natural language processing
        foreach ($this->processors as $processor) {
            $result = $processor($query, $parsed);
            if ($result) {
                $parsed = array_merge($parsed, $result);
            }
        }
        
        // Generate interpretation
        $parsed['interpretation'] = $this->generateInterpretation($parsed);
        
        // Calculate confidence score
        $parsed['confidence'] = $this->calculateConfidence($parsed);
        
        return $parsed;
    }
    
    /**
     * Process matched pattern
     */
    protected function processPattern(string $type, array $matches, string $query): array
    {
        [$category, $value] = explode(':', $type, 2);
        
        switch ($category) {
            case 'phone':
                return [
                    'type' => 'phone',
                    'value' => preg_replace('/[^0-9+]/', '', $matches[0]),
                ];
                
            case 'date':
                return [
                    'type' => 'date',
                    'value' => $this->parseDateValue($value, $matches),
                ];
                
            case 'duration':
                return [
                    'type' => 'duration',
                    'value' => $this->parseDurationValue($value, $matches),
                ];
                
            case 'status':
                return [
                    'type' => 'status',
                    'value' => $value === 'success' ? 'completed' : 'failed',
                ];
                
            case 'appointment':
                return [
                    'type' => 'appointment',
                    'value' => $value === 'yes',
                ];
                
            case 'sentiment':
                return [
                    'type' => 'sentiment',
                    'value' => $value,
                ];
                
            case 'cost':
                return [
                    'type' => 'cost',
                    'value' => $this->parseCostValue($value, $matches),
                ];
                
            default:
                return [
                    'type' => 'unknown',
                    'value' => $matches[0],
                ];
        }
    }
    
    /**
     * Search calls based on parsed query
     */
    protected function searchCalls(array $parsedQuery, int $companyId): Collection
    {
        $query = Call::where('company_id', $companyId);
        
        // Apply filters from parsed query
        foreach ($parsedQuery['filters'] as $filter) {
            switch ($filter['type']) {
                case 'phone':
                    $query->where('phone_number', 'like', '%' . $filter['value'] . '%');
                    break;
                    
                case 'date':
                    $this->applyDateFilter($query, $filter['value']);
                    break;
                    
                case 'duration':
                    $this->applyDurationFilter($query, $filter['value']);
                    break;
                    
                case 'status':
                    $query->where('status', $filter['value']);
                    break;
                    
                case 'appointment':
                    $query->where('appointment_made', $filter['value'] ? 1 : 0);
                    break;
                    
                case 'sentiment':
                    $query->where('sentiment', $filter['value']);
                    break;
                    
                case 'cost':
                    $this->applyCostFilter($query, $filter['value']);
                    break;
            }
        }
        
        // If no specific filters, perform full-text search
        if (empty($parsedQuery['filters'])) {
            $searchTerm = $parsedQuery['original'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('call_id', 'like', "%{$searchTerm}%")
                  ->orWhere('phone_number', 'like', "%{$searchTerm}%")
                  ->orWhere('transcript', 'like', "%{$searchTerm}%")
                  ->orWhere('call_summary', 'like', "%{$searchTerm}%");
            });
        }
        
        return $query->with(['customer', 'appointment'])
                     ->orderBy('created_at', 'desc')
                     ->limit(50)
                     ->get();
    }
    
    /**
     * Search customers based on parsed query
     */
    protected function searchCustomers(array $parsedQuery, int $companyId): Collection
    {
        $query = Customer::where('company_id', $companyId);
        
        // Look for phone numbers in filters
        foreach ($parsedQuery['filters'] as $filter) {
            if ($filter['type'] === 'phone') {
                $query->where('phone', 'like', '%' . $filter['value'] . '%');
            }
        }
        
        // If no phone filter, search by general term
        if (empty(array_filter($parsedQuery['filters'], fn($f) => $f['type'] === 'phone'))) {
            $searchTerm = $parsedQuery['original'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        }
        
        return $query->withCount('calls')
                     ->orderBy('calls_count', 'desc')
                     ->limit(20)
                     ->get();
    }
    
    /**
     * Generate insights based on search
     */
    protected function generateInsights(array $parsedQuery, int $companyId): array
    {
        $insights = [];
        
        // Date-based insights
        foreach ($parsedQuery['filters'] as $filter) {
            if ($filter['type'] === 'date') {
                $dateInsight = $this->generateDateInsight($filter['value'], $companyId);
                if ($dateInsight) {
                    $insights[] = $dateInsight;
                }
            }
        }
        
        // Pattern insights
        if (empty($parsedQuery['filters'])) {
            // Search for patterns in the query
            $patterns = $this->detectPatterns($parsedQuery['original'], $companyId);
            foreach ($patterns as $pattern) {
                $insights[] = $pattern;
            }
        }
        
        return $insights;
    }
    
    /**
     * Generate search suggestions
     */
    protected function generateSuggestions(string $query, int $companyId): array
    {
        $suggestions = [];
        
        // Suggest completions
        if (strlen($query) >= 3) {
            // Suggest customer names
            $customers = Customer::where('company_id', $companyId)
                ->where('name', 'like', $query . '%')
                ->limit(5)
                ->pluck('name')
                ->toArray();
            
            foreach ($customers as $name) {
                $suggestions[] = [
                    'type' => 'customer',
                    'value' => $name,
                    'display' => "Kunde: {$name}",
                ];
            }
            
            // Suggest common searches
            $commonSearches = $this->getCommonSearches($companyId);
            foreach ($commonSearches as $search) {
                if (stripos($search, $query) === 0) {
                    $suggestions[] = [
                        'type' => 'common',
                        'value' => $search,
                        'display' => $search,
                    ];
                }
            }
        }
        
        // Suggest filters
        if (empty($suggestions)) {
            $suggestions = $this->suggestFilters($query);
        }
        
        return array_slice($suggestions, 0, 10);
    }
    
    /**
     * Initialize natural language processors
     */
    protected function initializeProcessors(): void
    {
        // Time expression processor
        $this->processors[] = function ($query, $parsed) {
            if (preg_match('/vor (\d+) (tag|tage|woche|wochen|monat|monate)/i', $query, $matches)) {
                $amount = (int)$matches[1];
                $unit = strtolower($matches[2]);
                
                $date = Carbon::now();
                switch ($unit) {
                    case 'tag':
                    case 'tage':
                        $date->subDays($amount);
                        break;
                    case 'woche':
                    case 'wochen':
                        $date->subWeeks($amount);
                        break;
                    case 'monat':
                    case 'monate':
                        $date->subMonths($amount);
                        break;
                }
                
                return [
                    'filters' => array_merge($parsed['filters'], [[
                        'type' => 'date',
                        'value' => ['from' => $date->format('Y-m-d')],
                    ]]),
                ];
            }
            return null;
        };
        
        // Complex query processor
        $this->processors[] = function ($query, $parsed) {
            // Handle "und" / "oder" logic
            if (preg_match('/(.*)\s+(und|oder|and|or)\s+(.*)/i', $query, $matches)) {
                return [
                    'logic' => strtolower($matches[2]) === 'und' || strtolower($matches[2]) === 'and' ? 'AND' : 'OR',
                    'parts' => [trim($matches[1]), trim($matches[3])],
                ];
            }
            return null;
        };
    }
    
    /**
     * Parse date value from pattern match
     */
    protected function parseDateValue(string $type, array $matches): array
    {
        switch ($type) {
            case 'today':
                return ['exact' => Carbon::today()];
            case 'yesterday':
                return ['exact' => Carbon::yesterday()];
            case 'this_week':
                return [
                    'from' => Carbon::now()->startOfWeek(),
                    'to' => Carbon::now()->endOfWeek(),
                ];
            case 'last_week':
                return [
                    'from' => Carbon::now()->subWeek()->startOfWeek(),
                    'to' => Carbon::now()->subWeek()->endOfWeek(),
                ];
            case 'dmy':
                return ['exact' => Carbon::createFromFormat('d.m.Y', $matches[0])];
            case 'ymd':
                return ['exact' => Carbon::createFromFormat('Y-m-d', $matches[0])];
            default:
                return [];
        }
    }
    
    /**
     * Parse duration value from pattern match
     */
    protected function parseDurationValue(string $type, array $matches): array
    {
        switch ($type) {
            case 'minutes':
                return ['seconds' => ((int)$matches[1]) * 60];
            case 'seconds':
                return ['seconds' => (int)$matches[1]];
            case 'short':
                return ['max' => 60];
            case 'long':
                return ['min' => 300];
            default:
                return [];
        }
    }
    
    /**
     * Parse cost value from pattern match
     */
    protected function parseCostValue(string $type, array $matches): array
    {
        switch ($type) {
            case 'amount':
                return ['exact' => (float)$matches[1]];
            case 'high':
                return ['min' => 10.0];
            case 'low':
                return ['max' => 2.0];
            default:
                return [];
        }
    }
    
    /**
     * Apply date filter to query
     */
    protected function applyDateFilter($query, array $value): void
    {
        if (isset($value['exact'])) {
            $query->whereDate('created_at', $value['exact']);
        } elseif (isset($value['from']) && isset($value['to'])) {
            $query->whereBetween('created_at', [$value['from'], $value['to']]);
        } elseif (isset($value['from'])) {
            $query->where('created_at', '>=', $value['from']);
        } elseif (isset($value['to'])) {
            $query->where('created_at', '<=', $value['to']);
        }
    }
    
    /**
     * Apply duration filter to query
     */
    protected function applyDurationFilter($query, array $value): void
    {
        if (isset($value['seconds'])) {
            $query->where('duration_sec', $value['seconds']);
        } elseif (isset($value['min']) && isset($value['max'])) {
            $query->whereBetween('duration_sec', [$value['min'], $value['max']]);
        } elseif (isset($value['min'])) {
            $query->where('duration_sec', '>=', $value['min']);
        } elseif (isset($value['max'])) {
            $query->where('duration_sec', '<=', $value['max']);
        }
    }
    
    /**
     * Apply cost filter to query
     */
    protected function applyCostFilter($query, array $value): void
    {
        if (isset($value['exact'])) {
            $cents = $value['exact'] * 100;
            $query->whereBetween('cost_cents', [$cents - 10, $cents + 10]);
        } elseif (isset($value['min'])) {
            $query->where('cost_cents', '>=', $value['min'] * 100);
        } elseif (isset($value['max'])) {
            $query->where('cost_cents', '<=', $value['max'] * 100);
        }
    }
    
    /**
     * Generate interpretation of parsed query
     */
    protected function generateInterpretation(array $parsed): string
    {
        $parts = [];
        
        foreach ($parsed['filters'] as $filter) {
            switch ($filter['type']) {
                case 'phone':
                    $parts[] = "Telefonnummer enthält '{$filter['value']}'";
                    break;
                case 'date':
                    if (isset($filter['value']['exact'])) {
                        $parts[] = "am " . $filter['value']['exact']->format('d.m.Y');
                    } elseif (isset($filter['value']['from']) && isset($filter['value']['to'])) {
                        $parts[] = "zwischen " . $filter['value']['from']->format('d.m.Y') . 
                                  " und " . $filter['value']['to']->format('d.m.Y');
                    }
                    break;
                case 'duration':
                    if (isset($filter['value']['seconds'])) {
                        $parts[] = "Dauer: " . ($filter['value']['seconds'] / 60) . " Minuten";
                    }
                    break;
                case 'status':
                    $parts[] = "Status: " . $filter['value'];
                    break;
                case 'appointment':
                    $parts[] = $filter['value'] ? "mit Termin" : "ohne Termin";
                    break;
                case 'sentiment':
                    $parts[] = "Stimmung: " . $filter['value'];
                    break;
            }
        }
        
        if (empty($parts)) {
            return "Suche nach '{$parsed['original']}'";
        }
        
        return "Suche nach Anrufen: " . implode(', ', $parts);
    }
    
    /**
     * Calculate confidence score for the search interpretation
     */
    protected function calculateConfidence(array $parsed): float
    {
        $confidence = 0.5; // Base confidence
        
        // Increase confidence for each recognized filter
        $confidence += count($parsed['filters']) * 0.1;
        
        // Decrease confidence if query is very short
        if (strlen($parsed['original']) < 3) {
            $confidence -= 0.2;
        }
        
        // Increase confidence for exact matches
        foreach ($parsed['filters'] as $filter) {
            if (in_array($filter['type'], ['phone', 'date', 'status'])) {
                $confidence += 0.1;
            }
        }
        
        return min(1.0, max(0.0, $confidence));
    }
    
    /**
     * Generate date-based insight
     */
    protected function generateDateInsight(array $dateValue, int $companyId): ?array
    {
        if (!isset($dateValue['exact'])) {
            return null;
        }
        
        $date = $dateValue['exact'];
        $stats = Call::where('company_id', $companyId)
            ->whereDate('created_at', $date)
            ->selectRaw('
                COUNT(*) as total,
                AVG(duration_sec) as avg_duration,
                SUM(appointment_made) as appointments
            ')
            ->first();
        
        if ($stats->total > 0) {
            return [
                'type' => 'date_stats',
                'title' => 'Statistik für ' . $date->format('d.m.Y'),
                'data' => [
                    'total_calls' => $stats->total,
                    'avg_duration' => round($stats->avg_duration / 60, 1) . ' Min',
                    'appointments' => $stats->appointments,
                    'conversion_rate' => round(($stats->appointments / $stats->total) * 100, 1) . '%',
                ],
            ];
        }
        
        return null;
    }
    
    /**
     * Detect patterns in search query
     */
    protected function detectPatterns(string $query, int $companyId): array
    {
        $patterns = [];
        
        // Check if searching for a trend
        if (stripos($query, 'trend') !== false) {
            $patterns[] = [
                'type' => 'trend',
                'title' => 'Anruftrend letzte 7 Tage',
                'data' => $this->getCallTrend($companyId, 7),
            ];
        }
        
        // Check if searching for problems
        if (preg_match('/problem|fehler|issue/i', $query)) {
            $patterns[] = [
                'type' => 'problems',
                'title' => 'Aktuelle Probleme',
                'data' => $this->detectProblems($companyId),
            ];
        }
        
        return $patterns;
    }
    
    /**
     * Get call trend data
     */
    protected function getCallTrend(int $companyId, int $days): array
    {
        $trend = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $count = Call::where('company_id', $companyId)
                ->whereDate('created_at', $date)
                ->count();
            
            $trend[] = [
                'date' => $date->format('d.m'),
                'calls' => $count,
            ];
        }
        
        return $trend;
    }
    
    /**
     * Detect problems in call data
     */
    protected function detectProblems(int $companyId): array
    {
        $problems = [];
        
        // Check for high failure rate
        $failureRate = Call::where('company_id', $companyId)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->selectRaw('AVG(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as rate')
            ->value('rate');
        
        if ($failureRate > 0.1) {
            $problems[] = [
                'severity' => 'high',
                'message' => 'Hohe Fehlerrate: ' . round($failureRate * 100, 1) . '% der Anrufe',
            ];
        }
        
        // Check for low conversion
        $conversionRate = Call::where('company_id', $companyId)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->selectRaw('AVG(appointment_made) as rate')
            ->value('rate');
        
        if ($conversionRate < 0.2) {
            $problems[] = [
                'severity' => 'medium',
                'message' => 'Niedrige Konversionsrate: ' . round($conversionRate * 100, 1) . '%',
            ];
        }
        
        return $problems;
    }
    
    /**
     * Get common searches for suggestions
     */
    protected function getCommonSearches(int $companyId): array
    {
        // This would ideally come from a search history table
        return [
            'heute',
            'gestern',
            'letzte woche',
            'lange anrufe',
            'ohne termin',
            'fehlgeschlagen',
            'positiv',
        ];
    }
    
    /**
     * Suggest filters based on partial query
     */
    protected function suggestFilters(string $query): array
    {
        $suggestions = [];
        
        $filters = [
            'heute' => 'Anrufe von heute',
            'gestern' => 'Anrufe von gestern',
            'diese woche' => 'Anrufe dieser Woche',
            'mit termin' => 'Anrufe mit Terminbuchung',
            'ohne termin' => 'Anrufe ohne Terminbuchung',
            'lange' => 'Lange Anrufe (>5 Min)',
            'kurze' => 'Kurze Anrufe (<1 Min)',
            'positiv' => 'Positive Stimmung',
            'negativ' => 'Negative Stimmung',
        ];
        
        foreach ($filters as $key => $display) {
            if (stripos($key, $query) !== false || stripos($display, $query) !== false) {
                $suggestions[] = [
                    'type' => 'filter',
                    'value' => $key,
                    'display' => $display,
                ];
            }
        }
        
        return $suggestions;
    }
}