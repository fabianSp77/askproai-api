<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class WebhookMonitor extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static ?string $navigationLabel = 'Webhook Monitor';
    protected static ?string $title = 'Webhook Monitoring Dashboard';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 120;
    
    protected static string $view = 'filament.admin.pages.webhook-monitor';
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole(['Super Admin', 'super_admin', 'developer']) || $user->email === 'dev@askproai.de');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
    
    public array $stats = [];
    public array $recentWebhooks = [];
    public array $errorWebhooks = [];
    public array $providerStats = [];
    public array $hourlyStats = [];
    
    public function mount(): void
    {
        $this->loadStats();
    }
    
    protected function getActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action('loadStats'),
                
            Action::make('clearErrors')
                ->label('Clear Error Log')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action('clearErrorLog'),
                
            Action::make('testWebhooks')
                ->label('Test All Webhooks')
                ->icon('heroicon-o-beaker')
                ->action('testAllWebhooks')
        ];
    }
    
    public function loadStats(): void
    {
        $now = Carbon::now();
        $oneDayAgo = $now->copy()->subDay();
        $oneHourAgo = $now->copy()->subHour();
        
        // Overall Stats
        $this->stats = [
            'total_24h' => $this->getWebhookCount($oneDayAgo),
            'total_1h' => $this->getWebhookCount($oneHourAgo),
            'success_rate' => $this->getSuccessRate($oneDayAgo),
            'avg_processing_time' => $this->getAvgProcessingTime($oneDayAgo),
            'active_providers' => $this->getActiveProviders(),
            'duplicate_rate' => $this->getDuplicateRate($oneDayAgo)
        ];
        
        // Recent Webhooks
        $this->recentWebhooks = DB::table('webhook_logs')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($webhook) {
                $webhook->time_ago = Carbon::parse($webhook->created_at)->diffForHumans();
                $webhook->payload_preview = $this->getPayloadPreview($webhook->payload);
                return $webhook;
            })
            ->toArray();
        
        // Error Webhooks
        $this->errorWebhooks = DB::table('webhook_logs')
            ->where('status', 'error')
            ->where('created_at', '>=', $oneDayAgo)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($webhook) {
                $webhook->time_ago = Carbon::parse($webhook->created_at)->diffForHumans();
                $webhook->error_preview = $this->getErrorPreview($webhook->error_message);
                return $webhook;
            })
            ->toArray();
        
        // Provider Stats
        $this->providerStats = DB::table('webhook_logs')
            ->select('provider', DB::raw('COUNT(*) as total'), DB::raw('AVG(processing_time_ms) as avg_time'))
            ->where('created_at', '>=', $oneDayAgo)
            ->groupBy('provider')
            ->get()
            ->mapWithKeys(function ($stat) {
                return [$stat->provider => [
                    'total' => $stat->total,
                    'avg_time' => round($stat->avg_time, 2),
                    'health' => $this->getProviderHealth($stat->provider)
                ]];
            })
            ->toArray();
        
        // Hourly Stats
        $this->hourlyStats = $this->getHourlyStats();
    }
    
    private function getWebhookCount(Carbon $since): int
    {
        return Cache::remember(
            "webhook_count_{$since->timestamp}",
            60,
            fn() => DB::table('webhook_logs')
                ->where('created_at', '>=', $since)
                ->count()
        );
    }
    
    private function getSuccessRate(Carbon $since): float
    {
        $stats = Cache::remember(
            "webhook_success_rate_{$since->timestamp}",
            60,
            fn() => DB::table('webhook_logs')
                ->select(DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success'))
                ->where('created_at', '>=', $since)
                ->first()
        );
        
        return $stats->total > 0 ? round(($stats->success / $stats->total) * 100, 1) : 100;
    }
    
    private function getAvgProcessingTime(Carbon $since): float
    {
        return Cache::remember(
            "webhook_avg_time_{$since->timestamp}",
            60,
            fn() => DB::table('webhook_logs')
                ->where('created_at', '>=', $since)
                ->avg('processing_time_ms') ?? 0
        );
    }
    
    private function getActiveProviders(): int
    {
        return Cache::remember(
            'webhook_active_providers',
            300,
            fn() => DB::table('webhook_logs')
                ->where('created_at', '>=', Carbon::now()->subDay())
                ->distinct('provider')
                ->count('provider')
        );
    }
    
    private function getDuplicateRate(Carbon $since): float
    {
        $stats = Cache::remember(
            "webhook_duplicate_rate_{$since->timestamp}",
            60,
            fn() => DB::table('webhook_logs')
                ->select(DB::raw('COUNT(*) as total'), DB::raw('SUM(is_duplicate) as duplicates'))
                ->where('created_at', '>=', $since)
                ->first()
        );
        
        return $stats->total > 0 ? round(($stats->duplicates / $stats->total) * 100, 1) : 0;
    }
    
    private function getProviderHealth(string $provider): array
    {
        $recentErrors = DB::table('webhook_logs')
            ->where('provider', $provider)
            ->where('status', 'error')
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();
        
        $recentTotal = DB::table('webhook_logs')
            ->where('provider', $provider)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();
        
        if ($recentTotal === 0) {
            return ['status' => 'unknown', 'message' => 'No recent webhooks'];
        }
        
        $errorRate = ($recentErrors / $recentTotal) * 100;
        
        if ($errorRate === 0) {
            return ['status' => 'healthy', 'message' => 'All webhooks successful'];
        } elseif ($errorRate < 5) {
            return ['status' => 'warning', 'message' => "{$errorRate}% error rate"];
        } else {
            return ['status' => 'error', 'message' => "{$errorRate}% error rate"];
        }
    }
    
    private function getHourlyStats(): array
    {
        $stats = [];
        
        for ($i = 23; $i >= 0; $i--) {
            $hour = Carbon::now()->subHours($i);
            $nextHour = $hour->copy()->addHour();
            
            $count = DB::table('webhook_logs')
                ->whereBetween('created_at', [$hour, $nextHour])
                ->count();
            
            $stats[] = [
                'hour' => $hour->format('H:00'),
                'count' => $count
            ];
        }
        
        return $stats;
    }
    
    private function getPayloadPreview($payload): string
    {
        if (!$payload) return 'N/A';
        
        try {
            $data = json_decode($payload, true);
            
            // Extract key information based on provider
            if (isset($data['event'])) {
                return "Event: {$data['event']}";
            } elseif (isset($data['triggerEvent'])) {
                return "Trigger: {$data['triggerEvent']}";
            } elseif (isset($data['type'])) {
                return "Type: {$data['type']}";
            }
            
            return 'Complex payload';
        } catch (\Exception $e) {
            return 'Invalid JSON';
        }
    }
    
    private function getErrorPreview($errorMessage): string
    {
        if (!$errorMessage) return 'Unknown error';
        
        // Truncate long error messages
        if (strlen($errorMessage) > 100) {
            return substr($errorMessage, 0, 100) . '...';
        }
        
        return $errorMessage;
    }
    
    public function clearErrorLog(): void
    {
        DB::table('webhook_logs')
            ->where('status', 'error')
            ->delete();
        
        Cache::flush();
        
        $this->loadStats();
        
        Notification::make()
            ->title('Error log cleared')
            ->success()
            ->send();
    }
    
    public function testAllWebhooks(): void
    {
        // Test webhooks for each provider
        $results = [];
        
        // Test Retell
        try {
            $response = Http::post(url('/api/retell/webhook'), [
                'event' => 'test',
                'timestamp' => now()->timestamp
            ]);
            $results['retell'] = $response->successful() ? 'success' : 'failed';
        } catch (\Exception $e) {
            $results['retell'] = 'error';
        }
        
        // Test Cal.com
        try {
            $response = Http::get(url('/api/calcom/webhook'));
            $results['calcom'] = $response->successful() ? 'success' : 'failed';
        } catch (\Exception $e) {
            $results['calcom'] = 'error';
        }
        
        // Test Stripe
        try {
            $response = Http::post(url('/api/stripe/webhook'), [
                'type' => 'test',
                'created' => now()->timestamp
            ]);
            $results['stripe'] = $response->successful() ? 'success' : 'failed';
        } catch (\Exception $e) {
            $results['stripe'] = 'error';
        }
        
        // Show results
        $message = "Test Results:\n";
        foreach ($results as $provider => $result) {
            $emoji = $result === 'success' ? '✅' : '❌';
            $message .= "{$emoji} {$provider}: {$result}\n";
        }
        
        Notification::make()
            ->title('Webhook Tests Complete')
            ->body($message)
            ->success()
            ->send();
        
        $this->loadStats();
    }
}