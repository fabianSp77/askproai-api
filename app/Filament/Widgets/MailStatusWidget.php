<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class MailStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.mail-status-widget';

    public function getMailStatus(): array
    {
        $mailer = config('mail.mailer');
        $host = config('mail.host');
        $port = config('mail.port');
        $user = config('mail.username');
        $from = config('mail.from.address');
        $smtpOnline = null;
        $error = null;

        try {
            $transport = app('mailer')->getSymfonyTransport();
            $smtpOnline = true;
        } catch (\Throwable $e) {
            $smtpOnline = false;
            $error = $e->getMessage();
        }

        // Letzten relevanten Fehler suchen (nur letzte 40 Zeilen)
        $logFile = storage_path('logs/laravel.log');
        $lastError = null;
        if (file_exists($logFile)) {
            $lines = [];
            $fp = fopen($logFile, 'r');
            $chunk = 1024;
            fseek($fp, 0, SEEK_END);
            $pos = ftell($fp);
            $buffer = '';
            while ($pos > 0 && count($lines) < 40) {
                $read = ($pos - $chunk > 0) ? $chunk : $pos;
                $pos -= $read;
                fseek($fp, $pos, SEEK_SET);
                $buffer = fread($fp, $read) . $buffer;
                $lines = explode("\n", $buffer);
            }
            fclose($fp);
            $lines = array_reverse($lines);
            foreach ($lines as $line) {
                if (stripos($line, 'mail') !== false || stripos($line, 'smtp') !== false) {
                    $lastError = trim($line);
                    break;
                }
            }
        }

        return [
            'mailer' => $mailer,
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'from' => $from,
            'online' => $smtpOnline,
            'error' => $error,
            'lastError' => $lastError,
        ];
    }
}
