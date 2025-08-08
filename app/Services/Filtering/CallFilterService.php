<?php

namespace App\Services\Filtering;

use App\Models\Call;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CallFilterService
{
    /**
     * Available filter types with their configurations
     */
    private const FILTER_CONFIGS = [
        'date_range' => [
            'type' => 'daterange',
            'label' => 'Zeitraum',
            'operators' => ['between', 'before', 'after', 'exact'],
        ],
        'status' => [
            'type' => 'select',
            'label' => 'Status',
            'multiple' => true,
        ],
        'customer' => [
            'type' => 'search',
            'label' => 'Kunde',
            'searchable' => true,
        ],
        'duration' => [
            'type' => 'range',
            'label' => 'Anrufdauer',
            'unit' => 'seconds',
        ],
        'sentiment' => [
            'type' => 'select',
            'label' => 'Stimmung',
            'options' => ['positive', 'neutral', 'negative'],
        ],
        'cost' => [
            'type' => 'range',
            'label' => 'Kosten',
            'unit' => 'EUR',
        ],
        'appointment' => [
            'type' => 'boolean',
            'label' => 'Termin gebucht',
        ],
        'branch' => [
            'type' => 'select',
            'label' => 'Filiale',
            'relationship' => true,
        ],
        'staff' => [
            'type' => 'select',
            'label' => 'Mitarbeiter',
            'relationship' => true,
        ],
        'transcript' => [
            'type' => 'fulltext',
            'label' => 'Transkript-Suche',
        ],
        'tags' => [
            'type' => 'tags',
            'label' => 'Tags',
            'multiple' => true,
        ],
        'phone_number' => [
            'type' => 'text',
            'label' => 'Telefonnummer',
        ],
        'outcome' => [
            'type' => 'select',
            'label' => 'Ergebnis',
            'options' => ['completed', 'voicemail', 'busy', 'no_answer', 'failed'],
        ],
    ];
    
    /**
     * Apply filters to query builder
     */
    public function apply(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if (empty($value) && $value !== '0' && $value !== 0) {
                continue;
            }
            
            $method = 'apply' . str_replace('_', '', ucwords($key, '_')) . 'Filter';
            
            if (method_exists($this, $method)) {
                $this->$method($query, $value);
            } else {
                $this->applyGenericFilter($query, $key, $value);
            }
        }
        
        return $query;
    }
    
    /**
     * Apply date range filter
     */
    protected function applyDateRangeFilter(Builder $query, $value): void
    {
        if (is_array($value)) {
            if (isset($value['from']) && $value['from']) {
                $query->where('created_at', '>=', Carbon::parse($value['from'])->startOfDay());
            }
            if (isset($value['to']) && $value['to']) {
                $query->where('created_at', '<=', Carbon::parse($value['to'])->endOfDay());
            }
        } elseif (is_string($value)) {
            // Handle preset ranges
            switch ($value) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', Carbon::yesterday());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ]);
                    break;
                case 'last_week':
                    $query->whereBetween('created_at', [
                        Carbon::now()->subWeek()->startOfWeek(),
                        Carbon::now()->subWeek()->endOfWeek()
                    ]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('created_at', Carbon::now()->subMonth()->month)
                          ->whereYear('created_at', Carbon::now()->subMonth()->year);
                    break;
                case 'last_7_days':
                    $query->where('created_at', '>=', Carbon::now()->subDays(7));
                    break;
                case 'last_30_days':
                    $query->where('created_at', '>=', Carbon::now()->subDays(30));
                    break;
                case 'last_90_days':
                    $query->where('created_at', '>=', Carbon::now()->subDays(90));
                    break;
            }
        }
    }
    
    /**
     * Apply status filter
     */
    protected function applyStatusFilter(Builder $query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('status', $value);
        } else {
            $query->where('status', $value);
        }
    }
    
    /**
     * Apply customer filter
     */
    protected function applyCustomerFilter(Builder $query, $value): void
    {
        $query->whereHas('customer', function ($q) use ($value) {
            $q->where(function ($subQ) use ($value) {
                $subQ->where('name', 'like', "%{$value}%")
                     ->orWhere('email', 'like', "%{$value}%")
                     ->orWhere('phone', 'like', "%{$value}%");
            });
        });
    }
    
    /**
     * Apply duration filter
     */
    protected function applyDurationFilter(Builder $query, $value): void
    {
        if (is_array($value)) {
            if (isset($value['min'])) {
                $query->where('duration_sec', '>=', $value['min']);
            }
            if (isset($value['max'])) {
                $query->where('duration_sec', '<=', $value['max']);
            }
        } elseif (is_string($value)) {
            // Handle preset ranges
            switch ($value) {
                case 'short': // < 1 minute
                    $query->where('duration_sec', '<', 60);
                    break;
                case 'medium': // 1-5 minutes
                    $query->whereBetween('duration_sec', [60, 300]);
                    break;
                case 'long': // 5-15 minutes
                    $query->whereBetween('duration_sec', [300, 900]);
                    break;
                case 'very_long': // > 15 minutes
                    $query->where('duration_sec', '>', 900);
                    break;
            }
        }
    }
    
    /**
     * Apply sentiment filter
     */
    protected function applySentimentFilter(Builder $query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('sentiment', $value);
        } else {
            $query->where('sentiment', $value);
        }
    }
    
    /**
     * Apply cost filter
     */
    protected function applyCostFilter(Builder $query, $value): void
    {
        if (is_array($value)) {
            if (isset($value['min'])) {
                $query->where('cost_cents', '>=', $value['min'] * 100);
            }
            if (isset($value['max'])) {
                $query->where('cost_cents', '<=', $value['max'] * 100);
            }
        }
    }
    
    /**
     * Apply appointment filter
     */
    protected function applyAppointmentFilter(Builder $query, $value): void
    {
        if ($value === true || $value === 'true' || $value === '1' || $value === 1) {
            $query->where('appointment_made', 1);
        } elseif ($value === false || $value === 'false' || $value === '0' || $value === 0) {
            $query->where('appointment_made', 0);
        }
    }
    
    /**
     * Apply branch filter
     */
    protected function applyBranchFilter(Builder $query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('branch_id', $value);
        } else {
            $query->where('branch_id', $value);
        }
    }
    
    /**
     * Apply staff filter
     */
    protected function applyStaffFilter(Builder $query, $value): void
    {
        $query->whereHas('appointment', function ($q) use ($value) {
            if (is_array($value)) {
                $q->whereIn('staff_id', $value);
            } else {
                $q->where('staff_id', $value);
            }
        });
    }
    
    /**
     * Apply transcript search filter
     */
    protected function applyTranscriptFilter(Builder $query, $value): void
    {
        // Use FULLTEXT search if available
        if ($this->hasFulltextIndex('calls', 'transcript')) {
            $query->whereRaw("MATCH(transcript, call_summary) AGAINST(? IN BOOLEAN MODE)", [$value]);
        } else {
            // Fallback to LIKE search
            $query->where(function ($q) use ($value) {
                $q->where('transcript', 'like', "%{$value}%")
                  ->orWhere('call_summary', 'like', "%{$value}%");
            });
        }
    }
    
    /**
     * Apply tags filter
     */
    protected function applyTagsFilter(Builder $query, $value): void
    {
        $tags = is_array($value) ? $value : [$value];
        
        foreach ($tags as $tag) {
            $query->whereJsonContains('tags', $tag);
        }
    }
    
    /**
     * Apply phone number filter
     */
    protected function applyPhoneNumberFilter(Builder $query, $value): void
    {
        // Clean phone number for search
        $cleaned = preg_replace('/[^0-9+]/', '', $value);
        
        $query->where(function ($q) use ($value, $cleaned) {
            $q->where('phone_number', 'like', "%{$value}%")
              ->orWhere('phone_number', 'like', "%{$cleaned}%");
        });
    }
    
    /**
     * Apply outcome filter
     */
    protected function applyOutcomeFilter(Builder $query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('session_outcome', $value);
        } else {
            $query->where('session_outcome', $value);
        }
    }
    
    /**
     * Apply generic filter
     */
    protected function applyGenericFilter(Builder $query, string $key, $value): void
    {
        // Check if the column exists
        if ($this->columnExists('calls', $key)) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }
    }
    
    /**
     * Get saved filter presets for a user
     */
    public function getSavedFilters(int $userId): array
    {
        return DB::table('user_filter_presets')
            ->where('user_id', $userId)
            ->where('resource', 'calls')
            ->orderBy('name')
            ->get()
            ->map(function ($preset) {
                return [
                    'id' => $preset->id,
                    'name' => $preset->name,
                    'filters' => json_decode($preset->filters, true),
                    'is_default' => $preset->is_default,
                ];
            })
            ->toArray();
    }
    
    /**
     * Save filter preset
     */
    public function saveFilterPreset(int $userId, string $name, array $filters, bool $isDefault = false): int
    {
        // If setting as default, unset other defaults
        if ($isDefault) {
            DB::table('user_filter_presets')
                ->where('user_id', $userId)
                ->where('resource', 'calls')
                ->update(['is_default' => false]);
        }
        
        return DB::table('user_filter_presets')->insertGetId([
            'user_id' => $userId,
            'resource' => 'calls',
            'name' => $name,
            'filters' => json_encode($filters),
            'is_default' => $isDefault,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    /**
     * Delete filter preset
     */
    public function deleteFilterPreset(int $presetId, int $userId): bool
    {
        return DB::table('user_filter_presets')
            ->where('id', $presetId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }
    
    /**
     * Get filter suggestions based on data
     */
    public function getFilterSuggestions(int $companyId): array
    {
        $suggestions = [];
        
        // Suggest filters based on data patterns
        $recentCalls = Call::where('company_id', $companyId)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
        
        if ($recentCalls > 100) {
            $suggestions[] = [
                'name' => 'Lange Gespräche diese Woche',
                'filters' => [
                    'date_range' => 'last_7_days',
                    'duration' => 'long',
                ],
                'description' => 'Zeigt alle langen Gespräche der letzten 7 Tage',
            ];
        }
        
        // Check for failed calls
        $failedCount = Call::where('company_id', $companyId)
            ->where('status', 'failed')
            ->where('created_at', '>=', Carbon::now()->subDays(1))
            ->count();
        
        if ($failedCount > 0) {
            $suggestions[] = [
                'name' => 'Fehlgeschlagene Anrufe heute',
                'filters' => [
                    'date_range' => 'today',
                    'status' => 'failed',
                ],
                'description' => "Es gibt {$failedCount} fehlgeschlagene Anrufe heute",
            ];
        }
        
        // Check for appointments
        $appointmentRate = Call::where('company_id', $companyId)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->selectRaw('AVG(appointment_made) as rate')
            ->value('rate');
        
        if ($appointmentRate < 0.3) {
            $suggestions[] = [
                'name' => 'Anrufe ohne Terminbuchung',
                'filters' => [
                    'date_range' => 'last_7_days',
                    'appointment' => false,
                ],
                'description' => 'Niedrige Terminbuchungsrate erkannt - Analyse empfohlen',
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Check if column exists
     */
    private function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }
    
    /**
     * Check if fulltext index exists
     */
    private function hasFulltextIndex(string $table, string $column): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Index_type = 'FULLTEXT'");
        
        foreach ($indexes as $index) {
            if ($index->Column_name === $column) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get filter configuration
     */
    public function getFilterConfig(): array
    {
        return self::FILTER_CONFIGS;
    }
    
    /**
     * Export filters to shareable format
     */
    public function exportFilters(array $filters): string
    {
        return base64_encode(json_encode($filters));
    }
    
    /**
     * Import filters from shareable format
     */
    public function importFilters(string $encoded): array
    {
        try {
            return json_decode(base64_decode($encoded), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}