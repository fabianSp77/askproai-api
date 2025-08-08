<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BackupRestorePointsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.backup-restore-points-widget';
    
    public static function canView(): bool
    {
        // Nur für Super Admin sichtbar
        $user = auth()->user();
        return $user && $user->email === 'fabian@askproai.de';
    }

    protected function getViewData(): array
    {
        $goldenBackup = '/var/www/backups/askproai-full-backup-20250805-230451.tar.gz';
        $lastGoldenBackup = File::exists($goldenBackup) 
            ? Carbon::parse('2025-08-05 23:04:51')
            : null;

        $hoursSinceBackup = $lastGoldenBackup 
            ? $lastGoldenBackup->diffInHours(now())
            : null;

        return [
            'lastGoldenBackup' => $lastGoldenBackup,
            'hoursSinceBackup' => $hoursSinceBackup,
            'backupStatus' => $this->getBackupStatus($hoursSinceBackup),
        ];
    }

    protected function getBackupStatus($hours): array
    {
        if (!$hours) {
            return ['color' => 'danger', 'text' => 'Kein Golden Backup gefunden'];
        }
        
        if ($hours < 24) {
            return ['color' => 'success', 'text' => 'Golden Backup aktuell'];
        } elseif ($hours < 72) {
            return ['color' => 'warning', 'text' => 'Golden Backup wird alt'];
        } else {
            return ['color' => 'danger', 'text' => 'Golden Backup veraltet'];
        }
    }
}