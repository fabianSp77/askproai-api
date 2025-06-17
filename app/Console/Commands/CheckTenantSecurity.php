<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CheckTenantSecurity extends Command
{
    protected $signature = 'tenant:check-security';
    protected $description = 'Check all models for proper tenant isolation';
    
    protected array $violations = [];
    protected array $warnings = [];
    protected array $secure = [];
    
    public function handle()
    {
        $this->info('Checking tenant security across all models...');
        
        $modelPath = app_path('Models');
        $files = File::allFiles($modelPath);
        
        foreach ($files as $file) {
            $this->checkModel($file);
        }
        
        $this->displayResults();
        
        return count($this->violations) > 0 ? 1 : 0;
    }
    
    protected function checkModel($file)
    {
        $content = File::get($file);
        $className = $this->getClassName($file);
        
        // Skip abstract classes and traits
        if (Str::contains($content, 'abstract class') || Str::contains($content, 'trait ')) {
            return;
        }
        
        // Check if model has company_id
        $hasCompanyId = Str::contains($content, "'company_id'") || 
                       Str::contains($content, '"company_id"');
        
        if (!$hasCompanyId) {
            // Model doesn't need tenant isolation
            return;
        }
        
        // Check for TenantScope usage
        $hasTenantScope = Str::contains($content, 'TenantScope') || 
                         Str::contains($content, 'HasTenantScope') ||
                         Str::contains($content, 'extends TenantModel');
        
        // Check for custom scope implementations
        $hasCustomScope = Str::contains($content, "where('company_id'") ||
                         Str::contains($content, 'where("company_id"') ||
                         Str::contains($content, '->company_id');
        
        if (!$hasTenantScope) {
            if ($hasCustomScope) {
                $this->warnings[] = [
                    'model' => $className,
                    'issue' => 'Has custom company_id filtering - should use TenantScope instead'
                ];
            } else {
                $this->violations[] = [
                    'model' => $className,
                    'issue' => 'Has company_id but NO tenant isolation!'
                ];
            }
        } else {
            $this->secure[] = $className;
        }
    }
    
    protected function getClassName($file): string
    {
        $path = $file->getRelativePathname();
        $className = Str::replace('/', '\\', $path);
        $className = Str::replace('.php', '', $className);
        
        return 'App\\Models\\' . $className;
    }
    
    protected function displayResults()
    {
        $this->line('');
        
        if (count($this->violations) > 0) {
            $this->error('CRITICAL SECURITY VIOLATIONS FOUND!');
            $this->table(
                ['Model', 'Issue'],
                $this->violations
            );
        }
        
        if (count($this->warnings) > 0) {
            $this->line('');
            $this->warn('Warnings (potential improvements):');
            $this->table(
                ['Model', 'Issue'],
                $this->warnings
            );
        }
        
        $this->line('');
        $this->info('Summary:');
        $this->line('- Secure models: ' . count($this->secure));
        $this->line('- Violations: ' . count($this->violations));
        $this->line('- Warnings: ' . count($this->warnings));
        
        if (count($this->violations) > 0) {
            $this->line('');
            $this->error('Fix these violations immediately by:');
            $this->line('1. Extending from TenantModel instead of Model');
            $this->line('2. Or adding "use HasTenantScope;" trait');
            $this->line('3. Run tests: php artisan test --filter MultiTenancyIsolationTest');
        }
    }
}