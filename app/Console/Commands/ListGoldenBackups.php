<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class ListGoldenBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:list-golden 
                            {--verify : Verify checksums of golden backups}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all golden backup restore points';

    /**
     * Golden backups configuration
     */
    protected array $goldenBackups = [
        [
            'id' => 1,
            'name' => 'GOLDEN BACKUP #1',
            'path' => '/var/www/backups/askproai-full-backup-20250805-230451.tar.gz',
            'date' => '2025-08-05 23:04:51',
            'checksum' => 'c34f6f8071106404ff8e8b9415c06589',
            'description' => 'Stabiler Zustand nach allen Fehlerbehebungen',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('🏆 GOLDEN BACKUP RESTORE POINTS');
        $this->info('================================');
        $this->info('');

        $backups = $this->collectBackupInfo();

        if ($this->option('json')) {
            $this->line(json_encode($backups, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        if (empty($backups)) {
            $this->warn('Keine Golden Backups gefunden.');
            return Command::SUCCESS;
        }

        // Display backups in table format
        $headers = ['ID', 'Name', 'Datum', 'Größe', 'Status', 'Pfad'];
        $rows = [];

        foreach ($backups as $backup) {
            $rows[] = [
                $backup['id'],
                $backup['name'],
                $backup['date'],
                $backup['size'],
                $backup['status_text'],
                $this->truncatePath($backup['path']),
            ];
        }

        $this->table($headers, $rows);

        // Show verification results if requested
        if ($this->option('verify')) {
            $this->info('');
            $this->info('Verifiziere Checksums...');
            $this->verifyBackups($backups);
        }

        // Show quick restore commands
        $this->info('');
        $this->info('QUICK RESTORE BEFEHLE:');
        $this->info('─────────────────────');
        
        foreach ($backups as $backup) {
            if ($backup['exists']) {
                $this->info("Backup #{$backup['id']}:");
                $this->line("  cd " . dirname($backup['path']));
                $this->line("  tar -xzf " . basename($backup['path']));
                $this->line("  ./restore-backup.sh");
                $this->info('');
            }
        }

        // Show symlink directory
        $goldenDir = '/var/www/GOLDEN_BACKUPS';
        if (File::isDirectory($goldenDir)) {
            $this->info('Symlink-Verzeichnis: ' . $goldenDir);
            $symlinks = File::glob($goldenDir . '/*.tar.gz');
            if (!empty($symlinks)) {
                $this->info('Gefundene Symlinks:');
                foreach ($symlinks as $link) {
                    $target = readlink($link);
                    $this->line("  • " . basename($link) . " → " . $target);
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Collect information about all golden backups
     */
    protected function collectBackupInfo(): array
    {
        $backups = [];

        foreach ($this->goldenBackups as $config) {
            $exists = File::exists($config['path']);
            $size = $exists ? $this->formatBytes(filesize($config['path'])) : 'N/A';
            
            $backup = [
                'id' => $config['id'],
                'name' => $config['name'],
                'path' => $config['path'],
                'date' => $config['date'],
                'checksum' => $config['checksum'],
                'description' => $config['description'],
                'exists' => $exists,
                'size' => $size,
                'status' => $this->getBackupStatus($config['path'], $config['date']),
                'status_text' => $this->getBackupStatusText($config['path'], $config['date']),
            ];

            $backups[] = $backup;
        }

        // Check for additional golden backups in documentation
        $docFile = '/var/www/api-gateway/GOLDEN_BACKUP_RESTORE_POINTS.md';
        if (File::exists($docFile)) {
            $content = File::get($docFile);
            
            // Parse for additional golden backups not in config
            if (preg_match_all('/## 🌟 GOLDEN BACKUP #(\d+).*?Pfad.*?`([^`]+)`.*?Checksum.*?`([^`]+)`/s', $content, $matches)) {
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $id = $matches[1][$i];
                    $path = $matches[2][$i];
                    $checksum = $matches[3][$i];
                    
                    // Check if this backup is already in our list
                    $alreadyListed = false;
                    foreach ($backups as $backup) {
                        if ($backup['path'] === $path) {
                            $alreadyListed = true;
                            break;
                        }
                    }
                    
                    if (!$alreadyListed && File::exists($path)) {
                        $backups[] = [
                            'id' => $id,
                            'name' => "GOLDEN BACKUP #{$id}",
                            'path' => $path,
                            'date' => date('Y-m-d H:i:s', filemtime($path)),
                            'checksum' => $checksum,
                            'description' => 'Aus Dokumentation geladen',
                            'exists' => true,
                            'size' => $this->formatBytes(filesize($path)),
                            'status' => 'documented',
                            'status_text' => '📄 Dokumentiert',
                        ];
                    }
                }
            }
        }

        return $backups;
    }

    /**
     * Get backup status
     */
    protected function getBackupStatus(string $path, string $date): string
    {
        if (!File::exists($path)) {
            return 'missing';
        }

        $backupDate = Carbon::parse($date);
        $hoursSince = $backupDate->diffInHours(now());

        if ($hoursSince < 24) {
            return 'current';
        } elseif ($hoursSince < 72) {
            return 'recent';
        } elseif ($hoursSince < 168) { // 1 week
            return 'aging';
        } else {
            return 'old';
        }
    }

    /**
     * Get backup status text with emoji
     */
    protected function getBackupStatusText(string $path, string $date): string
    {
        if (!File::exists($path)) {
            return '❌ Fehlt';
        }

        $status = $this->getBackupStatus($path, $date);

        return match($status) {
            'current' => '✅ Aktuell',
            'recent' => '🟢 Kürzlich',
            'aging' => '🟡 Alternd',
            'old' => '🟠 Alt',
            'documented' => '📄 Dokumentiert',
            default => '⚪ Unbekannt',
        };
    }

    /**
     * Verify backup checksums
     */
    protected function verifyBackups(array $backups): void
    {
        foreach ($backups as $backup) {
            if (!$backup['exists']) {
                $this->error("  ❌ Backup #{$backup['id']}: Datei nicht gefunden");
                continue;
            }

            if (empty($backup['checksum'])) {
                $this->warn("  ⚠️  Backup #{$backup['id']}: Keine Checksum vorhanden");
                continue;
            }

            $this->info("  Verifiziere Backup #{$backup['id']}...");
            $actualChecksum = md5_file($backup['path']);

            if ($actualChecksum === $backup['checksum']) {
                $this->info("  ✅ Backup #{$backup['id']}: Checksum korrekt");
            } else {
                $this->error("  ❌ Backup #{$backup['id']}: Checksum stimmt nicht überein!");
                $this->error("     Erwartet: {$backup['checksum']}");
                $this->error("     Aktuell:  {$actualChecksum}");
            }
        }
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Truncate long paths for display
     */
    protected function truncatePath(string $path, int $maxLength = 50): string
    {
        if (strlen($path) <= $maxLength) {
            return $path;
        }

        $start = substr($path, 0, 20);
        $end = substr($path, -25);
        
        return $start . '...' . $end;
    }
}