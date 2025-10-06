<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookLog;
use App\Services\WebhookRouter;

class UnifiedWebhookController extends Controller
{
    /**
     * Handle incoming webhook from Retell (legacy route)
     * This maintains backward compatibility with existing Retell configuration
     */
    public function handleRetellLegacy(Request $request)
    {
        Log::channel('retell')->info('[Webhook] Received at /api/webhook (legacy route)', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'ip' => $request->ip(),
        ]);

        // Skip WebhookLog to avoid conflicts - using WebhookEvent in RetellWebhookController
        // $this->logWebhook('retell', '/api/webhook', $request);

        // Forward to actual Retell webhook handler
        return app(RetellWebhookController::class)->__invoke($request);
    }

    /**
     * Unified webhook monitoring endpoint
     */
    public function monitor(Request $request)
    {
        $stats = [
            'retell' => $this->getWebhookStats('retell'),
            'calcom' => $this->getWebhookStats('calcom'),
            'stripe' => $this->getWebhookStats('stripe'),
        ];

        $recentWebhooks = WebhookLog::orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'stats' => $stats,
            'recent_webhooks' => $recentWebhooks,
        ]);
    }

    /**
     * Log webhook receipt for monitoring
     */
    private function logWebhook(string $source, string $endpoint, Request $request): void
    {
        try {
            WebhookLog::create([
                'source' => $source,
                'endpoint' => $endpoint,
                'method' => $request->method(),
                'headers' => json_encode($request->headers->all()),
                'payload' => json_encode($request->all()),
                'ip_address' => $request->ip(),
                'status' => 'received',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log webhook', [
                'error' => $e->getMessage(),
                'source' => $source,
            ]);
        }
    }

    /**
     * Get webhook statistics for a source
     */
    private function getWebhookStats(string $source): array
    {
        $now = now();

        try {
            return [
                'total' => WebhookLog::where('source', $source)->count(),
                'today' => WebhookLog::where('source', $source)
                    ->whereDate('created_at', $now->toDateString())
                    ->count(),
                'last_hour' => WebhookLog::where('source', $source)
                    ->where('created_at', '>=', $now->subHour())
                    ->count(),
                'last_received' => WebhookLog::where('source', $source)
                    ->orderBy('created_at', 'desc')
                    ->value('created_at'),
                'success_rate' => $this->calculateSuccessRate($source),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting webhook stats', [
                'source' => $source,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'today' => 0,
                'last_hour' => 0,
                'last_received' => null,
                'success_rate' => 0,
            ];
        }
    }

    /**
     * Calculate success rate for webhook processing
     */
    private function calculateSuccessRate(string $source): float
    {
        $total = WebhookLog::where('source', $source)->count();
        if ($total === 0) {
            return 0;
        }

        $successful = WebhookLog::where('source', $source)
            ->where('status', 'processed')
            ->count();

        return round(($successful / $total) * 100, 2);
    }
}