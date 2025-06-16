<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class BackupStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.backup-status-widget';

    public function getBackupStatus(): array
    {
        $backupDir = base_path('backups');
        $files = [];
        if (is_dir($backupDir)) {
            foreach (scandir($backupDir, SCANDIR_SORT_DESCENDING) as $file) {
                if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['sql', 'zip', 'gz'])) {
                    $files[] = $file;
                    if (count($files) >= 3) break;
                }
            }
        }
        return [
            'files' => $files,
        ];
    }
}
