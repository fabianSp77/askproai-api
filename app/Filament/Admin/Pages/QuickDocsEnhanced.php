<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class QuickDocsEnhanced extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Documentation Hub';

    protected static ?string $slug = 'docs-enhanced';

    protected static string $view = 'filament.admin.pages.quick-docs-enhanced';

    protected static ?int $navigationSort = 1;

    // Search & Filter Properties
    public string $search = '';

    public array $selectedCategories = [];

    public string $selectedDifficulty = '';

    public string $sortBy = 'relevance';

    // User Interaction Properties
    public array $favorites = [];

    public array $recentlyViewed = [];

    public array $readingProgress = [];

    // Content Properties
    public array $documents = [];

    public array $featuredDocs = [];

    public array $popularDocs = [];

    public array $relatedDocs = [];

    // UI State
    public string $viewMode = 'grid'; // grid, list, compact

    public bool $darkMode = false;

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'Super Admin',
            'company_admin',
            'Company Admin',
        ]) ?? false;
    }

    public function mount(): void
    {
        $this->loadUserPreferences();
        $this->loadDocuments();
    }

    protected function loadUserPreferences(): void
    {
        $userId = auth()->id();

        $this->favorites = Cache::remember("user.{$userId}.doc_favorites", 3600, function () use ($userId) {
            return DB::table('user_doc_favorites')
                ->where('user_id', $userId)
                ->pluck('document_id')
                ->toArray();
        });

        $this->recentlyViewed = Cache::remember("user.{$userId}.recent_docs", 3600, function () use ($userId) {
            return DB::table('doc_views')
                ->where('user_id', $userId)
                ->orderBy('viewed_at', 'desc')
                ->take(5)
                ->pluck('document_id')
                ->toArray();
        });

        $this->readingProgress = Cache::remember("user.{$userId}.reading_progress", 3600, function () use ($userId) {
            return DB::table('reading_progress')
                ->where('user_id', $userId)
                ->pluck('progress', 'document_id')
                ->toArray();
        });
    }

    protected function loadDocuments(): void
    {
        // Enhanced document structure with all metadata
        $this->documents = [
            // Critical Business Documents
            [
                'id' => 'onboarding-5min',
                'title' => '🚀 5-Min Onboarding Excellence',
                'description' => 'Transform new customers into success stories in record time',
                'url' => '/mkdocs/5-MINUTEN_ONBOARDING_PLAYBOOK/',
                'category' => 'critical',
                'difficulty' => 'beginner',
                'readingTime' => 5,
                'features' => [
                    'Interactive checklists with progress tracking',
                    'Industry-specific templates (Medical, Beauty, Legal)',
                    'Real-time error diagnosis and solutions',
                    'Video walkthroughs for complex steps',
                ],
                'tags' => ['onboarding', 'quickstart', 'setup'],
                'prerequisites' => [],
                'lastUpdated' => '2025-06-25',
                'version' => '2.0',
                'rating' => 4.8,
                'views' => 1250,
                'icon' => 'rocket-launch',
                'color' => 'success',
                'interactive' => true,
                'hasVideo' => true,
                'aiSummary' => 'Get any new customer live in 5 minutes with our battle-tested onboarding playbook. Features interactive checklists, industry templates, and real-time error resolution.',
            ],
            [
                'id' => 'emergency-response',
                'title' => '🚨 24/7 Emergency Response Playbook',
                'description' => 'Crisis management protocols for any situation',
                'url' => '/mkdocs/EMERGENCY_RESPONSE_PLAYBOOK/',
                'category' => 'critical',
                'difficulty' => 'intermediate',
                'readingTime' => 10,
                'features' => [
                    'Traffic light system (Green/Yellow/Red zones)',
                    'Automated healing procedures',
                    'Escalation chain with contact info',
                    'Incident postmortem templates',
                ],
                'tags' => ['emergency', 'crisis', 'support'],
                'prerequisites' => ['basic-troubleshooting'],
                'lastUpdated' => '2025-06-24',
                'version' => '1.5',
                'rating' => 4.9,
                'views' => 856,
                'icon' => 'fire',
                'color' => 'danger',
                'interactive' => true,
                'hasVideo' => false,
                'aiSummary' => 'Comprehensive crisis management guide with automated responses, escalation procedures, and recovery protocols for 24/7 operations.',
            ],
            [
                'id' => 'troubleshooting-tree',
                'title' => '🔍 Smart Troubleshooting Decision Trees',
                'description' => 'Solve any problem in 5 clicks or less',
                'url' => '/mkdocs/TROUBLESHOOTING_DECISION_TREE/',
                'category' => 'critical',
                'difficulty' => 'beginner',
                'readingTime' => 7,
                'features' => [
                    'Interactive decision trees',
                    'One-click fix commands',
                    'Live system diagnostics',
                    'ML-powered problem prediction',
                ],
                'tags' => ['troubleshooting', 'debug', 'fix'],
                'prerequisites' => [],
                'lastUpdated' => '2025-06-26',
                'version' => '2.1',
                'rating' => 4.7,
                'views' => 2103,
                'icon' => 'magnifying-glass',
                'color' => 'warning',
                'interactive' => true,
                'hasVideo' => true,
                'aiSummary' => 'AI-powered troubleshooting system that guides you to solutions with interactive decision trees and automated fixes.',
            ],

            // Process Documentation
            [
                'id' => 'phone-to-appointment',
                'title' => '📞 Phone-to-Appointment Flow Mastery',
                'description' => 'Deep dive into the complete data flow',
                'url' => '/mkdocs/PHONE_TO_APPOINTMENT_FLOW/',
                'category' => 'process',
                'difficulty' => 'intermediate',
                'readingTime' => 15,
                'features' => [
                    '5-phase detailed breakdown',
                    'Latency metrics and optimization',
                    'Debug points and logging',
                    'Performance benchmarks',
                ],
                'tags' => ['process', 'flow', 'architecture'],
                'prerequisites' => ['basic-architecture'],
                'lastUpdated' => '2025-06-23',
                'version' => '1.8',
                'rating' => 4.6,
                'views' => 945,
                'icon' => 'phone',
                'color' => 'info',
                'interactive' => false,
                'hasVideo' => true,
                'aiSummary' => 'Comprehensive guide to the phone-to-appointment conversion process, including data flow, latency optimization, and debugging strategies.',
            ],
            [
                'id' => 'kpi-dashboard',
                'title' => '📊 KPI Dashboard & ROI Calculator',
                'description' => 'Measure success and prove value',
                'url' => '/mkdocs/KPI_DASHBOARD_TEMPLATE/',
                'category' => 'process',
                'difficulty' => 'beginner',
                'readingTime' => 8,
                'features' => [
                    'ROI calculator with industry benchmarks',
                    'Live metrics dashboard',
                    'Custom KPI builder',
                    'Export-ready reports',
                ],
                'tags' => ['analytics', 'kpi', 'roi', 'metrics'],
                'prerequisites' => [],
                'lastUpdated' => '2025-06-25',
                'version' => '1.3',
                'rating' => 4.5,
                'views' => 678,
                'icon' => 'chart-bar',
                'color' => 'success',
                'interactive' => true,
                'hasVideo' => false,
                'aiSummary' => 'Track and visualize your success with customizable KPI dashboards and ROI calculations tailored to your industry.',
            ],
            [
                'id' => 'health-monitor',
                'title' => '🏥 Integration Health Monitor',
                'description' => 'Real-time system health and auto-healing',
                'url' => '/mkdocs/INTEGRATION_HEALTH_MONITOR/',
                'category' => 'process',
                'difficulty' => 'advanced',
                'readingTime' => 12,
                'features' => [
                    'Live status dashboard',
                    'Circuit breaker patterns',
                    'Alert rule configuration',
                    'Self-healing procedures',
                ],
                'tags' => ['monitoring', 'health', 'integrations'],
                'prerequisites' => ['basic-architecture', 'troubleshooting-tree'],
                'lastUpdated' => '2025-06-26',
                'version' => '2.0',
                'rating' => 4.8,
                'views' => 432,
                'icon' => 'heart',
                'color' => 'primary',
                'interactive' => true,
                'hasVideo' => true,
                'aiSummary' => 'Monitor integration health in real-time with automated healing, circuit breakers, and intelligent alerting.',
            ],

            // Technical Documentation
            [
                'id' => 'claude-md',
                'title' => '📚 CLAUDE.md - Master Documentation',
                'description' => 'Complete technical reference guide',
                'url' => '/mkdocs/CLAUDE/',
                'category' => 'technical',
                'difficulty' => 'intermediate',
                'readingTime' => 30,
                'features' => [
                    'Architecture overview',
                    'API reference',
                    'Code examples',
                    'Best practices',
                ],
                'tags' => ['reference', 'technical', 'api'],
                'prerequisites' => [],
                'lastUpdated' => '2025-06-26',
                'version' => '3.0',
                'rating' => 4.9,
                'views' => 3421,
                'icon' => 'book-open',
                'color' => 'primary',
                'interactive' => false,
                'hasVideo' => false,
                'aiSummary' => 'The definitive technical reference for the AskProAI platform, covering architecture, APIs, and implementation details.',
            ],
            [
                'id' => 'service-architecture',
                'title' => '🏗️ Service Architecture Deep Dive',
                'description' => '69 services explained and visualized',
                'url' => '/mkdocs/architecture/services/',
                'category' => 'technical',
                'difficulty' => 'advanced',
                'readingTime' => 25,
                'features' => [
                    'Service dependency graphs',
                    'Communication patterns',
                    'Scaling strategies',
                    'Performance optimization',
                ],
                'tags' => ['architecture', 'services', 'advanced'],
                'prerequisites' => ['claude-md'],
                'lastUpdated' => '2025-06-22',
                'version' => '1.6',
                'rating' => 4.7,
                'views' => 234,
                'icon' => 'cube',
                'color' => 'secondary',
                'interactive' => true,
                'hasVideo' => false,
                'aiSummary' => 'Deep technical dive into the microservices architecture with dependency graphs, patterns, and optimization strategies.',
            ],
            [
                'id' => 'api-reference',
                'title' => '🔐 API & Webhook Reference',
                'description' => 'Complete endpoint documentation',
                'url' => '/mkdocs/api/webhooks/',
                'category' => 'technical',
                'difficulty' => 'intermediate',
                'readingTime' => 20,
                'features' => [
                    'Interactive API explorer',
                    'Webhook signature verification',
                    'Rate limiting guide',
                    'Error code reference',
                ],
                'tags' => ['api', 'webhooks', 'integration'],
                'prerequisites' => ['basic-architecture'],
                'lastUpdated' => '2025-06-24',
                'version' => '2.2',
                'rating' => 4.6,
                'views' => 567,
                'icon' => 'key',
                'color' => 'warning',
                'interactive' => true,
                'hasVideo' => false,
                'aiSummary' => 'Comprehensive API documentation with interactive testing, webhook guides, and security best practices.',
            ],

            // Reference Documentation
            [
                'id' => 'security-compliance',
                'title' => '🔒 Security & GDPR Compliance',
                'description' => 'Data protection and security protocols',
                'url' => '/mkdocs/security/gdpr/',
                'category' => 'reference',
                'difficulty' => 'intermediate',
                'readingTime' => 18,
                'features' => [
                    'GDPR compliance checklist',
                    'Security audit procedures',
                    'Data encryption guide',
                    'Incident response plan',
                ],
                'tags' => ['security', 'gdpr', 'compliance'],
                'prerequisites' => [],
                'lastUpdated' => '2025-06-25',
                'version' => '1.4',
                'rating' => 4.8,
                'views' => 892,
                'icon' => 'shield-check',
                'color' => 'danger',
                'interactive' => false,
                'hasVideo' => true,
                'aiSummary' => 'Essential security protocols and GDPR compliance guidelines to protect your data and maintain regulatory compliance.',
            ],
            [
                'id' => 'deployment-guide',
                'title' => '🚀 Production Deployment Guide',
                'description' => 'Zero-downtime deployment strategies',
                'url' => '/mkdocs/deployment/production/',
                'category' => 'reference',
                'difficulty' => 'advanced',
                'readingTime' => 22,
                'features' => [
                    'Blue-green deployment',
                    'Database migration strategies',
                    'Rollback procedures',
                    'Performance testing',
                ],
                'tags' => ['deployment', 'devops', 'production'],
                'prerequisites' => ['service-architecture', 'api-reference'],
                'lastUpdated' => '2025-06-26',
                'version' => '1.9',
                'rating' => 4.7,
                'views' => 145,
                'icon' => 'server',
                'color' => 'info',
                'interactive' => false,
                'hasVideo' => true,
                'aiSummary' => 'Master production deployments with zero downtime using proven strategies, rollback procedures, and performance testing.',
            ],
        ];

        // Set featured docs
        $this->featuredDocs = array_slice($this->documents, 0, 3);

        // Set popular docs (sorted by views)
        $this->popularDocs = collect($this->documents)
            ->sortByDesc('views')
            ->take(5)
            ->values()
            ->toArray();
    }

    public function updatedSearch(): void
    {
        if (strlen($this->search) >= 2) {
            $this->filterDocuments();
        }
    }

    public function filterDocuments(): void
    {
        // Implement intelligent filtering
        $filtered = collect($this->documents);

        // Search filter
        if ($this->search) {
            $filtered = $filtered->filter(function ($doc) {
                $searchLower = strtolower($this->search);

                return str_contains(strtolower($doc['title']), $searchLower) ||
                       str_contains(strtolower($doc['description']), $searchLower) ||
                       str_contains(strtolower($doc['aiSummary']), $searchLower) ||
                       collect($doc['tags'])->contains(fn ($tag) => str_contains(strtolower($tag), $searchLower));
            });
        }

        // Category filter
        if (! empty($this->selectedCategories)) {
            $filtered = $filtered->whereIn('category', $this->selectedCategories);
        }

        // Difficulty filter
        if ($this->selectedDifficulty) {
            $filtered = $filtered->where('difficulty', $this->selectedDifficulty);
        }

        // Sort
        switch ($this->sortBy) {
            case 'newest':
                $filtered = $filtered->sortByDesc('lastUpdated');

                break;
            case 'popular':
                $filtered = $filtered->sortByDesc('views');

                break;
            case 'rating':
                $filtered = $filtered->sortByDesc('rating');

                break;
            case 'reading_time':
                $filtered = $filtered->sortBy('readingTime');

                break;
            default: // relevance
                // Keep original order for now
                break;
        }

        $this->documents = $filtered->values()->toArray();
    }

    public function toggleFavorite(string $docId): void
    {
        $userId = auth()->id();

        if (in_array($docId, $this->favorites)) {
            // Remove from favorites
            $this->favorites = array_values(array_diff($this->favorites, [$docId]));
            DB::table('user_doc_favorites')
                ->where('user_id', $userId)
                ->where('document_id', $docId)
                ->delete();
        } else {
            // Add to favorites
            $this->favorites[] = $docId;
            DB::table('user_doc_favorites')->insert([
                'user_id' => $userId,
                'document_id' => $docId,
                'created_at' => now(),
            ]);
        }

        // Clear cache
        Cache::forget("user.{$userId}.doc_favorites");

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => in_array($docId, $this->favorites) ? 'Added to favorites' : 'Removed from favorites',
        ]);
    }

    public function trackDocumentView(string $docId): void
    {
        $userId = auth()->id();

        // Track view
        DB::table('doc_views')->insert([
            'user_id' => $userId,
            'document_id' => $docId,
            'viewed_at' => now(),
            'session_id' => session()->getId(),
        ]);

        // Update recently viewed
        if (! in_array($docId, $this->recentlyViewed)) {
            array_unshift($this->recentlyViewed, $docId);
            $this->recentlyViewed = array_slice($this->recentlyViewed, 0, 5);
        }

        // Clear cache
        Cache::forget("user.{$userId}.recent_docs");

        // Load related documents
        $this->loadRelatedDocuments($docId);
    }

    protected function loadRelatedDocuments(string $docId): void
    {
        $currentDoc = collect($this->documents)->firstWhere('id', $docId);

        if ($currentDoc) {
            // Find related by tags and category
            $this->relatedDocs = collect($this->documents)
                ->filter(fn ($doc) => $doc['id'] !== $docId)
                ->filter(function ($doc) use ($currentDoc) {
                    // Same category
                    if ($doc['category'] === $currentDoc['category']) {
                        return true;
                    }

                    // Shared tags
                    $sharedTags = array_intersect($doc['tags'], $currentDoc['tags']);

                    return count($sharedTags) > 0;
                })
                ->take(3)
                ->values()
                ->toArray();
        }
    }

    public function updateReadingProgress(string $docId, int $progress): void
    {
        $userId = auth()->id();

        DB::table('reading_progress')->updateOrInsert(
            ['user_id' => $userId, 'document_id' => $docId],
            ['progress' => $progress, 'updated_at' => now()]
        );

        $this->readingProgress[$docId] = $progress;

        // Clear cache
        Cache::forget("user.{$userId}.reading_progress");
    }

    protected function filterByFavorites(): void
    {
        $this->documents = collect($this->documents)
            ->filter(fn ($doc) => in_array($doc['id'], $this->favorites))
            ->values()
            ->toArray();
    }

    protected function showRecentlyViewed(): void
    {
        $this->documents = collect($this->documents)
            ->filter(fn ($doc) => in_array($doc['id'], $this->recentlyViewed))
            ->values()
            ->toArray();
    }

    public function exportDocument(string $docId): void
    {
        // Export to PDF functionality
        $doc = collect($this->documents)->firstWhere('id', $docId);

        if ($doc) {
            $this->dispatch('exportToPdf', [
                'url' => $doc['url'],
                'title' => $doc['title'],
            ]);
        }
    }

    public function shareDocument(string $docId): void
    {
        $doc = collect($this->documents)->firstWhere('id', $docId);

        if ($doc) {
            $shareUrl = url($doc['url']);

            $this->dispatch('copyToClipboard', [
                'text' => $shareUrl,
                'message' => 'Document link copied to clipboard!',
            ]);
        }
    }

    #[On('keydown.window.prevent')]
    public function handleKeyboardShortcuts($key): void
    {
        switch ($key) {
            case 'cmd+k':
            case 'ctrl+k':
                $this->toggleCommandPalette();

                break;
            case 'cmd+/':
            case 'ctrl+/':
                $this->showOnboarding = true;

                break;
            case 'escape':
                $this->showCommandPalette = false;
                $this->showOnboarding = false;

                break;
        }
    }
}
