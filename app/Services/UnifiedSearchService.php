<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Staff;
use App\Models\SearchHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UnifiedSearchService
{
    protected array $searchableModels = [
        'customers' => [
            'model' => Customer::class,
            'fields' => ['name', 'email', 'phone', 'notes'],
            'icon' => 'heroicon-o-user',
            'weight' => 10,
            'route' => 'filament.admin.resources.customers.view',
            'title_field' => 'name',
            'subtitle_field' => 'phone',
        ],
        'appointments' => [
            'model' => Appointment::class,
            'fields' => ['customer.name', 'staff.name', 'service.name'],
            'icon' => 'heroicon-o-calendar',
            'weight' => 8,
            'route' => 'filament.admin.resources.appointments.view',
            'title_field' => 'customer.name',
            'subtitle_field' => 'starts_at',
        ],
        'calls' => [
            'model' => Call::class,
            'fields' => ['phone_number', 'summary', 'transcript'],
            'icon' => 'heroicon-o-phone',
            'weight' => 7,
            'route' => 'filament.admin.resources.calls.view',
            'title_field' => 'phone_number',
            'subtitle_field' => 'created_at',
        ],
        'staff' => [
            'model' => Staff::class,
            'fields' => ['name', 'email', 'phone', 'specializations'],
            'icon' => 'heroicon-o-user-group',
            'weight' => 6,
            'route' => 'filament.admin.resources.staff.view',
            'title_field' => 'name',
            'subtitle_field' => 'email',
        ],
    ];

    protected int $limit = 10;
    protected ?int $companyId = null;

    public function __construct()
    {
        $this->companyId = auth()->user()?->company_id;
    }
    
    public function setCompanyId(?int $companyId): self
    {
        $this->companyId = $companyId;
        return $this;
    }

    /**
     * Perform unified search across all models.
     */
    public function search(string $query, ?string $category = null): Collection
    {
        if (strlen($query) < 2) {
            return collect();
        }

        $results = collect();
        $searchableModels = $category 
            ? [$category => $this->searchableModels[$category]] 
            : $this->searchableModels;

        foreach ($searchableModels as $type => $config) {
            $modelResults = $this->searchModel($query, $type, $config);
            $results = $results->concat($modelResults);
        }

        // Record search history
        $this->recordSearchHistory($query, $results->count());

        // Sort by relevance and limit
        return $results
            ->sortByDesc('score')
            ->take($this->limit)
            ->values();
    }

    /**
     * Search within a specific model.
     */
    protected function searchModel(string $query, string $type, array $config): Collection
    {
        // Temporarily disable tenant scope for search
        app()->instance('disable_tenant_scope', true);
        
        try {
            $model = $config['model'];
            $searchQuery = $model::query();

        // Apply company scope - but handle models differently
        $hasCompanyId = in_array($type, ['customers', 'calls', 'staff']); // Models with company_id
        
        if ($hasCompanyId) {
            $searchQuery->withoutGlobalScope(\App\Scopes\TenantScope::class);
            
            if ($this->companyId) {
                $searchQuery->where('company_id', $this->companyId);
            } elseif (auth()->check() && auth()->user()->company_id) {
                $searchQuery->where('company_id', auth()->user()->company_id);
            }
        } else if ($type === 'appointments') {
            // Appointments don't have company_id directly but can be filtered via branch
            $searchQuery->withoutGlobalScope(\App\Scopes\TenantScope::class);
            
            $companyIdToUse = $this->companyId ?: (auth()->check() ? auth()->user()->company_id : null);
            if ($companyIdToUse) {
                $searchQuery->whereHas('branch', function($q) use ($companyIdToUse) {
                    $q->withoutGlobalScope(\App\Scopes\TenantScope::class)
                      ->where('company_id', $companyIdToUse);
                });
            }
        }

        // Build search conditions
        $searchQuery->where(function ($q) use ($query, $config) {
            foreach ($config['fields'] as $field) {
                if (Str::contains($field, '.')) {
                    // Handle relationship fields
                    [$relation, $relationField] = explode('.', $field);
                    $q->orWhereHas($relation, function ($rq) use ($relationField, $query) {
                        $rq->where($relationField, 'LIKE', "%{$query}%");
                    });
                } else {
                    $q->orWhere($field, 'LIKE', "%{$query}%");
                }
            }
        });

        // Load necessary relationships based on model type
        if ($type === 'appointments') {
            $searchQuery->with([
                'customer' => function($q) {
                    $q->withoutGlobalScope(\App\Scopes\TenantScope::class);
                },
                'staff' => function($q) {
                    $q->withoutGlobalScope(\App\Scopes\TenantScope::class);
                },
                'service' => function($q) {
                    $q->withoutGlobalScope(\App\Scopes\TenantScope::class);
                },
                'branch' => function($q) {
                    $q->withoutGlobalScope(\App\Scopes\TenantScope::class);
                }
            ]);
        }
        
        // Get results
        $results = $searchQuery->limit(5)->get();

        // Format results
        return $results->map(function ($item) use ($type, $config, $query) {
            // Special handling for appointments
            if ($type === 'appointments') {
                $customerName = $item->customer?->name ?? 'Unbekannt';
                $serviceName = $item->service?->name ?? 'Termin';
                $title = "{$customerName} - {$serviceName}";
                $subtitle = $this->getFieldValue($item, $config['subtitle_field']);
            } else {
                $title = $this->getFieldValue($item, $config['title_field']);
                $subtitle = $this->getFieldValue($item, $config['subtitle_field']);
            }
            
            return [
                'id' => $item->id,
                'type' => $type,
                'title' => $title,
                'subtitle' => $subtitle,
                'icon' => $config['icon'],
                'route' => route($config['route'], $item->id),
                'score' => $this->calculateScore($title, $subtitle, $query, $config['weight']),
                'model' => class_basename($config['model']),
                'actions' => $this->getQuickActions($type, $item),
                'highlight' => $this->highlightMatch($title, $query),
            ];
        });
        } finally {
            // Re-enable tenant scope
            app()->instance('disable_tenant_scope', false);
        }
    }

    /**
     * Get field value from model (handles relationships).
     */
    protected function getFieldValue($model, $field)
    {
        if ($field === 'created_at' || $field === 'starts_at' || $field === 'start_at') {
            $value = Str::contains($field, '.') ? data_get($model, $field) : $model->$field;
            return $value instanceof \Carbon\Carbon ? $value->format('d.m.Y H:i') : $value;
        }
        
        if (Str::contains($field, '.')) {
            return data_get($model, $field);
        }
        
        return $model->$field;
    }

    /**
     * Calculate relevance score.
     */
    protected function calculateScore(?string $title, ?string $subtitle, string $query, int $baseWeight): float
    {
        $score = $baseWeight;
        
        // Handle null title
        if (!$title) {
            return $score;
        }
        
        $query = strtolower($query);
        $title = strtolower($title);
        
        // Exact match in title
        if ($title === $query) {
            $score += 50;
        }
        // Title starts with query
        elseif (str_starts_with($title, $query)) {
            $score += 30;
        }
        // Title contains query
        elseif (str_contains($title, $query)) {
            $score += 20;
        }
        
        // Check subtitle
        if ($subtitle) {
            $subtitle = strtolower($subtitle);
            if (str_contains($subtitle, $query)) {
                $score += 10;
            }
        }
        
        return $score;
    }

    /**
     * Get quick actions for search result.
     */
    protected function getQuickActions(string $type, $model): array
    {
        switch ($type) {
            case 'customers':
                return [
                    ['label' => 'Anrufen', 'icon' => 'heroicon-o-phone', 'action' => 'call'],
                    ['label' => 'Termin', 'icon' => 'heroicon-o-calendar', 'action' => 'appointment'],
                ];
            case 'appointments':
                return [
                    ['label' => 'Bearbeiten', 'icon' => 'heroicon-o-pencil', 'action' => 'edit'],
                    ['label' => 'Absagen', 'icon' => 'heroicon-o-x-circle', 'action' => 'cancel'],
                ];
            case 'calls':
                return [
                    ['label' => 'Anhören', 'icon' => 'heroicon-o-play', 'action' => 'play'],
                    ['label' => 'Transkript', 'icon' => 'heroicon-o-document-text', 'action' => 'transcript'],
                ];
            default:
                return [];
        }
    }

    /**
     * Highlight search match in text.
     */
    protected function highlightMatch(?string $text, string $query): string
    {
        if (!$text) {
            return '';
        }
        
        $pattern = '/(' . preg_quote($query, '/') . ')/i';
        return preg_replace($pattern, '<mark class="bg-yellow-200">$1</mark>', $text);
    }

    /**
     * Record search history.
     */
    protected function recordSearchHistory(string $query, int $resultsCount): void
    {
        if (!auth()->check()) return;
        
        SearchHistory::create([
            'user_id' => auth()->id(),
            'query' => $query,
            'results_count' => $resultsCount,
            'context' => request()->header('X-Search-Context', 'global'),
        ]);
    }

    /**
     * Get recent searches for user.
     */
    public function getRecentSearches(int $limit = 5): Collection
    {
        if (!auth()->check()) return collect();
        
        return SearchHistory::where('user_id', auth()->id())
            ->where('results_count', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->pluck('query')
            ->unique()
            ->values();
    }

    /**
     * Get search suggestions based on partial query.
     */
    public function getSuggestions(string $query): Collection
    {
        if (strlen($query) < 2) return collect();
        
        // Get from search history
        $historical = SearchHistory::where('query', 'LIKE', $query . '%')
            ->where('results_count', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->pluck('query');
        
        // Get from common searches
        $common = collect([
            'Neuer Termin',
            'Anrufe heute',
            'Kunden suchen',
            'Offene Termine',
            'Mitarbeiter',
        ])->filter(fn ($item) => str_contains(strtolower($item), strtolower($query)));
        
        return $historical->concat($common)->unique()->take(5);
    }
}