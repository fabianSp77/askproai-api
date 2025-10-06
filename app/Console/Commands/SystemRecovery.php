<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SystemRecovery extends Command
{
    protected $signature = 'system:recover
                           {--check : Only check system status}
                           {--force : Force recovery even if healthy}
                           {--auto : Run in automatic mode}';

    protected $description = 'Advanced system recovery and self-healing';

    private $issues = [];
    private $fixes = [];

    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║              SYSTEM RECOVERY & SELF-HEALING                      ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Phase 1: System Diagnosis
        $this->info('Phase 1: System Diagnosis...');
        $healthy = $this->diagnoseSystem();

        if ($this->option('check')) {
            $this->displayDiagnostics($healthy);
            return $healthy ? 0 : 1;
        }

        if (!$healthy || $this->option('force')) {
            // Phase 2: Attempt Recovery
            $this->info('Phase 2: Attempting Recovery...');
            $this->performRecovery();

            // Phase 3: Verify Recovery
            $this->info('Phase 3: Verifying Recovery...');
            $this->verifyRecovery();
        } else {
            $this->info('✅ System is healthy. No recovery needed.');
        }

        $this->displaySummary();
        return 0;
    }

    /**
     * Diagnose system health
     */
    private function diagnoseSystem(): bool
    {
        $healthy = true;

        // Check Database
        try {
            DB::select('SELECT 1');
            $this->fixes[] = '✅ Database: Connected';
        } catch (\Exception $e) {
            $this->issues[] = '❌ Database: Connection failed';
            $healthy = false;
        }

        // Check Redis
        try {
            Redis::ping();
            $this->fixes[] = '✅ Redis: Connected';
        } catch (\Exception $e) {
            $this->issues[] = '❌ Redis: Connection failed';
            $healthy = false;
        }

        // Check Cache
        try {
            Cache::put('recovery_test', true, 1);
            Cache::forget('recovery_test');
            $this->fixes[] = '✅ Cache: Working';
        } catch (\Exception $e) {
            $this->issues[] = '❌ Cache: Not working';
            $healthy = false;
        }

        // Check Storage Permissions
        $storageCheck = $this->checkStoragePermissions();
        if (!$storageCheck) {
            $healthy = false;
        }

        // Check View Cache
        $viewCacheDir = storage_path('framework/views');
        if (!is_writable($viewCacheDir)) {
            $this->issues[] = '❌ View cache: Not writable';
            $healthy = false;
        } else {
            $this->fixes[] = '✅ View cache: Writable';
        }

        // Check Configuration
        if (config('app.key') === null) {
            $this->issues[] = '❌ App key: Not set';
            $healthy = false;
        } else {
            $this->fixes[] = '✅ App key: Set';
        }

        // Check for common errors
        $this->checkForCommonErrors();

        return $healthy;
    }

    /**
     * Perform recovery operations
     */
    private function performRecovery(): void
    {
        $this->info('Executing recovery procedures...');

        // 1. Clear all caches
        $this->task('Clearing all caches', function () {
            Artisan::call('cache:clear', [], $this->output);
            Artisan::call('config:clear', [], $this->output);
            Artisan::call('route:clear', [], $this->output);
            Artisan::call('view:clear', [], $this->output);
            return true;
        });

        // 2. Fix permissions
        $this->task('Fixing file permissions', function () {
            return $this->fixPermissions();
        });

        // 3. Rebuild caches
        $this->task('Rebuilding optimized caches', function () {
            Artisan::call('config:cache', [], $this->output);
            Artisan::call('route:cache', [], $this->output);
            Artisan::call('view:cache', [], $this->output);
            return true;
        });

        // 4. Clean up old sessions
        $this->task('Cleaning old sessions', function () {
            $sessionPath = storage_path('framework/sessions');
            if (is_dir($sessionPath)) {
                $files = File::files($sessionPath);
                $oldFiles = 0;
                $cutoff = now()->subDays(7)->timestamp;

                foreach ($files as $file) {
                    if ($file->getMTime() < $cutoff) {
                        File::delete($file);
                        $oldFiles++;
                    }
                }
                $this->fixes[] = "Cleaned $oldFiles old session files";
            }
            return true;
        });

        // 5. Optimize autoloader
        $this->task('Optimizing autoloader', function () {
            exec('composer dump-autoload -o 2>&1', $output, $returnCode);
            return $returnCode === 0;
        });

        // 6. Restart services if in auto mode
        if ($this->option('auto')) {
            $this->task('Restarting PHP-FPM', function () {
                exec('systemctl restart php8.3-fpm 2>&1', $output, $returnCode);
                return $returnCode === 0;
            });
        }
    }

    /**
     * Verify recovery success
     */
    private function verifyRecovery(): void
    {
        $this->info('Verifying recovery...');

        $tests = [
            'Database Query' => fn() => DB::select('SELECT 1'),
            'Redis Connection' => fn() => Redis::ping(),
            'Cache Operation' => function() {
                Cache::put('verify_test', true, 1);
                return Cache::get('verify_test') === true;
            },
            'Route Access' => fn() => app('router')->getRoutes()->count() > 0,
            'View Compilation' => function() {
                $testView = 'test_' . time();
                File::put(resource_path("views/$testView.blade.php"), 'test');
                view($testView)->render();
                File::delete(resource_path("views/$testView.blade.php"));
                return true;
            }
        ];

        foreach ($tests as $name => $test) {
            try {
                $result = $test();
                $this->info("✅ $name: Passed");
                $this->fixes[] = "$name verification passed";
            } catch (\Exception $e) {
                $this->error("❌ $name: Failed - " . $e->getMessage());
                $this->issues[] = "$name verification failed";
            }
        }
    }

    /**
     * Check storage permissions
     */
    private function checkStoragePermissions(): bool
    {
        $directories = [
            'storage/app',
            'storage/framework',
            'storage/logs',
            'bootstrap/cache'
        ];

        $allWritable = true;
        foreach ($directories as $dir) {
            $path = base_path($dir);
            if (!is_writable($path)) {
                $this->issues[] = "❌ Directory not writable: $dir";
                $allWritable = false;
            } else {
                $this->fixes[] = "✅ Directory writable: $dir";
            }
        }

        return $allWritable;
    }

    /**
     * Fix file permissions
     */
    private function fixPermissions(): bool
    {
        $commands = [
            'chown -R www-data:www-data ' . storage_path(),
            'chmod -R 775 ' . storage_path(),
            'chmod -R 775 ' . base_path('bootstrap/cache')
        ];

        foreach ($commands as $command) {
            exec($command . ' 2>&1', $output, $returnCode);
            if ($returnCode !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check for common errors in logs
     */
    private function checkForCommonErrors(): void
    {
        $logFile = storage_path('logs/laravel.log');
        if (!file_exists($logFile)) {
            return;
        }

        $recentErrors = [];
        $lines = File::lines($logFile);
        $lineCount = 0;

        foreach ($lines as $line) {
            if ($lineCount++ > 100) break; // Only check last 100 lines

            if (stripos($line, 'ERROR') !== false) {
                if (stripos($line, 'Permission denied') !== false) {
                    $recentErrors['permission'] = true;
                } elseif (stripos($line, 'No such file or directory') !== false) {
                    $recentErrors['missing_file'] = true;
                } elseif (stripos($line, 'Connection refused') !== false) {
                    $recentErrors['connection'] = true;
                }
            }
        }

        if (!empty($recentErrors)) {
            $this->issues[] = '⚠️ Recent errors found in logs: ' . implode(', ', array_keys($recentErrors));
        }
    }

    /**
     * Display diagnostics
     */
    private function displayDiagnostics(bool $healthy): void
    {
        $this->newLine();
        $this->info('DIAGNOSTIC RESULTS');
        $this->info(str_repeat('─', 70));

        if (!empty($this->issues)) {
            $this->error('Issues Found:');
            foreach ($this->issues as $issue) {
                $this->line('  ' . $issue);
            }
        }

        if (!empty($this->fixes)) {
            $this->info('Healthy Components:');
            foreach ($this->fixes as $fix) {
                $this->line('  ' . $fix);
            }
        }

        $this->newLine();
        if ($healthy) {
            $this->info('🟢 SYSTEM STATUS: HEALTHY');
        } else {
            $this->error('🔴 SYSTEM STATUS: NEEDS RECOVERY');
        }
    }

    /**
     * Display summary
     */
    private function displaySummary(): void
    {
        $this->newLine();
        $this->info('RECOVERY SUMMARY');
        $this->info(str_repeat('─', 70));

        $this->info('Issues Detected: ' . count($this->issues));
        $this->info('Fixes Applied: ' . count($this->fixes));

        if (count($this->issues) === 0) {
            $this->info('✅ System recovery successful!');
        } else {
            $this->warn('⚠️ Some issues remain. Manual intervention may be required.');
        }

        // Log recovery action
        Log::info('System recovery executed', [
            'issues' => $this->issues,
            'fixes' => $this->fixes,
            'timestamp' => now()->toIso8601String()
        ]);

        $this->newLine();
        $this->info('Recovery logged to: storage/logs/laravel.log');
    }
}