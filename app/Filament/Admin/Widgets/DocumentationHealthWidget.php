<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

class DocumentationHealthWidget extends Widget
{
    protected static string $view = 'filament.widgets.documentation-health';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 999;
    
    public static function canView(): bool
    {
        // Check for both variations of role names (with spaces and underscores)
        return auth()->user()?->hasAnyRole([
            'super_admin', 
            'Super Admin',
            'company_admin',
            'Company Admin'
        ]);
    }
    
    public function getHealthData(): array
    {
        return Cache::remember('documentation-health', 300, function () {
            try {
                $result = Process::run('php artisan docs:check-updates --json');
                
                if ($result->successful()) {
                    $data = json_decode($result->output(), true);
                    
                    return [
                        'health_score' => $data['health_score'] ?? 0,
                        'outdated_docs' => $data['outdated_docs'] ?? [],
                        'broken_links' => $data['broken_links'] ?? [],
                        'suggestions' => $data['suggestions'] ?? [],
                        'last_check' => now()->format('Y-m-d H:i:s'),
                    ];
                }
            } catch (\Exception $e) {
                // Fallback bei Fehler
            }
            
            return [
                'health_score' => 100,
                'outdated_docs' => [],
                'broken_links' => [],
                'suggestions' => [],
                'last_check' => now()->format('Y-m-d H:i:s'),
            ];
        });
    }
    
    public function refreshHealth(): void
    {
        Cache::forget('documentation-health');
        $this->dispatch('$refresh');
    }
}