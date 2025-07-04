<?php

namespace App\Console\Commands;

use App\Services\Documentation\DocumentationAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class DocsMonitorFreshness extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:monitor-freshness 
                            {--days=30 : Tage bis Dokument als veraltet gilt}
                            {--slack : Sende Alerts an Slack}
                            {--email= : Sende Report an Email}
                            {--json : Output als JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Überwacht die Aktualität der Dokumentation und meldet veraltete Dokumente';

    /**
     * Execute the console command.
     */
    public function handle(DocumentationAnalyzer $analyzer): int
    {
        $this->info('📊 Analysiere Dokumentations-Aktualität...');
        
        $thresholdDays = (int) $this->option('days');
        $report = [];
        
        // Analysiere alle Dokumentations-Dateien
        $documents = $analyzer->getAllDocumentationFiles();
        
        $this->output->progressStart($documents->count());
        
        foreach ($documents as $doc) {
            $freshness = $this->calculateFreshness($doc, $thresholdDays);
            
            if ($freshness['score'] < 0.7 || $freshness['days_old'] > $thresholdDays) {
                $report[] = $freshness;
            }
            
            $this->output->progressAdvance();
        }
        
        $this->output->progressFinish();
        
        // Berechne Gesamt-Score
        $healthScore = $analyzer->calculateHealthScore();
        
        // Zeige Report
        if (!$this->option('json')) {
            $this->displayReport($report, $healthScore);
        }
        
        // Sende Notifications wenn gewünscht
        if ($this->option('slack') && !empty($report)) {
            $this->sendSlackAlert($report, $healthScore);
        }
        
        if ($email = $this->option('email')) {
            $this->sendEmailReport($email, $report, $healthScore);
        }
        
        // JSON Output
        if ($this->option('json')) {
            $this->output->write(json_encode([
                'health_score' => $healthScore,
                'outdated_documents' => $report,
                'total_documents' => $documents->count(),
                'outdated_count' => count($report)
            ]));
        }
        
        return count($report) > 0 ? 1 : 0;
    }
    
    /**
     * Berechnet die Aktualität eines Dokuments
     */
    private function calculateFreshness(string $docPath, int $thresholdDays): array
    {
        $relativePath = str_replace(base_path() . '/', '', $docPath);
        $lastModified = File::lastModified($docPath);
        $daysSinceUpdate = (time() - $lastModified) / 86400;
        
        // Basis-Score basierend auf Alter
        $ageScore = max(0, 1 - ($daysSinceUpdate / $thresholdDays));
        
        // Analysiere Inhalt
        $content = File::get($docPath);
        $contentIssues = [];
        
        // Prüfe auf TODO/FIXME
        preg_match_all('/(?:TODO|FIXME)(.+)$/m', $content, $todos);
        if (count($todos[0]) > 0) {
            $contentIssues[] = count($todos[0]) . ' TODO/FIXME Kommentare';
            $ageScore -= 0.1 * count($todos[0]);
        }
        
        // Prüfe auf veraltete Versionsnummern
        if (preg_match_all('/(?:version|v)\s*(\d+\.\d+(?:\.\d+)?)/i', $content, $versions)) {
            foreach ($versions[1] as $version) {
                // Vereinfachte Prüfung: Versionen < 3.0 sind veraltet für Filament
                if (str_contains($content, 'Filament') && version_compare($version, '3.0', '<')) {
                    $contentIssues[] = "Veraltete Filament Version: {$version}";
                    $ageScore -= 0.2;
                }
            }
        }
        
        // Prüfe auf tote interne Links
        $brokenLinks = $this->checkInternalLinks($docPath, $content);
        if (!empty($brokenLinks)) {
            $contentIssues[] = count($brokenLinks) . ' defekte Links';
            $ageScore -= 0.05 * count($brokenLinks);
        }
        
        // Prüfe Related Files aus Git
        $relatedChanges = $this->getRecentRelatedChanges($relativePath, $daysSinceUpdate);
        if (!empty($relatedChanges)) {
            $contentIssues[] = count($relatedChanges) . ' neuere Änderungen in verwandten Dateien';
            $ageScore -= 0.1 * count($relatedChanges);
        }
        
        return [
            'file' => $relativePath,
            'score' => max(0, $ageScore),
            'days_old' => round($daysSinceUpdate, 1),
            'last_update' => date('Y-m-d', $lastModified),
            'issues' => $contentIssues,
            'broken_links' => $brokenLinks,
            'related_changes' => $relatedChanges
        ];
    }
    
    /**
     * Prüft interne Links in einem Dokument
     */
    private function checkInternalLinks(string $docPath, string $content): array
    {
        $brokenLinks = [];
        $docDir = dirname($docPath);
        
        // Finde alle Markdown-Links
        preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/', $content, $matches);
        
        foreach ($matches[2] as $index => $link) {
            // Ignoriere externe Links
            if (preg_match('/^https?:\/\//', $link)) {
                continue;
            }
            
            // Ignoriere Anker-Links
            if (str_starts_with($link, '#')) {
                continue;
            }
            
            // Prüfe relative und absolute Pfade
            if (str_starts_with($link, '/')) {
                $targetPath = base_path(ltrim($link, '/'));
            } else {
                $targetPath = realpath($docDir . '/' . $link);
            }
            
            if (!$targetPath || !File::exists($targetPath)) {
                $brokenLinks[] = [
                    'text' => $matches[1][$index],
                    'link' => $link
                ];
            }
        }
        
        return $brokenLinks;
    }
    
    /**
     * Findet neuere Änderungen in verwandten Code-Dateien
     */
    private function getRecentRelatedChanges(string $docPath, float $daysSinceDocUpdate): array
    {
        $changes = [];
        
        // Mapping von Doku zu Code (vereinfacht)
        $mappings = [
            'docs/api/' => 'app/Http/Controllers/',
            'docs/MCP' => 'app/Services/MCP/',
            'docs/architecture/database' => 'database/migrations/',
            'CLAUDE.md' => 'app/',
        ];
        
        foreach ($mappings as $docPattern => $codePattern) {
            if (str_contains($docPath, $docPattern)) {
                // Git log für Code-Änderungen
                $sinceDate = date('Y-m-d', strtotime("-{$daysSinceDocUpdate} days"));
                $cmd = "git log --since='{$sinceDate}' --name-only --pretty=format: -- '{$codePattern}' 2>/dev/null | sort | uniq";
                $output = shell_exec($cmd);
                
                if ($output) {
                    $files = array_filter(explode("\n", trim($output)));
                    foreach ($files as $file) {
                        if (!empty($file) && str_starts_with($file, $codePattern)) {
                            $changes[] = $file;
                        }
                    }
                }
                break;
            }
        }
        
        return array_slice($changes, 0, 5); // Maximal 5 Änderungen zeigen
    }
    
    /**
     * Zeigt den Freshness-Report an
     */
    private function displayReport(array $report, float $healthScore): void
    {
        $this->newLine();
        
        // Gesamt-Score mit Farbe
        $scoreColor = $healthScore >= 80 ? 'green' : ($healthScore >= 60 ? 'yellow' : 'red');
        $this->line("📊 <fg={$scoreColor}>Dokumentations-Gesundheit: {$healthScore}%</>");
        $this->newLine();
        
        if (empty($report)) {
            $this->info('✅ Alle Dokumente sind aktuell!');
            return;
        }
        
        $this->warn('⚠️  Veraltete oder problematische Dokumente:');
        $this->newLine();
        
        // Sortiere nach Score (schlechteste zuerst)
        usort($report, fn($a, $b) => $a['score'] <=> $b['score']);
        
        foreach ($report as $doc) {
            $scoreBar = $this->createScoreBar($doc['score']);
            $this->line("📄 <fg=cyan>{$doc['file']}</>");
            $this->line("   Score: {$scoreBar} ({$doc['score']})");
            $this->line("   Alter: {$doc['days_old']} Tage (zuletzt: {$doc['last_update']})");
            
            if (!empty($doc['issues'])) {
                $this->line("   Issues:");
                foreach ($doc['issues'] as $issue) {
                    $this->line("     - {$issue}");
                }
            }
            
            if (!empty($doc['broken_links'])) {
                $this->line("   Defekte Links:");
                foreach ($doc['broken_links'] as $link) {
                    $this->line("     - [{$link['text']}]({$link['link']})");
                }
            }
            
            if (!empty($doc['related_changes'])) {
                $this->line("   Neuere Code-Änderungen:");
                foreach ($doc['related_changes'] as $change) {
                    $this->line("     - {$change}");
                }
            }
            
            $this->newLine();
        }
        
        // Zusammenfassung
        $this->info('📈 Zusammenfassung:');
        $this->line("   - Veraltete Dokumente: " . count($report));
        $this->line("   - Durchschnittliches Alter: " . round(collect($report)->avg('days_old'), 1) . " Tage");
        $this->line("   - Niedrigster Score: " . round(collect($report)->min('score'), 2));
        
        $this->newLine();
        $this->comment('💡 Tipp: Führe "php artisan docs:check-updates --auto-fix" aus um Updates zu generieren.');
    }
    
    /**
     * Erstellt eine visuelle Score-Anzeige
     */
    private function createScoreBar(float $score): string
    {
        $percentage = round($score * 100);
        $filled = round($score * 10);
        $empty = 10 - $filled;
        
        $color = $score >= 0.7 ? 'green' : ($score >= 0.4 ? 'yellow' : 'red');
        
        return "<fg={$color}>" . str_repeat('█', $filled) . "</>" . 
               "<fg=gray>" . str_repeat('░', $empty) . "</> " .
               "{$percentage}%";
    }
    
    /**
     * Sendet Slack-Alert
     */
    private function sendSlackAlert(array $report, float $healthScore): void
    {
        $webhookUrl = config('documentation.monitoring.slack_webhook');
        if (!$webhookUrl) {
            $this->warn('Slack Webhook URL nicht konfiguriert!');
            return;
        }
        
        $color = $healthScore >= 80 ? 'good' : ($healthScore >= 60 ? 'warning' : 'danger');
        
        $fields = [];
        foreach (array_slice($report, 0, 5) as $doc) {
            $fields[] = [
                'title' => $doc['file'],
                'value' => "Score: {$doc['score']} | Alter: {$doc['days_old']} Tage",
                'short' => false
            ];
        }
        
        $payload = [
            'attachments' => [
                [
                    'color' => $color,
                    'title' => '📚 Dokumentations-Freshness Report',
                    'text' => "Gesundheits-Score: {$healthScore}%\nVeraltete Dokumente: " . count($report),
                    'fields' => $fields,
                    'footer' => 'AskProAI Documentation Monitor',
                    'ts' => time()
                ]
            ]
        ];
        
        try {
            Http::post($webhookUrl, $payload);
            $this->info('✅ Slack-Notification gesendet');
        } catch (\Exception $e) {
            $this->error('❌ Fehler beim Senden der Slack-Notification: ' . $e->getMessage());
        }
    }
    
    /**
     * Sendet Email-Report
     */
    private function sendEmailReport(string $email, array $report, float $healthScore): void
    {
        $data = [
            'healthScore' => $healthScore,
            'report' => $report,
            'totalOutdated' => count($report),
            'averageAge' => round(collect($report)->avg('days_old'), 1),
            'generated_at' => now()->format('Y-m-d H:i:s')
        ];
        
        try {
            Mail::send('emails.documentation-freshness-report', $data, function ($message) use ($email) {
                $message->to($email)
                    ->subject('📚 AskProAI Dokumentations-Freshness Report');
            });
            
            $this->info("✅ Email-Report gesendet an: {$email}");
        } catch (\Exception $e) {
            $this->error('❌ Fehler beim Senden des Email-Reports: ' . $e->getMessage());
        }
    }
}