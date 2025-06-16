<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class LogStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.log-status-widget';

    public function getLogStatus(): array
    {
        $file = storage_path('logs/laravel.log');
        $lastLines = [];
        if (file_exists($file)) {
            $fp = fopen($file, 'r');
            $pos = -2; // Start at end of file
            $eof = '';
            $lines = [];
            while (count($lines) < 20 && abs($pos) < filesize($file)) {
                fseek($fp, $pos, SEEK_END);
                $char = fgetc($fp);
                if ($char === "\n") {
                    $line = fgets($fp);
                    if ($line !== false && trim($line)) {
                        array_unshift($lines, trim($line));
                    }
                }
                $pos--;
            }
            fclose($fp);
            $lastLines = $lines;
        }
        return [
            'last' => $lastLines
        ];
    }
}
