<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MetricsService
{
    public function export(): string
    {
        $metrics = [];
        
        // Application metrics
        $metrics[] = $this->gauge('askproai_companies_total', Company::count(), 'Total number of companies');
        $metrics[] = $this->gauge('askproai_active_calls', Call::where('status', 'in_progress')->count(), 'Number of active calls');
        $metrics[] = $this->gauge('askproai_appointments_today', Appointment::whereDate('starts_at', today())->count(), 'Appointments scheduled for today');
        
        // Queue metrics
        $metrics[] = $this->gauge('laravel_queue_jobs_pending', DB::table('jobs')->count(), 'Pending jobs in queue');
        $metrics[] = $this->gauge('laravel_queue_jobs_failed_total', DB::table('failed_jobs')->count(), 'Total failed jobs');
        
        // Cache metrics
        $metrics[] = $this->gauge('laravel_cache_hits_total', Cache::get('metrics.cache_hits', 0), 'Total cache hits');
        $metrics[] = $this->gauge('laravel_cache_misses_total', Cache::get('metrics.cache_misses', 0), 'Total cache misses');
        
        return implode("\n", $metrics);
    }
    
    private function gauge(string $name, $value, string $help = ''): string
    {
        $output = [];
        
        if ($help) {
            $output[] = "# HELP {$name} {$help}";
        }
        
        $output[] = "# TYPE {$name} gauge";
        $output[] = "{$name} {$value}";
        
        return implode("\n", $output);
    }
}