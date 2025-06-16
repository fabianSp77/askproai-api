<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckSystemErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:errors 
                            {--lines=50 : Number of lines to show}
                            {--critical : Show only critical errors}
                            {--page=* : Filter by specific page}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check latest system errors from logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (!File::exists($logFile)) {
            $this->error('Log file not found!');
            return;
        }
        
        $lines = $this->option('lines');
        $showCritical = $this->option('critical');
        $pageFilter = $this->option('page');
        
        // Read last N lines from today's log file
        $todayLog = storage_path('logs/laravel-' . now()->format('Y-m-d') . '.log');
        if (File::exists($todayLog)) {
            $logFile = $todayLog;
        }
        
        $content = `tail -n {$lines} {$logFile}`;
        $logLines = explode("\n", $content);
        
        $errors = [];
        $currentError = null;
        
        foreach ($logLines as $line) {
            // Check if this is a new error entry
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?\.ERROR:(.*)/', $line, $matches)) {
                if ($currentError) {
                    $errors[] = $currentError;
                }
                $currentError = [
                    'timestamp' => $matches[1],
                    'message' => trim($matches[2]),
                    'details' => [],
                    'is_critical' => str_contains($line, 'CRITICAL'),
                ];
            } elseif ($currentError && !empty(trim($line))) {
                $currentError['details'][] = $line;
            }
        }
        
        if ($currentError) {
            $errors[] = $currentError;
        }
        
        // Filter errors
        if ($showCritical) {
            $errors = array_filter($errors, fn($e) => $e['is_critical']);
        }
        
        if (!empty($pageFilter)) {
            $errors = array_filter($errors, function($e) use ($pageFilter) {
                foreach ($pageFilter as $page) {
                    if (str_contains($e['message'], $page) || 
                        str_contains(implode(' ', $e['details']), $page)) {
                        return true;
                    }
                }
                return false;
            });
        }
        
        // Display errors
        if (empty($errors)) {
            $this->info('No errors found matching your criteria.');
            return;
        }
        
        $this->error(sprintf('Found %d errors:', count($errors)));
        $this->newLine();
        
        foreach (array_reverse($errors) as $error) {
            $this->line(sprintf(
                '<fg=yellow>[%s]</> %s %s',
                $error['timestamp'],
                $error['is_critical'] ? '<fg=red;options=bold>[CRITICAL]</>' : '',
                $error['message']
            ));
            
            if (!empty($error['details'])) {
                foreach (array_slice($error['details'], 0, 10) as $detail) {
                    $this->line('  ' . $detail);
                }
                if (count($error['details']) > 10) {
                    $this->line('  ... ' . (count($error['details']) - 10) . ' more lines');
                }
            }
            
            $this->newLine();
        }
        
        // Show specific error patterns
        $this->info('Quick Error Summary:');
        $this->table(
            ['Type', 'Count'],
            [
                ['SQL Errors', count(array_filter($errors, fn($e) => str_contains($e['message'], 'SQLSTATE')))],
                ['500 Errors', count(array_filter($errors, fn($e) => str_contains($e['message'], '500')))],
                ['Critical', count(array_filter($errors, fn($e) => $e['is_critical']))],
                ['UltimateSystemCockpit', count(array_filter($errors, fn($e) => str_contains($e['message'], 'UltimateSystemCockpit')))],
            ]
        );
    }
}