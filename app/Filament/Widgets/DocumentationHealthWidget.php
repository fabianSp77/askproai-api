<?php

namespace App\Filament\Widgets;

use App\Services\Documentation\DocumentationAnalyzer;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class DocumentationHealthWidget extends Widget
{
    protected static string $view = 'filament.widgets.documentation-health';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 99;
    
    /**
     * Widget nur für Super-Admins sichtbar
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
    
    /**
     * Polling Interval für Live-Updates (30 Sekunden)
     */
    protected static ?string $pollingInterval = '30s';
    
    protected function getViewData(): array
    {
        // Cache für 5 Minuten um Performance zu verbessern
        return Cache::remember('documentation-health-widget-data', 300, function () {
            $analyzer = app(DocumentationAnalyzer::class);
            
            // Basis-Metriken
            $allDocs = $analyzer->getAllDocumentationFiles();
            $totalDocs = $allDocs->count();
            $healthScore = $analyzer->calculateHealthScore();
            
            // Analysiere veraltete Dokumente
            $outdatedDocs = [];
            $brokenLinks = 0;
            $totalTodos = 0;
            $recentlyUpdated = [];
            
            $now = time();
            $thirtyDaysAgo = $now - (30 * 86400);
            $sevenDaysAgo = $now - (7 * 86400);
            
            foreach ($allDocs as $doc) {
                $lastModified = File::lastModified($doc);
                $relativePath = str_replace(base_path() . '/', '', $doc);
                
                // Veraltete Dokumente (> 30 Tage)
                if ($lastModified < $thirtyDaysAgo) {
                    $outdatedDocs[] = [
                        'path' => $relativePath,
                        'last_update' => date('Y-m-d', $lastModified),
                        'days_old' => round(($now - $lastModified) / 86400)
                    ];
                }
                
                // Kürzlich aktualisiert (< 7 Tage)
                if ($lastModified > $sevenDaysAgo) {
                    $recentlyUpdated[] = [
                        'path' => $relativePath,
                        'updated' => date('Y-m-d H:i', $lastModified),
                        'days_ago' => round(($now - $lastModified) / 86400, 1)
                    ];
                }
                
                // Analysiere Inhalt
                if (File::exists($doc)) {
                    $content = File::get($doc);
                    
                    // Zähle TODOs
                    preg_match_all('/(?:TODO|FIXME)/i', $content, $todos);
                    $totalTodos += count($todos[0]);
                    
                    // Zähle broken links (vereinfacht)
                    preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/', $content, $links);
                    foreach ($links[2] as $link) {
                        if (str_starts_with($link, '/') || str_starts_with($link, './')) {
                            $linkedFile = base_path(ltrim($link, './'));
                            if (!File::exists($linkedFile)) {
                                $brokenLinks++;
                            }
                        }
                    }
                }
            }
            
            // Sortiere Listen
            usort($outdatedDocs, fn($a, $b) => $b['days_old'] <=> $a['days_old']);
            usort($recentlyUpdated, fn($a, $b) => $a['days_ago'] <=> $b['days_ago']);
            
            // Limitiere Arrays
            $outdatedDocs = array_slice($outdatedDocs, 0, 5);
            $recentlyUpdated = array_slice($recentlyUpdated, 0, 5);
            
            // Berechne Trends (Mock-Daten, könnte aus DB kommen)
            $healthTrend = $this->calculateHealthTrend($healthScore);
            
            // Git-Statistiken
            $lastDocCommit = $this->getLastDocumentationCommit();
            $undocumentedCommits = $this->getUndocumentedCommits();
            
            return [
                'totalDocs' => $totalDocs,
                'healthScore' => $healthScore,
                'healthTrend' => $healthTrend,
                'outdatedCount' => count($outdatedDocs),
                'outdatedDocs' => $outdatedDocs,
                'brokenLinks' => $brokenLinks,
                'totalTodos' => $totalTodos,
                'recentlyUpdated' => $recentlyUpdated,
                'lastDocCommit' => $lastDocCommit,
                'undocumentedCommits' => $undocumentedCommits,
                'lastCheck' => now()->format('H:i:s')
            ];
        });
    }
    
    /**
     * Berechnet Health-Score Trend
     */
    private function calculateHealthTrend(float $currentScore): array
    {
        // In Production würde dies aus einer Datenbank kommen
        // Hier generieren wir Mock-Daten für die letzten 7 Tage
        $trend = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            // Simuliere leichte Schwankungen
            $score = $currentScore + rand(-10, 5) - ($i * 2);
            $score = max(0, min(100, $score));
            
            $trend[] = [
                'date' => $date->format('d.m'),
                'score' => $score
            ];
        }
        
        // Aktueller Wert
        $trend[] = [
            'date' => now()->format('d.m'),
            'score' => $currentScore
        ];
        
        return $trend;
    }
    
    /**
     * Holt letzten Dokumentations-Commit
     */
    private function getLastDocumentationCommit(): ?array
    {
        $output = shell_exec('git log -1 --pretty=format:"%h|%s|%ar" -- "*.md" "docs/" 2>/dev/null');
        
        if ($output) {
            [$hash, $message, $time] = explode('|', $output);
            return [
                'hash' => $hash,
                'message' => $message,
                'time' => $time
            ];
        }
        
        return null;
    }
    
    /**
     * Zählt Commits ohne Dokumentations-Updates
     */
    private function getUndocumentedCommits(): int
    {
        // Commits der letzten 7 Tage
        $allCommits = shell_exec('git rev-list --count --since="7 days ago" HEAD 2>/dev/null');
        $docCommits = shell_exec('git rev-list --count --since="7 days ago" HEAD -- "*.md" "docs/" 2>/dev/null');
        
        return max(0, intval($allCommits) - intval($docCommits));
    }
    
    /**
     * Generiert Status-Badge HTML
     */
    public static function getStatusBadgeHtml(float $score): string
    {
        $color = match (true) {
            $score >= 80 => 'success',
            $score >= 60 => 'warning',
            default => 'danger'
        };
        
        $icon = match (true) {
            $score >= 80 => 'heroicon-o-check-circle',
            $score >= 60 => 'heroicon-o-exclamation-triangle',
            default => 'heroicon-o-x-circle'
        };
        
        return sprintf(
            '<div class="flex items-center gap-2">
                <x-filament::icon :icon="%s" class="w-5 h-5 text-%s-500" />
                <span class="text-%s-700 dark:text-%s-300 font-semibold">%.1f%%</span>
            </div>',
            $icon,
            $color,
            $color,
            $color,
            $score
        );
    }
}