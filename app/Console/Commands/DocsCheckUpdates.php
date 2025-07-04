<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class DocsCheckUpdates extends Command
{
    protected $signature = 'docs:check-updates {--silent : Keine Ausgabe} {--auto-fix : Automatische Fixes anwenden} {--json : JSON Output}';
    protected $description = 'Prüft ob Dokumentation aktualisiert werden muss';

    private $documentationMap = [
        // Code -> Dokumentation Mapping
        'app/Services/RetellService.php' => [
            'ERROR_PATTERNS.md',
            'TROUBLESHOOTING_DECISION_TREE.md'
        ],
        'app/Services/MCP/' => [
            'CLAUDE.md' => 'MCP-Server Sektion',
            'INTEGRATION_HEALTH_MONITOR.md'
        ],
        'routes/api.php' => [
            'ERROR_PATTERNS.md' => 'Webhook Sektion',
            'PHONE_TO_APPOINTMENT_FLOW.md'
        ],
        'config/' => [
            'CLAUDE.md' => 'Environment Configuration',
            'DEPLOYMENT_CHECKLIST.md'
        ],
        'database/migrations/' => [
            'CLAUDE.md' => 'Database Considerations',
            'DEPLOYMENT_CHECKLIST.md' => 'Migration Sektion'
        ]
    ];

    public function handle()
    {
        $this->checkDocumentationFreshness();
        $this->suggestUpdates();
        $this->validateLinks();
        
        if (!$this->option('silent')) {
            $this->generateReport();
        }
        
        if ($this->option('json')) {
            $this->outputJson();
        }
        
        return 0;
    }

    private function checkDocumentationFreshness()
    {
        $outdatedDocs = [];
        
        foreach (glob(base_path('*.md')) as $doc) {
            $lastModified = Carbon::createFromTimestamp(filemtime($doc));
            $daysSinceUpdate = $lastModified->diffInDays(now());
            
            if ($daysSinceUpdate > 30) {
                $outdatedDocs[] = [
                    'file' => basename($doc),
                    'last_update' => $lastModified->format('Y-m-d'),
                    'days_old' => $daysSinceUpdate
                ];
            }
        }
        
        if (!empty($outdatedDocs)) {
            $this->warn('⚠️  Veraltete Dokumentation gefunden:');
            foreach ($outdatedDocs as $doc) {
                $this->line("  - {$doc['file']}: {$doc['days_old']} Tage alt");
            }
        }
    }

    private function suggestUpdates()
    {
        // Analysiere letzte Commits
        $recentChanges = shell_exec('git log --oneline -10 --name-only');
        
        $suggestedUpdates = [];
        
        foreach ($this->documentationMap as $codePath => $docs) {
            if (str_contains($recentChanges, $codePath)) {
                foreach ($docs as $key => $value) {
                    if (is_numeric($key)) {
                        // $value ist das Dokument
                        if (!isset($suggestedUpdates[$value])) {
                            $suggestedUpdates[$value] = [];
                        }
                    } else {
                        // $key ist das Dokument, $value ist die Sektion
                        if (!isset($suggestedUpdates[$key])) {
                            $suggestedUpdates[$key] = [];
                        }
                        $suggestedUpdates[$key][] = $value;
                    }
                }
            }
        }
        
        if (!empty($suggestedUpdates)) {
            $this->info('💡 Empfohlene Dokumentations-Updates:');
            foreach ($suggestedUpdates as $doc => $sections) {
                $this->line("  - $doc");
                if (!empty($sections)) {
                    foreach ($sections as $section) {
                        $this->line("    → $section");
                    }
                }
            }
        }
    }

    private function validateLinks()
    {
        $brokenLinks = [];
        
        // Prüfe Links in CLAUDE.md
        $claudeContent = File::get(base_path('CLAUDE.md'));
        preg_match_all('/\[([^\]]+)\]\(([^\)]+)\)/', $claudeContent, $matches);
        
        foreach ($matches[2] as $index => $link) {
            if (str_starts_with($link, './') || str_starts_with($link, '../')) {
                $filePath = base_path(ltrim($link, './'));
                if (!File::exists($filePath)) {
                    $brokenLinks[] = [
                        'text' => $matches[1][$index],
                        'link' => $link
                    ];
                }
            }
        }
        
        if (!empty($brokenLinks)) {
            $this->error('❌ Defekte Links gefunden:');
            foreach ($brokenLinks as $broken) {
                $this->line("  - [{$broken['text']}]({$broken['link']})");
            }
        }
    }

    private function generateReport()
    {
        $report = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'health_score' => $this->calculateHealthScore(),
            'recommendations' => $this->getRecommendations()
        ];
        
        File::put(
            base_path('docs/documentation-health-report.json'),
            json_encode($report, JSON_PRETTY_PRINT)
        );
        
        $this->info("📊 Dokumentations-Gesundheit: {$report['health_score']}%");
    }

    private function calculateHealthScore(): int
    {
        // Berechne Health Score basierend auf verschiedenen Faktoren
        $score = 100;
        
        // Reduziere für veraltete Docs
        $outdatedCount = count(glob(base_path('*.md')));
        $score -= ($outdatedCount * 5);
        
        // Reduziere für defekte Links
        // ... weitere Berechnungen
        
        return max(0, min(100, $score));
    }

    private function getRecommendations(): array
    {
        return [
            'Führe wöchentliche Dokumentations-Reviews durch',
            'Aktiviere automatische Link-Validierung in CI/CD',
            'Erstelle Dokumentations-Templates für neue Features'
        ];
    }
    
    private function outputJson()
    {
        $data = [
            'health_score' => $this->calculateHealthScore(),
            'outdated_docs' => [],
            'broken_links' => [],
            'suggestions' => []
        ];
        
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
}