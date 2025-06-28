<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use App\Services\MCP\Discovery\MCPDiscoveryService;
use App\Services\MCP\UIUXBestPracticesMCP;
use App\Services\ContinuousImprovement\ImprovementEngine;
use Livewire\Attributes\On;

class SystemImprovements extends Page
{

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static ?string $navigationGroup = 'Einstellungen';
    protected static ?int $navigationSort = 100;
    protected static string $view = 'filament.admin.pages.system-improvements';
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert - Functionality merged into other pages
    }

    public array $latestAnalysis = [];
    public array $mcpCatalog = [];
    public array $uiuxAnalysis = [];
    public array $performanceMetrics = [];
    public array $activeOptimizations = [];
    
    public function mount(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        // Load latest improvement analysis
        $this->latestAnalysis = Cache::remember('system:improvements:latest', 300, function () {
            return app(ImprovementEngine::class)->analyze();
        });

        // Load MCP catalog
        $this->mcpCatalog = Cache::remember('system:mcp:catalog', 3600, function () {
            return app(MCPDiscoveryService::class)->getCatalog();
        });

        // Load UI/UX analysis
        $this->uiuxAnalysis = Cache::remember('system:uiux:analysis', 3600, function () {
            return app(UIUXBestPracticesMCP::class)->analyzeCurrentImplementation();
        });

        // Load performance metrics
        $this->performanceMetrics = Cache::get('improvement_engine:latest_metrics', []);

        // Load active optimizations
        $this->activeOptimizations = $this->getActiveOptimizations();
    }

    protected function getActiveOptimizations(): array
    {
        // This would fetch from database or storage
        return [
            [
                'id' => 'opt_001',
                'type' => 'Query Optimization',
                'status' => 'in_progress',
                'started_at' => now()->subMinutes(15),
                'progress' => 65,
            ],
            [
                'id' => 'opt_002',
                'type' => 'Cache Warming',
                'status' => 'completed',
                'started_at' => now()->subHours(2),
                'completed_at' => now()->subHours(1),
                'impact' => '+15% performance',
            ],
        ];
    }

    #[On('refresh-data')]
    public function refreshData(): void
    {
        Cache::forget('system:improvements:latest');
        Cache::forget('system:mcp:catalog');
        Cache::forget('system:uiux:analysis');
        
        $this->loadData();
        
        $this->dispatch('data-refreshed');
    }

    public function runDiscovery(): void
    {
        try {
            $discoveries = app(MCPDiscoveryService::class)->discoverNewMCPs();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Discovered ' . count($discoveries) . ' new MCPs',
            ]);
            
            $this->refreshData();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Discovery failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function runAnalysis(): void
    {
        try {
            app(ImprovementEngine::class)->analyze();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Analysis completed successfully',
            ]);
            
            $this->refreshData();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Analysis failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function applyOptimization(string $optimizationId): void
    {
        try {
            $result = app(ImprovementEngine::class)->applyOptimization($optimizationId);
            
            if ($result['status'] === 'success') {
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Optimization applied successfully',
                ]);
            } else {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Optimization failed: ' . ($result['error'] ?? 'Unknown error'),
                ]);
            }
            
            $this->refreshData();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to apply optimization: ' . $e->getMessage(),
            ]);
        }
    }

    protected function getViewData(): array
    {
        return [
            'latestAnalysis' => $this->latestAnalysis,
            'mcpCatalog' => $this->mcpCatalog,
            'uiuxAnalysis' => $this->uiuxAnalysis,
            'performanceMetrics' => $this->performanceMetrics,
            'activeOptimizations' => $this->activeOptimizations,
            'hasRecommendations' => !empty($this->latestAnalysis['recommendations']),
            'recommendationCount' => count($this->latestAnalysis['recommendations'] ?? []),
            'bottleneckCount' => $this->countBottlenecks(),
            'mcpCount' => $this->mcpCatalog['total_discovered'] ?? 0,
            'performanceScore' => $this->calculatePerformanceScore(),
            'uiuxScore' => $this->uiuxAnalysis['accessibility']['score'] ?? 0,
        ];
    }

    protected function countBottlenecks(): int
    {
        $count = 0;
        foreach ($this->latestAnalysis['bottlenecks'] ?? [] as $category => $items) {
            $count += count($items);
        }
        return $count;
    }

    protected function calculatePerformanceScore(): int
    {
        $perf = $this->latestAnalysis['performance'] ?? [];
        
        // Simple scoring based on response time and error rate
        $responseTime = $perf['response_times']['average'] ?? 1000;
        $errorRate = $perf['error_rates']['rate'] ?? 0.01;
        
        $score = 100;
        
        // Deduct for slow response times
        if ($responseTime > 3000) $score -= 40;
        elseif ($responseTime > 1000) $score -= 20;
        elseif ($responseTime > 500) $score -= 10;
        
        // Deduct for high error rates
        if ($errorRate > 0.05) $score -= 30;
        elseif ($errorRate > 0.01) $score -= 15;
        elseif ($errorRate > 0.001) $score -= 5;
        
        return max(0, $score);
    }
}