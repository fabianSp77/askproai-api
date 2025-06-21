<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeTag;
use App\Models\KnowledgeAnalytic;
use Carbon\Carbon;

class KnowledgeStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'knowledge:stats 
                            {--period=week : Time period (day, week, month, year)}
                            {--email : Send stats via email}
                            {--export= : Export stats to file (csv, json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display knowledge base statistics and analytics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = $this->option('period');
        $startDate = $this->getStartDate($period);
        
        $this->info('Knowledge Base Statistics');
        $this->info('Period: ' . ucfirst($period) . ' (Since ' . $startDate->format('Y-m-d') . ')');
        $this->newLine();

        // Content Statistics
        $this->displayContentStats();
        $this->newLine();

        // Activity Statistics
        $this->displayActivityStats($startDate);
        $this->newLine();

        // Popular Content
        $this->displayPopularContent();
        $this->newLine();

        // Search Analytics
        $this->displaySearchAnalytics($startDate);
        $this->newLine();

        // Category Distribution
        $this->displayCategoryDistribution();

        // Export if requested
        if ($exportPath = $this->option('export')) {
            $this->exportStats($exportPath);
        }

        // Send email if requested
        if ($this->option('email')) {
            $this->sendStatsEmail();
        }

        return 0;
    }

    /**
     * Get start date based on period
     */
    protected function getStartDate(string $period): Carbon
    {
        return match($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subWeek(),
        };
    }

    /**
     * Display content statistics
     */
    protected function displayContentStats(): void
    {
        $this->line('<options=bold>📚 Content Overview</>');
        
        $stats = [
            'Total Documents' => KnowledgeDocument::count(),
            'Published' => KnowledgeDocument::published()->count(),
            'Draft' => KnowledgeDocument::where('status', 'draft')->count(),
            'Categories' => KnowledgeCategory::count(),
            'Tags' => KnowledgeTag::count(),
            'Code Snippets' => \App\Models\KnowledgeCodeSnippet::count(),
            'Total Word Count' => KnowledgeDocument::sum(\DB::raw('LENGTH(content) - LENGTH(REPLACE(content, " ", "")) + 1')),
            'Avg Reading Time' => round(KnowledgeDocument::avg('reading_time'), 1) . ' minutes',
        ];

        $this->table(['Metric', 'Value'], collect($stats)->map(function ($value, $key) {
            return [$key, is_numeric($value) && !str_contains($value, '.') ? number_format($value) : $value];
        })->toArray());
    }

    /**
     * Display activity statistics
     */
    protected function displayActivityStats(Carbon $since): void
    {
        $this->line('<options=bold>📊 Activity Statistics</>');
        
        $views = KnowledgeAnalytic::where('event_type', 'view')
            ->where('created_at', '>=', $since)
            ->count();
            
        $searches = KnowledgeAnalytic::where('event_type', 'search')
            ->where('created_at', '>=', $since)
            ->count();
            
        $uniqueUsers = KnowledgeAnalytic::where('created_at', '>=', $since)
            ->distinct('session_id')
            ->count('session_id');
            
        $codeCopies = KnowledgeAnalytic::where('event_type', 'copy_code')
            ->where('created_at', '>=', $since)
            ->count();

        $stats = [
            'Page Views' => $views,
            'Searches' => $searches,
            'Unique Visitors' => $uniqueUsers,
            'Code Copies' => $codeCopies,
            'New Documents' => KnowledgeDocument::where('created_at', '>=', $since)->count(),
            'Updated Documents' => KnowledgeDocument::where('updated_at', '>=', $since)
                ->where('created_at', '<', $since)
                ->count(),
        ];

        $this->table(['Activity', 'Count'], collect($stats)->map(function ($value, $key) {
            return [$key, number_format($value)];
        })->toArray());
    }

    /**
     * Display popular content
     */
    protected function displayPopularContent(): void
    {
        $this->line('<options=bold>🔥 Popular Content</>');
        
        $popular = KnowledgeDocument::published()
            ->orderBy('views_count', 'desc')
            ->limit(10)
            ->get(['title', 'views_count', 'type']);

        $this->table(
            ['Title', 'Views', 'Type'],
            $popular->map(function ($doc) {
                return [
                    \Str::limit($doc->title, 50),
                    number_format($doc->views_count),
                    ucfirst($doc->type),
                ];
            })->toArray()
        );
    }

    /**
     * Display search analytics
     */
    protected function displaySearchAnalytics(Carbon $since): void
    {
        $this->line('<options=bold>🔍 Search Analytics</>');
        
        $searches = KnowledgeAnalytic::where('event_type', 'search')
            ->where('created_at', '>=', $since)
            ->whereNotNull('event_data->query')
            ->get()
            ->map(function ($analytic) {
                return [
                    'query' => $analytic->event_data['query'] ?? '',
                    'results' => $analytic->event_data['results_count'] ?? 0,
                ];
            })
            ->groupBy('query')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'avg_results' => round($group->avg('results'), 1),
                ];
            })
            ->sortByDesc('count')
            ->take(10);

        if ($searches->isEmpty()) {
            $this->line('No search data available for this period.');
            return;
        }

        $this->table(
            ['Search Query', 'Times', 'Avg Results'],
            $searches->map(function ($data, $query) {
                return [
                    \Str::limit($query, 40),
                    $data['count'],
                    $data['avg_results'],
                ];
            })->toArray()
        );

        // Show searches with no results
        $noResults = KnowledgeAnalytic::where('event_type', 'search')
            ->where('created_at', '>=', $since)
            ->whereNotNull('event_data->query')
            ->where('event_data->results_count', 0)
            ->get()
            ->pluck('event_data.query')
            ->unique()
            ->take(5);

        if ($noResults->isNotEmpty()) {
            $this->newLine();
            $this->warn('Searches with no results:');
            foreach ($noResults as $query) {
                $this->line('  - ' . $query);
            }
        }
    }

    /**
     * Display category distribution
     */
    protected function displayCategoryDistribution(): void
    {
        $this->line('<options=bold>📁 Category Distribution</>');
        
        $categories = KnowledgeCategory::withCount(['documents' => function ($query) {
            $query->published();
        }])
        ->orderBy('documents_count', 'desc')
        ->get();

        $total = $categories->sum('documents_count');

        $this->table(
            ['Category', 'Documents', 'Percentage'],
            $categories->map(function ($category) use ($total) {
                $percentage = $total > 0 ? round(($category->documents_count / $total) * 100, 1) : 0;
                return [
                    $category->name,
                    $category->documents_count,
                    $percentage . '%',
                ];
            })->toArray()
        );
    }

    /**
     * Export statistics
     */
    protected function exportStats(string $path): void
    {
        $format = pathinfo($path, PATHINFO_EXTENSION) ?: 'json';
        
        $data = [
            'generated_at' => now()->toIso8601String(),
            'content_stats' => [
                'total_documents' => KnowledgeDocument::count(),
                'published' => KnowledgeDocument::published()->count(),
                'categories' => KnowledgeCategory::count(),
                'tags' => KnowledgeTag::count(),
            ],
            'popular_documents' => KnowledgeDocument::published()
                ->orderBy('views_count', 'desc')
                ->limit(20)
                ->get(['title', 'slug', 'views_count', 'type'])
                ->toArray(),
        ];

        if ($format === 'csv') {
            // Export as CSV (simplified)
            $csv = "Metric,Value\n";
            foreach ($data['content_stats'] as $key => $value) {
                $csv .= "{$key},{$value}\n";
            }
            file_put_contents($path, $csv);
        } else {
            // Export as JSON
            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
        }

        $this->info("Statistics exported to: {$path}");
    }

    /**
     * Send statistics via email
     */
    protected function sendStatsEmail(): void
    {
        // This would integrate with your email system
        $this->info('Email functionality would be implemented here.');
    }
}