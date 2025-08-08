<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

class BackupRestorePoints extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = "⚙️ System";
    protected static ?int $navigationSort = 830;
    protected static ?string $navigationLabel = "Backup Restore Points";
    protected static string $view = 'filament.admin.pages.backup-restore-points';

    #[Url]
    public string $filter = 'all'; // all, golden, automatic, manual
    
    #[Url]
    public string $search = '';
    
    public array $backups = [];
    public array $filteredBackups = [];
    public array $stats = [];

    public static function canAccess(): bool
    {
        // Nur für Super Admin (fabian@askproai.de) sichtbar
        $user = auth()->user();
        return $user && $user->email === 'fabian@askproai.de';
    }

    public function mount(): void
    {
        // Doppelte Sicherheit: Check permissions
        abort_unless(auth()->user()->email === 'fabian@askproai.de', 403, 'Nur für Super Admin zugänglich');
        
        $this->loadBackups();
        $this->applyFilters();
        $this->calculateStats();
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    public function loadBackups(): void
    {
        $this->backups = array_merge(
            $this->getGoldenBackups(),
            $this->getAutomaticBackups(),
            $this->getManualBackups()
        );
        
        // Sort by date, newest first
        usort($this->backups, function($a, $b) {
            return Carbon::parse($b['date'])->timestamp - Carbon::parse($a['date'])->timestamp;
        });
    }

    public function getGoldenBackups(): array
    {
        $backups = [];
        
        // Golden Backup #1 - August 5, 2025
        $goldenBackup1 = '/var/www/backups/askproai-full-backup-20250805-230451.tar.gz';
        if (File::exists($goldenBackup1)) {
            $backups[] = [
                'id' => 'golden_1',
                'name' => '🏆 GOLDEN BACKUP #1',
                'date' => '2025-08-05 23:04:51',
                'path' => $goldenBackup1,
                'size' => $this->formatBytes(filesize($goldenBackup1)),
                'checksum' => 'd3b92dbb5f89c3d9a08ab7e78e9f4a59',
                'status' => 'golden',
                'type' => 'full',
                'description' => 'Vollständiges System-Backup nach erfolgreicher Migration',
                'features' => [
                    '✅ Vollständige Datenbank (182 Tabellen)',
                    '✅ Komplette Codebasis (32,665 Dateien)',
                    '✅ System-Konfigurationen',
                    '✅ Alle Umgebungsvariablen',
                    '✅ Logs und Dokumentation'
                ]
            ];
        }
        
        // Golden Backup #2 - August 6, 2025 (TODAY)
        $goldenBackup2Dir = '/var/www/backups/golden_backup_20250806_175541';
        if (File::isDirectory($goldenBackup2Dir)) {
            $archiveFile = $goldenBackup2Dir . '/codebase_complete.tar.gz';
            $totalSize = 0;
            
            // Calculate total size of all files in the directory
            $files = File::files($goldenBackup2Dir, true);
            foreach ($files as $file) {
                $totalSize += $file->getSize();
            }
            
            $backups[] = [
                'id' => 'golden_2',
                'name' => '🏆 GOLDEN BACKUP #2 (LATEST)',
                'date' => '2025-08-06 17:55:41',
                'path' => $goldenBackup2Dir,
                'size' => $this->formatBytes($totalSize),
                'checksum' => $this->calculateDirectoryChecksum($goldenBackup2Dir),
                'status' => 'golden',
                'type' => 'full',
                'description' => 'Golden Backup nach Retell.ai MCP Migration & Notion Dokumentation',
                'features' => [
                    '✅ Retell.ai MCP Migration komplett',
                    '✅ Notion Dokumentation erstellt',
                    '✅ MCP Configuration Interface',
                    '✅ Analytics Dashboard mit Charts',
                    '✅ System bereinigt (67 obsolete Dateien entfernt)',
                    '✅ 182 Tabellen, 13 Companies, 207 Calls',
                    '✅ Git: fix/cleanup-uncommitted (132 staged)',
                    '✅ Vollständige Restore-Scripts'
                ]
            ];
        }
        
        // Check for other golden backups in the directory
        $backupDirs = [
            '/var/www/backups',
            '/var/www/GOLDEN_BACKUPS',
            '/var/www/api-gateway/backups'
        ];
        
        foreach ($backupDirs as $dir) {
            if (!File::isDirectory($dir)) continue;
            
            $files = File::glob($dir . '/golden_backup_*');
            foreach ($files as $file) {
                $basename = basename($file);
                
                // Skip already processed
                if ($basename === 'golden_backup_20250806_175541') continue;
                
                // Extract date from filename
                if (preg_match('/golden_backup_(\d{8})_(\d{6})/', $basename, $matches)) {
                    $date = Carbon::createFromFormat('Ymd His', $matches[1] . ' ' . $matches[2]);
                    
                    $size = 0;
                    if (is_dir($file)) {
                        $allFiles = File::files($file, true);
                        foreach ($allFiles as $f) {
                            $size += $f->getSize();
                        }
                    } else {
                        $size = filesize($file);
                    }
                    
                    $backups[] = [
                        'id' => 'golden_' . uniqid(),
                        'name' => '🏆 Golden Backup',
                        'date' => $date->format('Y-m-d H:i:s'),
                        'path' => $file,
                        'size' => $this->formatBytes($size),
                        'checksum' => null,
                        'status' => 'golden',
                        'type' => 'full',
                        'description' => 'Golden Backup - Restore Point',
                        'features' => []
                    ];
                }
            }
        }
        
        return $backups;
    }
    
    public function getAutomaticBackups(): array
    {
        $backups = [];
        
        // Database backups
        $dbBackupPath = '/var/www/api-gateway/backups';
        if (File::isDirectory($dbBackupPath)) {
            $files = File::glob($dbBackupPath . '/*.sql.gz');
            
            foreach ($files as $file) {
                $basename = basename($file);
                $date = null;
                
                // Try to extract date from filename
                if (preg_match('/(\d{4}-\d{2}-\d{2})[_\s]?(\d{2}[:\-]?\d{2}[:\-]?\d{2})?/', $basename, $matches)) {
                    $dateStr = $matches[1];
                    if (isset($matches[2])) {
                        $timeStr = str_replace(['-', '_'], ':', $matches[2]);
                        $dateStr .= ' ' . $timeStr;
                    }
                    $date = Carbon::parse($dateStr);
                } else {
                    $date = Carbon::createFromTimestamp(filemtime($file));
                }
                
                $backups[] = [
                    'id' => 'auto_' . uniqid(),
                    'name' => 'Auto-Backup (Database)',
                    'date' => $date->format('Y-m-d H:i:s'),
                    'path' => $file,
                    'size' => $this->formatBytes(filesize($file)),
                    'checksum' => null,
                    'status' => 'automatic',
                    'type' => 'database',
                    'description' => 'Automatisches Datenbank-Backup',
                    'features' => []
                ];
            }
        }
        
        return $backups;
    }
    
    public function getManualBackups(): array
    {
        $backups = [];
        
        // Check for manual backups
        $manualPaths = [
            '/var/www/backups/manual_*',
            '/var/www/api-gateway/backups/manual_*'
        ];
        
        foreach ($manualPaths as $pattern) {
            $files = File::glob($pattern);
            foreach ($files as $file) {
                $basename = basename($file);
                $date = Carbon::createFromTimestamp(filemtime($file));
                
                $size = 0;
                if (is_dir($file)) {
                    $allFiles = File::files($file, true);
                    foreach ($allFiles as $f) {
                        $size += $f->getSize();
                    }
                } else {
                    $size = filesize($file);
                }
                
                $backups[] = [
                    'id' => 'manual_' . uniqid(),
                    'name' => 'Manual Backup',
                    'date' => $date->format('Y-m-d H:i:s'),
                    'path' => $file,
                    'size' => $this->formatBytes($size),
                    'checksum' => null,
                    'status' => 'manual',
                    'type' => is_dir($file) ? 'full' : 'file',
                    'description' => 'Manuelles Backup',
                    'features' => []
                ];
            }
        }
        
        return $backups;
    }
    
    public function applyFilters(): void
    {
        $filtered = $this->backups;
        
        // Apply status filter
        if ($this->filter !== 'all') {
            $filtered = array_filter($filtered, function($backup) {
                return $backup['status'] === $this->filter;
            });
        }
        
        // Apply search filter
        if (!empty($this->search)) {
            $searchLower = strtolower($this->search);
            $filtered = array_filter($filtered, function($backup) use ($searchLower) {
                return str_contains(strtolower($backup['name']), $searchLower) ||
                       str_contains(strtolower($backup['description']), $searchLower) ||
                       str_contains(strtolower($backup['date']), $searchLower) ||
                       str_contains(strtolower(implode(' ', $backup['features'])), $searchLower);
            });
        }
        
        $this->filteredBackups = array_values($filtered);
    }
    
    public function calculateStats(): void
    {
        $this->stats = [
            'total' => count($this->backups),
            'golden' => count(array_filter($this->backups, fn($b) => $b['status'] === 'golden')),
            'automatic' => count(array_filter($this->backups, fn($b) => $b['status'] === 'automatic')),
            'manual' => count(array_filter($this->backups, fn($b) => $b['status'] === 'manual')),
            'total_size' => $this->calculateTotalSize(),
            'latest_backup' => !empty($this->backups) ? $this->backups[0]['date'] : 'N/A',
            'oldest_backup' => !empty($this->backups) ? end($this->backups)['date'] : 'N/A'
        ];
    }
    
    protected function calculateTotalSize(): string
    {
        $totalBytes = 0;
        foreach ($this->backups as $backup) {
            // Parse size back to bytes
            $size = $backup['size'];
            if (preg_match('/^([\d.]+)\s*([KMGT]?B)$/i', $size, $matches)) {
                $value = (float)$matches[1];
                $unit = strtoupper($matches[2]);
                
                $multiplier = match($unit) {
                    'KB' => 1024,
                    'MB' => 1024 * 1024,
                    'GB' => 1024 * 1024 * 1024,
                    'TB' => 1024 * 1024 * 1024 * 1024,
                    default => 1
                };
                
                $totalBytes += $value * $multiplier;
            }
        }
        
        return $this->formatBytes($totalBytes);
    }
    
    protected function calculateDirectoryChecksum($dir): ?string
    {
        $checksumFile = $dir . '/CHECKSUM.md5';
        if (File::exists($checksumFile)) {
            return trim(File::get($checksumFile));
        }
        
        // Calculate checksum for main archive if exists
        $mainArchive = $dir . '/codebase_complete.tar.gz';
        if (File::exists($mainArchive)) {
            return md5_file($mainArchive);
        }
        
        return null;
    }

    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    public function setFilter($filter): void
    {
        $this->filter = $filter;
        $this->applyFilters();
    }
    
    public function updatedSearch(): void
    {
        $this->applyFilters();
    }

    public function downloadBackup(string $path): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Sicherheitscheck - nur Super Admin
        abort_unless(auth()->user()->email === 'fabian@askproai.de', 403);
        
        if (!File::exists($path) && !File::isDirectory($path)) {
            Notification::make()
                ->title('Backup nicht gefunden')
                ->danger()
                ->send();
            
            abort(404, 'Backup-Datei nicht gefunden');
        }

        // Sicherheitscheck - nur Backups aus erlaubten Verzeichnissen
        $allowedPaths = [
            '/var/www/backups/',
            '/var/www/api-gateway/backups/',
            '/var/www/GOLDEN_BACKUPS/'
        ];
        
        $isAllowed = false;
        foreach ($allowedPaths as $allowedPath) {
            if (str_starts_with($path, $allowedPath)) {
                $isAllowed = true;
                break;
            }
        }
        
        if (!$isAllowed) {
            abort(403, 'Zugriff auf diesen Pfad nicht erlaubt');
        }
        
        // If it's a directory (like golden backup), create a tar.gz
        if (File::isDirectory($path)) {
            $tempFile = tempnam(sys_get_temp_dir(), 'backup_') . '.tar.gz';
            $basename = basename($path);
            
            exec("cd " . dirname($path) . " && tar -czf $tempFile $basename 2>&1", $output, $returnCode);
            
            if ($returnCode !== 0) {
                Notification::make()
                    ->title('Fehler beim Erstellen des Archivs')
                    ->danger()
                    ->send();
                abort(500, 'Konnte Archiv nicht erstellen');
            }
            
            return response()->download($tempFile, $basename . '.tar.gz')->deleteFileAfterSend();
        }

        return response()->download($path, basename($path));
    }

    public function verifyChecksum(string $id): void
    {
        $backup = collect($this->backups)->firstWhere('id', $id);
        
        if (!$backup) {
            Notification::make()
                ->title('Backup nicht gefunden')
                ->danger()
                ->send();
            return;
        }
        
        if (!$backup['checksum']) {
            Notification::make()
                ->title('Keine Checksum vorhanden')
                ->warning()
                ->send();
            return;
        }
        
        $path = $backup['path'];
        $actualChecksum = null;
        
        if (File::isDirectory($path)) {
            // For directories, check the main archive
            $mainArchive = $path . '/codebase_complete.tar.gz';
            if (File::exists($mainArchive)) {
                $actualChecksum = md5_file($mainArchive);
            }
        } else {
            $actualChecksum = md5_file($path);
        }
        
        if ($actualChecksum === $backup['checksum']) {
            Notification::make()
                ->title('✅ Checksum verifiziert')
                ->body('Das Backup ist intakt und unverändert.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('⚠️ Checksum stimmt nicht überein!')
                ->body("Erwartet: {$backup['checksum']}\nAktuell: {$actualChecksum}")
                ->danger()
                ->send();
        }
    }
    
    public function restoreBackup(string $id): void
    {
        $backup = collect($this->backups)->firstWhere('id', $id);
        
        if (!$backup) {
            Notification::make()
                ->title('Backup nicht gefunden')
                ->danger()
                ->send();
            return;
        }
        
        // Show restore command
        $command = $this->getRestoreCommand($backup);
        
        Notification::make()
            ->title('Restore-Befehl')
            ->body(new HtmlString("
                <div class='font-mono text-xs bg-gray-100 p-2 rounded'>
                    {$command}
                </div>
                <div class='mt-2 text-xs text-gray-600'>
                    Kopieren Sie diesen Befehl und führen Sie ihn im Terminal aus.
                </div>
            "))
            ->persistent()
            ->actions([
                \Filament\Notifications\Actions\Action::make('copy')
                    ->label('Kopieren')
                    ->icon('heroicon-o-clipboard')
                    ->action(fn() => null)
            ])
            ->send();
    }

    public function getRestoreCommand(array $backup): string
    {
        if ($backup['type'] === 'full') {
            if (File::isDirectory($backup['path'])) {
                // For directory-based backups (like golden backup #2)
                return "cd {$backup['path']} && sudo ./restore_system.sh --confirm";
            } else {
                // For tar.gz archives
                return "cd " . dirname($backup['path']) . " && tar -xzf " . basename($backup['path']) . " && cd " . str_replace('.tar.gz', '', basename($backup['path'])) . " && ./restore-backup.sh";
            }
        } else {
            // For database-only backups
            return "gunzip -c {$backup['path']} | mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db";
        }
    }
    
    public function createBackup(): void
    {
        Notification::make()
            ->title('Backup wird erstellt...')
            ->body('Dies kann einige Minuten dauern.')
            ->info()
            ->send();
        
        // Execute backup script in background
        $timestamp = date('Ymd_His');
        $backupPath = "/var/www/backups/manual_backup_{$timestamp}";
        
        exec("nohup /var/www/backups/create_golden_backup.sh {$backupPath} > /dev/null 2>&1 &");
        
        Notification::make()
            ->title('Backup-Prozess gestartet')
            ->body("Backup wird erstellt in: {$backupPath}")
            ->success()
            ->persistent()
            ->send();
    }
}